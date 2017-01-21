---
layout: post
date: 2017-01-20 23:18:37-07:00
title: FORMERR from Microsoft DNS Server for DIG
description: "Some versions of Microsoft DNS Server return FORMERR to any \
queries from the BIND DIG tool version 9.11 or later due to DNS COOKIE.  This \
post discusses the issue and a workaround."
tags: [ dns, sysadmin ]
---
While helping to diagnose name resolution issues on a Windows Domain, I
discovered that Microsoft DNS Server (version 1DB10106 (6.1 build 7601))
responds to requests from the [BIND DIG](https://www.isc.org/downloads/bind/)
tool (version 9.11) with response code 1
[`FORMERR`](https://tools.ietf.org/html/rfc1035#page-27) (Request format
error).  This post discusses why and a workaround.

<!--more-->

First, an example request and response, to clarify the issue:

    ; <<>> DiG 9.11.0 <<>> kevinlocke.name @127.0.0.1
    ;; global options: +cmd
    ;; Got answer:
    ;; ->>HEADER<<- opcode: QUERY, status: FORMERR, id: 59675
    ;; flags: qr rd; QUERY: 1, ANSWER: 0, AUTHORITY: 0, ADDITIONAL: 1
    ;; WARNING: recursion requested but not available

    ;; OPT PSEUDOSECTION:
    ; EDNS: version: 0, flags:; udp: 4096
    ; COOKIE: 808a22be618a7750 (echoed)
    ;; QUESTION SECTION:
    ;kevinlocke.name.			IN	A

    ;; Query time: 62 msec
    ;; SERVER: 127.0.0.1#53(127.0.0.1)
    ;; WHEN: Fri Jan 20 17:24:10 Mountain Daylight Time 2017
    ;; MSG SIZE  rcvd: 51

DIG requested `kevinlocke.name` and received `FORMERR`.  After some trial and
error, I determined that the issue results from DIG 9.11 sending the [DNS
COOKIE](https://tools.ietf.org/html/rfc7873) option.  This option was [enabled
by default in BIND
9.11](https://ftp.isc.org/isc/bind/9.11.0/RELEASE-NOTES-bind-9.11.0.html).
Unfortunately, adding this option causes DNS Server to treat the request as
malformed.  This behavior appears to violate "Any OPTION-CODE values not
understood by a responder or requestor MUST be ignored." from [Section 6.1.2
of RFC 6891](https://tools.ietf.org/html/rfc6891#section-6.1.2), but that is
of small consolation for a non-working system.

As a workaround, pass the `+nocookie` option (or `+noedns` to disable all EDNS
options) as in `dig +nocookie kevinlocke.name`.
