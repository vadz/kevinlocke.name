---
layout: post
date: 2016-07-19 21:08:18-07:00
title: "Faster git filter-branch for file filtering"
description: "Speed up git filter-branch when filtering for specific files \
by passing the filenames as rev-list arguments filter-branch."
tags: [ git tip ]
---
When filtering the commit history of a [Git](https://git-scm.com/) repository
to contain only the history of certain files, and performance is an issue,
consider the following suggestions:

1.  Use [BFG Repo-Cleaner](https://rtyley.github.io/bfg-repo-cleaner/) where
    possible.  It's quite fast.
2.  Otherwise, use the `--subdirectory-filter` option of
    [`git filter-branch`](https://git-scm.com/docs/git-filter-branch), where
    appropriate.
3.  Otherwise, use the `--index-filter` option of
    [`git filter-branch`](https://git-scm.com/docs/git-filter-branch) and
    **specify the desired files as arguments**.

<!--more-->

Listing the files as arguments to `filter-branch` was not obvious to me, but
makes a huge difference when filtering for a small subset of the commits.  As
an example, consider extracting the history for getopt from the [FreeBSD src
repo](https://github.com/freebsd/freebsd):

``` sh
git filter-branch --prune-empty \
    --index-filter 'git ls-files -s | \
        sed -n "s/\tlib\/libc\/stdlib\/getopt/\tgetopt/p" | \
        GIT_INDEX_FILE=$GIT_INDEX_FILE.new git update-index --index-info && \
        if test -f "$GIT_INDEX_FILE.new" ; then \
            mv "$GIT_INDEX_FILE.new" "$GIT_INDEX_FILE" ; \
        else \
            rm "$GIT_INDEX_FILE" ; \
        fi' \
    HEAD -- lib/libc/stdlib/getopt*
```

On my laptop, without the file arguments (the `-- lib/libc/stdlib/getopt*`)
after 5 minutes git estimates that the command will take about 4 more hours
(with the estimate continually increasing).  With the file arguments, it
completes in about 30 seconds.  By passing the file arguments, git only
applies the filter to commits which match those files.  Since this can be
determined efficiently it significantly reduces the processing and resulting
run-time.
