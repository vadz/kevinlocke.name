---
layout: post
date: 2015-10-02 19:05:09-07:00
title: Setting Content-Security-Policy with mod_headers
description: "This post describes a method for setting and/or manipulating \
the Content-Security-Policy (CSP) HTTP header using Apache mod_headers."
tags: [ apache ]
---

Web developers and admins looking to tighten the security of their websites
should consider defining a [Content Security
Policy](http://www.w3.org/TR/CSP2/) for their site.  For sites hosted using
[Apache](https://httpd.apache.org/), a simple way to achieve this is by
sending the `Content-Security-Policy` header using
[mod\_headers](https://httpd.apache.org/docs/current/mod/mod_headers.html).
Unfortunately, making this simple solution robust is more difficult than it
first appears.  This post describes a method for setting or modifying the
`Content-Security-Policy` header in a way that won't clobber previous values
set by earlier configuration options or returned by an application server.

<!--more-->

## The Problem

A first-attempt at setting the `Content-Security-Policy` header using
`mod_header` may look something like this:

``` apache
Header always set Content-Security-Policy "referrer origin"
```

For simple use cases, this is straight-forward and sufficient to get the job
done.  But what happens if the site includes an application which sets its own
`Content-Security-Policy` header (either via `.htaccess` or from a dynamic
page or application server)?  This configuration will clobber it.  That's not
ideal.

An easy solution would be to use `setifempty` and require that any overriding
configuration include the entire policy.  This can make configuration changes
during deployment difficult and it pushes all policy decisions up into the
application, which may be undesirable.  It's possible to do better.

A better solution would be to use `append` or `merge`.  Unfortunately, a quick
look at the documentation reveals that this is not quite right.  The major
issue is that it treats the header as a comma-separated list, while
`Content-Security-Policy` is semicolon-separated, and there is [no way to
change that behavior](https://bz.apache.org/bugzilla/show_bug.cgi?id=58475).

## A Solution

After pondering this problem for a bit, I realized it would not be difficult
to implement the `merge` behavior using a combination of `setifempty` and
`edit`.  Here's how:

``` apache
Header always setifempty Content-Security-Policy ""
Header always edit Content-Security-Policy "^(?!(?:.*;)?\s*referrer\s)" "referrer origin;"
```

It first ensures that the header exists using `setifempty` (otherwise `edit`
will not apply), then prepends the `referrer` policy only if the header does
not already contain one (by matching with a negative-lookahead).  Note that it
relies on the fact that extra semicolons are permitted in both
[CSP1](http://www.w3.org/TR/CSP/#policy-syntax) and
[CSP2](http://www.w3.org/TR/CSP2/#policy-syntax), since that will occur when
the header is empty.  Alternatively, it's easy to add another `edit` command
to remove a tailing semicolon if it is not desirable for some reason.

But wait, there's more!  Using `edit` provides more power than just
prepending.  With a quick adjustment, the regular expression can be used to
unconditionally replace policy components.  Here's how:

``` apache
Header always setifempty Content-Security-Policy ""
Header always edit Content-Security-Policy "(^(?!(?:.*;)?\s*referrer\s)|(?:.*;)?\s*referrer\s+[^;]+;?)" "referrer origin;"
```

Now the regular expression matches against either the beginning of the string,
if it does not already contain the referrer policy, or against the existing
referrer policy if it does.  This way the configured policy directive is
always used, regardless of any other policies present.  This method can also
be applied multiple times for multiple policy directives, using either the
first or second variant to either preserve or overwrite the policy directives
respectively:

``` apache
Header always setifempty Content-Security-Policy ""
# Override the referrer policy directive, if present
Header always edit Content-Security-Policy "(^(?!(?:.*;)?\s*referrer\s)|(?:.*;)?\s*referrer\s+[^;]+;?)" "referrer origin;"
# Preserve the script-src policy directive, if present
Header always edit Content-Security-Policy "^(?!(?:.*;)?\s*script-src\s)" "script-src 'self';"
```

This technique allows defining directives and overriding them wherever it is
most convenient, in either the application or in the Apache configuration
files, while minimizing the risk of unintentionally overwriting the policy,
either in whole or in part.  I hope you find it useful!
