---
layout: post
date: 2013-02-22 18:20:15-07:00
title: Firefox Fails in Odd Ways Without /proc
description: "Recent versions of Firefox crash on startup when /proc is not \
available.  This post lists some of the error messages that I have observed."
tags: [ mozilla ]
---
Recent versions of Firefox crash on startup when `/proc` is not mounted.
Although this is not a problem, per se, the fact that it crashes without
giving any indication of the reason can significantly complicate testing
alpha/beta/nightly releases.  This post simply lists the errors that I have
seen in hopes that it will save others some debugging time.

<!--more-->

## Symptoms

Current [Nightly](http://ftp.mozilla.org/pub/mozilla.org/firefox/nightly/2013-02-22-03-11-33-mozilla-central/firefox-22.0a1.en-US.linux-i686.tar.bz2) builds crash with:

    ###!!! ABORT: Recursive layout module initialization: file /builds/slave/m-cen-lx-ntly-0000000000000000/build/layout/build/nsLayoutModule.cpp, line 374
    ###!!! ABORT: Recursive layout module initialization: file /builds/slave/m-cen-lx-ntly-0000000000000000/build/layout/build/nsLayoutModule.cpp, line 374

Current
[Aurora](https://ftp.mozilla.org/pub/mozilla.org/firefox/nightly/2013-02-19-04-20-21-mozilla-aurora/firefox-20.0a2.en-US.linux-i686.tar.bz2)
and
[Beta](http://download.cdn.mozilla.net/pub/mozilla.org/firefox/releases/20.0b1/linux-i686/en-US/firefox-20.0b1.tar.bz2)
builds crash with:

    ###!!! ABORT: Recursive layout module initialization: file /builds/slave/m-aurora-lx-ntly-0000000000000/build/layout/build/nsLayoutModule.cpp, line 372
    ###!!! ABORT: Recursive layout module initialization: file /builds/slave/m-aurora-lx-ntly-0000000000000/build/layout/build/nsLayoutModule.cpp, line 372

Current
[Nightly](http://ftp.mozilla.org/pub/mozilla.org/firefox/nightly/2013-02-22-mozilla-central-debug/firefox-22.0a1.en-US.debug-linux-i686.tar.bz2)
and
[Aurora](https://ftp.mozilla.org/pub/mozilla.org/firefox/nightly/2013-02-22-mozilla-aurora-debug/firefox-21.0a2.en-US.debug-linux-i686.tar.bz2)
debug builds crash with:

    Assertion failure: stackBase, at ../../../js/src/jsnativestack.cpp:139

## Solution

Make sure `/proc` is mounted!

## Is It A Bug?

I wouldn't argue that Firefox should work without `/proc`, but I do think it
would be preferable to inform users why Firefox can't start, if it's feasible.
After asking on [#firefox](irc://irc.mozilla.org/firefox) without response,
I'm tempted to let it lie.  If anyone else wants to work on a fix, count me
in.
