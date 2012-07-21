---
layout: post
date: 2012-07-18 17:34:12-06:00
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

### How Thunderbird Chooses a Browser

Thunderbird has a variety of methods available for determining which browser to
use.  The methods are attempted one at a time until a browser is found.  The
methods that Thunderbird attempts are (in order):

#### XDG MIME action (Thunderbird > ~4)

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

#### Gconf (Thunderbird > ~3 with GNOME)

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

#### prefs.js (Thunderbird - All Versions)

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

#### mimeTypes.rdf (Thunderbird - All Versions?)

Various sources across the web mention changing the default browser in the
[mimeTypes.rdf file](http://kb.mozillazine.org/MimeTypes.rdf).  This file is
stored in the profile directory (usually
`~/.mozilla/thunderbird/XXXXXXXX.default/mimeTypes.rdf` where Xs are replaced
by random characters and `default` may be replaced by another profile name, if
named differently) and determines the "Helper Applications" which are used to
open external content based on the MIME type.  I have not personally seen any
of the URL pseudo-MIME types appear in this file, but it may be worth checking.

### Proper Documentation

The process of changing the default browser is documented on the
[mozillaZine Wiki](http://kb.mozillazine.org/Default_browser#Setting_the_browser_that_opens_in_Thunderbird_-_Linux).
Unfortunately, the page has not been updated since July 2010 and my requests
for an account have been silently ignored for weeks.  If any reader has the
ability to edit that page, I highly encourage you to do so.

Alternatively, this information should probably be posted on the Thunderbird
Messaging KB or Mozilla Wiki.  I have not yet had the time to rework this post
into a suitable format for posting in either location.  If someone would like
to make the changes, I'd be happy to assist.
