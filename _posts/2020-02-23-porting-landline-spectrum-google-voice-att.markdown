---
layout: post
date: 2020-02-23 12:44:13-07:00
title: Porting a Landline from Charter Spectrum to Google Voice via AT&T
description: Notes on porting a landline phone number from Charter Spectrum to Google Voice via AT&T.
tags: []
---

Not long ago I helped a few people transfer their landline phone number
from [Charter Spectrum](https://www.spectrum.net/) to [Google
Voice](https://voice.google.com/).  The general process is straight-forward,
but the devil is in the details.  Several steps are prone to failure and
delays if not done correctly.  This post is my notes about the exact steps
required.

<!--more-->

## Background

The process of transferring a phone number from one carrier to another is
called "porting".  (For details, see [local number
portability](https://en.wikipedia.org/wiki/Local_number_portability).)
[Google Voice only supports porting numbers from some wireless
carriers](https://support.google.com/voice/answer/1065667#xferin).  One way to
port a landline to Google Voice is to first port the number to a wireless
carrier.  The carrier I chose to use is [AT&T
Prepaid](https://www.att.com/prepaid/).


## Prerequisites

Before beginning the porting process, you will need:

1. An AT&T SIM Card.  I used an [AT&T Prepaid Activation
   Kit](https://www.att.com/buy/prepaid-phones/att-prepaid-sim-card-kit-white-prepaid.html),
   but my understanding is that a generic AT&T SIM Card (e.g. [from
   Amazon](https://amzn.com/B075B2FMG5)) or even a previously used AT&T SIM
   Card (from either prepaid or postpaid service) would work.
2. [Your Charter Spectrum account number and security
   code](https://www.spectrum.net/support/manage-account/finding-your-account-number-and-security-code/)
   along with the full name and billing address *exactly as it appears* on
   the account (e.g. from the online or paper statement).
3. An AT&T-compatible cell phone to use during the porting process.


## Porting Process

To port a landline from Charter Spectrum to Google Voice, use the following
steps:

1. Insert the AT&T SIM Card into the AT&T-compatible cell phone.
2. Activate an AT&T prepaid plan for the SIM Card.  This can be done by
   [online](https://att.com/activateprepaid), or by dialing
   `*123*ZIP Code*Plan Code#` from the cell phone, where `ZIP Code` is your
   5-digit ZIP Code and `Plan Code` is the code for your preferred prepaid
   plan.  (I'd recommend `02` for "$2 Daily Plan" or `17` for "25¢ Per Minute
   Plan".)

   **Write down the phone number assigned to your plan.**  Note that the last
   4 digits of the phone number are the AT&T account PIN.
3. [Transfer the landline number to AT&T
   Prepaid](https://www.att.com/support/article/wireless/KM1189707)
  1. Call [611](tel:611) from the AT&T phone (or
     [1-800-901-9878](tel:+1-800-901-9878) from any phone).  Navigate to
     "more options", then "tech support" in the current phone tree to speak to
     a tech support representative.  Request to "port a number from a different
     carrier to AT&T" and answer the questions they ask.

     Be sure to specify the name and address on the Charter Spectrum account
     **exactly as it appears on the account**.  If the information in the port
     request does not match the information in Charter Spectrum's system
     exactly, the port request may be rejected, which will delay the process.
  2. If the port doesn't complete immediately, ask for the estimated completion
     date, then check [att.com/port/](https://www.att.com/port/) or call the
     AT&T Ports Department at [1-888-898-7685](tel:+1-888-898-7685) to check
     the port status.

     If the port is rejected or doesn't complete by the date given, call AT&T
     Ports Department at [1-888-898-7685](tel:+1-888-898-7685) to inquire
     and/or retry.  If the support rep. is unable to determine the reason the
     port failed, call the Charter Spectrum ports department at
     [1-844-881-2092](tel:+1-844-881-2092) to have them determine the reason.

     Note that Charter Spectrum contracts with
     [Syniverse](https://www.syniverse.com/) for landline porting.  If the
     Charter support rep. is not able to unable to explain the failure, they
     can call Syniverse to inquire.  (Customers are not allowed to call them
     directly.)
  3. Once the port is completed ("Confirmed" on
     [att.com/port](https://www.att.com/port/)) you may need to
     "activate service" by confirming a phone plan and funding the account.
     This will be done automatically by the support rep. if the port completes
     during the call or can be done by calling AT&T customer support and
     requesting to "activate service on a ported number".

     Note that it may be possible to decline service activation (e.g. by
     deferring "until later") and save a few dollars.  However, I have not
     tried it.
  4. While on the phone with AT&T support (or in a subsequent call),
     **request the account number**.  (If there is confusion between the
     account number and the phone number, specify "the account number for
     porting to a different carrier".)  It will be a 12-digit number and is not
     currently available on [myAT&T Prepaid](https://att.com/myprepaid).
4. Sign in to [myAT&T Prepaid](https://att.com/myprepaid) and copy the name
   and address on the account **exactly as it appears**.
5. [Port or transfer your personal number to Google
   Voice](https://support.google.com/voice/answer/1065667#xferin) using the
   information from the AT&T account copied from myAT&T Prepaid in the previous
   step and the account number provided by tech support.


## Total Costs

* $0-10 AT&T SIM Card
* $0-10 AT&T Prepaid Plan (It may be possible to avoid "activating service" on
  AT&T.  I have not tried it.  Whether a minimum amount is required to fund
  the account differed.  It may be $2 or $10.)
* $20 Google Voice Porting Fee
* Lots of time on the phone with AT&T (and possibly Charter Spectrum).
