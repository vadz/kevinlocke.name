---
layout: post
date:  2012-08-25 14:17:39-06:00
title: NoSuchMethodException for Field Classes in Squeryl-Record
description: "A possible explanation for a NoSuchMethodException being thrown \
due to a missing constructor for a squeryl-record field class in Lift."
tags: [ lift ]
---
I recently spent way too much time tracking down the source of an error in the
[Squeryl](http://squeryl.org) integration to the
[Record](https://app.assembla.com/spaces/liftweb/wiki/Record) persistence layer
in [Lift](http://liftweb.net/).  In the hopes that it may be useful to others
encountering the same error, here are the details:

<!--more-->

## Background

After reading the
[Squeryl-Record](https://app.assembla.com/wiki/show/liftweb/Squeryl)
documentation and following the
[test-squerylrecord](https://github.com/migo/test-squerylrecord) and
[Basic-SquerylRecord-User-Setup](https://github.com/karma4u101/Basic-SquerylRecord-User-Setup)
examples, I set out to make use of Record with Squeryl.  After coding up a very
simple test table and schema, I launched the website and was presented with the
following exception and stack trace (excerpted):

    Message: java.lang.NoSuchMethodException: net.liftweb.record.field.OptionalStringField.<init>(scala.Option)
        java.lang.Class.getConstructor0(Class.java:2723)
        java.lang.Class.getConstructor(Class.java:1674)
        org.squeryl.internals.FieldMetaData$$anonfun$org$squeryl$internals$FieldMetaData$$_createCustomTypeFactory$1.apply(FieldMetaData.scala:511)
        org.squeryl.internals.FieldMetaData$$anonfun$org$squeryl$internals$FieldMetaData$$_createCustomTypeFactory$1.apply(FieldMetaData.scala:504)
        scala.Option.flatMap(Option.scala:146)
        org.squeryl.internals.FieldMetaData$.org$squeryl$internals$FieldMetaData$$_createCustomTypeFactory(FieldMetaData.scala:504)
        org.squeryl.internals.FieldMetaData$$anon$1.build(FieldMetaData.scala:425)
        org.squeryl.internals.PosoMetaData$$anonfun$3.apply(PosoMetaData.scala:111)
        org.squeryl.internals.PosoMetaData$$anonfun$3.apply(PosoMetaData.scala:80)
        scala.collection.immutable.HashMap$HashMap1.foreach(HashMap.scala:176)
        scala.collection.immutable.HashMap$HashTrieMap.foreach(HashMap.scala:345)
        org.squeryl.internals.PosoMetaData.<init>(PosoMetaData.scala:80)
        org.squeryl.View.<init>(View.scala:58)
        org.squeryl.Table.<init>(Table.scala:27)
        org.squeryl.Schema$class.table(Schema.scala:338)
	[...]

Why the OptionalStringField class (which is part of record) is missing a
constructor expected by Squeryl was beyond me.  Something odd was going on.

## Finding the Source

First, I tried copying my code into the (working) examples and running it.
Everything worked without issue.  The model code was not the issue.

After a bit more digging, I found that
`net.liftweb.squerylrecord.RecordMetaDataFactory` was not being set on
`org.squeryl.internals.FieldMetaData` because the session initialization and
transaction setup (in my application's Lift Boot class) were not being
called because Lift *continues after Boot throws an exception*, which was
occurring before the initialization code was reached.  Ugh!

## Conclusion

Takeaways:

* If you receive the above exception, check that record and Squeryl are being
  initialized correctly.
* Setup [logging](https://app.assembla.com/spaces/liftweb/wiki/Logging) for
  Lift very early in the development and watch for errors from Boot.  This is
  the only way you will be aware of them (other than the mysterious errors and
  lack of configuration that will result).
