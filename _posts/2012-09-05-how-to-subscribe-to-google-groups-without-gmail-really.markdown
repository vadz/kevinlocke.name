---
layout: post
date: 2012-09-05 18:45:23-06:00
title: How to Subscribe to Google Groups without GMail, Really
description: "Description of an odd occurrence of Google Groups sending emails \
to an address that wasn't subscribed, and how to subscribe with a non-GMail \
address."
tags: [ google ]
---
Although it does not appear to be officially documented, it is possible to
subscribe to a Google Group without a Google Account.  There are [several ways
to subscribe](https://webapps.stackexchange.com/q/13508) but, as I recently
found out, Google Groups tries really hard to use a GMail account, if you have
one.  This post explains how to subscribe to a Google Group via email and how
to avoid one pitfall that may result in messages being sent to your GMail
address rather than the address with which you subscribed.

<!--more-->

## The Story

Recently I have been tinkering around with the [Lift](http://liftweb.net/) web
framework and decided to subscribe to their [mailing
list](https://groups.google.com/group/liftweb), which happens to be hosted on
Google Groups.  After a quick search I found several sets of instructions for
[subscribing to a Google Group without a Google
Account](https://webapps.stackexchange.com/a/15593).  Interestingly, many of the
suggestions I found link to articles on Google Groups Help, none of which
actually contain the by-email instructions.  It is possible this was officially
supported in the past and is now deprecated, but it continues to work
nonetheless.

Undaunted, I followed the instructions and sent an email to
liftweb+subscribe@googlegroups.com, then replied to the confirmation email it
sent in reply.  I received a "welcome to the group" message and everything
looked good.  Piece of cake, just like the good ol' days.  Then the list
mail started flowing in **to my GMail address**.  What!?

That's odd, I thought.  So I double-checked my sent folder (in
[Mutt](http://www.mutt.org/)) and confirmed that all messages were sent and
received from my personal email address on this domain.  I checked the server
logs (did I mention that I host my own email?), all messages were sent through
my mail server with the envelope sender set to my personal email address.  How
did Google Groups even get my GMail address?

A short bit more thought and I realized that my personal address was probably
set as the backup/alternate email address for GMail, in case of the need to do
a password reset.  I checked, and indeed it was.  That explains how Google
associated the two accounts.

## The Workaround

The easiest method that I could find to prevent Google Groups from sending
emails to a GMail account is to make sure that the email address which is used
to subscribe is not associated with any GMail account.  If it is the
backup/alternate address to a GMail account, remove it, then subscribe, then
re-add it.  That's the best I could find.

If anyone can find an easier way to prevent this behavior, or a even a good
explanation for why this Google Groups behavior is beneficial to anyone but
Google, I'd love to hear it (and post it as a follow-up).  Otherwise, good
luck subscribing to a Google Group with your preferred email address.
