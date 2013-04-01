---
layout: post
date: 2013-04-01 08:55:58-06:00
title: Issues with Slow Cryptsetup
description: "Cryptsetup can be slow to create/open mappings in some \
situations.  This post is a quick note about one of those situations."
tags: [ debian, linux ]
---

I recently configured an additional encrypted partition mounted at boot using
[cryptsetup](http://code.google.com/p/cryptsetup) with LUKS.  Doing so
increased my boot time by about 5 seconds.  In tracking down this minor
annoyance, I learned two things about cryptsetup which may be helpful to
others in a similar situation:

<!--more-->

* PBKDF2 is used to strengthen the password.  The PBKDF2 iteration count is
  set based on the speed of the system creating the device (or, more
  specifically, of the system setting each key slot) with a default set to
  take about a second.
* When opening a LUKS device, each key slot is tested sequentially.  So if the
  device accepts one of five different passwords/keyfiles each created with a
  1 second iteration count and the last one is used, it will take 5 seconds to
  open.

So, to reduce boot time place the key which is typically used in the first key
slot (or specify the key slot explicitly) and, depending on the particular
security requirements for the device, reduce the iteration count when creating
this key slot.
