---
layout: post
date: 2012-07-20 14:07:33-06:00
title: Serving XHTML with Apache MultiViews
description: "A recipe for how to serve XHTML in preference to HTML using the \
MultiViews option in Apache."
tags: [ apache ]
updated: 2016-07-20 18:01:57-07:00
---
If you are reading this article on the web using a modern web browser, you
should be seeing an XHTML version of this page served as application/xhtml+xml.
The merits of the XHTML media type, and XHTML in general, have been widely
debated and I will not discus them here.  Instead, here is a brief discussion
of how this server is configured to serve HTML and XHTML content.

The impatient may wish to [skip to the recommended
configuration](#recommendations).

<!--more-->

## When to use MultiViews

The [MultiViews](https://httpd.apache.org/docs/current/mod/mod_negotiation.html#multiviews)
option, and the Apache
[content negotiation](https://httpd.apache.org/docs/current/content-negotiation.html)
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
[RewriteCond](https://httpd.apache.org/docs/current/mod/mod_rewrite.html#rewritecond)
to match on `%{HTTP:Accept}` followed by a
[RewriteRule](https://httpd.apache.org/docs/current/mod/mod_rewrite.html#rewriterule)
with the `T` flag to set the returned type.  Neither of these techniques will
be discussed further in this article.

## Setup Static Content

For each page that can be served as both HTML and XHTML, simply use the same
filename for each type with a differing file extension (`.html` for text/html
and `.xhtml` for XHTML) and place them in the same directory.  If the two
versions are intended to be identical, it may be possible to generate the HTML
version from the XHTML version using XSLT (as is done for this website using
[this XSLT](https://github.com/kevinoid/kevinlocke.name/blob/master/_build/xhtmltohtml.xsl)).

## Configure MultiViews

With the content in place, simply enable the MultiViews option in the Apache
configuration (e.g. a `.htaccess` file at the site root).  Also, in order to
enable content negotiation for directory indexes, it is necessary to change
the search order so that it uses a resource without a file extension.  This can
be done as follows:

{% highlight apache %}
# Enable MultiViews
Options +MultiViews

# Set the directory index to a resource named index
DirectoryIndex	index
{% endhighlight %}

After this change is made, resources should be accessible by URLs with or
without file extensions.  When accessed by file extension, the file matching
the requested name is returned.  When accessed without a file extension, Apache
uses the values from the HTTP Accept headers to determine which of the
available files best satisfies the request and returns that file to the client.
The exact algorithm is described in the [content
negotiation](https://httpd.apache.org/docs/current/content-negotiation.html#algorithm)
documentation (this will be important later).

Great!  At this point, everything should be working as intended.  Mission
accomplished.

## Serving XHTML in Preference to HTML

But wait!  You may have noticed that HTML is being served to browsers which
support XHTML.  What's going on?

All major browsers currently request HTML (text/html) and XHTML
(application/xhtml+xml) with equal preference (a `q` value of `1`).  With that
in mind, the [content negotiation
algorithm](https://httpd.apache.org/docs/current/content-negotiation.html#algorithm)
will return whichever variant has the smallest content length
(assuming they have the same language and character set).  If the documents are
structurally identical, this will be HTML (because of the namespace declaration
and extra closing tags).  So what do we do?

### Using Server Quality Values

The recommended solution is to set the quality-of-source factor (used in step 1
of the content negotiation algorithm), which indicates the relative quality of
a given type from the server's perspective.  This can be done on a per-file
basis using a [type
map](https://httpd.apache.org/docs/current/mod/mod_negotiation.html#typemaps),
or by redefining the type for the file extension to include a `qs` parameter
in the Apache configuration as follows:

{% highlight apache %}
AddType text/html;qs=0.99 .html
AddType application/xhtml+xml .xhtml
{% endhighlight %}

The above configuration specifies that text/html has a slightly lower (99%)
relative quality than application/xhtml+xml (with the default
quality-of-source of 1, i.e. 100%) such that if the browser requests them as
equal quality XHTML will be preferentially chosen.

This has two problems:  The first, and most significant, is that it will serve
XHTML to browsers which do not support XHTML and do not express a preference
between HTML and all other content.  This includes [Internet Explorer prior to
IE9](https://blogs.msdn.microsoft.com/ie/2010/11/01/xhtml-in-ie9/) which
expresses no preference by sending `Accept: */*`.  This can be avoided by
setting the quality-of-source differently when application/xhtml+xml appears
in the `Accept` header:

{% highlight apache %}
<If "%{HTTP_ACCEPT} =~ m#application/xhtml\+xml#i">
    # application/xhtml+xml is explicitly mentioned.  Prefer XHTML slightly.
    AddType text/html;qs=0.99 .html
    AddType application/xhtml+xml .xhtml
</If>
<Else>
    # application/xhtml+xml is not explicitly mentioned.  Prefer HTML slightly.
    AddType text/html .html
    AddType application/xhtml+xml;qs=0.99 .xhtml
</Else>
{% endhighlight %}

The other problem is that the `qs` media type parameter is also sent to the
client in the `Content-Type` header.  This is non-standard behavior, since the
`qs` is not defined for the HTML or XHTML media type.  This bug has been
reported as early as 2002 on the
[http-user](https://mail-archives.apache.org/mod_mbox/httpd-users/200202.mbox/%3CELEDJONBOPPAEGANDEEIKEEKCBAA.joshua@slive.ca%3E),
[http-dev](https://mail-archives.apache.org/mod_mbox/httpd-dev/200202.mbox/%3C0adf01c1b994$645169c0$94c0b0d0@v505%3E),
and
[ietf-http-wg](https://lists.w3.org/Archives/Public/ietf-http-wg/2002AprJun/0032.html)
mailing lists.   I opened [Bug
53595](https://bz.apache.org/bugzilla/show_bug.cgi?id=53595) to track the
issue, but I do not expect a fix any time soon (and I am not personally
working on one).

Although the standards require clients to ignore unrecognized media type
parameters, and I am not aware of any issues in popular browsers caused by the
`qs` parameter, sending it is asking for trouble.  Therefore, to avoid sending
the `qs` parameter, consider removing it using
[`mod_headers`](https://httpd.apache.org/docs/current/mod/mod_headers.html#header):

{% highlight apache %}
Header always edit "Content-Type" ";\s*qs=[0-9]*(?:\.[0-9]+)?\s*" ""
{% endhighlight %}

### Using Rewrite Rules

Before settling on the above solutions, I discovered an alternative way to
conditionally prefer XHTML during negotiation using
[`mod_rewrite`](https://httpd.apache.org/docs/current/mod/mod_rewrite.html).
This method is more complicated and error-prone than the above solutions, but
it can also be used to influence MultiViews behavior in much more powerful
ways.

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
3. An XHTML version of the file exists.
4. XHTML would have been chosen if the `qs` values were set (i.e. the browser
   supports XHTML and HTML with equal quality).
5. The browser actually supports XHTML.

To test the first criterion we use the fact that `%{IS_SUBREQ}` is `true` when
the URL has been changed during content negotiation.  This is fragile due to
the fact that if rewrite rules are added before this test it will trigger a
false positive, but I am not aware of a better alternative.  The second
criterion can be tested easily by file extension.  The third can be tested
using an `-f` RewriteCond. The fourth and fifth can be tested by matching
against the content of the HTTP Accept header sent by the client.  Rather than
compare the `q` values for HTML and XHTML, this implementation takes the
conservative approach and only returns XHTML if XHTML was requested without a
`q` value (which is an implicit value of `1`, the maximum).  This approach can
be realized with the following addition to the Apache configuration (in
`Directory` or `.htaccess` context):

{% highlight apache %}
RewriteCond "%{IS_SUBREQ}" "=true"
RewriteCond "%{REQUEST_FILENAME}" "^(.*)\.html$"
RewriteCond "%1.xhtml" "-f"
RewriteCond "%{HTTP:Accept}" "application/xhtml\+xml\s*(?:,|$)"
RewriteRule "^(.*)\.html$" "/$1.xhtml"
{% endhighlight %}

This approach is almost correct with two remaining problems.  First, the
content-negotiation process sets the HTTP Content-Location header to inform
the browser which resource was actually served.  Unfortunately, the
RewriteRule does not change this Content-Location.  This can be done by
setting an environment variable to remember that a change was made, then
editing the Content-Location header in the same way.  This is further
complicated by some [undocumented behavior of environment variables in
RewriteRules](https://stackoverflow.com/questions/3050444/when-setting-environment-variables-in-apache-rewriterule-directives-what-causes).
With this behavior in mind, the above configuration can be extended as
follows:

{% highlight apache %}
RewriteCond "%{IS_SUBREQ}" "=true"
RewriteCond "%{REQUEST_FILENAME}" "^(.*)\.html$"
RewriteCond "%1.xhtml" "-f"
RewriteCond "%{HTTP:Accept}" "application/xhtml\+xml\s*(?:,|$)"
RewriteRule "^(.*)\.html$" "/$1.xhtml" [ENV=NOW_XHTML]

Header always edit "Content-Location" "\.html$" ".xhtml" env=REDIRECT_NOW_XHTML
{% endhighlight %}

The second issue is that when [Serving Pre-Compressed Files with Apache
MultiViews]({% post_url _posts/2016-01-20-serving-pre-compressed-files-with-apache-multiviews %})
the filename may end in `.html.gz` or another encoding, rather than `.html`.
To address this, the above rules can be extended to match and preserve
additional extensions after `.html`:

{% highlight apache %}
RewriteCond "%{IS_SUBREQ}" "=true"
RewriteCond "%{REQUEST_FILENAME}" "^(.*)\.html(\..+)?$"
RewriteCond "%1.xhtml%2" "-f"
RewriteCond "%{HTTP:Accept}" "application/xhtml\+xml\s*(?:,|$)"
RewriteRule "^(.*)\.html(\..+)?$" "/$1.xhtml$2" [ENV=NOW_XHTML]

Header always edit "Content-Location" "\.html(\..+)?$" ".xhtml$1" env=REDIRECT_NOW_XHTML
{% endhighlight %}

## Recommendations

Due to the complexity and fragility of the RewriteRule method, my current
recommendation for serving XHTML with MultiViews, and the one used on this
website, is:

{% highlight apache %}
# Enable MultiViews
Options +MultiViews

# Set the directory index to a resource named index
DirectoryIndex index

<If "%{HTTP_ACCEPT} =~ m#application/xhtml\+xml#i">
    # application/xhtml+xml is explicitly mentioned.  Prefer XHTML slightly.
    AddType text/html;qs=0.99 .html
    AddType application/xhtml+xml .xhtml
</If>
<Else>
    # application/xhtml+xml is not explicitly mentioned.  Prefer HTML slightly.
    AddType text/html .html
    AddType application/xhtml+xml;qs=0.99 .xhtml
</Else>

# Remove qs parameter incorrectly sent by MultiViews due to
# https://bz.apache.org/bugzilla/show_bug.cgi?id=53595
Header always edit "Content-Type" ";\s*qs=[0-9]*(?:\.[0-9]+)?\s*" ""
{% endhighlight %}

This will serve XHTML in preference to HTML when supported and HTML otherwise,
for URLs without a type extension, allowing increased flexibility and [cool
URLs](http://www.w3.org/Provider/Style/URI.html).

## Article Changes

### 2016-07-20

* Add request-conditional configuration method for setting `qs` values.
* Add `mod_headers` method for removing `qs` values.
* Link to Bugzilla bug for sending `qs` parameter to clients.
* Add file existence check to RewriteRule method.
* Add encoding support to RewriteRule method.
* Rewrite closing section and update recommendations.
* Add syntax highlighting to Apache config snippets.
