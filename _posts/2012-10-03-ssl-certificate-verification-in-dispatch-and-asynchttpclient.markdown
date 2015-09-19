---
layout: post
date: 2012-10-03 14:34:21-06:00
title: SSL Certificate Verification in Dispatch and AsyncHttpClient
description: "A description of the insecure-by-default handling of SSL
connections in Ning/Sonatype AsyncHttpClient library and how to implement
certificate verification."
tags: [ java, scala, async-http-client ]
---
I've recently started using the [Dispatch](http://dispatch.databinder.net/)
library for HTTP/HTTPS, which is quite a nice library, as long as you don't
need documentation.  Dispatch uses the Ning/Sonatype
[AsyncHttpClient](https://github.com/sonatype/async-http-client) library,
which is also quite nice, and although AsyncHttpClient is a library which I
could recommend, it does have an insecure-by-default implementation of SSL.
This post is a quick discussion of the AsyncHttpClient defaults and how to
implement certificate verification to increase the security provided by SSL.

<!--more-->

## Background

This post will assume some familiarity with SSL and the need to verify
certificates.  If readers are unfamiliar with either of these topics, there
are many online resources available and you are encouraged to explore the
topic.

For background on how SSL is implemented on the Java platform, see the [Java
Secure Socket Extension (JSSE) Reference
Guide](https://docs.oracle.com/javase/1.5.0/docs/guide/security/jsse/JSSERefGuide.html).
Readers who are not concerned with the implementation details may feel free to
skip to the end of this article for "recipes" for SSL certificate
verification.

## Default SSLContext

The
[`SSLContext`](https://docs.oracle.com/javase/6/docs/api/javax/net/ssl/SSLContext.html)
class is central to the SSL implementation in Java in general and in
AsyncHttpClient in particular.  The default `SSLContext` for AsyncHttpClient
is dependent on whether the `javax.net.ssl.keyStore` system property is set.
If this property is set, AsyncHttpClient will create a TLS `SSLContext` with a
`KeyManager` based on the specified key store (and configured based on the
values of many other `javax.net.ssl` properties as described in the JSEE
Reference Guide linked above).  Otherwise, it will create a TLS `SSLContext`
with no `KeyManager` and a `TrustManager` which accepts everything.  In
effect, if `javax.net.ssl.keyStore` is unspecified, any ol' SSL certificate
will do.

If the trusted Certificate Authorities for the application should be the same
as the trusted CAs for the operating system, it is possible to avoid the
hassles of dealing with Java key stores by using the (JRE) default
`SSLContext`.  Simply instantiate a new `SSLContext` and initialize it with
all `null` values.  This offloads the burden to the JRE provider and OS vendor
and works like a charm on my test system.

Unfortunately, there does not appear to be a way to set the default
`SSLContext` used by AsyncHttpClient.  Instead, applications must set their
preferred `SSLContext` for each connection.

## Default HostnameVerifier

Even if the `SSLContext` can verify that a certificate is signed by a trusted
Certificate Authority, there is still room for problems.  What happens if the
connection hostname doesn't match the certificate hostname?  Java provides the
[`HostnameVerifier`](https://docs.oracle.com/javase/6/docs/api/javax/net/ssl/HostnameVerifier.html)
interface to give client code the option of providing a policy for handling
this situations.  AsyncHttpClient adopts this interface for this purpose as
well.  However, unlike the JDK, the default policy provided by AsyncHttpClient
is to allow all connections regardless of hostname.

Unlike `SSLContext`, using the Java default
([`HttpsURLConnection.getDefaultHostnameVerifier`](https://docs.oracle.com/javase/6/docs/api/javax/net/ssl/HttpsURLConnection.html#getDefaultHostnameVerifier%28%29))
is not a viable option because the default `HostnameVerifier` expects to only
be called in the case that there is a mismatch (and therefore always returns
`false`) while some of the AsyncHttpClient providers (e.g. Netty, the default)
[call it on all
connections](https://github.com/sonatype/async-http-client/issues/146).  To
make matters worse, the check is not trivial (consider <abbr title="Subject
Alt. Name">SAN</abbr> and wildcard matching) and is implemented in
[`sun.security.util.HostnameChecker`](http://hg.openjdk.java.net/jdk7/modules/jdk/file/tip/src/share/classes/sun/security/util/HostnameChecker.java)
(a Sun internal proprietary API).  This leaves the developer in the position
of either depending on an internal API or finding/copying/creating another
implementation of this functionality.  For the examples in this article, I
have opted for the first option.

Unfortunately, as with `SSLContext`, there does not appear to be a way to set
the default `HostnameVerifier` used by AsyncHttpClient.  Instead, applications
must set their preferred `HostnameVerifier` for each connection.

## Implementation

First, a quick note:  The purpose of these example implementations is to
demonstrate how to verify certificates.  The programs should include better
exception handling, logging, and a more modular functional decomposition, but
this would lengthen the examples and obscure their core purpose.  Please feel
free to use your better judgement when copying and expanding on these
examples.

### Scala

First, an example program which downloads a given URL using Dispatch in Scala:

{% highlight_gist scala kevinoid 3829187 MyDownloader.scala %}

### Java

Then, the same program using AsyncHttpClient directly from Java:

{% highlight_gist java kevinoid 3829665 MyDownloader.java %}

## Caveats

Although the default initialization of `SSLContext` works quite well on my
test machine, I have not found a clear specification of its behavior and it
may not be guaranteed to use/trust the operating system certificate store on
all platforms.

In my testing I found that if the invalid certificate is returned by a process
on the local machine (e.g. using [mitmproxy](https://mitmproxy.org/))
AsyncHttpClient will throw a `java.io.IOException: Remotely Closed` rather
than `java.net.ConnectException: General SSLEngine problem`.  This is probably
a [bug](https://github.com/sonatype/async-http-client/issues/145).  In either
case, users should be wary of this behavior when troubleshooting a failing
SSL/TLS connection.
