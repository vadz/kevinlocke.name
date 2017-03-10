---
layout: post
date: 2015-10-01 21:25:09-07:00
title: A Warning About ErrorDocument with MultiViews
description: "This post presents a way to avoid issues relating to content \
negotiation of error documents when the ErrorDocument and MultiViews \
configuration options are used together on an Apache server."
tags: [ apache ]
---

For those of you who are [Serving XHTML with Apache
MultiViews]({% post_url 2012-07-20-serving-xhtml-with-apache-multiviews %})
you may want to be careful about how `MultiViews` interacts with
[`ErrorDocument`](https://httpd.apache.org/docs/current/mod/core.html#errordocument).
Configuring error documents with content negotiation can lead to compound
errors in the case that the client does not accept any of the types available
for the error document.  This results in both unexpected behavior and a
suboptimal user experience.  This post describes how to avoid such errors
while still negotiating the returned content type.

<!--more-->

## The Issue

Lets assume that you have a website with the following `.htaccess` file:

``` apache
Options +MultiViews
ErrorDocument 404 /404
```

Along with `404.html`:

``` html
<!DOCTYPE html>
<html>
<head><title>404 HTML</title></head>
<body><h1>404 HTML</h1></body>
</html>
```

and `404.xhtml`:

``` html
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head><title>404 XHTML</title></head>
<body><h1>404 XHTML</h1></body>
</html>
```

Accessing a non-existent URL from your browser will result in a 404 response
with the content from the file of the preferred content type being returned,
as expected.  But what happens to a user agent which requests an unsupported
type, such as a bot collecting favicons?  Lets examine the result of running
`curl -i -H "Accept: image/vnd.microsoft.icon, image/x-icon"
http://localhost/favicon.ico`:

``` http
HTTP/1.1 404 Not Found
Date: Thu, 01 Oct 2015 03:57:33 GMT
Server: Apache/2.4.16 (Debian)
Alternates: {"404.html" 1 {type text/html} {length 99}}, {"404.xhtml" 1 {type application/xhtml+xml} {length 155}}
Vary: negotiate,accept
TCN: list
Content-Length: 403
Content-Type: text/html; charset=iso-8859-1

<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">
<html><head>
<title>404 Not Found</title>
</head><body>
<h1>Not Found</h1>
<p>The requested URL /favicon.ico was not found on this server.</p>
<p>Additionally, a 404 Not Found
error was encountered while trying to use an ErrorDocument to handle the request.</p>
<hr>
<address>Apache/2.4.16 (Debian) Server at localhost Port 80</address>
</body></html>
```

Which is accompanied by the following error in the Apache log:

	[negotiation:error] [pid XXXX] [client ::1:XXXXX] AH00690: no acceptable variant: /path/to/404

The problem is that the client has requested `/favicon.ico`, which doesn't
exist, and negotiating the `ErrorDocument` for `/404` has failed because the
client only accepts icon types and the `ErrorDocument` is only available in
HTML types.  The result is a server-generated error page announcing the
compound-error.  (Although it describes the second error as another 404,
rather than a 406, which is odd.)  Not ideal.

## A Solution

Ideally Apache would provide a configuration option to specify a fallback
content type when none is accepted, analogous to what
[`ForceLanguagePriority`](https://httpd.apache.org/docs/current/mod/mod_negotiation.html#forcelanguagepriority)
does for content language.  This could then be scoped to error pages using
`<Directory>` or `<Files>`.  Unfortunately, I could not find any way to
specify such a fallback.

The solution that I came up with is to set `ErrorDocument` conditionally by
matching against the `Accept` header.  This is basically a poor-man's content
negotiation, but works reasonably well when there are few types to choose
between and quality comparison isn't required.  To negotiate between the HTML
and XHTML versions of the 404 page, modify `.htaccess` as follows:

``` apache
Options +MultiViews
<If "%{HTTP_ACCEPT} =~ m#application/xhtml\+xml#i">
	ErrorDocument 404 /404.xhtml
</If>
<Else>
	ErrorDocument 404 /404.html
</Else>
```

This sends the XHTML version whenever `application/xhtml+xml` is present in
the `Accept` header (which, in practice, is only true for browsers which
support it and prefer it equally to `text/html`) and otherwise send the HTML
version.  The same curl command now returns:

``` http
HTTP/1.1 404 Not Found
Date: Thu, 01 Oct 2015 04:16:13 GMT
Server: Apache/2.4.16 (Debian)
Vary: Accept
Last-Modified: Thu, 01 Oct 2015 03:54:21 GMT
ETag: "63-5210300883cb3;521034eaa61f4"
Accept-Ranges: bytes
Content-Length: 99
Content-Type: text/html

<!DOCTYPE html>
<html>
<head><title>404 HTML</title></head>
<body><h1>404 HTML</h1></body>
</html>
```

Another drawback of this approach, which you can see from the response above,
is that the response omits the Transparent Content Negotiation (TCN) headers.
Although [RFC 2295](https://tools.ietf.org/html/rfc2295) does not specify 404
behavior explicitly, my reading is that since the representation of the 404 is
negotiated, the headers should indicate the chosen representation and expose
the negotiation process.  But, as with the above limitations, it has little
practical effect since the response includes the preferred type and I am not
aware of any user agents that would want to dynamically negotiate error
documents.

## Additional Considerations for Language Negotiation

Although the above solution works well when only one dimension, with few
alternatives, is being negotiated.  It quickly becomes unwieldy when multiple
dimensions (e.g. type and language) are under consideration.  My attempts to
use `MultiViews` to negotiate only the language have so far been unsuccessful.
The Apache documentation includes an example of a language-negotiated
`ErrorDocument` configuration using a type-map in
[httpd-multilang-errordoc.conf](https://svn.apache.org/viewvc/httpd/httpd/tags/2.4.16/docs/conf/extra/httpd-multilang-errordoc.conf.in?view=markup).
Although I expected that combining this technique with a type-map that only
negotiates on language (such as in [Apache: The Definitive Guide Section
6.4](http://docstore.mik.ua/orelly/linux/apache/ch06_04.htm)) would achieve
the desired effect, it does not appear to.  I am still seeing the same
`AH00690` error as before.

Since I am currently only negotiating the content type, this is not an issue
for me.  However, if anyone is able to solve this problem, I would be very
curious about how you did it, and more than happy to post the solution here
for others to use.  Until then, best of luck with the type-only solution!
