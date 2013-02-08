---
layout: post
date: 2013-02-07 17:10:41-07:00
title: A Warning About Auto-Commit with Flyway and BoneCP
description: "A quick warning about the behavior of the current versions of
Flyway and BoneCP which results in connections with auto-commit disabled."
tags: [ java ]
---
This post is just a quick warning that Flyway (before commit
[55985b](https://github.com/flyway/flyway/commit/55985baa41fc54ec24507cc07eb6b5f95a224edb),
which includes version 2.0.3, the current version) disables auto-commit on its
JDBC Connection.  Also, BoneCP (before commit
[99d50d](https://github.com/wwadge/bonecp/commit/99d50d93137124d238a88bb430afc76c3babb5f1),
resulting from [bug 790585](https://bugs.launchpad.net/bonecp/+bug/790585),
which includes version 0.7.1.RELEASE, the current version) did not apply the
default auto-commit or read-only setting to recycled connections.  When these
behaviors are combined, connections will be returned from the connection pool
which have differing auto-commit.  Plan accordingly.

Another quick note, version 0.8.0-rc1 has auto-commit set to `false` by
default, which differs from the JDBC behavior.  I consider this [a
bug](https://bugs.launchpad.net/bonecp/+bug/1118793).
