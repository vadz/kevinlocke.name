---
layout: post
date: 2019-07-31 10:18:44-06:00
title: Prefer Terminal for GnuPG Pinentry
description: >-
  This post describes the GnuPG pinentry process and provides a script which
  automatically chooses between a terminal or graphical interface based on
  the PINENTRY_USER_DATA environment variable.
---

[GnuPG](https://www.gnupg.org/) 2 uses a
[pinentry](https://www.gnupg.org/related_software/pinentry/) program to prompt
the user for passphrases and PINs.  The [standard pinentry
collection](https://git.gnupg.org/cgi-bin/gitweb.cgi?p=pinentry.git) includes
executables for GNOME, plain GTK+, Qt, Curses, and TTY user interfaces.  By
default, the graphical programs will fall back to Curses when `$DISPLAY` is
not available.  For my own use, I would like the opposite behavior:  Present a
text UI if a terminal is available, otherwise fall back to a graphical UI.
This post describes one way to accomplish that behavior.

<!--more-->


## Pinentry Architecture

[gpg-agent](https://www.gnupg.org/documentation/manuals/gnupg/Invoking-GPG_002dAGENT.html)
invokes the pinentry executable configured by `pinentry-program` in
`gpg-agent.conf` (default: `pinentry`, which is managed by the [Debian
Alternatives System](https://wiki.debian.org/DebianAlternatives) on
Debian-based distros) whenever the user must be prompted for a passphrase
or PIN.  The standard input and output of pinentry are pipes over which the
configuration and response information is sent in the [Assuan
Protocol](https://www.gnupg.org/documentation/manuals/assuan/).  (See the
[pinentry
Manual](https://git.gnupg.org/cgi-bin/gitweb.cgi?p=pinentry.git;a=blob;f=doc/pinentry.texi)
for specifics.)  Additionally, environment variables which contain
configuration information passed via Assuan (e.g. `$DISPLAY`, `$GPG_TTY`,
`$TERM`) are not passed to pinentry.  (See
[`stdenvnames`](https://git.gnupg.org/cgi-bin/gitweb.cgi?p=gnupg.git;a=blob;f=common/session-env.c;h=c1eb1d9593043719544c2ad5c1e1e898fa5eceec;hb=591523ec94b6279b8b39a01501d78cf980de8722#l66)
for a full list and mapping.)

This architecture keeps pinentry simple and self-contained, but it makes
environment detection and conditional execution difficult:

* `stdin` is always a pipe.
* `$DISPLAY` and `$GPG_TTY` are never set.
* Reading configuration information requires implementing the Assuan protocol
  (and proxying it to any child pinentry processes).
* Fallback between different pinentry programs is only possible if they don't
  read any Assuan messages before failing (or the messages are proxied to each
  invocation).

To achieve the desired behavior in a robust way, without additional
configuration, subject to the above constraints, likely requires implementing
a pinentry program using
[libassuan](https://www.gnupg.org/software/libassuan/) or modifying an
existing pinentry program to present a UI based on the configuration
information passed via Assuan.  However, I am too lazy to write and maintain
my own pinentry program, so I came up with a different solution which requires
a little configuration:


## Using `$PINENTRY_USER_DATA` for Configuration

As a result of [Task 799](https://dev.gnupg.org/T799), GnuPG 2.08 and later
pass the `PINENTRY_USER_DATA` environment variable from the calling
environment to gpg-agent to pinentry.  The format of this variable is not
specified (and not used by any programs in the standard pinentry collection
that I can find).  [pinentry-mac](https://github.com/GPGTools/pinentry-mac)
[assumes it is a comma-separated sequence of NAME=VALUE pairs with no quoting
or
escaping](https://github.com/GPGTools/pinentry-mac/blob/v0.9.4/Source/AppDelegate.m#L78)
and [recognizes USE_CURSES=1 to control curses
fallback](https://github.com/GPGTools/pinentry-mac/pull/2).  I adopted this
convention for a simple pinentry script which chooses the UI based on the
presence of `USE_TTY=1` in `$PINENTRY_USER_DATA`:

```sh
{% include {{ page.url | append: "pinentry-auto.sh" }} %}
```

To use this script for pinentry:

1. Save [the script](pinentry-auto.sh) (e.g. as `~/bin/pinentry-auto`).
2. Make it executable (`chmod +x pinentry-auto`).
3. Add `pinentry-program /path/to/pinentry-auto` to `~/.gnupg/gpg-agent.conf`.
4. `export PINENTRY_USER_DATA=USE_TTY=1` in environments where prompting via
   TTY is desired (e.g. alongside `$GPG_TTY` in `~/.bashrc`).

The script and settings are also available [as a
Gist](https://gist.github.com/kevinoid/189a0168ef4ceae76ed669cd696eaa37).
