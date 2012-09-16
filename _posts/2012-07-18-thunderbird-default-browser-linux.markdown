---
layout: post
date: 2012-07-18 17:34:12-06:00
updated: 2012-09-01 23:07:30-06:00
title: Changing the Default Browser in Thunderbird on Linux
description: "A discussion of the process that Thunderbird uses to determine \
the default browser on Linux and how to change it."
tags: [ thunderbird ]
---
Recently Thunderbird started opening http URLs in the wrong browser.  Although
you may think that the solution would be a simple configuration change, as I
did at the time, it turns out that the process which Thunderbird uses to
determine which browser to use is complex, poorly documented, and has changed
several times between Thunderbird versions.  This post outlines my
understanding of the process and, most importantly, how to change the default
browser in current versions of Thunderbird.

<!--more-->

## How Thunderbird Chooses a Browser

Thunderbird has a variety of methods available for determining which browser to
use.  The methods are attempted one at a time until a browser is found.

**Update:** [Raman Gupta](http://vivosys.com) pointed out that my original
determination of the order in which the methods are attempted was incorrect
and after further testing it appears that mimeTypes.rdf is consulted before
XDG MIME action (at least on Thunderbird 10 and later, probably on previous
versions as well).

This realization also prompted me to dig into the Thunderbird sources to get a
better idea of how the process works.  The specific order in which the methods
are tried, and which methods are available, is based on the platform and the
compilation options used for the running version of Thunderbird.  Interested
developers can look at GetProtocolHandler in
[nsExternalHelperAppService](http://mxr.mozilla.org/comm-central/ident?i=nsExternalHelperAppService)
for the starting point, GetProtocolHandlerInfoFromOS in subclasses of
[nsExternalHelperAppService](http://mxr.mozilla.org/comm-central/ident?i=nsExternalHelperAppService)
for the platform-specific bits and the
[HandlerService](http://mxr.mozilla.org/comm-central/ident?i=HandlerService)
implementation for the platform-agnostic bits.  On Unix, the OS-specific
methods are mostly handled by
[GIO](http://developer.gnome.org/gio/stable/GAppInfo.html#g-app-info-get-default-for-uri-scheme)
(if available) or
[GnomeVFS](http://developer.gnome.org/gnome-vfs/stable/gnome-vfs-2.0-gnome-vfs-mime-database.html#gnome-vfs-mime-application-launch)
or
[QDesktopServices](http://doc.trolltech.com/4.6/qdesktopservices.html#openUrl)
(if compiled with QT).  Although it appears to me that the platform-specific
methods are attempted first, the behavior that I have observed indicates that
the platform-agnostic methods are attempted first.  The behavior that I have
observed is that each of the following methods are attempted, one at a time,
until one of them is successful.  The methods that Thunderbird attempts are
(in order):

### mimeTypes.rdf (Thunderbird - All Versions?)

The [mimeTypes.rdf file](http://kb.mozillazine.org/MimeTypes.rdf) contains
information for the "Helper Applications" which are used to open external
content.  Existing entries can be adjusted in on the Incoming tab of the
Attachments pane of the Preferences window (`Edit -> Preferences ->
Attachments -> Incoming`), or simply on the Attachments pane in earlier
versions.  Unfortunately, [there is no way to add new file-type
associations](https://bugzilla.mozilla.org/show_bug.cgi?id=503303) in the
preferences window.

To add an entry for a scheme that does not appear in the list (e.g. "http" or "https"), use the following process:

1. Open the configuration editor (`about:config`) which can be accessed
   through `Edit -> Preferences -> Advanced -> Config Editor...`.
2. Change `network.protocol-handler.warn-external.<protocol>` to `true` for
   each of the protocols that you wish to configure by double-clicking on
   the preference.  (e.g. change `network.protocol-handler.warn-external.http`
   to `true` to configure the program for http URLs).
3. Click on a URL in an email in Thunderbird. Thunderbird will prompt
   for the application to use to open the link.  Select the desired program
   and check the option to remember the selection.

**Note:** Raman Gupta made a great suggestion that choosing
`/usr/bin/xdg-open` as the preferred application will force Thunderbird to use
the XDG MIME action (the desktop manager's default) if it wouldn't otherwise
do so.

Advanced users can also view/edit the mimeTypes.rdf file directly, although
this is not recommended.  The mimeTypes.rdf file is stored in the [profile
directory](https://support.mozillamessaging.com/en-US/kb/profiles) which can
be found through `Help -> Troubleshooting Information`.  It is usually located
at `~/.mozilla/thunderbird/XXXXXXXX.default/mimeTypes.rdf` where Xs are
replaced by random characters and `default` may be replaced by another profile
name, if named differently.  The program association will be stored as an RDF
Description for `urn:scheme:externalApplication:<protocol>` with an NC:path
containing the application to run.

### XDG MIME action (Thunderbird > ~4)

The default browser is chosen based on the information in the [XDG MIME
database](http://www.freedesktop.org/wiki/Specifications/shared-mime-info-spec),
as specified in the
[XDG MIME actions spec](http://www.freedesktop.org/wiki/Specifications/mime-actions-spec).
Thunderbird looks up the program associated with the URL pseudo-MIME type (e.g.
x-scheme-handler/http for HTTP URLs) or the attachment MIME type (e.g.
text/html).  The XDG MIME database can be queried and modified using the
xdg-mime command-line tool as follows:

    # Query the current default for HTTP URLs
    xdg-mime query default x-scheme-handler/http
    # Set default program for HTTP URLs to Firefox
    xdg-mime default firefox.desktop x-scheme-handler/http

If xdg-mime is not available, the defaults can be changed by editing
`~/.local/share/applications/mimeapps.list` as described in the
[XDG MIME actions spec](http://www.freedesktop.org/wiki/Specifications/mime-actions-spec#User-specified_application_ordering)
and using information from `/usr/share/applications/mimeinfo.cache` for
reference.  For example, the default program for HTTP URLs can be set as
follows:

    [Default Applications]
    x-scheme-handler/http=firefox.desktop

### Gconf (Thunderbird > ~3 with GNOME)

When running in a GNOME environment (if the GNOME libraries are present),
Thunderbird attempts to determine the default browser based on the preferences
stored in [Gconf](http://projects.gnome.org/gconf/).  Thunderbird uses the
following preferences for URLs of various types:

    /desktop/gnome/url-handlers/http/command
    /desktop/gnome/url-handlers/https/command
    /desktop/gnome/url-handlers/about/command
    /desktop/gnome/url-handlers/unknown/command

If the command string contains `%s`, it will be substituted with the URL being
opened.

The information may be modified and queried using gconftool-2 on the command-line as follows:

    # Query the current command for http
    gconftool-2 --get /desktop/gnome/url-handlers/http/command
    # Set the command for http to firefox
    gconftool-2 --set /desktop/gnome/url-handlers/http/command \
        --type string 'firefox "%s"'

The information can also be queried and modified using a graphical program
such as `gconf-editor`.

### Thunderbird Preferences System (e.g. prefs.js) (Thunderbird - Old Versions?)

In all versions of Thunderbird, the default browser may be determined based on
the settings in the Thunderbird preferences.  Preferences can be edited by
opening the configuration editor (about:config) which can be accessed through
`Edit -> Preferences -> Advanced -> Config Editor...`.  Setting a default
protocol handler requires two preferences to be specified.  First,
[`network.protocol-handler.external.<protocol>`](http://kb.mozillazine.org/Network.protocol-handler.external.%28protocol%29)
must be `true` to indicate that
the protocol should be handled by an external program.  Next,
[`network.protocol-handler.app.<protocol>`](http://kb.mozillazine.org/Network.protocol-handler.app.%28protocol%29)
must be set to the name/path of the
program which should be run to handle the URL.  The following preferences may
need to be set/changed:

    network.protocol-handler.app.http
    network.protocol-handler.app.https
    network.protocol-handler.app.ftp
    network.protocol-handler.expose.http
    network.protocol-handler.expose.https
    network.protocol-handler.expose.ftp

Note that these preferences can be managed across multiple machines or made
permanent by editing the [user.js file](http://kb.mozillazine.org/User.js_file).
This is not recommended for normal situations but is mentioned here for
completeness.

**Update:** After examining the Thunderbird sources, I am doubtful about
whether this method is still attempted in recent versions of Thunderbird.
There are no [references to `network.protocol-handler.app` in the Thunderbird
sources](http://mxr.mozilla.org/comm-central/search?string=network.protocol-handler.app)
and I didn't find any code which looks like it accesses these preferences.

## Proper Documentation

The process of changing the default browser is documented on the
[mozillaZine Wiki](http://kb.mozillazine.org/Default_browser#Setting_the_browser_that_opens_in_Thunderbird_-_Linux).
Unfortunately, the page has not been updated since July 2010 and my requests
for an account have been silently ignored for weeks.  If any reader has the
ability to edit that page, I highly encourage you to do so.

Alternatively, this information should probably be posted on the Thunderbird
Messaging KB or Mozilla Wiki.  I have not yet had the time to rework this post
into a suitable format for posting in either location.  If someone would like
to make the changes, I'd be happy to assist.

## Article Changes

### 2012-09-01

* Added and clarified lots of information about mimeTypes.rdf and corrected
  the order in which mimeTypes.rdf is consulted based on input from Raman
  Gupta.
* Added a brief discussion of how handlers are determined programmatically
  with references to the Thunderbird sources.
* Added a note that the prefs.js method may not be used anymore.
