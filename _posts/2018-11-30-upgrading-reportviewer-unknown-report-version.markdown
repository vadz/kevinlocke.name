---
layout: post
date: 2018-11-30 14:36:06-07:00
title: 'Upgrading ReportViewer: Unknown Report Version and Other Errors'
description: >-
  This post describes issues encountered upgrading a Web Site Project using
  ReportViewer 2005 to ReportViewer 2017 and how they were resolved.
tags: [asp.net]
---

Recently I helped a client update an ASP.NET web site project from
ReportViewer 2005 to ReportViewer 2017.  This post documents a few issues that
I encountered during the process:

<!--more-->

## Unknown Report Version: 9.0

Opening a ReportViewer 2005 RDLC file in the [Microsoft RDLC Report Designer
for Visual
Studio](https://marketplace.visualstudio.com/items?itemName=ProBITools.MicrosoftRdlcReportDesignerforVisualStudio-18001)
produces the prompt "Do you want to convert this report to the latest RDLC
format?" After clicking OK, everything appears to work, except that saving the
report displays "Unknown Report Version: 9.0" an error dialog box.  This issue
can be worked around by first saving the RDLC files using an intermediate
version of ReportViewer.  I tested [Visual Studio Community
2015](https://visualstudio.microsoft.com/vs/older-downloads/) with SQL Server
Data Tools (SSDT) version [17.4 (build
14.0.61712.050)](https://go.microsoft.com/fwlink/?linkid=863440), [17.2 (build
14.0.61707.300)](http://go.microsoft.com/fwlink/?linkid=393524), and [from the
VS2015 Installer (build 14.0.60519.0)](https://stackoverflow.com/a/31587357).
All versions worked.  [Visual Studio Community
2013](https://visualstudio.microsoft.com/vs/older-downloads/) with either
[SSDT or SSDT-BI](https://stackoverflow.com/q/49351506) should also work,
although I did not test it.


## Incorrect `<Style>` Conversion

After completing the above procedure and saving the RDLC files, `<Style>`
elements inside `<Body>` are incorrectly applied to the `<Page>` element.  To
correct the problem, simply edit the RDLC files with a text or XML editor and
swap the empty `<Style />` in `<Body>` with the non-empty one in `<Page>`.


## Error Installing NuGet Package

Attempting to install the
`Microsoft.ReportingServices.ReportViewerControl.WebForms` NuGet package
(according to the [Getting
Started](https://docs.microsoft.com/en-us/sql/reporting-services/application-integration/integrating-reporting-services-using-reportviewer-controls-get-started)
directions) results in the following error:

    Install-Package : An error occurred while applying transformation to 'web.config' in project 'webdb' No element in the source document matches 
    '/configuration/system.web'
      No element in the source document matches '/configuration/system.web'
    At line:1 char:1
    + Install-Package Microsoft.ReportingServices.ReportViewerControl.WebFo ...
    + ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        + CategoryInfo          : NotSpecified: (:) [Install-Package], Exception
        + FullyQualifiedErrorId : NuGetCmdletUnhandledException,NuGet.PackageManagement.PowerShellCmdlets.InstallPackageCommand

This problem was due to the presence of
`xmlns="http://schemas.microsoft.com/.NetConfiguration/v2.0"` on the root
`<configuration>` element in `web.config`.  This namespace was conventional
in the past, but is no longer expected (nor supported, apparently).  After
removing `xmlns` from `<configuration>` in `web.config`, the NuGet package
installed without error.


## Compiler: The Report Definition Is Not Valid
 
Compiling the project produced the following error:

    The report definition is not valid.  Details: The report definition has an invalid target namespace 'http://schemas.microsoft.com/sqlserver/reporting/2008/01/reportdefinition' which cannot be upgraded.

This was due to my own mistake of not correctly updating the `<buildProvider>`
to `Version=15.0.0.0`.  I mention it here in case it helps anyone else who
makes the same mistake.

Best of luck with your upgrade!
