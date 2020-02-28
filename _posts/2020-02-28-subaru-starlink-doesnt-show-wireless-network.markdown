---
layout: post
date: 2020-02-28 14:31:48-07:00
title: Subaru STARLINK Doesn't Show My Wireless Network
description: >-
  Notes on connecting the Subaru STARLINK system to a home wireless
  network (e.g. for firmware updates).
tags: []
---

Some quick notes about connecting [Subaru
STARLINK](https://www.subaru.com/engineering/starlink/overview.html) to a home
wireless network (e.g. for firmware updates):

<!--more-->

* **Wi-Fi Settings** only shows password-protected wireless networks in its
  available Wi-Fi networks list.  I have not found a way to connect to open
  networks.
* **Wi-Fi Settings** does not show networks on channel 48.  Presumably it
  excludes channels 36-64, which are [limited to indoor
  use](https://en.wikipedia.org/wiki/List_of_WLAN_channels#5.0_GHz_(802.11j)_WLAN)
  in many countries (although not the USA, where it is currently configured).
  Channels 153 and 157 worked for me.  (Presumably 149-165 would also work.)
* **Check for Updates** may display the error "Please Connect to a Wi-Fi
  Access Point or connect your mobile device and run the Aha Radio application
  (version 5 or later) to check for updates." even when it is connected to a
  wireless network.  This is a generic "can't connect to update server" message
  which could be caused by low signal strength, or any other network issue.
  It is not necessarily a wireless connection issue.

These issues together significantly complicated the connecting and
troubleshooting process for me.  I hope that by reading this you can more
easily correct or avoid these issues.  Good luck!
