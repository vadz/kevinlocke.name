---
layout: post
date: 2020-04-12 13:34:26-06:00
updated: 2021-02-15 18:13:18-07:00
title: Resolving Desktop Notifications D-Bus Service Conflicts
description: |-
  A procedure for resolving a D-Bus service activation conflict when multiple
  services implement org.freedesktop.Notifications for desktop notifications.
tags: []
---

Recently I started using the [Sway](https://swaywm.org/) window manager, with
occasional fallback to [XFCE](https://xfce.org/).  Having both
[mako](https://wayland.emersion.fr/mako/) and
[xfce4-notifyd](https://docs.xfce.org/apps/notifyd/start) installed causes a
conflict over the `org.freedesktop.Notifications` D-Bus service name (see [Red
Hat Bug 484945](https://bugzilla.redhat.com/484945)).  This post describes the
workaround I am currently using, until [dynamic activation
directories](https://gitlab.freedesktop.org/dbus/dbus/issues/17) or another
solution is implemented.

<!--more-->

## Background

The [Desktop Notifications
Specification](https://people.gnome.org/~mccann/docs/notification-spec/notification-spec-latest.html)
defines a protocol that applications can use to generate popups to notify the
user of events.  It requires a notification server to implement the
`org.freedesktop.Notifications` service on the D-Bus session bus.  The [D-Bus
Specification](https://dbus.freedesktop.org/doc/dbus-specification.html)
defines service activation based on information in service description files.
This mechanism is used by both xfce4-notifyd and mako use to start on demand.
However, this poses a problem.  From [Message Bus Starting Services
(Activation)](https://dbus.freedesktop.org/doc/dbus-specification.html#message-bus-starting-services):

> On the well-known session bus, if two .service files in the same directory
> offer the same service name, the result is undefined. Distributors should
> avoid this situation, for instance by naming session services' .service
> files according to their service name. 

On my system, the result is that xfce4-notifyd is always started, which
provides a poor experience on Sway.


## A Daemon Workaround

If all notification services are daemons (i.e. they only exit at the end of
the user session), an easy workaround is to avoid D-Bus Activation altogether
by starting whichever daemon is desired at the beginning of the user session.
This can be done from systemd units, [Desktop
Autostart](https://specifications.freedesktop.org/autostart-spec/latest/), or
traditional startup scripts (e.g. `.xsessionrc`).  My understanding is that
both GNOME and KDE notification services have moved to this approach.

Unfortunately, this approach forfeits the benefits of D-Bus activation, such
as deferring the costs of running the notification service until/unless
required and avoiding delay and contention at the start of the user session.
It also doesn't work for notification services like mako which exit after
displaying a notification.


## An Autostart Workaround

The next paragraph in the D-Bus Specification hints at a potential workaround:

> If two .service files in different directories offer the same service name,
> the one in the higher-priority directory is used: for instance, on the
> system bus, .service files in /usr/local/share/dbus-1/system-services take
> precedence over those in /usr/share/dbus-1/system-services. 

The service directories are defined by the `dbus-daemon` configuration, as
described in
[dbus-daemon(1)](https://dbus.freedesktop.org/doc/dbus-daemon.1.html), with
the standard session directories:

* `$XDG_RUNTIME_DIR/dbus-1/services`
* `$XDG_DATA_HOME/dbus-1/services` (default `~/.local/share`)
* `$XDG_DATA_DIRS/dbus-1/services` for each directory in `$XDG_DATA_DIRS`
  (default `/usr/local/share:/usr/share`)
* `${datadir}/dbus-1/services` compiled-in default (default `/usr/share`)

This behavior can be used to implement a workaround by placing a service
definition file in `$XDG_DATA_HOME/dbus-1/services` for a service which
activates the appropriate notification service for the current desktop
environment.


### systemd Activation

The D-Bus Specification also defines [systemd
Activation](https://dbus.freedesktop.org/doc/dbus-specification.html#message-bus-starting-services-systemd)
for starting a service via systemd instead of executing a binary.  Although
the specification does not describe the details, [systemd activation in
`bus/activation.c`](https://gitlab.freedesktop.org/dbus/dbus/-/blob/dbus-1.12.16/bus/activation.c#L2002-2124)
is reasonably easy to follow: Send an `ActivationRequest` signal to
`org.freedesktop.systemd1.Activator` at path `/org/freedesktop/DBus` on the
session bus with a string argument containing the service name to activate.


### Implementation

To implement the workaround, create a service definition file named
`~/.local/share/dbus-1/services/org.freedesktop.Notifications.service` with
the following content:

```ini
[D-BUS Service]
Name=org.freedesktop.Notifications
Exec=/home/username/.local/lib/notify-dispatch
```

Create an executable script named `~/.local/lib/notify-dispatch` which runs
the preferred notification service based on the environment:

```sh
#!/bin/sh

if [ "$XDG_SESSION_DESKTOP" = sway ]; then
	exec /usr/bin/mako "$@"
fi

if [ "$XDG_SESSION_DESKTOP" = xfce ]; then
        exec dbus-send --session \
                --dest=org.freedesktop.systemd1 \
                /org/freedesktop/DBus \
                org.freedesktop.systemd1.Activator.ActivationRequest \
                string:xfce4-notifyd.service
fi

echo "Error: No notification service for $XDG_SESSION_DESKTOP in $0." >&2
exit 1
```

Then log out and back in (or `killall -HUP dbus-daemon`) to apply the changes.
To test, send a desktop notification (e.g. `notify-send Test`).


### Addendum: Environment Variables

The `notify-dispatch` script inherits its environment from the D-Bus
activation environment.  For `$XDG_SESSION_DESKTOP` to be set,
[`dbus-update-activation-environment`](https://dbus.freedesktop.org/doc/dbus-update-activation-environment.1.html)
must be called (with `--all` or `XDG_SESSION_DESKTOP=...` explicitly) after
the session bus is started, before a notification is sent.  On Debian, this is
done by the [Xsession](https://wiki.debian.org/Xsession) script
`/etc/X11/Xsession.d/95dbus_update-activation-env` (from the
[`dbus-x11`](https://packages.debian.org/sid/dbus-x11) package).  (Note:
Although, as its name implies, Xsession is a component of the X Window System,
some display managers also run Xsession when starting Wayland sessions.
[LightDM](https://github.com/canonical/lightdm) is one example.)
