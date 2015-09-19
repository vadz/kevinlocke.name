---
layout: post
date: 2013-02-05 13:43:25-07:00
title: Configuring Logging in Tests with Lift
description: "An example of how to configure Logback (or log4j) when running
tests using specs2 or scalatest in a Lift project."
tags: [ scala, lift ]
---
The [Lift](http://liftweb.net/) web framework integrates the
[SLF4J](http://www.slf4j.org/) logging framework through a set of interfaces
for performing logging and a configuration mechanism.  The configuration
mechanism attempts to configure the logging in a manner similar to the
configuration for other parts of Lift.  Unfortunately, this mechanism performs
differently (or not at all) when running tests than it does when running
normally.  This post is a quick explanation of the configuration mechanism and
how to configure logging during tests.

<!--more-->

## Logging Configuration (In Theory)

The configuration mechanism that Lift uses is documented on the
[Logging page on the Lift
Wiki](https://www.assembla.com/spaces/liftweb/wiki/Logging#configuration).
This post presents only a simplified overview.

### Programmatic Configuration

Logging can be configured programmatically in lift-webkit by assigning a
configuration function to `LiftRules.configureLogging` (or directly to
`Logger.setup`, to which `LiftRules.configureLogging` delegates) before the
logging system is initialized.  During initialization, the function will be called to configure the logging backend.  Initialization is performed at most once,
when the first `Logger` is created.  So assigning a configuration function
after this point is useless.

### Automatic Configuration

When using lift-webkit, configuration files ending with either `.logback.xml`
(when using [Logback](http://logback.qos.ch/)) or `.log4j.xml` or
`.log4j.props` (when using [Log4J](https://logging.apache.org/log4j/1.2/)) are
found in the same way as Lift configuration properties files.  For
example, the file `src/main/resources/props/production.default.logback.xml`
would be used in production mode on any server, if it existed.

This automatic configuration is accomplished by the function returned from
`net.liftweb.util.LoggingAutoConfigurer.apply()`, which is the default value
of `LiftRules.configureLogging`.

## The Problem When Testing

Unfortunately, when testing (using
[Specs2](https://etorreborre.github.io/specs2/) or
[ScalaTest](http://www.scalatest.org/) with [SBT](http://www.scala-sbt.org/)),
the Automatic Configuration method is unlikely to work.  The problem is that
if an instance of `LiftRules` is not created before the first `Logger` is
created, `LoggingAutoConfigurer` will not be assigned to `Logger.setup` before
setup is completed (and therefore it is never executed).

## Solutions

### Do Setup Before Each Test

All of the testing frameworks provide a mechanism for running code
before/after tests (or suites of tests).  It's quite possible to either create
an instance of `LiftRules`, access a `LiftRules` method on the `LiftRules`
object (which has an implicit conversion to `LiftRules`), or assign
`Logger.setup` directly through this mechanism.  However, this requires the
most work and is therefore the worst solution (in my opinion).

Be warned, this method is very fragile.  Because the order in which tests are
run is not deterministic, if any test creates a `Logger` without performing
logging setup, it will prevent future configuration and cause all other tests
to log all messages in the default configuration.

### Do Setup Before All Tests

In SBT, it is also possible to run code before any tests run by using
[Tests.Setup and
Tests.Cleanup](http://www.scala-sbt.org/release/docs/Testing.html#Setup+and+Cleanup)
in the `testOptions` setting.  This method is a bit awkward, since SBT project
code does not have access to the Lift classes directly (without adding a
dependency to the project code), so everything must be done via reflection.
To pass `LoggingAutoConfigurer` to `Logger.setup`, add the following to
`build.sbt` (Note that blank lines would confuse the `.sbt` parser, but would
be allowed in a `.scala` file):

{% highlight scala %}
testOptions += Tests.Setup { loader: ClassLoader =>
  // Get Logger.setup
  val boxClass = loader.loadClass("net.liftweb.common.Box")
  val loggerClass = loader.loadClass("net.liftweb.common.Logger$")
  val logger = loggerClass.getField("MODULE$").get(null)
  val loggerSetupEq = loggerClass.getMethod("setup_$eq", boxClass)
  // Get function from LoggingAutoConfigurer.apply()
  val configurerClass = loader.loadClass("net.liftweb.util.LoggingAutoConfigurer$")
  val configurer = configurerClass.getField("MODULE$").get(null)
  val configFunc = configurerClass.getMethod("apply").invoke(configurer)
  // Put it in a Box
  val fullClass = loader.loadClass("net.liftweb.common.Full")
  val fullConstructor = fullClass.getConstructor(classOf[Object])
  val configFuncBox = fullConstructor.newInstance(configFunc)
  // Call Logger.setup on the Box
  loggerSetupEq.invoke(logger, configFuncBox.asInstanceOf[Object])
}
{% endhighlight %}

### Use The Default Search Path

If `Logger.setup` has not been assigned, the logging backend will not be
configured by Lift.  This does not mean that the logging backend will not be
configured at all.  Conveniently, both Logback and Log4J, in their default
configurations, will search for configuration files on the classpath.  Logback
will use [`logback-test.xml` or
`logback.xml`](http://logback.qos.ch/manual/configuration.html) and Log4J will
use [`log4j.properties` or the value of the `log4j.configuration` system
property](https://logging.apache.org/log4j/1.2/manual.html#defaultInit).  Using
this fact, it is possible to configure Logback by creating
`src/test/resources/logback.xml` (and similar for Log4J).  This is by far the
easiest solution, if you don't mind the asymmetry of the configuration file
locations.

## Conclusion And A Note

It is possible to configure logging in Lift during tests using any of the
above methods.  My recommendation is to use either of the last two methods,
based on whichever is more suitable for a given project.

In any case, be aware that in the default SBT configuration (`fork := false`),
the JVM is shared between not only all tests, but all tasks invoked from SBT.
For this reason, re-running the `test` task without exiting SBT will not
re-initialize logging.  Also, running multiple tasks (such as `test` and
`run`) will break Lift, since the mode is determined only once.
