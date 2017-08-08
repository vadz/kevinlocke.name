---
layout: post
date: 2017-08-08 00:49:38-06:00
title: Saving SQL passwords in Excel Power Pivot Connections
description: "This post describes how to save the SQL password in a Power \
Pivot data connection in the Power Pivot Excel Add-in."
tags: [ ms-office ]
---

I recently ran into some difficulty enabling SQL password saving for a data
source which had password saving disabled when it was created in the Power
Pivot Excel Add-in.  After some trial and error, I discovered that the trick
is to enable password saving in the Excel workbook connection before
specifying a password and enabling password saving in the Power Pivot
connection.  This post provides a detailed walkthrough of the process.

<!--more-->

To enable SQL password saving (aka "Persist Security Info") for an existing
Power Pivot connection, use the following process:

1.  Open the Excel file of interest.  If already open, close then re-open the
    file to ensure the SQL password has not been cached.
2.  Click the Connections button in the Connections section of the Data tab.
    (Note that this button is disabled when a Power View worksheet is active.
    Switch to a non-Power View worksheet first, if necessary.)  ![Connections
    button on the Data tab](data-connections.png){:.img-instruction-step}
3.  Select the connection for which you want to save the SQL password, then
    click the Properties button.  ![Properties button on the Workbook
    Connections window](workbook-connections.png){:.img-instruction-step}
4.  Check the "Save password" box on the Definition tab of Connection
    Properties.  ![Save password box in Connection Properties
    window](connection-properties.png){:.img-instruction-step}
    Click the "Yes" button if you are willing to save the SQL password in the
    workbook without encryption.  ![Yes button of password encryption
    warning](password-warning.png){:.img-instruction-step}
5.  Press the "OK" button to close the Connection Properties window, then
    "Close" to close Workbook Connections window.
6.  Click the "Manage Data Model" button the in Data Tools section of the Data
    tab.  ![Manage Data Model button on Data tab](data-manage-data-model.png){:
    .img-instruction-step}
7.  Click the "Existing Connections" button in the "Get External Data" section
    of the Home tab.  ![Existing Connections button on Home
    tab](home-existing-connections.png){:.img-instruction-step}
8.  Select the connection for which you want to save the SQL password, then
    click the Edit button.  ![Edit button in Existing Connections
    window](existing-connections-edit.png){:.img-instruction-step}
9.  Enter the password for SQL Server Authentication and check the "Save my
    password" box, then click the "Save" button to close the Edit Connection
    window.  ![Save my password box in Edit Connection
    window](edit-connection.png){:.img-instruction-step}
10. **Important** Click the Refresh button and enter the SQL password when
    prompted.  I do not know why this is required, but in my testing, saving
    without refreshing the connection results in the password not being saved.
    ![Refresh button in Existing Connections
    window](existing-connections-refresh.png){:.img-instruction-step}
11. Click the Close button to close the Existing Connections window then close
    the Power Pivot for Excel window.
12. Save the Excel workbook.
13. (Optional) Close the workbook, then re-open it and Refresh a Power View
    sheet to confirm the password has been saved.

**Note:**  Excel caches the SQL password for workbook connections.  During
testing it is important to close and re-open the workbook before refreshing to
test whether the password has been successfully saved.
