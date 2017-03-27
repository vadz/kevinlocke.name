---
layout: post
date: 2017-03-27 12:14:12-06:00
title: Checking MSRs for x2APIC on Windows
description: "An example of how to read Model-Specific Register values on \
Windows."
tags: [ sysadmin ]
---

While troubleshooting a [graphics-related freeze on
Linux](https://bugzilla.kernel.org/show_bug.cgi?id=56051) I was asked whether
Windows uses x2APIC.  It was not immediately clear to me how to check, and my
initial searching did not come up with a convenient command or WMI property to
query.  This post describes the method I used to read the configuration from
the [model-specific registers
(MSRs)](http://wiki.osdev.org/Model_Specific_Registers) in hopes that it may
save others the time effort of figuring it out.

<!--more-->

The [Intel(R) 64 Architecture x2APIC
Specification](https://www-ssl.intel.com/content/www/us/en/architecture-and-technology/64-architecture-x2apic-specification.html)
says that "System software can place the local APIC in the x2APIC mode by
setting the x2APIC mode enable bit (bit 10) in the `IA32_APIC_BASE` MSR at MSR
address 01BH."  Conversely, reading the `IA32_APIC_BASE` MSR and checking
bit 10 will indicate whether the system is in x2APIC mode.  Since the `rdmsr`
instruction must be executed at privilege level 0, a kernel-mode driver must
be used.

## Performance Inspector

One method for reading the MSR values is to use the
[`msr`](http://perfinsp.sourceforge.net/msr.html) from the (abandoned)
[Performance Inspector](http://perfinsp.sourceforge.net) project:

1.  Download and unzip
    [pi_win64-20100715.zip](https://sourceforge.net/projects/perfinsp/files/Performance%20Inspector/Jul-15-2010/pi_win64-20100715.zip/download)
    (or
    [pi_win32-20100715.zip](https://sourceforge.net/projects/perfinsp/files/Performance%20Inspector/Jul-15-2010/pi_win32-20100715.zip/download)
    for 32-bit Windows).
2.  Run `tinstall.cmd` (as Administrator) to install the driver.
3.  Run `msr -r APIC_BASE`

The output should look something like the following:

    ***** msr v2.0.7 for x64 *****
    CPU0  msr 0x1B = 0x00000000:FEE00900 (4276095232)
    CPU1  msr 0x1B = 0x00000000:FEE00800 (4276094976)
    CPU2  msr 0x1B = 0x00000000:FEE00800 (4276094976)
    CPU3  msr 0x1B = 0x00000000:FEE00800 (4276094976)

Since bit 10 (0x400) is not set for any processor, it is clear that my system
is not running in x2APIC mode.

## Debugging Tools

It might also be possible to use the [`rdmsr`
command](https://msdn.microsoft.com/en-us/library/windows/hardware/ff553516.aspx)
in the [Debugging Tools for
Windows](https://msdn.microsoft.com/en-us/library/windows/hardware/ff551063.aspx)
to read the `IA32_APIC_BASE` MSR.

## Boot Policy

Whether x2APIC is enabled or disabled is both a matter of hardware/BIOS/driver
support and a matter of policy.  If the x2APIC state does not match
expectations, consider checking the Windows boot configuration using [`bcdedit
/enum`](https://technet.microsoft.com/en-us/library/cc709667.aspx) and
adjusting the configuration with [`bcdedit /set x2apicpolicy
enable`](https://msdn.microsoft.com/en-us/library/windows/hardware/ff542202.aspx)
or `bcdedit /set x2apicpolicy disable` as appropriate.
