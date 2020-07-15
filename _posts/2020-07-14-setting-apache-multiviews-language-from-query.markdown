---
layout: post
date: 2020-07-14 21:02:00-06:00
title: Setting Apache MultiViews Language from Query String
description: "A technique for choosing the preferred language to serve using \
Apache MultiViews (i.e. mod_negotiation) based on the value of a query \
parameter."
tags: [ apache ]
---

An intrepid reader asked about how to extend the technique from [Serving XHTML
with Apache MultiViews]({% post_url
2012-07-20-serving-xhtml-with-apache-multiviews %}) and [Serving
Pre-Compressed Files with Apache MultiViews]({% post_url
2016-01-20-serving-pre-compressed-files-with-apache-multiviews %}) to serve
files for a language requested using a query parameter.  This post outlines
the slick technique we worked out.

<!--more-->

Initially we investigated using
[mod_rewrite](https://httpd.apache.org/docs/current/mod/mod_rewrite.html) and
[mod_headers](https://httpd.apache.org/docs/current/mod/mod_headers.html) in
various ways.  Unfortunately, it appears that
[mod_negotiation](https://httpd.apache.org/docs/current/mod/mod_negotiation.html)
(which implements `MultiViews`) performs negotiation in the [`type_checker`
hook of the preparation phase of request
processing](https://httpd.apache.org/docs/current/developer/request.html#preparation)
(see
[mod_negotiation.c:3212](https://github.com/apache/httpd/blob/2.4.43/modules/mappers/mod_negotiation.c#L3212))
which is before the `fixup` hook used by mod_headers (see
[mod_headers.c:1007](https://github.com/apache/httpd/blob/2.4.43/modules/metadata/mod_headers.c#L1007))
and mod_rewrite (see
[mod_rewrite.c:5315](https://github.com/apache/httpd/blob/2.4.43/modules/mappers/mod_rewrite.c#L5315)).
This prevents header or environment changes made by those modules from
affecting MultiViews negotiation, unless other trickery (e.g. sub-requests) is
used.

The solution we came up with is to use
[`SetEnvIfExpr`](https://httpd.apache.org/docs/current/mod/mod_setenvif.html#setenvifexpr)
from
[mod_setenvif](https://httpd.apache.org/docs/current/mod/mod_setenvif.html) to
set the [`prefer-language` environment
variable](https://httpd.apache.org/docs/current/content-negotiation.html#exceptions).
For example, to use the language from a query parameter named `lang` if it
contains a value which might be a valid [Basic Language
Range](https://tools.ietf.org/html/rfc4647#section-2.1) for
[Accept-Language](https://tools.ietf.org/html/rfc7231#section-5.3.5):

``` apache
Options +MultiViews
SetEnvIfExpr "%{QUERY_STRING} =~ /(?:^|&)lang=([A-Za-z]{1,8}(?:-[A-Za-z0-9]{1,8})*)(?:&|$)/" prefer-language=$1
```

This combination should be sufficient for requests such as `GET
/index.html?lang=fr` to return `index.html.fr` regardless of the value in the
`Accept-Language` request header, if present.

Note: The mapping from language tags to file extensions is configured by
[`AddLanguage`](https://httpd.apache.org/docs/current/mod/mod_mime.html#addlanguage).
On Debian, the default mapping is defined in
`/etc/apache2/mods-available/mime.conf` (linked from
`/etc/apache2/mods-enabled/mime.conf` when mod_mime is enabled) and can be
changed using `RemoveLanguage`/`AddLanguage` as desired.

Best of luck!
