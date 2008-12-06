#!/bin/sh
# rotatebg.sh:  Script to change the desktop wallpaper (background image)
# Copyright (C) 2006 Kevin Locke <kwl7@cornell.edu>
# This script is released under the same terms as the grotbckgd script on which
# it is based, no additional terms are added (and it does not apply to the new
# copyright notice).

# Based on grotbckgd:
# a shell script to change background in GNOME 2
#
# This script rotate backgrounds that are stored in
# a directory. It changes backgrounds using gconftool.
# Run grotbckgd.sh -h for usage information.
# 
# Copyright (C) 2002 Damien Merenne <dam at capsule dot org>
# The author requires that any copies or derived works include this
# copyright notice; no other restrictions are placed on its use.

# Note:	It is possible to name file in any braindead way that you like.  
#	In fact, the only 'reserved' characters for filenames are '/' and '\0'
#	But, if your files have a '\n' or '#' in the filename this script will
#	fail.

# Prevent globbing from interfering with calls to find
set -f

# Set reasonable defaults
GNOMEGETBGCMD="/usr/bin/gconftool --get /desktop/gnome/background/picture_filename"
GNOMESETBGCMD="/usr/bin/gconftool --type string --set /desktop/gnome/background/picture_filename"
TYPES="jpg,png"
VERBOSE=0
MODE=normal

usage ()
{
	cat <<- USAGEMESSAGE
	Usage:  rotatebg.sh [options] [file ...]

	Every time rotatebg.sh is called it builds a list of files to consider
	from the files and directories (searched recursively) listed on the
	command-line (or from stdin if none are listed).  It then sorts this
	list and changes the current background to the next in the list, or
	changes to the first in the list if the current background is not in 
	the list, or to a random file if -r is specified.

	Options:
	   -h, --help		this help screen
	   -g, --getbgcmd	command to get the current background
	   -f, --bgfile		file containing the current background
	   			(if there is no command to get the background,
				 using -f will read/write it to this file)
	   -s, --setbgcmd	command to set the background
	   -t, --types 		File extensions to use (separated by commas)
				Defaults to jpg,png
	   -r, --random		Select background randomly
	   -v, --verbose	Be verbose

	Note1:  If -s is given and neither -g or -f is given, -r is implied.
	Note2:  The extensions of files listed on the command-line are not
	        not checked.  This includes files expanded by the shell.
	USAGEMESSAGE
}

# Parse command-line arguments
GOTOPTS=`getopt -o :f:hg:s:t:rv --long getbgcmd,bgfile,setbgcmd,help,random,types,verbose -n "${0##/*/}" -- "$@"`

if [ $? -ne 0 ] ; then
	exit $?
fi

# Set positional parameters to command-line arguments
eval set -- "$GOTOPTS"

while true ; do
	case "$1" in
		-h|--help ) usage ; exit 0 ;;
	   	-g|--getbgcmd ) GETBGCMD="$2" ; shift 2 ;;
		-f|--bgfile ) BGFILE="$2" ; shift 2 ;;
	   	-s|--setbgcmd ) SETBGCMD="$2" ; shift 2 ;;
		-t|--type|--types ) TYPES="$2" ; shift 2 ;;
		-r|--random ) MODE=random ; shift ;;
		-v|--verbose ) VERBOSE=$(($VERBOSE + 1)) ; shift ;;
		-- ) shift ; break ;;
		* ) break ;;
	esac
done

# Set $GETBGCMD and $SETBGCMD reasonably
if [ -n "$SETBGCMD" ] ; then
	if [ -z "$GETBGCMD" ] && [ -r "$BGFILE" ] ; then
		GETBGCMD="cat $BGFILE"
	elif [ -z "$GETBGCMD" ] ; then
		MODE=random
	fi
else
	if [ -x ${GNOMESETBGCMD%% *} ] ; then
		SETBGCMD="$GNOMESETBGCMD"
		GETBGCMD="$GNOMEGETBGCMD"
	else
		echo "Error: setbgcmd not set and ${GNOMESETBGCMD%% *} not present." >&2
		exit 1
	fi
fi

# format allowed types
TYPES=$(echo $TYPES | sed 's/,/ /g')

# Build $BACKGROUNDS into a \n separated list of backgrounds
# (if you have newlines in your filenames this won't work... but I don't care ;)
# if no files are listed, read backgrounds from stdin
if [ $# -eq 0 ]; then
	while read BG ; do
		BACKGROUNDS="${BACKGROUNDS}
${BG}"
	done
# otherwise include files/directories listed
else
	# build the find command arguments
	FINDOPT="-not ( -name .* -prune ) -and ("
	for ext in ${TYPES}; do
		FINDOPT="${FINDOPT} -name *.${ext} -or "
	done
	FINDOPT=${FINDOPT% -or }
	FINDOPT="${FINDOPT} ) -and -type f -print"
	
	# get the list of backgrounds 
	BACKGROUNDS="";
	for i in "$@"; do
		# resolve full directory
		if ! echo "$i" | grep -q '^/' ; then
			FILE="${PWD}/${i}"
		else
			FILE="${i}"
		fi
		if [ -d "${FILE}" ]; then
			BACKGROUNDS="${BACKGROUNDS}
$(find $FILE ${FINDOPT})"
		elif [ -r "${FILE}" ]; then
			BACKGROUNDS="${BACKGROUNDS}
${FILE}"
		else
			echo "Error:  \"$FILE\" not found or not readable." >&2
		fi
	done

	# remove duplicates
	BACKGROUNDS=$(echo "$BACKGROUNDS" | sort | uniq )
fi

if [ -z "${BACKGROUNDS}" ]; then
	echo "Error:  No image files found." >&2
	exit 1
fi

# get the current background
CURRENT="$($GETBGCMD)"

if [ $? -ne 0 ] ; then
	echo "Warning: The getbgcmd \"$GETBGCMD\" exited with non-zero status." >&2
	echo "         You may need to specify/change --getbgcmd." >&2
fi

rotate()
{
	NEXT=0
	FIRST=""
	# We set IFS to only \n so filenames with spaces or tabs are not split
	IFS="
"
	for i in $BACKGROUNDS $BACKGROUNDS; do 
		# store the first one
		if [ -z "$FIRST" ]; then
			FIRST="$i";
		# if we have reach the end of the file list, use the first
		elif [ "${FIRST}" = "${i}" ]; then
			NEW="${FIRST}"
			break
		fi
		# this one is the good one
		if [ $NEXT -eq 1 ]; then
			NEW="${i}"
			break
		fi
		 # if this one is the current, then use the next one
		if [ "${i}" = "${CURRENT}" ]; then
			NEXT=1
		fi
	done
	unset IFS
}

random()
{
	# Remove the current background from our list
	if [ ! -z "${CURRENT}" ]; then
		BACKGROUNDS=$(echo "$BACKGROUNDS" | sed \#"${CURRENT}"#d );
	fi

	# If $RANDOM is not set, use awk to generate it
	RANDOM=${RANDOM:-`awk 'BEGIN {srand();print int(32767 * rand())}'`}
	FILES=$(echo "$BACKGROUNDS" | wc -l)
	NEW="$(echo "$BACKGROUNDS" | sed $(($RANDOM * $FILES / 32767 + 1))\!d )"
}

case $MODE in
	normal) rotate
	;;
	random) random
	;;
	*) echo "Error:  Invalid mode." >&2 ; exit 1
	;;
esac


# set the background
if [ $VERBOSE -gt 0 ]; then
	echo "switching to $NEW"
fi

if [ -n "$BGFILE" ] ; then
	if [ -f "$BGFILE" ] && [ ! -w "$BGFILE" ] ; then
		echo "Error: \"$BGFILE\" is not writeable." >&2
	else
		echo "$NEW" > "$BGFILE"
	fi
fi

$SETBGCMD "$NEW" &

if [ $? -ne 0 ] ; then
	echo "Warning: The setbgcmd \"$SETBGCMD $NEW\" exited with non-zero status." >&2
	echo "         You may need to specify/change --setbgcmd." >&2
fi
