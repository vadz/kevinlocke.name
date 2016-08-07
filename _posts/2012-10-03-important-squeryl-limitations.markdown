---
layout: post
date: 2012-10-03 14:34:21-06:00
title: Important Squeryl Limitations
description: "A discussion of some of the limitations of Squeryl which \
motivated a switch to ScalaQuery/SLICK"
tags: [ scala, squeryl, scalaquery, slick ]
updated: 2012-11-16 11:41:32-07:00
---
I recently had to make the difficult choice to replace
[Squeryl](http://squeryl.org/) with [SLICK](http://slick.lightbend.com/)
(formerly [ScalaQuery](http://scalaquery.org/)) much later in the development
cycle than I would have liked.  Although I do like some of the design and
features of Squeryl, it has some very significant limitations that anyone
considering using it should be aware of up-front.  Also, in an effort to avoid
excessive bias, I'll include a few of the limitations of SLICK that I have
encountered for comparison.

<!--more-->

Also, my apologies in advance if this is a bit of a diatribe.  I don't mean to
belittle the impressive amount of work that has been done on Squeryl at no
cost to the users.  I am simply frustrated that I did not find out about these
limitations sooner, which is more my fault than anyone else's.

## Limitations of Squeryl

First, a quick note that the [limitations on the Squeryl
website](http://squeryl.org/limitations.html) are not mentioned here.  Lack of
database-specific features is also not mentioned (and not generally expected).
Also, any example queries are written using the [SchoolDb
schema](https://github.com/max-l/Squeryl/blob/master/src/test/scala/org/squeryl/test/schooldb/SchoolDb.scala).
Now, with that out of the way, on to the limitations:

### Syntax and usage errors are often inscrutable

The biggest obstacle to using Squeryl effectively, in my opinion, is the
compiler errors which are produced when there is a syntax error in the SQL DSL
or a compile-time usage error (e.g. type error).  I spent a significant amount
of time tracking down a missing comma, incorrectly ordered clauses, type
errors, and a number of other simple errors.  Troubleshooting isn't always
easy, but the amount of the Squeryl implementation details which leak out into
error messages necessitates learning a lot about the Squeryl internals to
diagnose simple errors.

For example, consider the following query:

{% highlight scala %}
join(students, courseSubscriptions, courses)((s,cs,c) =>
  select(s.name, c.name)
  on(s.id === cs.studentId and c.id === cs.courseId)
)
{% endhighlight %}

Attempting to compile this results in the following error:

    [error] Example.scala:32: type mismatch;
    [error]  found   : org.squeryl.dsl.boilerplate.JoinQueryYield1[(String, String)]
    [error]  required: org.squeryl.dsl.boilerplate.JoinQueryYield2[?]
    [error]       on(s.id === cs.studentId and c.id === cs.courseId)
    [error]       ^
    [error] one error found

The problem is that the `on` method requires one argument for each table after
the first.  This makes good sense, but it isn't immediately clear from the
error message (although the number in the class name does provide a hint).

For another example (I know this query is sub-optimal, but it is meant to
parallel the first example):

{% highlight scala %}
join(students, courseSubscriptions, courses)((s,cs,c) =>
  compute(count(c.name))
  groupBy(s.name)
  on(s.id === cs.studentId, c.id === cs.courseId)
)
{% endhighlight %}

Which results in:

    [error] Example.scala:32: value groupBy is not a member of org.squeryl.dsl.fsm.ComputeStateStartOrWhereState[org.squeryl.PrimitiveTypeMode.LongType]
    [error] possible cause: maybe a semicolon is missing before `value groupBy'?
    [error]       groupBy(s.name)
    [error]       ^
    [error] one error found

The error here is that `compute` should follow `groupBy` (and both should
precede `on`).  Also, as an exercise for the reader, try putting `on` first.

### Problems with Option

I ran into issues properly typing expressions which are non-NULL (or where
NULLness is irrelevant) where the referenced column may be NULL (e.g. `SELECT
column FROM table WHERE column IS NOT NULL`).  Although you can call `.get` on
the Option type during AST construction to get the correct non-Option type,
there are cases where this results in a `NoSuchElementException`.  I need to
dig through some version history to figure out which cases those are.  For now,
feel free to take this limitation with a grain of salt.

### No support for building DML queries (INSERT/DELETE/UPDATE)

To be clear, it is possible to insert, delete, and update single rows through
Squeryl using concrete values.  What is not possible is to write DML queries
which may modify multiple rows (e.g. `DELETE FROM table WHERE column < 5`) or
to write DML queries which use the result of a SELECT statement (e.g.  `INSERT
INTO table2 SELECT * FROM table1`).  These queries must be done through JDBC
directly.

It's possible that DML queries which make use of the result of a SELECT
statement could be built by concatenating the SQL statement generated
by Squeryl to the end of the query text.  However, I am not sure if the column
order is guaranteed to match the variable order, so I would approach this
idea with caution.

### No support for calling stored procedures

I have been unable to find any mechanism for calling stored procedures through
Squeryl.  I assume it does not exist and that one must use JDBC directly (see
the next limitation for a discussion of this).

### No support for ad-hoc/raw SQL statements (prepared or otherwise)

This limitation was the real show-stopper for me.  Although it is possible to
use JDBC directly, Squeryl does not provide any mechanism that I could find
which would convert the JDBC results into user datatypes, run the queries in
the current session, enumerate results, or provide any of the Squeryl
functionality to these ad-hoc queries.

Every library has its limitations, but whether, and how, the developer is able
to work around those limitations is critically important.  When Squeryl
doesn't support a required feature, whether that is a database-specific
feature or one of the general limitations mentioned above, it provides no
support or convenience.  The developer must learn JDBC, implement
unmarshalling of result types for each result type, implement enumeration of
the result to Scala data types, and all of the other tedious details of
dealing directly with JDBC.  Also, the developer must be aware of the many
pitfalls of dealing with SQL directly, particularly things like using
`PreparedStatement` to avoid SQL-injection, which are so commonly done
incorrectly.  No thanks!

## Limitations of SLICK

Although I have found SLICK to be preferable to Squeryl, it is not without its
own limitations.

### Limit of 22-columns in table definitions and query results

This limit is the result of the choice of modeling tables and results as Scala
`Tuple`s (which are limited to 22 elements).  Although it should be possible to
link with a library which provides implementations for larger tuples, I have
not tried it.

### No support for retrieving auto-increment IDs (recently fixed)

Support for retrieving ID values for auto-incremented columns was added as a
result of issue [#10](https://github.com/slick/slick/issues/10) in commit
[09a65a8](https://github.com/slick/slick/commit/09a65a8e88a0363412e218dc5c06023b69809649),
which is in version 0.11.1 and later.  This may still be important in light of
the next limitation.

### No SLICK build for Scala 2.9

Unfortunately, there is [no SLICK for Scala
2.9](https://github.com/slick/slick/issues/25).  Anyone still using Scala 2.9
will be stuck using ScalaQuery 0.10.0-M1.  This is particularly frustrating
because Lift is unlikely to support Scala 2.10 [until it is
released](https://groups.google.com/d/msg/liftweb/4IhZBN2aMXk/RWPnv2JNcHYJ).

### No support in Lift

This isn't really a limitation of SLICK, but is important for users
considering whether to use SLICK with Lift.  Lift Record (one of the two
data-access mechanisms in Lift) does not currently support SLICK.  This can be
a significant limitation as many of the forms features (form construction and
validation in particular) either require, or are significantly easier when
using, Record.

## Limitations of both

### Lack of support for CASE expressions

The SQL CASE expression has two variants, often described as the "searched"
and the "simple" CASE expressions.  The "searched" CASE, which behaves like
the `switch` statement of many programming languages, compares each choice for
equality with the argument value and results in the value of the expression
associated with the first match.  The "simple" CASE, which behaves like a
sequence of `if` tests, evaluates each expression and results in the value
associated with the first expression which evaluates to true.

Although the behavior of CASE expressions in SELECT clauses of top-level
queries can be easily reproduced by post-processing the result, the real value
of using CASE in SQL is in sub-queries, HAVING clauses, and particularly in
GROUP BY clauses.

Unfortunately, Squeryl does not support either CASE expression in any context.
Support was added in
[54500b90](https://github.com/max-l/Squeryl/commit/54500b907f2fc1024b62a30475794dde32008cee),
then removed in
[8784db07](https://github.com/max-l/Squeryl/commit/8784db07f7fdec5aa73034556c1fb6a14c13e7d2)
and is still missing.  SLICK does not appear to have, or have had, any support
for CASE statements in any context.

## Conclusion

After switching from Squeryl to SLICK, I have never looked back.  I have found
the design of SLICK to be exceptional and although it is possible that I may
find show-stopping issues in the future, it seems unlikely.  It has already
taken me further than Squeryl.  I have managed to re-implement everything
previously done using Squeryl and a significant number of additional tasks
which would not have been easily possible (requiring ad-hoc queries).

I hope this post helps clarify some of the issues to consider when choosing a
Scala ORM.
