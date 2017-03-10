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

``` scala
/* An example program using Dispatch with SSL certificate verification
 *
 * To the extent possible under law, Kevin Locke has waived all copyright and
 * related or neighboring rights to this work.
 */
import com.ning.http.client.{AsyncHttpClient, AsyncHttpClientConfig}
import dispatch._
import java.security.cert.{CertificateException, X509Certificate}
import javax.net.ssl.{HostnameVerifier, SSLPeerUnverifiedException, SSLSession}
import javax.security.auth.kerberos.KerberosPrincipal
import sun.security.util.HostnameChecker

/** HostnameVerifier implementation which implements the same policy as the
 * Java built-in pre-HostnameVerifier policy.
 */
object MyHostnameVerifier extends HostnameVerifier {
  /** Checks if a given hostname matches the certificate or principal of a
   * given session.
   */
  private def hostnameMatches(hostname: String, session: SSLSession): Boolean = {
    val checker = HostnameChecker.getInstance(HostnameChecker.TYPE_TLS);

    try {
      session.getPeerCertificates match {
        case Array(cert: X509Certificate, _*) =>
          try {
            checker.`match`(hostname, cert)
            // Certificate matches hostname
            true
          } catch {
            case _: CertificateException =>
              // Certificate does not match hostname
              false
          }

        case _ =>
          // Peer does not have any certificates or they aren't X.509
          false
      }
    } catch {
      case _: SSLPeerUnverifiedException =>
        // Not using certificates for verification, try verifying the principal
        try {
          session.getPeerPrincipal match {
            case principal: KerberosPrincipal =>
              HostnameChecker.`match`(hostname, principal)

            case _ =>
              // Can't verify principal, not Kerberos
              false
          }
        } catch {
          case _: SSLPeerUnverifiedException =>
            // Can't verify principal, no principal
            false
        }
    }
  }

  def verify(hostname: String, session: SSLSession): Boolean = {
    if (hostnameMatches(hostname, session)) {
      true
    } else {
      // TODO: Add application-specific checks for hostname/certificate match
      false
    }
  }
}

/** Extension of Http which uses an AsyncHttpClient configured with our
 * customized SSLContext and HostnameVerifier
 */
object MyHttp extends Http {
  override lazy val client = new AsyncHttpClient(
    new AsyncHttpClientConfig.Builder()
      .setSSLContext({
        val ctx = javax.net.ssl.SSLContext.getInstance("TLS")
        ctx.init(null, null, null)
        ctx
      })
      .setHostnameVerifier(MyHostnameVerifier)
      .build
  )
}

/** Implements the "MyDownloader" application */
object MyDownloader {
  def main(args: Array[String]) {
    args match {
      case Array(url) =>
        val request = dispatch.url(url)

        Console.err.println("Downloading " + url)
        MyHttp(request OK as.String).either() match {
          case Left(e) =>
            // Something failed
            Console.err.println("Failure downloading " + url + ": " + e)

          case Right(content) =>
            // Success
            Console.err.println("Successfully downloaded " + url)
            Console.out.println(content)
        }

        MyHttp.shutdown()

      case _ =>
        Console.err.println("Usage: myhttp <URL>")
        System.exit(1)
    }
  }
}
```

The above code is [also available as part of a GitHub
Gist](https://gist.github.com/kevinoid/3829187#file-mydownloader-scala).

### Java

Then, the same program using AsyncHttpClient directly from Java:

``` java
/* An example program using AsyncHttpClient with SSL certificate verification
 *
 * To the extent possible under law, Kevin Locke has waived all copyright and
 * related or neighboring rights to this work.
 * A legal description of this waiver is available in LICENSE.txt.
 */
import com.ning.http.client.AsyncHttpClient;
import com.ning.http.client.AsyncHttpClientConfig;
import com.ning.http.client.Response;

import java.io.IOException;
import java.security.KeyManagementException;
import java.security.NoSuchAlgorithmException;
import java.security.Principal;
import java.security.cert.Certificate;
import java.security.cert.CertificateException;
import java.security.cert.X509Certificate;
import java.util.concurrent.ExecutionException;

import javax.net.ssl.HostnameVerifier;
import javax.net.ssl.SSLContext;
import javax.net.ssl.SSLPeerUnverifiedException;
import javax.net.ssl.SSLSession;
import javax.security.auth.kerberos.KerberosPrincipal;
import sun.security.util.HostnameChecker;

/** Implements the "MyDownloader" application */
public class MyDownloader {

    /** HostnameVerifier implementation which implements the same policy as the
     * Java built-in pre-HostnameVerifier policy.
     */
    private static class MyHostnameVerifier implements HostnameVerifier {
        /** Checks if a given hostname matches the certificate or principal of
         * a given session.
         */
        private boolean hostnameMatches(String hostname, SSLSession session) {
            HostnameChecker checker =
                HostnameChecker.getInstance(HostnameChecker.TYPE_TLS);

            boolean validCertificate = false, validPrincipal = false;
            try {
                Certificate[] peerCertificates = session.getPeerCertificates();

                if (peerCertificates.length > 0 &&
                        peerCertificates[0] instanceof X509Certificate) {
                    X509Certificate peerCertificate =
                            (X509Certificate)peerCertificates[0];

                    try {
                        checker.match(hostname, peerCertificate);
                        // Certificate matches hostname
                        validCertificate = true;
                    } catch (CertificateException ex) {
                        // Certificate does not match hostname
                    }
                } else {
                    // Peer does not have any certificates or they aren't X.509
                }
            } catch (SSLPeerUnverifiedException ex) {
                // Not using certificates for peers, try verifying the principal
                try {
                    Principal peerPrincipal = session.getPeerPrincipal();
                    if (peerPrincipal instanceof KerberosPrincipal) {
                        validPrincipal = HostnameChecker.match(hostname,
                                (KerberosPrincipal)peerPrincipal);
                    } else {
                        // Can't verify principal, not Kerberos
                    }
                } catch (SSLPeerUnverifiedException ex2) {
                    // Can't verify principal, no principal
                }
            }

            return validCertificate || validPrincipal;
        }

        public boolean verify(String hostname, SSLSession session) {
            if (hostnameMatches(hostname, session)) {
                return true;
            } else {
                // TODO: Add application-specific checks for
                // hostname/certificate match
                return false;
            }
        }
    }

    public static void main(String[] args) {
        if (args.length != 1) {
            System.err.println("Usage: myhttp <URL>");
        } else {
            String url = args[0];

            SSLContext context = null;
            try {
                context = SSLContext.getInstance("TLS");
            } catch (NoSuchAlgorithmException e) {
                e.printStackTrace();
                return;
            }

            try {
                context.init(null, null, null);
            } catch (KeyManagementException e) {
                e.printStackTrace();
                return;
            }

            AsyncHttpClient client = new AsyncHttpClient(
                    new AsyncHttpClientConfig.Builder()
                        .setSSLContext(context)
                        .setHostnameVerifier(new MyHostnameVerifier())
                        .build()
                );

            Response response = null;
            try {
                response = client.prepareGet(url).execute().get();
            } catch (InterruptedException e) {
                e.printStackTrace();
                return;
            } catch (ExecutionException e) {
                e.printStackTrace();
                return;
            } catch (IOException e) {
                e.printStackTrace();
                return;
            }

            if (response.getStatusCode() / 100 == 2) {
                try {
                    String responseBody = response.getResponseBody();
                    System.err.println("Successfully downloaded " + url);
                    System.out.println(responseBody);
                } catch (IOException e) {
                    e.printStackTrace();
                    return;
                }
            } else {
                System.err.println("Failure downloading " + url +
                        ": HTTP Status " + response.getStatusCode());
            }
        }
    }
}
```

The above code is [also available as part of a GitHub
Gist](https://gist.github.com/kevinoid/3829665#file-mydownloader-java).

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
