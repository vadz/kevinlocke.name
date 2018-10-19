---
layout: post
date: 2018-10-19 14:54:30-06:00
title: Disable NetBIOS over TCP/IP with Dnsmasq
description: "How to configure DHCP options in dnsmasq to disable NetBIOS on
Windows clients."
tags: [sysadmin]
---

A friend recently convinced me that it's time to disable
[NetBIOS](https://en.wikipedia.org/wiki/NetBIOS) (and
[WINS](https://en.wikipedia.org/wiki/Windows_Internet_Name_Service)) based in
part on [Microsoft's recommendation not to deploy
WINS](https://docs.microsoft.com/en-us/windows-server/networking/technologies/wins/wins-top),
[serious unpatched WINS
vulnerabilities](https://www.fortinet.com/blog/threat-research/wins-server-remote-memory-corruption-vulnerability-in-microsoft-windows-server.html),
[spoofability](https://isc.sans.edu/forums/diary/Is+it+time+to+get+rid+of+NetBIOS/12454/),
and because it complicates network lookups and masks DNS problems.  After
reviewing Ace Fekay's excellent post [Do I need
NetBIOS?](https://blogs.msmvps.com/acefekay/2013/03/02/do-i-need-netbios/) to
check for gotchas, I decided to [disable NetBIOS over TCP/IP by using DHCP
server options](https://support.microsoft.com/kb/313314).  This is
accomplished by setting the [Vendor-Specific Option Code
0x01](https://msdn.microsoft.com/en-us/library/cc227276.aspx) to the value
`0x00000001` for DHCP clients matching the [Microsoft Vendor Class Identifier
](https://msdn.microsoft.com/en-us/library/cc227279.aspx) (using "`MSFT`" for
forward-compatibility rather than the entire "`MSFT 5.0`" identifier).  In
[dnsmasq](http://www.thekelleys.org.uk/dnsmasq/doc.html) this can be
accomplished by adding the following to `/etc/dnsmasq.conf`:

    dhcp-option=vendor:MSFT,1,2i

(For reference, there is [more explanation of how `dhcp-option` vendor options
work in a dnsmasq-discuss
post](http://lists.thekelleys.org.uk/pipermail/dnsmasq-discuss/2008q4/002693.html).)
Once configured, restart dnsmasq then acquire a new DHCP lease (e.g. by
running `ipconfig /release && ipconfig /renew`) and confirm NetBIOS over
TCP/IP is disabled (e.g. by running `ipconfig /all`).  With any luck you will
be free of NetBIOS.
