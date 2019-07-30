---
layout: post
date: 2019-07-30 11:42:18-06:00
title: More Robust Client-Side JavaScript Error Reporting
description: >-
  Some notes from recent work implementing client-side JavaScript error
  reporting, along with a basic implementation.
---

Recently I reimplemented client-side (i.e. in-browser) JavaScript error
reporting for a web application that I had written years ago.  This post
outlines some of the things I discovered and provides a basic implementation.

<!--more-->

## Consider an Error Reporting Service

For client-side error reporting, as with many things, it is relatively simple
to create a basic implementation and quite difficult to create a complete and
robust one.  Developers without an existing error collection system are
encouraged to consider existing services, such as
[Bugsnag](https://www.bugsnag.com/) ([JS client
source](https://github.com/bugsnag/bugsnag-js)),
[Rollbar](https://rollbar.com/) ([JS client
source](https://github.com/rollbar/rollbar.js/)),
[Sentry](https://sentry.io/) ([JS client
source](https://github.com/getsentry/sentry-javascript/)), and others which
have (presumably) already solved the problems in this post along with many
others.


## Listen for both `error` and `unhandledrejection` events

Uncaught exceptions cause [`error`
events](https://developer.mozilla.org/en-US/docs/Web/API/Element/error_event)
while unhandled
[Promise](https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Promise)
rejections cause
[`unhandledrejection`](https://developer.mozilla.org/en-US/docs/Web/API/Window/unhandledrejection_event)
(but only on Chrome 49+, Edge, Firefox 69+, and
[Bluebird](http://bluebirdjs.com) at the time of this writing).  Consider
listening for `unhandledRejection` (with a capital R) events to catch
unhandled rejections from [when.js](https://github.com/cujojs/when) and
[yaku](https://github.com/ysmood/yaku) promise libraries, if they may be used.
Reporting unhandled exceptions from other promise implementations requires
calling the reporting function explicitly.


## Carefully consider how to support IE

Websites which support any version of Internet Explorer (or Edge on a local
network) should consider how to handle errors on unsupported IE versions which
might be triggered by [Compatibility
View](https://docs.microsoft.com/en-us/openspecs/ie_standards/ms-iedoco/e3f53c89-d2d1-4db3-828f-fcbfe861b609)
(from [Compatibility View
List](https://docs.microsoft.com/en-us/previous-versions//dd567845(v=vs.85))
(by user, admin, or MS), [Security
Zone](https://docs.microsoft.com/en-us/previous-versions/windows/internet-explorer/ie-developer/platform-apis/ms537183(v=vs.85)),
or intranet site detection settings),
[<abbr title="Enterprise Mode IE">EMIE</abbr>](https://docs.microsoft.com/en-us/openspecs/ie_standards/ms-iedoco/2b3f2d0b-65dd-43a4-8448-6b090f28ffd3),
[X-UA-Compatible](https://docs.microsoft.com/en-us/openspecs/ie_standards/ms-iedoco/380e2488-f5eb-4457-a07a-0cb1b6e4b4b5)
(in document or HTTP header), and/or [Edge Enterprise
Mode](https://docs.microsoft.com/en-us/microsoft-edge/deploy/emie-to-improve-compatibility).
Be aware that the value sent in the `User-Agent` header of the HTTP request
does not accurately reflect the browser version in these modes (e.g.  IE 11
sends the `User-Agent` for IE 7 in Compatibility View, but will still operate
in IE 11 document mode based on `X-UA-Compatible: IE=Edge` in the response).
Should errors in unsupported modes be reported (to the user, administrator, or
webmaster) or ignored?

Be aware that IE 8 and before do not support `addEventListener`, so
`window.onerror` must be used.  Also, the `Event` object passed to `error`
event listeners by IE 9 does not include error information, which must be
retrieved from `window.event` with non-standard property names
(`errorMessage`, `errorUrl`, `errorLine`, `errorCharacter`).


## Use `fetch` with `keepalive` or `sendBeacon`

Using [XMLHttpRequest](https://xhr.spec.whatwg.org/) is problematic because
the page may be unloaded before the request is made, causing the request to be
aborted.  This is especially likely if the error occurs during the
[`unload`](https://developer.mozilla.org/en-US/docs/Web/API/Window/unload_event)
or
[`beforeunload`](https://developer.mozilla.org/en-US/docs/Web/API/Window/beforeunload_event)
events.  Previously this could be avoided by making a synchronous request
(calling [`open`](https://xhr.spec.whatwg.org/#the-open()-method) with `async`
`false`), but [synchronous requests cause delays for the user and have been
deprecated](https://developer.mozilla.org/en-US/docs/Web/API/XMLHttpRequest/Synchronous_and_Asynchronous_Requests#Synchronous_request)
and will be disallowed during page dismissal [in
Chrome](https://groups.google.com/a/chromium.org/d/msg/blink-dev/LnqwTCiT9Gs/tO0IBO4PAwAJ)
and [in Firefox](https://bugzilla.mozilla.org/1542967).

The preferred solution is to use [`fetch`](https://fetch.spec.whatwg.org/)
with [`keepalive:
true`](https://www.chromestatus.com/feature/5760375567941632).  Unfortunately,
this is not yet supported in Safari or Firefox ([Bug
1342484](https://bugzilla.mozilla.org/1342484)).  A more portable solution is
to use
[`navigator.sendBeacon`](https://developer.mozilla.org/en-US/docs/Web/API/Navigator/sendBeacon).
Unfortunately, [CORS](https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS)
[support is in flux](https://bugzilla.mozilla.org/1289387) and [Chrome
currently rejects non-CORS blob types](https://crbug.com/490015), so making a
[simple
request](https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS#Simple_requests)
(with `application/x-www-form-urlencoded`, `multipart/form-data`, or
`text/plain` body) is recommended.  Also note that Chrome sends
[`URLSearchParams`](https://developer.mozilla.org/en-US/docs/Web/API/URLSearchParams)
as `text/plain` instead of `application/x-www-form-urlencoded` due to [Bug
747787](https://crbug.com/747787), so using a `Blob` may be necessary.


## Resolve sources in stack traces

The JavaScript that is run in the browser is often the result of transforming
(e.g. transpiling, bundling, minifying) source files in complex ways.  The
stack traces may be significantly more useful if they refer to names and
locations used in the source files.  This can be accomplished using
information from [source
maps](https://docs.google.com/document/d/1U1RGAehQwRypUTovF1KRlpiOFze0b-_2gc6fAH0KY0k)
with libraries such as [stacktrace.js](https://www.stacktracejs.com/).

**Important Caveat:** stacktrace.js fetches source maps and sources
asynchronously, which reintroduces the problem solved in the previous section:
That the page may unload before the sources are resolved, preventing the error
from being reported.  This can be avoided by resolving the sources on the
error reporting server.  Alternatively, the error could be reported twice, both
before and after the sources are resolved, and the unresolved report discarded
by the server when the resolved report is received.


## A basic error reporting script

With the above tips in mind, here is a simplified version of the error
reporting script that I came up with (which omits source resolution due to the
need for server coordination):

```js
{% include {{ page.url | append: "error-reporting.js" }} %}
```

It is also available [as a file](error-reporting.js) and [as a
Gist](https://gist.github.com/kevinoid/a4a940a52b140bcc7dab9b67edab6dbf).
