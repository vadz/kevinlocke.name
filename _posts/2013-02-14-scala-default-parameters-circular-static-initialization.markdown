---
layout: post
date: 2013-02-14 22:58:41-07:00
title: "Scala Default Constructor Parameters Causing Circular Static \
Initialization"
description: "An esoteric bug in Scala which can cause unexpected \
initialization behavior (NullPointerException and ExceptionInInitializerError \
in my case)."
tags: [ scala java ]
---
I just finished tracking down a rather esoteric bug in a Scala application
that I am writing.  Understanding this bug requires some understanding of how
Scala is translated to Java and how Java handles static initialization,
neither of which will be explained (much) in this post.  So, if you are
interested in how default parameters on a constructor can cause circular
static initialization resulting in a NullPointerError, read on.

<!--more-->

## An Example

In order to see the problem, consider the following program:

{% highlight scala %}
case class Hobbit(
  name: String,
  hasRing: Boolean = false
)

object Hobbit {
  object Frodo extends Hobbit("Frodo", true)
  object Merry extends Hobbit("Merry")
  object Pippin extends Hobbit("Pippin")
  object Sam extends Hobbit("Sam")

  val gardenerName = Sam.name
}

object RunMe {
  def main(args: Array[String]) {
    println(Hobbit.Sam.name)
  }
}
{% endhighlight %}

What will happen if RunMe is launched?

## What Happens

If you thought a `NullPointerException` would occur, you are correct.  But
why?

As a reminder, Scala objects are represented as Java classes with `$` appended
to their name and their members are also accessible through static methods on
the Java class with a matching name which delegate to the `$` class through a
static variable named `MODULE$` on the `$` class.

The critical detail behind the error is that default arguments are also stored
as methods on the object for the containing class.  So the default value of
`hasRing` is stored as a method (named `init$default$2`, if you were curious)
in the `Hobbit$` class (and a static method of the same name on the `Hobbit`
class).  When we combine this with the [Initialization Procedure defined in
the Java Language
Spec.](http://docs.oracle.com/javase/specs/jls/se7/html/jls-12.html#jls-12.4.1)
what happens is as follows (simplified):

1. `Hobbit.Sam$.MODULE$.name` is accessed which starts static initialization
   of `Hobbit.Sam$`.
2. `Hobbit$.MODULE$.init$default$2` is accessed to get the default argument
   for the `Hobbit` constructor, which starts static initialization of
   `Hobbit$`.
3. `Hobbit.Sam$.MODULE$.name` is accessed in order to assign the name from its
   value to `gardenerName`.  Initialization is already in progress (started in
   step 1) so the current value of `MODULE$` is returned and `name` is called
   on it.  Unfortunately, the current value is still `null` because the
   `Hobbit` constructor has not yet been called for its initialization, which
   results in the `NullPointerException`.

Got it?  If not, another example, and the bug to follow for updates on this
behavior, is [SI-5366](https://issues.scala-lang.org/browse/SI-5366).

It's also worth pointing out that this bug can be complicated by several
factors.  If any member of `Hobbit` other than `Sam` is accessed first, the
error will not occur.  (Can you see why?)  When multiple threads are competing
for the first access to this class using different members, it can make the
behavior non-deterministic.  It can also be complicated by something eating
the `ExceptionInInitializerError` so that only the subsequent
`NoClassDefFoundError` is shown.  I have still not figured out what is eating
that error in my case... but I'm still working on it.
