---
layout: post
date: 2020-07-30 16:12:31-06:00
title: Sharing vimrc in Git for Windows
description: |-
  A method to load vimrc from Vim in Git for Windows and plain Vim.
tags: [ windows ]
---

I was surprised to find that the version of [Vim](https://www.vim.org/) which
ships with [Git for Windows](https://gitforwindows.org/) does not load my
[vimfiles/vimrc](https://github.com/kevinoid/vimfiles).  This post has the
explanation and an easy workaround.

<!--more-->

## Default vimrc Locations

By default, [Vim loads personal initializations from `$HOME/_vimrc`,
`$HOME/vimfiles/vimrc`, or
`$VIM/_vimrc`](https://vimhelp.org/starting.txt.html#vimrc) whichever is found
first.  The version of Vim which ships with Git for Windows instead loads
personal initializations from `$HOME/.vimrc` or `$HOME/.vim/vimrc`.
Presumably this was done to match Vim's default behavior on Unix (see
[git-for-windows/git#658
(comment)](https://github.com/git-for-windows/git/issues/658#issuecomment-184269470)).
The difference can be observed by comparing
[`:version`](https://vimhelp.org/various.txt.html#:version) from Vim installed
system-wide:

         user vimrc file: "$HOME\_vimrc"
     2nd user vimrc file: "$HOME\vimfiles\vimrc"
     3rd user vimrc file: "$VIM\_vimrc"

to `:version` from Vim in Git Bash:

         user vimrc file: "$HOME/.vimrc"
     2nd user vimrc file: "~/.vim/vimrc"


## Sharing vimrc

One way to use the same `vimfiles` in both versions of Vim (with minimal
confusion or clutter) is to create a `$HOME/.vimrc` file with the following
content:

```vim
" Git for Windows Vim user initialization file
" GFW uses ~/.vimrc and ~/.vim/vimrc instead of ~/_vimrc and ~/vimfiles/vimrc
" See https://github.com/git-for-windows/git/issues/658#issuecomment-184269470
" This file configures GFW Vim to behave like Windows Vim
let &runtimepath = '~/vimfiles,'
\ . join(filter(split(&runtimepath, ','), 'v:val !~? "/\\.vim"'), ',')
\ . ',~/vimfiles/after'
let &packpath = &runtimepath
source ~/vimfiles/vimrc
```

This replaces `~/.vim` with `~/vimfiles` in
[`'runtimepath'`](https://vimhelp.org/options.txt.html#%27runtimepath%27) and
[`'packpath'`](https://vimhelp.org/options.txt.html#%27packpath%27), then
loads `~/vimfiles/vimrc`.  Once this file is saved, both versions of Vim will
use the same paths for user configuration and runtime files.
