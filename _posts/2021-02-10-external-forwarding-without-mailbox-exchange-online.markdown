---
layout: post
date: 2021-02-10 06:54:21-07:00
title: External Forwarding without a Mailbox in Exchange Online
description: >-
  Steps for mail-enabling an on-premises AD user so they become a Mail User
  in Exchange Online after AD Connect Synchronization to facilitate email
  forwarding.
tags: [ sysadmin ]
---

Suppose you are using [Microsoft Exchange
Online](https://docs.microsoft.com/exchange/exchange-online) with [Azure AD
Connect
Sync](https://docs.microsoft.com/azure/active-directory/hybrid/how-to-connect-sync-whatis)
to synchronize users between an on-premises [Active
Directory](https://docs.microsoft.com/windows-server/identity/ad-ds/get-started/virtual-dc/active-directory-domain-services-overview)
and [Azure Active
Directory](https://docs.microsoft.com/azure/active-directory/fundamentals/active-directory-whatis).
Further suppose that there are some users for whom you do not want to create
an Exchange Online mailbox, but would like to forward email to an external
address.  This might occur for part-time employees, contractors, partners, or
other users for whom it is convenient to have a company email address, but a
mailbox to hold the email is not required or desired.  How would you
accomplish this?

<!--more-->

### Mail-Enable Users

The best solution I have come up, based on the Microsoft Answers question
[Convert on-premise AD users to MailUsers (Exchange
online)](https://answers.microsoft.com/en-us/msoffice/forum/msoffice_o365admin-mso_dirservices-mso_o365b/convert-on-premise-ad-users-to-mailusers-exchange/a2a1073f-8351-4ec3-9238-acd8a3a0c85d)
and other similar discussions, is to create a [Mail
User](https://docs.microsoft.com/exchange/recipients-in-exchange-online/manage-mail-users)
for each AD user.  Unfortunately, the Mail User can not be created using the
steps in the Mail User documentation because the user already exists, causing
a conflict:

> error
>
> The proxy address "SMTP:user@example.com" is already being used by
> the proxy addresses or LegacyExchangeDN. Please choose another proxy address.
>
> [Click here for help...](http://technet.microsoft.com/en-US/library/ms.exch.err.default(EXCHG.150).aspx?v=15.20.3825.30&e=ms.exch.err.ExB10BE9&l=1)

Instead, the user must be mail-enabled in the on-premises AD, then
synchronized to Azure AD.  If Exchange is installed on the server, this can be
accomplished with
[`Enable-MailUser`](https://docs.microsoft.com/powershell/module/exchange/enable-mailuser):

```pwsh
Enable-MailUser -Identity UserName -ExternalEmailAddress user@otherdomain.example
```

If Exchange is not installed, the same effect can be accomplished by [setting
the necessary AD user object
properties](https://social.msdn.microsoft.com/Forums/en-US/WindowsAzureAD/thread/478e3ee9-1723-4ac7-8d58-c6d0961e000f/#b9e80a5b-239b-4fd9-81ab-241ab8ee61af):

`mail`
: For the user's Primary SMTP Address.

`proxyAddresses`
: For any [additional addresses](https://docs.microsoft.com/Exchange/recipients/user-mailboxes/email-addresses) for the user.

`mailNickName`
: For the user's Exchange Alias.

`targetAddress`
: For the external address to which the user's mail will be forwarded.

These can be set by any LDAP or AD property editor, such as ADSI Edit or
[`Set-ADUser`](https://docs.microsoft.com/powershell/module/activedirectory/set-aduser):

```pwsh
Set-ADUser -Identity UserName -Replace @{mail='username@company.example';mailNickName='username';proxyAddresses=@('SMTP:username@company.example','SMTP:username@companyalt.example');targetAddress='SMTP:user@otherdomain.example'}
```


## AD Schema Extension

Unfortunately, if Exchange has not been installed, these properties may not
exist in the AD Schema, which would cause errors such as the following:

> Set-ADUser : The specified directory service attribute or value does not exist
> Parameter name: mailNickName

Be aware that ["when directory synchronization is enabled for a tenant and a
user is synchronized from on-premises, most of the attributes cannot be
managed from Exchange Online and must be managed from
on-premises"](https://docs.microsoft.com/en-us/exchange/decommission-on-premises-exchange#why-you-may-not-want-to-decommission-exchange-servers-from-on-premises).
To facilitate this, Microsoft provides the [Hybrid Configuration
Wizard](http://aka.ms/hybridkey) to license a locally-installed Exchange
server by validating your O365 tenant.  (Note that Exchange [doesn't need to
be fully configured to manage
mailboxes](https://jaapwesselius.com/2016/06/15/office-365-directory-synchronization-without-exchange-server-part-iii).)
Alternatively, it is possible to [use the Exchange installer to extend the AD
Schema without installing
Exchange](https://www.tachytelic.net/2017/11/office-365-hide-a-user-from-gal-ad-sync/):

```pwsh
setup.exe /prepareschema /iacceptexchangeserverlicenseterms
```

After `/prepareschema` completes, it should be possible to set the properties
described above on user objects.


## Synchronize to Azure

Once the properties have been set, simply wait for AD Connect Sync to
synchronize the properties to Azure or [start a sync
cycle](https://docs.microsoft.com/en-us/azure/active-directory/hybrid/how-to-connect-sync-feature-scheduler#start-the-scheduler)
to do so immediately.  Once synchronized, the mail-enabled users should appear
as Mail Users in the [Exchange Admin
Center](https://docs.microsoft.com/exchange/exchange-admin-center).
