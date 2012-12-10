---
layout: post
date: 2012-12-10 10:31:37-07:00
title: No Output From non-UTF-8 XMLStreamWriter
description: "A quick note about problems relating to non-UTF-8 output from \
XMLStreamWriter."
tags: [ java ]
---
Just a quick reminder to always flush your buffers (when appropriate) and that
the behavior of the JDK default `XMLStreamWriter`
(`com.sun.xml.internal.stream.writers.XMLStreamWriterImpl`) differs between
UTF-8 output, which is unbuffered, and non-UTF-8 output, which is buffered
through `com.sun.xml.internal.stream.writers.XMLWriter`.  I just spent way too
much time figuring this out (particularly because finding the actual location
of the source file is non-trivial - Hint: It's not in the OpenJDK source
tree).  Hopefully this post will save others that time/effort.
