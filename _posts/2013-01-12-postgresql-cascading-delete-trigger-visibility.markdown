---
layout: post
date: 2013-02-12 23:57:17-07:00
title: PostgreSQL Cascading Delete Trigger Visibility
description: "Are deleted rows in parent tables visible to delete triggers on
child tables?  This post explains my findings."
tags: [ postgresql sql ]
---
If a delete trigger is fired on a table due to an `ON DELETE CASCADE` action,
will the trigger see the rows in the parent table which triggered the cascade?
Does it matter if the trigger is a "before" or an "after" trigger?  The answer
to these questions was not immediately obvious to me, and my half-minute of
searching didn't find a clear answer, so I have written this post to remind
myself and others what happens in PostgreSQL 9.1.

<!--more-->

## Test Script

In order to test the behavior, I wrote the following test script.  It simply
creates a parent table, child table, results table, and a trigger which
fires on both the parent and child to record whether the parent row being
deleted is present in the parent table.

{% highlight scala %}
CREATE TABLE parents (
    parent_id INTEGER NOT NULL PRIMARY KEY
);

CREATE TABLE children (
    child_id INTEGER NOT NULL PRIMARY KEY,
    parent_id INTEGER NOT NULL REFERENCES parents(parent_id)
	ON DELETE CASCADE
	ON UPDATE CASCADE
);

CREATE TABLE results (
    result_id SERIAL PRIMARY KEY,
    table_name VARCHAR(10) NOT NULL,
    trigger_when VARCHAR(10) NOT NULL,
    parent_present BOOLEAN NOT NULL
);

CREATE OR REPLACE FUNCTION report_parent_id() RETURNS TRIGGER AS $$
BEGIN
    INSERT INTO results (table_name, trigger_when, parent_present)
    VALUES (TG_TABLE_NAME, TG_WHEN, EXISTS (SELECT 1 FROM parents WHERE parent_id = OLD.parent_id));

    RETURN OLD;
END; $$ LANGUAGE plpgsql;

CREATE TRIGGER tr_parents_bd_report_parent_id
BEFORE DELETE ON parents
FOR EACH ROW EXECUTE PROCEDURE report_parent_id();
CREATE TRIGGER tr_parents_ad_report_parent_id
AFTER DELETE ON parents
FOR EACH ROW EXECUTE PROCEDURE report_parent_id();
CREATE TRIGGER tr_children_bd_report_parent_id
BEFORE DELETE ON children
FOR EACH ROW EXECUTE PROCEDURE report_parent_id();
CREATE TRIGGER tr_children_ad_report_parent_id
AFTER DELETE ON children
FOR EACH ROW EXECUTE PROCEDURE report_parent_id();

INSERT INTO parents (parent_id) VALUES (1);
INSERT INTO children (child_id, parent_id) VALUES (1, 1);
DELETE FROM parents;

SELECT * FROM results;
{% endhighlight %}

## What Happens

Ok, here's the answer:  The parent row is not visible from either before or
after triggers on the child table.  It is visible in the before trigger on the
parent table but not the after trigger (as expected/documented).  Which changes
are visible during cascading updates is left as an exercise for the interested
reader.
