#!/usr/bin/perl

# PIV - Perl Image Viewer
#
# Copyright (c) 2007 Kevin Locke <kwl7@cornell.edu>
#
# Permission is hereby granted, free of charge, to any person obtaining a copy
# of this software and associated documentation files (the "Software"), to deal
# in the Software without restriction, including without limitation the rights
# to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
# copies of the Software, and to permit persons to whom the Software is
# furnished to do so, subject to the following conditions:
#
# The above copyright notice and this permission notice shall be included in
# all copies or substantial portions of the Software.
#
# THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
# IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
# FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
# AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
# LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
# OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
# THE SOFTWARE.

use strict;
use warnings;
use Carp;
use English;

use Getopt::Long qw/:config no_ignore_case bundling/;
use Glib qw/TRUE FALSE/;
use Gtk2 '-init';
use Gtk2::Gdk::Keysyms;
use List::Util qw/min max/;

use constant PROGRAM_NAME_LONG => 'Perl Image Viewer';
use constant PROGRAM_VERSION   => '0.2.0';

use constant ZOOM_FACTOR       => 2;
use constant DELAY_FACTOR      => 1.4142;   # sqrt(2)

# Keycodes from Gtk2::Gdk::Keysyms
# Addition of Mouse# where # indicates a mouse button
# Addition of MouseWheel{Up,Down} for mouse wheel
# Addition of modifiers (Ctrl,Alt,Super - in that order) + keycode syntax
my %keyactions = (
    'space'          => 'next-image',
    'Return'         => 'next-image',
    'Mouse1'         => 'next-image',
    'MouseWheelDown' => 'next-image',
    'Mouse3'         => 'prev-image',
    'MouseWheelUp'   => 'prev-image',
    'Mouse2'         => 'rand-image',
    'Page_Up'        => 'zoom-in',
    'Page_Down'      => 'zoom-out',
    's'              => 'pause',
    'Pause'          => 'pause',
    'F11'            => 'increase-slide-delay',
    'F12'            => 'decrease-slide-delay',
    'f'              => 'fullscreen',
    'Alt+Return'     => 'fullscreen',
    'i'              => 'toggle-status',
    'q'              => 'quit',
    'Escape'         => 'quit',
    'question'       => 'help',
    'F1'             => 'help',
);

########################## Version and Help ###################################

sub print_version {
    # Version information printed as per GNU standards for compatibility
    print PROGRAM_NAME_LONG.' '.PROGRAM_VERSION."\n";
    print <<HERE;
Copyright (c) 2007 Kevin Locke <kwl7\@cornell.edu>
License:  MIT <http://www.opensource.org/licenses/mit-license.php>
This is free software; you are free to modify and redistribute it.
There is NO WARRANTY, to the extent permitted by law.
HERE
}

=head1 NAME

piv - display a set of images for quick browsing

=head1 SYNOPSIS

piv [ -fhrRsSV ] [ -d I<secs> ] [ -F F<list> | F<file> | F<directory> ]...

=head1 DESCRIPTION

piv is a no-frills image viewer built using GTK and GDKPixmap.
It reads in all files given on the command-line, in given directories,
and from any list files specified.  It then presents these images, one
at a time, to the user.

=head1 OPTIONS

=over 4

=item B<-d> I<secs>, B<--delay>=I<secs>

Set the delay between images, in seconds.  After I<secs> elapses, the next
image will be automatically displayed.  I<secs> may be any real number.
Although there is no enforced minimum, B<piv> will always completely display
images which will result in a practical minimum based on the speed of the
host system.  Implies B<-s>.

=item B<-f>, B<--fullscreen>

Run B<piv> in fullscreen mode.  The displayed image will cover the whole
screen.

=item B<-F> F<listfile>, B<--file>=F<listfile>

Read a list of filenames from F<listfile>.  The format of F<listfile> is
one file per line, lines beginning with # and empty lines will be ignored.

=item B<-h>, B<--help>

=item B<-I> F<regex>, B<--include>=F<regex>

Include only files which match a given pattern.  The filename to match
will be either the filename as it was given on the command-line or in
the list file, or it will be the directory name given on the
command-line with the relative path from that directory to the file
appended (in the case of a recursive search).  

Print an informational message about the available command-line options.

=item B<-r>, B<--randomize>

Randomize the order in which the images will be displayed.  The default
behavior is to display the images in the order they are given on the
command-line or in the list file.

=item B<-R>, B<--recursive>

Add all files in sub-directories of the directories listed on the
command-line.  If this option is repeated and a list file is given
(using B<-F>), all directories in the file list will be searched as well.

=item B<-s>, B<--slideshow>

Start B<piv> in slideshow mode.  In slideshow mode, after a delay time has
elapsed the next image will be automatically displayed.  (See also B<-d>)

=item B<-S>, B<--sort>

Sort the list of files to display alphabetically.

=item B<-V>, B<--version>

Print version and license information.

=item B<-X> F<regex>, B<--exclude>=F<regex>

Exclude files which match a given pattern.  The filename to match will
be either the filename as it was given on the command-line or in the
list file, or it will be the directory name given on the command-line
with the relative path from that directory to the file appended (in the
case of a recursive search).  Exclude is applied after include.  This
has the effect that if a file is matched by both an inclusion and
exclusion pattern it will be excluded from the final list.

=back

=head1 INTERACTIVE CONTROLS

The default controls for B<piv> are:

=over 4

=item SpaceBar, Return, Left Mouse Button, Mouse Wheel Down

Advance to the next image in the sequence.

=item Right Mouse Button, Mouse Wheel Up

Return to the previous image in the sequence.

=item Center Mouse Button

Jump to a random image in the sequence.

=item PageUp

Zoom in.

=item PageDown

Zoom out.

=item S, Pause

Pause the slideshow.

=item F11

Increase the delay between images.

=item F12

Decrease the delay between images.

=item F, <Alt>+Return

Toggle fullscreen mode.

=item I

Toggle the status display.

=item A, Escape

Exit the program.

=item ?, F1

Print out a listing of the control keys.

=back

Additionally, the image can be moved around the window by clicking and
dragging (which can be very useful when zoomed).

=head1 EXAMPLES

=over 4

=item piv -d 60 -f -R -s slides

Display all images in the slides directory (and subdirectories) in fullscreen
mode with a delay of 60 seconds between slides.

=item piv -S -F photos-vacation.txt -F photos-fishing.txt

Display images listed in the photos-vacation.txt and photos-fishing.txt
files, sorted by filename.

=item piv -r -F newwallpapers.txt wallpapers

Display images listed in newwallpapers.txt and in the wallpapers folder in
random order.

=back

=head1 SEE ALSO

B<piv> was designed as a replacement for qiv(1).  Several of the (mis-)features
of B<qiv> are not yet implemented, others will never be, so see which fits your
need and run with it.

=head1 AUTHOR

Kevin Locke <kwl7@cornell.edu>

=head1 COPYRIGHT AND LICENSE

Copyright 2007 Kevin Locke <kwl7@cornell.edu>

This program is distributed under the terms of the MIT License.
See <http://www.opensource.org/licenses/mit-license.php> for details.

This is free software; you are free to modify and redistribute it.
There is NO WARRANTY, to the extent permitted by law.

=cut

sub print_usage {
    print PROGRAM_NAME_LONG;
    print <<HERE;
 is a program which displays a set of images in a no-frills way.  It supports
slideshow, zoom, fullscreen, and the several other options that any image
viewer is expected to provide.

Usage:  $PROGRAM_NAME [options] [filename|dirname...]
  $PROGRAM_NAME supports the following options:
  -d, --delay      Set the delay between slides (in seconds)(-s implied)
  -f, --fullscreen Start in fullscreen mode
  -F, --file       Read image file names from a given file (or stdin if '-')
  -h, --help       Print this informational message
  -r, --randomize  Randomize the order in which images are displayed
  -R, --recursive  Include images in subdirectories of listed directories
  -s, --slideshow  Start in "slideshow" mode, cycle images on a timer
  -S, --sort       Display images in order, by name
  -V, --version    Print version information for this program

Report bugs to Kevin Locke <kwl7\@cornell.edu>.
HERE
}

######################### ImageDisplay Class ##################################

{
    # Package for controlling how the image is displayed and drawing the
    # image to a widget
    package ImageDisplay;
    use List::Util qw(min max);
    use Glib qw(TRUE FALSE);
    use fields qw(gc offset_x offset_y pixbuf pixbufscaled scale);

    sub new
    {
	my $invocant = shift;
	my $class    = ref($invocant) || $invocant;

	my $self = fields::new($class);
	$self->{offset_x} = 0;
	$self->{offset_y} = 0;
	$self->{scale}    = 1;

	return $self;
    }

    # The pixbuf which is currently being displayed
    sub pixbuf
    {
	my $self = shift;
	return $self->{pixbuf} if (!@_);

	if (!$_[0]) {
	    warn "ImageDisplay::pixbuf() called with undefined pixbuf";
	    return;
	}
        $self->{pixbuf} = shift;

	# Note:  If scale reset is removed, need to check that image > 2x2
	undef($self->{pixbufscaled});
	$self->{offset_x} = 0;
	$self->{offset_y} = 0;
	$self->{scale} = 1;

	return $self->{pixbuf};
    }

    sub scale
    {
	my $self = shift;
	if (@_) {
	    my $scale = shift;

	    # Min scale results in 2 pixels
	    if ($self->{pixbuf}) {
		if ($self->{pixbuf}->get_width*$scale <= 1) {
	    	    $scale = 2/$self->{pixbuf}->get_width;
	    	}

	    	if ($self->{pixbuf}->get_height*$scale <= 1) {
	    	    $scale = 2/$self->{pixbuf}->get_height;
	    	}
	    }

	    $self->{scale} = $scale;
	}

	return $self->{scale};
    }

    sub offsets
    {
	my $self = shift;

	if (@_) {
	    $self->{offset_x} = shift;
	    $self->{offset_y} = shift if (@_);
	}

	return ($self->{offset_x}, $self->{offset_y});
    }

    sub render {
	my $self   = shift;
	my $window = shift if (@_);
	my $gc = shift if (@_);

	if (!$gc) {
	    if ($self->{gc}) {
		 $gc = $self->{gc};
	    } else {
		$self->{gc} = Gtk2::Gdk::GC->new($window);
		$gc = $self->{gc};
	    }
	}

	my ($src_x, $src_y)       = (0,0);
	my ($dest_x, $dest_y)     = (0,0);
	my ($window_w, $window_h) = $window->get_size();
	my ($pixbuf_w, $pixbuf_h) =
	    ($self->{pixbuf}->get_width, $self->{pixbuf}->get_height);

	# Internal scale factor to make image fill window
	my $iscale = min($window_w/$pixbuf_w, $window_h/$pixbuf_h);

	# Apply compound scaling ($self->{scale} == 2 implies twice window size)
	$pixbuf_w *= $iscale * $self->{scale};
	$pixbuf_h *= $iscale * $self->{scale};

	$pixbuf_w = int($pixbuf_w);
	$pixbuf_h = int($pixbuf_h);

	if (!$self->{pixbufscaled} ||
	    $self->{pixbufscaled}->get_width != $pixbuf_w ||
	    $self->{pixbufscaled}->get_height != $pixbuf_h) {
	    $self->{pixbufscaled} =
		$self->{pixbuf}->scale_simple($pixbuf_w, $pixbuf_h, 'bilinear'),
	}

	# Calculate centered values
	if ($window_w > $pixbuf_w) {
	    $dest_x = $window_w / 2 - $pixbuf_w / 2;
	} else {
	    $src_x = $pixbuf_w / 2 - $window_w / 2;
	}

	if ($window_h > $pixbuf_h) {
	    $dest_y = $window_h / 2 - $pixbuf_h / 2;
	} else {
	    $src_y = $pixbuf_h / 2 - $window_h / 2;
	}

	# Apply offsets
	my ($offset_x, $offset_y) = ($self->{offset_x}, $self->{offset_y});
	$dest_x += $offset_x;
	$dest_y += $offset_y;
	if ($dest_x < 0) {
	    $src_x -= $dest_x;
	    $dest_x = 0;
	} elsif ($dest_x > 0 && $src_x > 0) {
	    if ($src_x >= $dest_x) {
		$src_x -= $dest_x;
		$dest_x = 0;
	    } else {
		$dest_x -= $src_x;
		$src_x = 0;
	    }
	}
	if ($dest_y < 0) {
	    $src_y -= $dest_y;
	    $dest_y = 0;
	} elsif ($dest_y > 0 && $src_y > 0) {
	    if ($src_y >= $dest_y) {
		$src_y -= $dest_y;
		$dest_y = 0;
	    } else {
		$dest_y -= $src_y;
		$src_y = 0;
	    }
	}

	# Do it
	# Only actually draw it if it will appear on screen...
	if ($src_x < $pixbuf_w && $src_y < $pixbuf_h &&
	    $dest_x < $window_w && $dest_y < $window_h) {
	    $window->draw_pixbuf($gc,
		$self->{pixbufscaled},
		$src_x, $src_y,
		$dest_x, $dest_y,
		$pixbuf_w - $src_x,
		$pixbuf_h - $src_y,
		'none',
		0, 0);
	}
    }
}

######################### StatusDisplay Class #################################

{
    # Package for rendering status information
    package StatusDisplay;
    use Glib qw(TRUE FALSE);
    use Gtk2;
    use Gtk2::Pango;
    use fields qw(delay filename gc layout paused showdelay textbg textbgscaled);

    use constant TEXT_PADDING            => 5;
    use constant TEXT_BACKGROUND_COLOR   => 0x555555AA;
    use constant SHOW_DELAY_TIME         => 3000;	    # in milliseconds

    sub new
    {
	my $invocant = shift;
	my $class = ref($invocant) || $invocant;
	my $context = shift;

	my $self = fields::new($class);
	$self->{layout}       = Gtk2::Pango::Layout->new($context);
	$self->{textbg}       = Gtk2::Gdk::Pixbuf->new('rgb', TRUE, 8, 1, 1);
	$self->{textbgscaled} = $self->{textbg};
	$self->{delay}        = 5;
	$self->{paused}       = 1;
	$self->{filename}     = "";
	$self->{showdelay}    = 0;

	$self->{textbg}->fill(TEXT_BACKGROUND_COLOR);
	$self->{layout}->set_alignment('right');
	$self->{layout}->set_wrap('word-char');
	$self->{layout}->set_markup("<span size='large' weight='bold' foreground='#FFFFFF'>".(' 'x200)."</span>");

	return $self;
    }

    sub context_changed
    {
	my $self = shift;

	$self->{layout}->context_changed();
    }
    
    my $status_changed = sub {
	my $self = shift;
	my $text = "";

	if ($self->{showdelay}) {
	    if ($self->{paused}) {
		$text .= "Paused";
	    } else {
		$text = "Delay: ";

		if ($self->{delay} >= 60) {
		    $text .= int($self->{delay}/60)." mins ";
		}
		$text .= sprintf "%.2f secs",
		    $self->{delay} - int($self->{delay}/60)*60;
	    }
	}

	if ($self->{filename}) {
	    $text .= "\n" if ($text);
	    $text .= $self->{filename};
	}

	#$self->{layout}->set_markup("<span size='large' weight='bold' foreground='#FFFFFF'>$text</span>");
	$self->{layout}->set_text($text);
    };

    {
	my $timerid = -1;

	sub show_delay {
	    my $self = shift;

	    Glib::Source->remove($timerid) if ($timerid >= 0);

	    $self->{showdelay} = 1;

	    # FIXME:  This needs to queue a redraw...
	    $timerid = Glib::Timeout->add(SHOW_DELAY_TIME, sub {
		    my $self = shift;
		    $self->{showdelay} = 0;
		    $timerid = -1;
		    return FALSE;
		}, $self);

	    &$status_changed($self);
	}
    }

    # In milliseconds
    sub delay
    {
	my $self = shift;
	if (@_) {
	    $self->{delay} = shift() / 1000;
	    $self->show_delay();
	}
	return $self->{delay};
    }

    sub filename
    {
	my $self = shift;
	if (@_) {
	    $self->{filename} = shift;
	    &$status_changed($self);
	}
	return $self->{filename};
    }

    sub paused
    {
	my $self = shift;
	if (@_) {
	    $self->{paused} = shift;
	    $self->show_delay;
	}
	return $self->{paused};
    }

    sub render
    {
	my $self   = shift;
	my $window = shift if (@_);
	my $gc     = shift if (@_);

	if (!$gc) {
	    if ($self->{gc}) {
		 $gc = $self->{gc};
	    } else {
		$self->{gc} = Gtk2::Gdk::GC->new($window);
		$gc = $self->{gc};
	    }
	}

	my ($window_w, $window_h) = $window->get_size();
	if ($self->{layout}->get_width / Gtk2::Pango->scale != $window_w) {
	    $self->{layout}->set_width($window_w * Gtk2::Pango->scale);
	}

	my ($inkrect, $logicrect) = $self->{layout}->get_pixel_extents();
	my ($text_w, $text_h)     = ($$logicrect{width}, $$logicrect{height});
	my ($textbg_w, $textbg_h) =
	    ($text_w + 2*TEXT_PADDING, $text_h + 2*TEXT_PADDING);
	my ($src_x, $src_y)       = (0,0);
	my ($dest_x, $dest_y)     = (0,0);

	if ($self->{textbgscaled}->get_width != $textbg_w ||
	    $self->{textbgscaled}->get_height != $textbg_h) {
	    $self->{textbgscaled} =
		$self->{textbg}->scale_simple($textbg_w, $textbg_h,
		    'nearest');
	}

	$dest_x = $window_w - $textbg_w - TEXT_PADDING;
	if ($dest_x < 0) {
	    $src_x -= $dest_x + TEXT_PADDING;
	    $dest_x = 0;
	}

	$dest_y = $window_h - $textbg_h - TEXT_PADDING;
	if ($dest_y < 0) {
	    $src_y -= $dest_y + TEXT_PADDING;
	    $dest_y = 0;
	}

	$window->draw_pixbuf($gc,
	    $self->{textbgscaled},
	    $src_x, $src_y,
	    $dest_x, $dest_y,
	    $textbg_w - $src_x, $textbg_h - $src_y,
	    'none', 0, 0);

	if ($dest_x > 0) {
	    $dest_x += TEXT_PADDING;
	} else {
	    $dest_x += ($window_w - $text_w)/2;
	}

	if ($dest_y > 0) {
	    $dest_y += TEXT_PADDING;
	} else {
	    $dest_y += ($window_h - $text_h)/2;
	}

	$window->draw_layout($gc,
	    $dest_x - $$logicrect{x},
	    $dest_y - $$logicrect{y},
	    $self->{layout});
    }
}

########################## ImageBundle Class ##################################

{
    # Package for handling a set of images
    # (Basically an image iterator)
    # Invariants:
    #	0 <= curind <= |filelist|
    #	loadahead is "ahead of" curind (so if loadahead < curind it wrapped)
    #	loadbehind is "behind" curind (so if loadbehind > curind it wrapped)

    package ImageBundle;
    use Carp;
    use Glib qw(TRUE FALSE);
    use Tie::Cache;
    use fields qw(cache curind filelist loadahead loadbehind loading);

    use constant CACHE_SIZE	    => 20;  # Should be > MAX_BEHIND+MAX_AHEAD
    use constant MAX_LOAD_AHEAD	    => 10;
    use constant MAX_LOAD_BEHIND    => 5;

    my $load_image = sub {
	my $filename = shift;

	my $pixbuf;
    	eval {
    	    $pixbuf = Gtk2::Gdk::Pixbuf->new_from_file($filename);
    	};

    	carp "$@\n" if ($@);

	return $pixbuf;
    };

    my $remove_index = sub {
	my $self = shift;
	my $ind = shift;

	if ($ind >= @{$self->{filelist}}) {
	   warn "remove_index called with invalid index";
	   return;
	}

	my $filename = $self->{filelist}[$ind];

	delete $self->{cache}{$filename} if (exists $self->{cache}{$filename});
	splice(@{$self->{filelist}}, $ind, 1);
	    
	$self->{curind}-- if ($self->{curind} >= @{$self->{filelist}});
	$self->{loadahead}-- if ($self->{loadahead} >= $ind);
	$self->{loadbehind}-- if ($self->{loadbehind} >= $ind);
    };

    # Returns TRUE if an attempt was made to load an image, FALSE otherwise
    my $preload_an_image = sub {
	my $self = shift;

	# If there are no images to load...
	if (!@{$self->{filelist}}) {
	    $self->{loading} = 0;
	    return FALSE;
	}

	my $cache	= $self->{cache};
	my $curind      = $self->{curind};
	my $filelist    = $self->{filelist};
	my $loadahead	= $self->{loadahead};
	my $loadbehind	= $self->{loadbehind};

	# Check if have preloaded everything
	if ($loadbehind == ($loadbehind - 1) % @{$filelist}) {
	    $self->{loading} = 0;
	    return FALSE;
	}

	if (($loadahead - $curind)%@{$filelist} < MAX_LOAD_AHEAD) {
	    my $ind = ($loadahead + 1)%@{$filelist};
	    my $filename = $$filelist[$ind];

	    if (!$$cache{$filename}) {
		my $image = &$load_image($filename);
		$$cache{$filename} = $image if ($image);
	    }
	    $self->{loadahead} = $ind;
	} elsif (($curind - $loadbehind)%@{$filelist} < MAX_LOAD_BEHIND) {
	    my $ind = ($loadbehind - 1)%@{$filelist};
	    my $filename = $$filelist[$ind];

	    if (!$$cache{$filename}) {
		my $image = &$load_image($filename);
		$$cache{$filename} = $image if ($image);
	    }
	    $self->{loadbehind} = $ind;
	} else {
	    # loaded up to the bounds on front and back
	    $self->{loading} = 0;
	    return FALSE;
	}

	return TRUE;
    };

    my $start_preloading = sub {
	my $self = shift;

	return if ($self->{loading});

	$self->{loading} = 1;
	Glib::Idle->add($preload_an_image, $self);
    };

    sub new
    {
	my $invocant = shift;
	my $class = ref($invocant) || $invocant;

	my %cache;
	tie %cache, 'Tie::Cache', CACHE_SIZE;

	my $self = fields::new($class);
	$self->{cache}	    = \%cache;
	$self->{curind}	    = 0;
	$self->{filelist}   = [ @_ ];
	$self->{loadahead}  = 0;
	$self->{loadbehind} = 0;
	$self->{loading}    = 0;

	&$start_preloading($self);

	return $self;
    }

    sub get_current
    {
	my $self = shift;
	my $imgname = $self->{filelist}[$self->{curind}];
	my $image = $self->{cache}{$imgname};

	if (!$image) {
	    $image = &$load_image($imgname);
	    $self->{cache}{$imgname} = $image if ($image);
	}

	return $image;
    }

    sub get_current_filename
    {
	my $self = shift;
	return $self->{filelist}[$self->{curind}];
    }

    sub get_current_index
    {
	my $self = shift;
	return $self->{curind};
    }

    sub get_next
    {
	my $self = shift;
	my $step = 1; 
	$step = shift if (@_);

	my $newind = ($self->{curind} + $step) % @{$self->{filelist}};
	if (($self->{loadahead} < $newind && 
		$self->{loadahead} >= $self->{curind}) ||
	    ($self->{loadbehind} > $newind &&
		$self->{loadbehind} <= $self->{curind})) {
	    $self->{loadahead} = $newind;
	    $self->{loadbehind} = $newind;
	}

	$self->{curind} = $newind;

	&$start_preloading($self);

	return $self->get_current;
    }

    sub get_prev
    {
	my $self = shift;
	my $step = 1; 
	$step = shift if (@_);
	return $self->get_next(-$step);
    }

    sub get_random
    {
	my $self = shift;
	return $self->get_next(int(rand $#{$self->{filelist}}));
    }

    sub remove_current
    {
	my $self = shift;
	&$remove_index($self, $self->{curind});
    }
}

###############################################################################

my $window;
my $widget;
my $isfullscreen = 0;
my $showstatus = 0;
my $imagedisplay;
my $statusdisplay;
my $imagebundle;

sub render_all
{
    $widget->window->begin_paint_rect(
	Gtk2::Gdk::Rectangle->new(0, 0, $widget->window->get_size));
    $imagedisplay->render($widget->window);
    $statusdisplay->render($widget->window) if ($showstatus);
    $widget->window->end_paint();
}

# Control for auto-advancing to next image
{
    my $timerid = -1;
    my $slidedelay = 5000;	# in milliseconds

    sub get_slide_delay { return $slidedelay; }

    sub set_slide_delay {
	$slidedelay = $_[0];

	Glib::Source->remove($timerid) if ($timerid >= 0);

	if ($slidedelay > 0) {
	    $timerid =
		Glib::Timeout->add($slidedelay,
		    sub {
			$imagebundle->remove_current while (!$imagebundle->get_next);
			$imagedisplay->pixbuf($imagebundle->get_current);
			$statusdisplay->filename($imagebundle->get_current_filename);
			$window->set_title($imagebundle->get_current_filename);
			render_all();
			TRUE;
		    });
	} else {
	    $timerid = -1;
	}
    }

    sub is_slide_paused {
	return $timerid < 0;
    }

    sub pause_slide {
	Glib::Source->remove($timerid) if ($timerid >= 0);
	$timerid = -1;
    }

    sub unpause_slide {
	set_slide_delay($slidedelay);
    }
}

my %actions = (
    'next-image' => sub {
	$imagebundle->remove_current while (!$imagebundle->get_next);
	$imagedisplay->pixbuf($imagebundle->get_current);
	$statusdisplay->filename($imagebundle->get_current_filename);
	$window->set_title($imagebundle->get_current_filename);
	render_all();
	# Restart delay timer
	if (!is_slide_paused) {
	    set_slide_delay(get_slide_delay);
	}
    },

    'prev-image' => sub {
	$imagebundle->remove_current while (!$imagebundle->get_prev);
	$imagedisplay->pixbuf($imagebundle->get_current);
	$statusdisplay->filename($imagebundle->get_current_filename);
	$window->set_title($imagebundle->get_current_filename);
	render_all();
	# Restart delay timer
	if (!is_slide_paused) {
	    set_slide_delay(get_slide_delay);
	}
    },

    'rand-image' => sub {
	$imagebundle->remove_current while (!$imagebundle->get_random);
	$imagedisplay->pixbuf($imagebundle->get_current);
	$statusdisplay->filename($imagebundle->get_current_filename);
	$window->set_title($imagebundle->get_current_filename);
	render_all();
	# Restart delay timer
	if (!is_slide_paused) {
	    set_slide_delay(get_slide_delay);
	}
    },

    'zoom-in' => sub {
	$imagedisplay->scale($imagedisplay->scale*ZOOM_FACTOR);
	render_all();
    },

    'zoom-out' => sub {
	$imagedisplay->scale($imagedisplay->scale/ZOOM_FACTOR);
	render_all();
    },

    'pause' => sub {
	if (is_slide_paused) {
	    unpause_slide;
	    $statusdisplay->paused(0);
	    render_all();
	} else {
	    pause_slide;
	    $statusdisplay->paused(1);
	    render_all();
	}
    },

    'increase-slide-delay' => sub {
	if (is_slide_paused) {
	    unpause_slide;
	    $statusdisplay->paused(0);
	}

	my $olddelay = get_slide_delay();
	if ($olddelay >= 60000) {
	    set_slide_delay($olddelay+60000);
	} elsif ($olddelay >= 10000) {
	    set_slide_delay($olddelay+10000);
	} elsif ($olddelay >= 1000) {
	    set_slide_delay($olddelay+1000);
	} elsif ($olddelay >= 100) {
	    set_slide_delay($olddelay+100);
	} else {
	    set_slide_delay($olddelay+10);
	}
	$statusdisplay->delay(get_slide_delay());
	render_all();
    },

    'decrease-slide-delay' => sub {
	if (is_slide_paused) {
	    unpause_slide;
	    $statusdisplay->paused(0);
	}

	my $olddelay = get_slide_delay();
	if ($olddelay > 60000) {
	    set_slide_delay($olddelay-60000);
	} elsif ($olddelay > 10000) {
	    set_slide_delay($olddelay-10000);
	} elsif ($olddelay > 1000) {
	    set_slide_delay($olddelay-1000);
	} elsif ($olddelay > 100) {
	    set_slide_delay($olddelay-100);
	} elsif ($olddelay > 10) {
	    set_slide_delay($olddelay-10);
	}
	$statusdisplay->delay(get_slide_delay());
	render_all();
    },

    'fullscreen' => sub {
	if ($isfullscreen) {
	    $window->unfullscreen;
	    $isfullscreen = 0;
	} else {
	    $window->fullscreen;
	    $isfullscreen = 1;
	}
    },

    'toggle-status' => sub {
	$showstatus = !$showstatus;
	render_all();
    },

    'quit' => sub {
	Gtk2->main_quit;
    },

    'help' => sub {
	print "Action Keys:\n";
	for my $key (keys %keyactions) {
	    printf "\t%-20s%-20s\n", $key, $keyactions{$key};
	}
    }
);

$window = Gtk2::Window->new;
$window->set_default_size(500,500);
$window->signal_connect (destroy => sub { Gtk2->main_quit; });

$widget = Gtk2::DrawingArea->new;
$widget->set_size_request(1,1);		# To allow shrinking of window
$widget->unset_flags('double-buffered');    # since we do this ourselves

$widget->signal_connect ('expose-event' => sub {
	render_all();
	return TRUE;
    });


$window->add ($widget);

$window->add_events ('key-release-mask');
$window->signal_connect (key_release_event => sub {
	my ($window, $event) = @_;

	my $keyname = "";
	if ($event->state & 'control-mask') {
	    $keyname .= "Ctrl+";
	}
	if ($event->state & 'mod1-mask') {
	    $keyname .= "Alt+";
	}
	if ($event->state & 'super-mask') {
	    $keyname .= "Super+";
	}

	$keyname .= Gtk2::Gdk->keyval_name($event->keyval);
	if (exists $keyactions{$keyname}) {
	    $actions{$keyactions{$keyname}}->();
	}

	return FALSE;
    });

{
    my ($mouse_x, $mouse_y) = (0,0);
    my $pressed = 0;
    my $dragged = 0;

    $window->add_events ('button-press-mask');
    $window->signal_connect (button_press_event => sub {
	    my ($window, $event) = @_;
	    $pressed = 1 if ($event->button == 1);
	    return TRUE;
	});

    $window->add_events ('button-release-mask');
    $window->signal_connect (button_release_event => sub {
	    my ($window, $event) = @_;

	    if (!$dragged) {
		my $buttonname = "Mouse".$event->button;
		if (exists $keyactions{$buttonname}) {
		    $actions{$keyactions{$buttonname}}->();
		}
	    }

	    if ($event->button == 1) {
		$pressed = 0;
		$dragged = 0;
	    }

	    return FALSE;
	});

    $window->add_events('pointer-motion-mask');
    $window->signal_connect(motion_notify_event => sub {
	    my ($window, $event) = @_;

	    if ($pressed) {
		my $dx = $event->x - $mouse_x;
		my $dy = $event->y - $mouse_y;

		my ($offset_x, $offset_y) = $imagedisplay->offsets;
		$imagedisplay->offsets($offset_x+$dx, $offset_y+$dy);
		# Queue a redraw rather than doing it to keep up with dragging
		$widget->window->invalidate_rect(
		    Gtk2::Gdk::Rectangle->new(0, 0, $widget->window->get_size),
		    FALSE);

		$dragged = 1;
	    }

	    $mouse_x = $event->x;
	    $mouse_y = $event->y;

	    #$event->request_motions();

	    return FALSE;
	});
}

$window->signal_connect(scroll_event => sub {
	my ($window, $event) = @_;

	my $buttonname = "MouseWheel".(ucfirst $event->direction);
	if (exists $keyactions{$buttonname}) {
	    $actions{$keyactions{$buttonname}}->();
	}

	return FALSE;
    });

# Parse command-line options
{
    my $fullscreen = 0;
    my $includeregex;
    my @listfiles;
    my $randomize = 0;
    my $recursive = 0;
    my $slideshowdelay = 5;
    my $slideshowmode = 0;
    my $sort = 0;
    my $excluderegex;

    GetOptions(
	'd|delay=f'	 => sub {$slideshowdelay = $_[1]; $slideshowmode = 1; },
	'f|fullscreen!'  => \$fullscreen,
	'F|file=s'       => sub { push @listfiles, $_[1]; },
        'h|help'         => sub { print_usage; exit 0; },
	'I|include=s'	 => \$includeregex,
	'r|randomize!'   => \$randomize,
	'R|recursive'    => sub { $recursive++ },
	's|slideshow!'	 => \$slideshowmode,
	'S|sort!'        => \$sort,
        'V|version'      => sub { print_version; exit 0; },
	'X|exclude=s'	 => \$excluderegex,
    );

    if ($sort and $randomize) {
	die "Can not sort and randomize simultaneously.  ".
	    "Bogosort not supported.\n";
    }

    if ($slideshowdelay < 0) {
	die "Delay between slides must be positive.\n";
    }

    if ($fullscreen) {
	$window->fullscreen;
	$isfullscreen = 1;
    }

    my @namelist = @ARGV;   # Unprocessed (could be directories)
    my @filenamelist;	    # Processed   (files to display)

    for my $listfile (@listfiles) {
	open FILELIST, "<$listfile"
	    or die "Can't read image list file \"$listfile\"";
	while (<FILELIST>) {
    	    next if (/^\s*(#.*)?$/);
    	    chomp;

	    if ($recursive > 1) {
		push @namelist, $_;
	    } else  {
		push @filenamelist, $_;
	    }
    	}
    }

    die "No images listed.\n" if (!@filenamelist && !@namelist);

    # Remove tailing slashes from directories
    foreach my $name (@namelist) {
	$name =~ s/\/$//;
    }

    foreach my $filename (@namelist) {
	if (-d $filename) {
	    opendir(DIR, $filename)
		or warn "Unable to read '$filename':  $!\n" and next;
	    push @namelist, map { "$filename/$_" }
			    grep { $_ ne '.' and $_ ne '..'
				    and ($recursive or -f "$filename/$_") }
			    readdir DIR;
	} elsif (!-f $filename) {
	    warn "'$filename' does not exist or is not a regular file.\n";
	} elsif (!-r $filename) {
	    warn "'$filename' is not readable.\n";
	} else {
	    push @filenamelist, $filename;
	}
    }

    die "No images found.\n" if (!@filenamelist);

    @filenamelist = grep /$includeregex/o, @filenamelist if ($includeregex);
    @filenamelist = grep !/$excluderegex/o, @filenamelist if ($excluderegex);

    die "All images excluded.\n" if (!@filenamelist);

    if ($sort) {
	@filenamelist = sort @filenamelist;
    } elsif ($randomize) {
	# On each loop choose an element to fill index i from remaining elements
	for (my $i=0; $i<@filenamelist; $i++) {
    	    my $randind = int(rand(@filenamelist-$i))+$i;
    	    my $tmp = $filenamelist[$i];
    	    $filenamelist[$i] = $filenamelist[$randind];
    	    $filenamelist[$randind] = $tmp;
    	}
    }

    $imagebundle = ImageBundle->new(@filenamelist);
    set_slide_delay($slideshowdelay*1000) if ($slideshowmode);
}

# All set, lets go
$window->show_all;
$window->modify_bg('normal', Gtk2::Gdk::Color->new(0,0,0));
$widget->window->set_background(Gtk2::Gdk::Color->new(0,0,0));

$imagedisplay = ImageDisplay->new();

$statusdisplay = StatusDisplay->new($widget->get_pango_context());
$statusdisplay->delay(get_slide_delay());
$statusdisplay->paused(is_slide_paused());
my $update_context = sub { $statusdisplay->context_changed(); TRUE; };
$widget->signal_connect ('style-set' => $update_context );
$widget->signal_connect ('direction-changed' => $update_context );

$imagebundle->remove_current while (!$imagebundle->get_current);
$imagedisplay->pixbuf($imagebundle->get_current);
$statusdisplay->filename($imagebundle->get_current_filename);
$window->set_title($imagebundle->get_current_filename);
render_all();

Gtk2->main;

# vim: set sts=4 sw=4 noet:
