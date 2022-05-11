---
layout: post
date: 2022-05-11 16:06:32-06:00
title: Excel PowerQuery System.EnterpriseServices Impersonation Level is Invalid
description: >-
  Description of an obscure error messsage I encountered in Excel and how I
  was able to resolve it.
tags: [ sysadmin, windows ]
---

Recently a user encountered the following error message when refreshing a
query in Excel that used PowerQuery to connect to Microsoft SQL Server using
Windows authentication:

> Could not load file or assembly 'System.EnterpriseServices, Version=4.0.0.0, Culture=neutral, PublicKeyToken=b03f5f7f11d50a3a' or one of its dependencies. Either a required impersonation level was not provided, or the provided impersonation level is invalid. (Exception from HRESULT: 0x80070542)

What's going on?  Read on for all the gory details.

**Spoiler:** The problem was caused by attempting to "Use alternate
credentials" for Windows authentication in PowerQuery.  It was solved by
switching to "Use my current credentials" in Data Source Settings, as
described in "Manage data source credentials" in [Manage data source settings
and permissions (Power
Query)](https://support.microsoft.com/en-us/office/manage-data-source-settings-and-permissions-power-query-9f24a631-f7eb-4729-88dd-6a4921380ca9#__toc354511926).

<!--more-->

## Breaking Down the Error Message

The first part of the error message "Could not load file or assembly
'System.EnterpriseServices, Version=4.0.0.0, Culture=neutral,
PublicKeyToken=b03f5f7f11d50a3a' or one of its dependencies." can be a bit
misleading.  It indicates that the System.EnterpriseServices .NET assembly
failed to load.  Often this is caused by missing or corrupted DLL files (in
this case, `System.EnterpriseServices.dll` or any of its dependencies).  I
found many well-intentioned suggestions to run [System File
Checker](https://docs.microsoft.com/troubleshoot/windows-server/deployment/system-file-checker)
or reinstall .NET framework to address the failure.  However, in this case the
error is not caused by missing or corrupted DLLs.

The rest of the error message indicates what caused the assembly load failure.
We can use the [Microsoft Error Lookup
Tool](https://docs.microsoft.com/windows/win32/debug/system-error-code-lookup-tool)
to decode the
[HRESULT](https://docs.microsoft.com/openspecs/windows_protocols/ms-erref/0642cb2f-2075-4469-918c-4441e69c548a)
and determine that it is [1346 (0x542)
`ERROR_BAD_IMPERSONATION_LEVEL`](https://docs.microsoft.com/windows/win32/debug/system-error-codes--1300-1699-),
which has a description that matches the preceding error message.  Although
this doesn't tell us much that we didn't already know, it fills in the details
a bit.


## Additional Symptoms

Since the error message refers to impersonation, I suspected it had something
to do with database authentication.  I used the following tests, which you may
find useful:

### Can you connect from another program?

I was able to connect using
[`sqlcmd`](https://docs.microsoft.com/sql/tools/sqlcmd-utility), [SQL Server
Management
Studio](https://docs.microsoft.com/sql/ssms/sql-server-management-studio-ssms),
and [PowerShell](https://microsoft.com/powershell) (by running `$c =
New-Object System.Data.Odbc.OdbcConnection "DRIVER=SQL
Server;SERVER=$myServerName;Trusted_Connection=Yes;DATABASE=$myDbName";
$c.Open()`) which confirmed that the issue was specific to Excel.

### Can you connect from another workbook?

I tried creating a new workbook and querying data from the same database,
using the same connection type (PowerQuery in my case).  It failed with the
same error message, confirming that the issue is not specific to a particular
file.


## Fixing Invalid Windows Credentials

If you **are** able to connect from other programs using Windows
authentication as the current user, the issue may be caused the data source
credentials for the SQL Server database connection in PowerQuery being
configured to "Use alternate credentials" for Windows authentication rather
than "Use my current credentials".  This can be changed in the Data Source
Settings, as described in "Manage data source credentials" in [Manage data
source settings and permissions (Power
Query)](https://support.microsoft.com/en-us/office/manage-data-source-settings-and-permissions-power-query-9f24a631-f7eb-4729-88dd-6a4921380ca9#__toc354511926)
and reproduced below:

1. In Excel, select Data > Get Data > Data Source Settings.
2. Select the data source experiencing the error.
3. Press the "Edit Permissions..." button.
4. Press the "Edit..." button in the "Credentials" section.
5. Switch to "Use my current credentials"
or read on for all the gory details. I was able to fix the issue by changed by
navigating to the Data tab, pressing Queries & Connections, editing the
affected connection, opening Data source settings, clicking "Edit
Permissions...", clicking "Edit..." in the "Credentials" section, then fixing
the credentials (in my case, switching to "Use my current credentials").


## Changing Local Security Policies

If the SQL Server connection **does** require using alternate credentials, the
issue may be that [User Rights
Assignment](https://docs.microsoft.com/windows/security/threat-protection/security-policy-settings/user-rights-assignment)
does not allow [Impersonate a client after
authentication](https://docs.microsoft.com/windows/security/threat-protection/security-policy-settings/impersonate-a-client-after-authentication)
in the [Local Security
Policy](https://docs.microsoft.com/windows/win32/secmgmt/local-security-policy).
See [Configure security policy
settings](https://docs.microsoft.com/windows/security/threat-protection/security-policy-settings/how-to-configure-security-policy-settings)
for how to change this setting, as suggested by [How do I make sure that my
windows impersonation level is valid?](https://stackoverflow.com/q/28597551)
on Stack Overflow.
