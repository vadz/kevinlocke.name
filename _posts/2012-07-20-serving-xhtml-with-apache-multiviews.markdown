---
layout: post
date: 2012-07-20 14:07:33-06:00
title: Serving XHTML with Apache MultiViews
description: "A recipe for how to serve XHTML in preference to HTML using the \
MultiViews option in Apache."
tags: [ apache ]
---
If you are reading this article on the web using a modern web browser, you
should be seeing an XHTML version of this page served as application/xhtml+xml.
The merits of the XHTML media type, and XHTML in general, have been widely
debated and I will not discus them here.  Instead, here is a brief discussion
of how this server is configured to serve HTML and XHTML content.

<!--more-->

# When to use MultiViews

The [MultiViews](http://httpd.apache.org/docs/current/mod/mod_negotiation.html#multiviews)
option, and the Apache
[content negotiation](http://httpd.apache.org/docs/current/content-negotiation.html)
process in general, are well suited for serving resources represented by
multiple static files with differing file extensions for each representation.
File extensions may indicate language and encoding in addition to media type,
but this article will focus primarily on the handling of different media types.

For resources which are not represented by multiple static files, other methods
may be better suited than MultiViews to performing content negotiation.  In
particular, dynamic content is typically handled by varying the Content-Type
header returned from the content generator while static files with a single
representation which may be served under different media types (e.g. XHTML
being served as text/html) more easily by using a
[RewriteCond](http://httpd.apache.org/docs/current/mod/mod_rewrite.html#rewritecond)
to match on `%{HTTP:Accept}` followed by a
[RewriteRule](http://httpd.apache.org/docs/current/mod/mod_rewrite.html#rewriterule)
with the `T` flag to set the returned type.  Neither of these techniques will
be discussed further in this article.

# Setup Static Content

For each page that can be served as both HTML and XHTML, simply use the same
filename for each type with a differing file extension (`.html` for text/html
and `.xhtml` for XHTML) and place them in the same directory.  If the two
versions are intended to be identical, it may be possible to generate the HTML
version from the XHTML version using XSLT (as is done for this website using
[this XSLT](https://github.com/kevinoid/kevinlocke.name/blob/master/_build/xhtmltohtml.xsl)).

# Configure MultiViews

With the content in place, simply enable the MultiViews option in the Apache
configuration (e.g. a `.htaccess` file at the site root).  Also, in order to
enable content negotiation for directory indexes, it is necessary to change
the search order so that it uses a resource without a file extension.  This can
be done as follows:

    # Enable MultiViews
    Options +MultiViews

    # Set the directory index to a resource named index
    DirectoryIndex	index

After this change is made, resources should be accessible by URLs with or
without file extensions.  When accessed by file extension, the file matching
the requested name is returned.  When accessed without a file extension, Apache
uses the values from the HTTP Accept headers to determine which of the
available files best satisfies the request and returns that file to the client.
The exact algorithm is described in the [content
negotiation](http://httpd.apache.org/docs/current/content-negotiation.html#algorithm)
documentation (this will be important later).

Great!  At this point, everything should be working as intended.  Mission
accomplished.

# Serving XHTML in Preference to HTML

But wait!  You may have noticed that HTML is being served to browsers which
support XHTML.  What's going on?

All major browsers currently request HTML (text/html) and XHTML
(application/xhtml+xml) with equal preference (a `q` value of `1`).  With that
in mind, the [content negotiation
algorithm](http://httpd.apache.org/docs/current/content-negotiation.html#algorithm)
algorithm will return whichever variant has the smallest content length
(assuming they have the same language and character set).  If the documents are
structurally identical, this will be HTML (because of the namespace declaration
and extra closing tags).  So what do we do?

## Using Server Quality Values

The recommended solution is to set the quality-of-source factor (used in step 1
of the content negotiation algorithm), which indicates the relative quality of
a given type from the server's perspective.  This can be done on a per-file
basis using a [type
map](http://httpd.apache.org/docs/current/mod/mod_negotiation.html#typemaps),
or by redefining the type for the file extension to include a `qs` parameter
in the Apache configuration as follows:

    AddType text/html;qs=0.999 .html
    AddType application/xhtml+xml;qs=1 .xhtml

The above configuration specifies that text/html has an ever-so-slightly lower
relative quality than application/xhtml+xml such that if the browser requests
them as equal quality XHTML will be preferentially chosen.

**Warning:** Although this is the recommended solution, it does have one
notable drawback.  The `qs` media type parameters are also sent to the client
in the `Content-Type` header in violation of RFC 2616.  This bug has been
reported as early as 2002 on
the [http-user](http://mail-archives.apache.org/mod_mbox/httpd-users/200202.mbox/%3CELEDJONBOPPAEGANDEEIKEEKCBAA.joshua@slive.ca%3E),
[http-dev](http://mail-archives.apache.org/mod_mbox/httpd-dev/200202.mbox/%3C0adf01c1b994$645169c0$94c0b0d0@v505%3E),
and [ietf-http-wg](http://lists.w3.org/Archives/Public/ietf-http-wg/2002AprJun/0032.html)
mailing lists, but it is still not fixed.  I am not aware of any browsers which
have problems when the `qs` parameter is present, which makes this
implementation at least tolerable.

## Using Rewrite Rules

Personally, if I am going to the trouble of serving XHTML as
application/xhtml+xml, the last thing I want to do is violate the HTTP spec to
do it, so I went looking for another solution.  The solution I found is ugly.
In fact, it may be ugly enough to make the `qs` parameter seem pleasant by
comparison.  With that said, here's the idea:

The content negotiation process occurs before the rewrite process [when the
rewrite rules are in directory
context](https://issues.apache.org/bugzilla/show_bug.cgi?id=29576).  This
allows RewriteRules to change the result of the negotiation when it results
in HTML rather than XHTML.  It is made more difficult if the restriction that
HTML pages requested explicitly (with a URL that ends in `.html`) should still
be served as HTML is maintained.  To get the desired behavior, the request
should be changed from HTML to XHTML when all of the following are true:

1. Content negotiation was conducted (i.e. the type was not requested
   explicitly by file extension in the URL).
2. Content negotiation chose HTML as the resulting type.
3. XHTML would have been chosen if the `qs` values were set (i.e. the browser
   supports XHTML and HTML with equal quality).
4. (Extra) The browser actually supports XHTML.  Some browsers request all
   types with equal quality and do not support XHTML.  In these cases HTML
   should be sent rather than XHTML.

To test the first criterion we use the fact that `%{IS_SUBREQ}` is `true` when
the URL has been changed during content negotiation.  This is fragile due to
the fact that if rewrite rules are added before this test it will trigger a
false positive, but a better method has not yet been found.  The second
criterion can be tested easily by file extension.  The third and fourth can
be tested by matching against the content of the HTTP Accept header sent by
the client.  Rather than compare the `q` values for HTML and XHTML, this
implementation takes the conservative approach and only returns XHTML if XHTML
was requested without a `q` value (which is an implicit value of `1`, the
maximum).  This approach can be realized with the following addition to the
Apache configuration (in `Directory` or `.htaccess` context):

    RewriteCond "%{IS_SUBREQ}" "=true"
    RewriteCond "%{REQUEST_FILENAME}" "\.html$"
    RewriteCond "%{HTTP:Accept}" "application/xhtml\+xml\s*(?:,|$)"
    RewriteRule "^(.*)\.html$" "/$1.xhtml" [QSA]

This approach is almost correct with one outstanding problem.  The
content-negotiation process sets the HTTP Content-Location header to inform
the browser which resource was actually served.  Unfortunately, the
RewriteRule does not change this Content-Location.  This can be done by
setting an environment variable to remember that a change was made, then
editing the Content-Location header in the same way.  This is further
complicated by some [undocumented behavior of environment variables in
RewriteRules](http://stackoverflow.com/questions/3050444/when-setting-environment-variables-in-apache-rewriterule-directives-what-causes).
With this behavior in mind, the above configuration can be extended as
follows:

    RewriteCond "%{IS_SUBREQ}" "=true"
    RewriteCond "%{REQUEST_FILENAME}" "\.html$"
    RewriteCond "%{HTTP:Accept}" "application/xhtml\+xml\s*(?:,|$)"
    RewriteRule "^(.*)\.html$" "/$1.xhtml" [QSA,ENV=NOW_XHTML]

    Header always edit "Content-Location" "\.html$" ".xhtml" env=REDIRECT_NOW_XHTML

# Final Thoughts

With the above configuration fragments it is possible to serve a variety of
content using MultiViews for content negotiation and serving XHTML in
preference to HTML.  The choice between using server quality values or using
rewrite rules is largely one of aesthetics.  Is sending a `qs` media type
parameter in the Content-Type header more or less desirable than a ugly and
possibly fragile Apache configuration?  In either case, more of the advanced
features of HTTP are exposed to clients which provides increased flexibility
and [cool URLs](http://www.w3.org/Provider/Style/URI.html).
