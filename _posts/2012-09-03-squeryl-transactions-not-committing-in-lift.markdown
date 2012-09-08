---
layout: post
date: 2012-09-03 12:53:40-06:00
updated:  2012-09-07 20:45:23-06:00
title: Squeryl Transactions Not Committing in Lift
description: "A quick tip for making sure Squeryl transactions commit when \
Lift throws flow control exceptions."
tags: [ lift ]
---
The latest issue that I've encountered while working with
[Squeryl](http://squeryl.org) in a [Lift](http://liftweb.net/)-based web
application, is that not all transactions are being committed to the database.
This post is a quick discussion of the symptoms that I was seeing and a note
on how to avoid the issue.

<!--more-->

### The Setup

[As noted
before](/bits/2012/08/31/bonecp-0.8.0-alpha1-unusable-with-lift-squeryl-record),
the setup that I am using is quite generic and based on the example
configurations on the [Squeryl-Record wiki
page](https://www.assembla.com/wiki/show/liftweb/Squeryl).  The only portion
which is relevant to this article is how transactions are handled.  For now,
I'm using a simple transaction-per-request strategy, implemented as follows:

    S.addAround(new LoanWrapper {
      override def apply[T](f: => T): T = inTransaction { f }
    })

Although this code fragment appears in most of the examples on the web, it has
at least one significant flaw.

### The Symptoms

The flaw with the above code fragment is that it does not handle Lift's flow
control exceptions properly.  In retrospect, the behavior is very clear and
easy to spot (but was not quite so obvious at the time):  The transactions
failed to commit to the database whenever the request which caused the database
updates resulted in an HTTP redirect response.  The mechanics for why this
happens are equally clear in retrospect.

Squeryl's `inTransaction` method assumes that the transaction should be aborted
if the code that it is executing throws an exception and it will rollback the
transaction when this happens.  However, Lift implements partial or
short-circuited responses (e.g. redirects) by throwing a
`LiftFlowOfControlException`.  Therefore, whenever a response is redirected
after database changes are made, those changes will be rolled back.

### The Solution

The solution that I am using is reasonably simple.  Replace the above code
fragment with:

    S.addAround(new LoanWrapper {
      override def apply[T](f: => T): T = {
        val resultOrExcept = inTransaction {
          try {
            Right(f)
          } catch {
            case e: LiftFlowOfControlException => Left(e)
          }
        }

        resultOrExcept match {
          case Right(result) => result
          case Left(except) => throw except
        }
      }
    })

This way whenever a `LiftFlowOfControlException` is thrown it will be returned
through inTransaction and the transaction will commit while any other exception
propagates out as before causing transaction rollback.  Then, if an exception
was returned, it is re-thrown from the `LoanWrapper` to continue propagating
up to the Lift internals.

With this in place, transactions should commit and Lift's control flow
exceptions should continue to work as expected.  At least, I hope so...

### Article Changes

#### 2012-09-07

* Fixed the code using `Either` to follow the standard convention that `Left`
  is failure and `Right` is success (as documented in the [scala.Either
  scaladoc](http://www.scala-lang.org/api/current/index.html#scala.Either)).
