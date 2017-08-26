---
layout: post
date: 2017-08-26 13:20:51-06:00
title: "strongSwan and SonicWall: payload type ID_V1 was not encrypted"
description: "A workaround for issues connecting to a SonicWall IPsec VPN \
server from a strongSwan client."
tags: [ sysadmin, vpn ]
---

I recently encountered the following error while attempting to connect to a
SonicWall IPsec VPN using strongSwan:

    payload type ID_V1 was not encrypted

This issue has been [encountered in Chromium OS](https://crbug.com/334620) and
subsequently [fixed](https://chromium-review.googlesource.com/191108).  The
fix was [upstreamed to
strongSwan](https://git.strongswan.org/?p=strongswan.git;a=commit;h=c4c9d291d2aaeccf9d36971de763b0ab60af9e66)
and included in strongSwan 5.2.0 and later behind the
`charon.accept_unencrypted_mainmode_messages` configuration option.  Users
encountering the above error may want to include the following in
`/etc/strongswan.conf`:

```
charon {
    accept_unencrypted_mainmode_messages = yes
}
```

On Debian-based distributions this can be accomplished by editing the
appropriate line in `/etc/strongswan.d/charon.conf`.
