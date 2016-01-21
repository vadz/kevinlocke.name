---
layout: post
date: 2016-01-20 15:22:48-08:00
title: Serving Pre-Compressed Files with Apache MultiViews
description: "This post describes how to serve pre-compressed files, such as \
gzipped CSS and JavaScript, using MultiViews in Apache while avoiding the \
common pitfalls encountered in common examples."
tags: [ apache ]
---

A common tactic to increase performance and decrease bandwidth is to compress
HTTP responses.  This is particularly useful for text content such as the CSS,
JavaScript, and HTML that are fundamental to the web.  There are several
different methods for configuring compression in Apache, but most have subtle
(or not so subtle) issues.  This post continues the series of `MultiViews`
posts (after the earlier
[XHTML]({% post_url 2012-07-20-serving-xhtml-with-apache-multiviews %}) and
[ErrorDocuments]({% post_url 2015-10-01-a-warning-about-errordocument-with-multiviews %})
posts) by outlining the problems encountered in popular compression
configurations and how to avoid them using `MultiViews`.

<!--more-->

Note:  Readers who are not interested in the tradeoffs or potential issues are
advised to [skip to the end](#the-multiviews-method) for the working
configuration.
{: .note}

## Non-MultiViews Methods

In order to motivate the choice of `MultiViews` for serving pre-compressed
content, it's useful to consider the popular alternatives.  The first is using
[`mod_deflate`](https://httpd.apache.org/docs/2.4/mod/mod_deflate.html) to
compress response content on the fly.  This works well for dynamic content,
but wastes resources for static content, which is needlessly recompressed for
each request.

This limitation of `mod_deflate` is prominently mentioned in the
documentation, [which
recommends](https://httpd.apache.org/docs/2.4/mod/mod_deflate.html#precompressed)
using [`mod_rewrite`](https://httpd.apache.org/docs/2.4/mod/mod_rewrite.html)
to rewrite requests to their compressed alternatives when appropriate.
Although this method can work (and [I recommended it to get the desired
behavior for
XHTML]({% post_url 2012-07-20-serving-xhtml-with-apache-multiviews %}))
it has the major drawback that you are reimplementing [content
negotiation](https://httpd.apache.org/docs/2.4/content-negotiation.html)
(which
[`mod_negotiation`](https://httpd.apache.org/docs/2.4/mod/mod_negotiation.html)
was designed to do) and are likely to get it wrong and lack features supported
by `mod_negotiation`.  Some common problems and pitfalls with this approach:

- Sending an incorrect or missing `Content-Encoding` header.
- Not sending the `Vary` header or setting it incorrectly (overwriting
  previous values for other headers which cause the response to vary).
- Sending `Content-Type: application/x-gzip` instead of the underlying type.
- Sending double-gzipped content due to forgetting to set `no-gzip` in the
  environment to exclude the response from `mod_deflate`.
- Not respecting client preferences (i.e. quality values/qvalues).  According
  to [RFC 7231](https://tools.ietf.org/html/rfc7231#section-5.3.4) (and
  [RFC 2616](https://tools.ietf.org/html/rfc2616#section-14.3) before it)
  clients can send a numeric value between 0 and 1 (inclusive) to express
  their relative preference for each encoding.  An `Accept-Encoding: gzip;q=0`
  header would signify that the client wants "anything but gzip".  Most
  `mod_rewrite` implementations would send them gzip.  A more realistic
  example would be a client that sends `Accept-Encoding: br;q=1, gzip;q=0.5,
  deflate;q=0.1` to signify that they prefer Brotli, then gzip, then deflate.
  Writing `mod_rewrite` rules which properly handle these sorts of expressed
  preferences is extremely difficult.

In addition to the above issues, this approach requires writing a redirect
rule for each supported combination of negotiated values.  Supporting gzip
encoding for a few file extensions is reasonable, but if additional types and
encodings are added (much less languages or charsets) it quickly becomes
unreasonable.  It also doesn't support any of the features of [Transparent
Content Negotiation](https://tools.ietf.org/html/rfc2295) which are supported
out of the box by `mod_negotiation`.

## Building A Solution Using MultiViews

As in previous posts, we will build up a solution iteratively, tackling
problems as they appear.  For this to work, `mod_mime` and `mod_negotiation`
must be enabled/loaded.   On Debian and related distributions this can be done
by running `a2enmod mime` and `a2enmod negotiation` as root.  The following
examples use the domain `localhost` and the files `style.css` and
`style.css.gz` in the site root.

We start by simply enabling `MultiViews` and declaring the extension `.gz` to
identify the gzip encoding using following configuration (which must be inside
a `<Directory>` directive or `.htaccess` file, as noted in the description of
the `MultiViews` value for
[`Options`](https://httpd.apache.org/docs/2.4/mod/core.html#options)):

{% highlight apache %}
Options +MultiViews
AddEncoding gzip .gz
{% endhighlight %}

If we test the result using `curl -I -H "Accept-Encoding: gzip"
http://localhost/style.css` we get something like the following:

{% highlight http %}
HTTP/1.1 200 OK
Date: Thu, 21 Jan 2016 01:03:58 GMT
Server: Apache/2.4.18 (Debian)
Last-Modified: Thu, 21 Jan 2016 01:00:51 GMT
ETag: "f9e-529cda2456c78-gzip"
Accept-Ranges: bytes
Vary: Accept-Encoding
Content-Encoding: gzip
Content-Length: 1323
Content-Type: text/css
{% endhighlight %}

Notice that the server sent a response with the correct `Content-Type` and
`Content-Encoding`.  We are already 95% of the way to the solution.  However,
some issues appear if we request `style.css.gz` directly or request `style`
without an extension to negotiate the `Content-Type`.[^negotiatetype]
Consider the result for `curl -I -H "Accept-Encoding: gzip"
http://localhost/style`:

{% highlight http %}
HTTP/1.1 200 OK
Date: Thu, 21 Jan 2016 01:08:30 GMT
Server: Apache/2.4.18 (Debian)
Content-Location: style.css.gz
Vary: negotiate,accept,accept-encoding
TCN: choice
Last-Modified: Thu, 21 Jan 2016 01:00:51 GMT
ETag: "536-529cda2456c78;529cdb967800b"
Accept-Ranges: bytes
Content-Length: 1334
Content-Type: application/x-gzip
Content-Encoding: gzip
{% endhighlight %}

This is all sorts of wrong (although it is common enough that [Firefox detects
it and provides a
workaround](https://mxr.mozilla.org/mozilla-esr38/source/netwerk/protocol/http/nsHttpChannel.cpp#4291)).
We wanted to send the browser a stylesheet, but instead we sent it a gzip file
(according to `Content-Type`) which is gzipped (according to
`Content-Encoding`).  We actually sent it the same gzipped stylesheet, but
with the wrong `Content-Type`.  This is because Debian (and related
distributions) set `AddType application/x-gzip .gz` in their default
configuration (in `/etc/apache2/mods-available/mime.conf`), so for
`style.css.gz` the `.gz` is being interpreted as both the type and the
encoding of the file.  This can be fixed using `RemoveType` as follows:

{% highlight apache %}
Options +MultiViews
RemoveType .gz
AddEncoding gzip .gz
{% endhighlight %}

With this fix, the response now includes the correct headers, as in the first
example response above.  Unfortunately, we've introduced a new problem.
Suppose we are hosting a gzipped-tarball `launch-codes.tar.gz`.  Requesting it
results in a response similar to the following:

{% highlight http %}
HTTP/1.1 200 OK
Date: Thu, 21 Jan 2016 01:32:51 GMT
Server: Apache/2.4.18 (Debian)
Last-Modified: Thu, 21 Jan 2016 01:27:34 GMT
ETag: "3b709-529ce01dc4107"
Accept-Ranges: bytes
Content-Length: 243465
Content-Type: application/x-tar
Content-Encoding: gzip
{% endhighlight %}

This tells the browser that we are sending it a tar file which is compressed
for transmission.  So, if the browser didn't have [workarounds for this
brokenness
too](https://mxr.mozilla.org/mozilla-esr38/source/uriloader/exthandler/nsExternalHelperAppService.cpp#572)),
it would decompress the response content and save the uncompressed file as
`launch-codes.tar` (or worse `launch-codes.tar.gz`).  What we actually wanted
was to send a gzipped file with no additional content encoding.  We can
achieve that by adding some further configuration to `.tar.gz` files:

{% highlight apache %}
Options +MultiViews
RemoveType .gz
AddEncoding gzip .gz
<FilesMatch ".+\.tar\.gz$">
    RemoveEncoding .gz
    AddType application/gzip .gz
</FilesMatch>
{% endhighlight %}

This approach can easily be extended to any other compound file extensions
that should be saved without gunzipping by altering the `FilesMatch`
expression.  It uses the `application/gzip` type of [RFC
6713](https://tools.ietf.org/html/rfc6713), which is the official type of gzip
files, but which lacks the same browser support as the legacy
`application/x-gzip` type.  Administrators concerned about older browsers
should use the legacy type.  Also, as a matter of style, the configuration
could have used `ForceType` instead of `AddType` within the `FilesMatch`
directive.

With this configuration we have eliminated all of the previous issues and
achieved the desired result.  It can also be extend to include additional
encodings easily, as we will demonstrate.

## The MultiViews Method

Now that we have found a working solution using `MultiViews`, lets add support
for
[Brotli](https://hacks.mozilla.org/2015/11/better-than-gzip-compression-with-brotli/)
with the extension `.brotli`, as the icing on the cake:

{% highlight apache %}
Options +MultiViews
RemoveType .gz
AddEncoding gzip .gz
# Note:  If using .br for brotli, uncomment the following line
#RemoveLanguage .br
AddEncoding br .brotli
<FilesMatch ".+\.tar\.gz$">
    RemoveEncoding .gz
    # Note:  Can use application/x-gzip for backwards-compatibility
    AddType application/gzip .gz
    # Alternatively:
    #ForceType application/gzip
</FilesMatch>
{% endhighlight %}

This configuration does not provide a `FilesMatch` for `.tar.brotli` since
this format is not currently widely used.  When serving tarballs that should
be saved as brotli-compressed, add a `FilesMatch` directive analogous to the
one for `tar.gz`.  Doing so is left as an exercise for the reader.

The choice of `.brotli` for the file extension is somewhat arbitrary.  As
noted in the comment above, using the extension `.br` would require the
addition of `RemoveLanguage .br` because `br` is the [ISO 639 language
code](https://www.loc.gov/standards/iso639-2/php/code_list.php) for Breton,
which is configured to the `.br` extension by default.  The [pull request
adding a proper command-line
program](https://github.com/google/brotli/pull/163)
uses `.bro`.  However, this naming was [rejected by
Mozilla](https://bugzilla.mozilla.org/show_bug.cgi?id=366559#c147).  So I used
`.brotli` as a neutral, if verbose, choice.

This configuration intentionally omits support for `deflate` encoding due to
[compatibility issues](http://www.gzip.org/zlib/zlib_faq.html#faq38) and no
use case that I am aware of, since all browsers which support deflate support
gzip.

If you encounter issues with this solution, please [let me know](/contact).
Otherwise, best of luck serving pre-compressed files with Apache!

[^negotiatetype]: Although type negotiation is not often used for stylesheets, it is currently used to [negotiate WebP](https://developers.google.com/speed/webp/faq?hl=en#server-side_content_negotiation_via_accept_headers), [XHTML]({% post_url 2012-07-20-serving-xhtml-with-apache-multiviews %}), and in some REST APIs.
