---
layout: post
date: 2017-03-10 10:28:37-07:00
title: Ownership of /usr/local by group staff
description: An example of how to use the staff group for good.
tags: [ sysadmin ]
---
I recently read through Debian [Bug
299007](https://bugs.debian.org/cgi-bin/bugreport.cgi?bug=299007) which
resulted in the policy change to move toward `/usr/local` being owned by group
root instead of group staff.  The move was largely motivated by concerns
that group staff is root-equivalent (i.e. a user in group staff has all the
power of the root account) because it can create/change binaries in the root
$PATH.  Although this is true, and is a good reason not to add users to group
staff, it ignores at least one good use case discussed in this post.

<!--more-->

With `/usr/local` owned by an empty staff group, you can do things like the
following:

```sh
sudo -g staff make install
```

Granting sudo permission for group staff to privileged user accounts allows
them to make system-wide changes after authenticating, while still providing
some protection against inadvertent changes.  If the `make install` script
tries to write outside of `/usr/local` (e.g. due to bad `configure --prefix`)
it will fail.  If the user, or programs under their control, inadvertently
tries to make modifications to `/usr/local` without `sudo`, they will fail.
The only time they have permission to write to `/usr/local` is when running
under sudo and sudo only grants `/usr/local` write permission.

When used this way, the staff group provides a very basic sort of Role-Based
Access Control where the user activates the staff role through sudo.  It
doesn't enhance security, since the user and executing processes are still
root-equivalent, but it provides some protection against unintentional misuse.
For a security policy to protect against intentional misuse, a security
framework such as [SELinux](https://selinuxproject.org/) should be used.

Note that this post isn't arguing for keeping `/usr/local` owned by group
staff by default.  Since most groups are used by adding privileged users (e.g.
audio, cdrom, dialout, etc.) and there are no documented warnings or guidance
to the contrary, misuse is highly likely.  I was guilty of adding users to
group staff myself before I realized the full implications.  This article is
an example of how a system could be configured, as a default or non-default,
to good effect.
