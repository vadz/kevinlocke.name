---
layout: post
date: 2021-12-10 12:50:56-07:00
updated: 2022-05-09 10:32:31-06:00
title: Windows 11 Guest VM with VirtIO on Libvirt
description: Notes on running Windows 11 (or 10) in a virtual machine with paravirtualized (virtio) drivers using libvirt.
tags: [ windows ]
---

I recently configured a Windows 11 guest virtual machine on
[libvirt](https://libvirt.org/) with the [VirtIO
drivers](https://www.linux-kvm.org/page/WindowsGuestDrivers).  This post is a
collection of my notes for how to configure the host and guest.  Most are
applicable to any recent version of Windows.

For the impatient, just use my [libvirt domain XML]({% post_url
2021-12-10-windows-11-guest-virtio-libvirt %}win11.xml).

<!--more-->

## Host Configuration

### Hyper-threading/Simultaneous Multithreading (SMT)

Many configuration guides recommend [disabling hyper-threading on Intel
chipsets before Sandy
Bridge](https://forum.proxmox.com/threads/20265/post-103282) for performance
reasons.  Additionally, if the VM may run untrusted code, it is recommended to
disable SMT on processors vulnerable to [Microarchitectural Data Sampling
(MDS)](https://www.kernel.org/doc/html/latest/admin-guide/hw-vuln/mds.html).


### RTC Synchronization

To keep [RTC](https://en.wikipedia.org/wiki/Real-time_clock) time in the guest
accurate across suspend/resume, it is advisable to set `SYNC_TIME=1` in
`/etc/default/libvirt-guests`, which calls [`virsh domtime
--sync`](https://www.libvirt.org/manpages/virsh.html#domtime) after the guest
is resumed.  This causes the [QEMU Guest
Agent](https://wiki.qemu.org/Features/GuestAgent) to call [`w32tm /resync
/nowait`](https://docs.microsoft.com/windows-server/networking/windows-time-service/windows-time-service-tools-and-settings#w32tmexe-windows-time)
in the guest, which synchronizes the clock with the configured w32time
provider (usually NTP, although [VMICTimeProvider could be used to sync with
the Hyper-V
host](https://docs.microsoft.com/archive/blogs/virtual_pc_guy/time-synchronization-in-hyper-v)).
Ignore the comment in older libvirt versions that SYNC_TIME is not supported
on Windows, which was fixed in
[qemu/qemu@105fad6bb22](https://git.qemu.org/?p=qemu.git;a=commit;h=105fad6bb226ac3404874fe3fd7013ad6f86fc86).


### Wayland Keyboard Inhibit

To send keyboard shortcuts (i.e. key combinations) to the virtual machine
viewer that has focus, rather than sending them to the Wayland compositor, the
compositor must support the [Wayland keyboard shortcut inhibition
protocol](https://gitlab.freedesktop.org/wayland/wayland-protocols/-/tree/main/unstable/keyboard-shortcuts-inhibit).
For example, [Sway](https://swaywm.org/) gained support for for this protocol
in Sway 1.5 ([swaywm/sway#5021](https://github.com/swaywm/sway/pull/5021)).
When using Sway 1.4 or earlier in the default configuration, pressing
<kbd><kbd>Win</kbd> + <kbd>d</kbd></kbd> would invoke
[dmenu](https://wiki.archlinux.org/title/dmenu) rather than [display or hide
the
desktop](https://support.microsoft.com/windows/keyboard-shortcuts-in-windows-dcc61a57-8ff0-cffe-9796-cb9706c75eec)
in the focused Windows VM.


## Guest Configuration

### BIOS vs UEFI (with SecureBoot)

There are trade-offs to consider when choosing between BIOS and UEFI:

* [Windows 11 Requires UEFI which is Secure Boot
  capable](https://support.microsoft.com/topic/86c11283-ea52-4782-9efd-7674389a7ba3).
  Although the secure boot check can be bypassed, allowing Windows 11 to be
  installed, it is an unsupported configuration.
* Libvirt [forbids internal snapshots with pflash
  firmware](https://gitlab.com/libvirt/libvirt/-/commit/9e2465834f4bff4068e270f15e9ed5d7301de045),
  which is used for UEFI variable storage, thus preventing internal snapshots
  ([RH Bug 1881850](https://bugzilla.redhat.com/1881850)).  Libvirt also lacks
  support for basic features with external snapshots ([RH Bug
  1519002](https://bugzilla.redhat.com/1519002)) such as reverting or deleting
  external snapshots.  This means [snapshots for guests with UEFI may not be
  supported for a
  while](https://www.redhat.com/archives/virt-tools-list/2017-September/msg00008.html).
  Which was true in 2017 and is still true in 2021.  There are some partial
  workarounds, such as libvirt disk-only snapshots or QEMU disk snapshots
  managed manually, as [described by Chris
  Siebenmann](https://utcc.utoronto.ca/~cks/space/blog/linux/LibvirtUEFISnapshots).
* The [Windows Driver Signing
  Policy](https://docs.microsoft.com/windows-hardware/drivers/install/kernel-mode-code-signing-policy--windows-vista-and-later-#signing-requirements-by-version)
  requires drivers to be WHQL-signed signed if Secure Boot is enabled on Windows
  8 and later.  It will refuse to boot with unsigned drivers if Secure Boot is
  enabled.  This is problematic for the VirtIO drivers, for which Red Hat
  donates non-WHQL signed binaries, but only provides WHQL-signed drivers to
  customers ([Bug 1844726](https://bugzilla.redhat.com/1844726)).  (Note: As of
  0.1.204 and later, most drivers are signed, excluding ivshmem, pvpanic, and
  possibly others.)

To enable UEFI with Secure Boot, [use
`OVMF_CODE_4M.ms.fd`](https://salsa.debian.org/qemu-team/edk2/-/blob/debian/debian/README.Debian).
If snapshots aren't required and UEFI is desired, don't enable Secure Boot:


### CPU Model

It may be preferable to choose a CPU model which satisfies the [Windows
Processor Requirements](https://aka.ms/CPUlist) for the Windows edition which
will be installed on the guest.  As of this writing, the choices are Skylake, Cascadelake, Icelake, Snowridge, Cooperlake, and EPYC.

If the VM may be migrated to a different machine, consider setting
`check='full'` on `<cpu/>` so `enforce` will be added to the QEMU `-cpu`
option and the domain will not start if the created vCPU doesn't match the
requested configuration.  This is not currently set by default.  ([Bug
822148](https://bugzilla.redhat.com/822148))


### CPU Topology

If topology is not specified, libvirt instructs QEMU to add a socket for each
vCPU (e.g. `<vcpu placement="static">4</vcpu>` results in `-smp
4,sockets=4,cores=1,threads=1`).  It may be preferable to change this for
several reasons:

First, as Jared Epp pointed out to me via email, for licensing reasons
[Windows 10 Home and Pro are limited to 2 CPUs
(sockets)](https://www.microsoft.com/microsoft-365/blog/2017/12/15/windows-10-pro-workstations-power-advanced-workloads/),
while Pro for Workstations and Enterprise are limited to 4 (possibly
[requiring build 1903 or later to use more than
2](https://superuser.com/a/1565953)). Similarly, [Windows 11 Home is limited
to 1 CPU while 11 Pro is limited to
2](https://www.xda-developers.com/windows-11-home-vs-windows-11-pro/).
Therefore, limiting sockets to 1 or two on these systems is strongly
recommended.

Additionally, it may be useful, particularly on a NUMA system, to specify a
topology matching (a subset of) the host and pin vCPUs to the matching
elements (e.g. virtual cores on physical cores).  See [KVM Windows 10 Guest -
CPU Pinning Recommended? on Reddit](https://redd.it/7zcn5g) and [PCI
passthrough via OVMF: CPU pinning on
ArchWiki](https://wiki.archlinux.org/index.php/PCI_passthrough_via_OVMF#CPU_pinning)
Be aware that, on my single-socket i5-3320M system, the matching
configurations I tried performed worse than the default.  Some expertise is
likely required to get this right.

It may be possible to reduce jitter by pinning vCPUs to host cores, emulator
and iothreads to other host cores and using a hook script with `cset shield`
to ensure host processes don't run on the vCPU cores.  See [Performance of
your gaming VM](https://rokups.github.io/#!pages/gaming-vm-performance.md).

Note that it is possible to set max CPUs in excess of current CPUs for CPU
hotplug.  See [Linux KVM – How to add /Remove vCPU to Guest on fly ? Part
9](https://www.unixarena.com/2015/12/linux-kvm-how-to-add-remove-vcpu-to-guest-on-fly.html/).


### Hyper-V Enlightenments

[QEMU supports several Hyper-V
Enlightenments](https://github.com/qemu/qemu/blob/master/docs/hyperv.txt) for
Windows guests.  virt-manager/virt-install enables some Hyper-V Enlightenments
by default, but is missing several useful recent additions
([virt-manager/virt-manager#154](https://github.com/virt-manager/virt-manager/issues/154)).
I recommend [editing the libvirt domain XML to enable Hyper-V
enlightenments](https://blog.wikichoon.com/2014/07/enabling-hyper-v-enlightenments-with-kvm.html)
which are not described as "nested specific".  In particular, `hv_stimer`,
which [reduces CPU usage when the guest is
paused](https://lore.kernel.org/kvm/20200625201046.GA179502@kevinolos/).


### Memory Size

When configuring the memory size, be aware of the system requirements ([4GB
for Windows
11](https://docs.microsoft.com/windows/whats-new/windows-11-requirements),
[1GB for 32-bit, 2GB for 64-bit Windows
10](https://support.microsoft.com/windows/windows-10-system-requirements-6d4e9a79-66bf-7950-467c-795cf0386715))
and [Memory Limits for Windows and Windows Server
Releases](https://docs.microsoft.com/windows/win32/memory/memory-limits-for-windows-releases)
which vary by edition.


### Memory Backing

If shared memory will be used (e.g. for [virtio-fs](#virtio-fs) discussed
below), define a (virtual) NUMA zone and memory backing.  The memory backing
can be backed by files (which are flexible, but can have performance issues if
not on hugetlbfs/tmpfs) or memfd (since QEMU 4.0, libvirt 4.10.0).  The memory
can be [Huge
Pages](https://www.kernel.org/doc/html/latest/admin-guide/mm/hugetlbpage.html)
(which have lower overhead, but can't be swapped) or regular pages.  (Note: If
hugepages are not configured, [Transparent
Hugepages](https://www.kernel.org/doc/html/latest/admin-guide/mm/transhuge.html)
may still be used, if [THP is enabled
system-wide](https://www.kernel.org/doc/html/latest/admin-guide/mm/transhuge.html#thp-sysfs)
on the host system.  This may be advantageous, since it reduces translation
overhead for merged pages while still allowing swapping.  Alternatively, it
may be disadvantageous due to increased CPU use for defrag/compact/reclaim
operations.)


### Memory Ballooning

If memory ballooning will be used, set current memory to the initial amount
and max memory to the upper limit.  Be aware that the balloon size is not
automatically managed by KVM.  There was an [Automatic
Ballooning](https://www.linux-kvm.org/page/Projects/auto-ballooning) project
which has not been merged.  Unless a separate tool, such as [oVirt Memory
Overcommitment Manager](https://www.ovirt.org/develop/projects/mom.html), is
used, the balloon size must be changed manually (e.g. using [`virsh --hmp
"balloon $size"`](https://unix.stackexchange.com/a/413462)) for the guest to
use more than "current memory".  Also be aware that when the balloon is
inflated, the guest [shows the memory as "in
use"](https://github.com/virtio-win/kvm-guest-drivers-windows/issues/61#issuecomment-238022304)
which may be [counter-intuitive](https://unix.stackexchange.com/q/534679).


### Machine Type

The [Q35 Machine Type](https://wiki.qemu.org/Features/Q35) adds support for
PCI-E, AHCI, PCI hotplug, and probably many other features, while removing
legacy features such as the ISA bus.  Historically it may have been preferable
to use i440FX for stability and bug avoidance, but my experience is that it's
generally preferable to use the latest Q35 version (e.g. `pc-q35-6.1` for QEMU
6.1).


### Storage Controller

Paravirtualized storage can be implemented using either SCSI with
`virtio-scsi` and the `vioscsi` driver or bulk storage with `virtio-blk` with
the `viostor` driver.  The choice is not obvious.  In general, `virtio-blk`
may be faster while `virtio-scsi` [supports more
features](https://wiki.qemu.org/Features/VirtioSCSI) (e.g. pass-through,
multiple LUNs, CD-ROMs, more than 28 disks).  Citations:

- QEMU [Configuring virtio-blk and virtio-scsi
  Devices](https://www.qemu.org/2021/01/19/virtio-blk-scsi-configuration/)
  has a detailed comparison.
- [`virtio-blk` is faster than `virtio-scsi` in Fam Zheng's LC3-2018
  presentation](https://events19.lfasiallc.com/wp-content/uploads/2017/11/Storage-Performance-Tuning-for-FAST-Virtual-Machines_Fam-Zheng.pdf#page=12).
- The [QEMU wiki VirtioSCSI page](https://wiki.qemu.org/Features/VirtioSCSI)
  notes `virtio-scsi` "rough numbers: 6% slower \[than `virtio-blk`] on iozone
  with a tmpfs-backed disk".
- [Paolo Bonzini (in 2017)
  thinks](https://lists.gnu.org/archive/html/qemu-devel/2017-10/msg02142.html)
  "long-term virtio-blk should only be used for high-performance scenarios
  where the guest SCSI layer slows down things sensibly."
- [Proxmox recommends SCSI and states "VirtIO block may get deprecated in the
  future."](https://pve.proxmox.com/wiki/Paravirtualized_Block_Drivers_for_Windows)
- `vioscsi` has supported discard for long time (pre 2015, when changelog
  starts?).  `viostor` only added support for discard recently (in
  [virtio-win/kvm-guest-drivers-windows#399](https://github.com/virtio-win/kvm-guest-drivers-windows/pull/399)
  for 0.1.172-1).  Although #399 is described as "preliminary support" the
  author clarified that [it is now full support on par with
  `vioscsi`](https://github.com/virtio-win/kvm-guest-drivers-windows/issues/392#issuecomment-640146021).


### Virtual Disk

#### Format

When choosing a format for the virtual disk, note that `qcow2` supports
snapshots.  `raw` does not.  However, `raw` is likely to have better
performance due to less overhead.

Alberto Garcia added support for [Subcluster allocation for qcow2
images](https://blogs.igalia.com/berto/2020/12/03/subcluster-allocation-for-qcow2-images/)
in QEMU 5.2.  When using 5.2 or later, it may be prudent to create `qcow2`
disk images with `extended_l2=on,cluster_size=128k` to reduce wasted space and
write amplification.  Note that extended L2 always uses 32 sub-clusters, so
`cluster_size` should be 32 times the filesystem cluster size (4k for NTFS
created by the Windows installer).


#### Discard

I find it generally preferable to set `discard` to `unmap` so that guest
discard/trim requests are passed through to the disk image on the host
filesystem, reducing its size.  For Windows guests, discard/trim requests are
normally only issued when [Defragment and Optimize
Drives](https://support.microsoft.com/windows/defragment-your-windows-10-pc-048aefac-7f1f-4632-d48a-9700c4ec702a)
is run.  It is scheduled to run weekly by default.

I do not recommend enabling `detect_zeroes` to detect write requests with all
zero bytes and optionally unmap the zeroed areas in the disk image.  As the
[libvirt docs note](https://libvirt.org/formatdomain.html#elementsDisks):
"enabling the detection is a compute intensive operation, but can save file
space and/or time on slow media".


### Video

There are [several options for graphics
cards](https://wiki.archlinux.org/index.php/QEMU#Graphic_card).  [VGA and
other display devices in qemu by Gerd
Hoffmann](https://www.kraxel.org/blog/2019/09/display-devices-in-qemu/) has
practical descriptions and recommendations ([kraxel's
news](https://www.kraxel.org/blog/) is great for following progress).
virtio-drivers 0.1.208 and later include the `viogpudo` driver for
`virtio-vga`.  ([Bug 1861229](https://bugzilla.redhat.com/1861229))
Unfortunately, it has some limitations:

- It is [limited to `height x width <=
  4x1024x1024`](https://github.com/virtio-win/kvm-guest-drivers-windows/issues/560#issuecomment-894033021).
- It requires additional work to configure automatic resolution switching,
  which is not done by the installer
  ([virtio-win/virtio-win-guest-tools-installer#32](https://github.com/virtio-win/virtio-win-guest-tools-installer/issues/32)).
  From [Bug 1923886](https://bugzilla.redhat.com/show_bug.cgi?id=1923886#c4):
  - Copy `viogpuap.exe` and `vgpusrv.exe` to a permanent location.
  - Run `vgpusrv.exe -i` as Administrator to register the "VioGpu Resolution Service" Windows Service.
- It doesn't support Windows 7
  ([virtio-win/kvm-guest-drivers-windows#591](https://github.com/virtio-win/kvm-guest-drivers-windows/issues/591))
- It is currently a [WDDM Display Only
  Driver](https://docs.microsoft.com/windows-hardware/drivers/display/wddm-in-windows-8)
  without support for 2-D or 3-D rendering.  (Same as the QXL-WDDM-DOD driver
  for QXL.)  This may be added in the future with [Virgil
  3d](https://virgil3d.github.io/) similarly to Linux guests.
- It [doesn't currently provide any advantages over
  QXL](https://github.com/virtio-win/kvm-guest-drivers-windows/issues/668#issuecomment-951451771).

However, unless the above limitations are critical for a particular use case,
I would recommend `virtio-vga` over QXL based on the understanding that it is
a better and more promising approach on technical grounds and that it is where
most current development effort is directed.

**Note:** If 3D acceleration is enabled for `virtio-vga`, the VM must have a
Spice display device with OpenGL enabled to avoid an "opengl is not available"
error when the VM is started.  Since the `viogpudo` driver does not support 3D
acceleration, I recommend disabling both.


### Keyboard and Mouse

I recommend adding a "Virtio Keyboard" and "Virtio Tablet" device in addition
to the default USB or PS/2 Keyboard and Mouse devices.  These are ["basically
sending linux evdev events over
virtio"](https://lists.nongnu.org/archive/html/qemu-devel/2015-03/msg05460.html),
which can be useful for keyboard or mouse with special features (e.g.
keys/buttons not supported by PS/2).  Possibly also a [latency or performance
advantage](https://passthroughpo.st/using-evdev-passthrough-seamless-vm-input/).
Note that it is not necessary to remove the USB or PS/2 devices, since [QEMU
will route input events to virtio-input devices if they have been initialized
by the guest](https://bugzilla.redhat.com/1357406#c12) and virtio input
devices are not supported without drivers, which can make setup and recovery
more difficult if the PS/2 devices are not present.


<!--
### Network

TODO: Investigate networking options (ArchWiki has good info)
- May want to set MTU on default network to 9000 or 65521 (maximum for TAP device) to minimize packet overhead and hyper-calls.

Note: May want to avoid slight boot delay due to loading iPXE from network boot option ROM by disabling its base address register:
https://askubuntu.com/a/226499
https://libvirt.org/formatdomain.html#elementsNICSROM
-->


### TPM

[Windows 11 requires TPM
2.0](https://support.microsoft.com/topic/86c11283-ea52-4782-9efd-7674389a7ba3).
Therefore, I recommend adding a [QEMU TPM
Device](https://qemu.readthedocs.io/en/latest/specs/tpm.html) to provide one.
Either TIS or CRB can be used.  ["TPM CRB interface is a simpler interface
than the TPM TIS and is only available for TPM
2."](https://listman.redhat.com/archives/libvir-list/2018-April/msg00756.html)
If emulated, [swtpm](https://github.com/stefanberger/swtpm) must be installed
and configured on the host.  Note:  swtpm was packaged for Debian in 2022
([Bug 941199](https://bugs.debian.org/941199)), so it is not available in
Debian 11 (Bullseye) or earlier releases.


### RNG

It may be useful to add a
[`virtio-rng`](https://wiki.qemu.org/Features/VirtIORNG) device to provide
entropy to the guest.  This is particularly true if the vCPU does not support
the [`RDRAND`](https://en.wikipedia.org/wiki/RDRAND) instruction or if it is
not trusted.


### File/Folder Sharing

There are several options for sharing files between the host and guest with
various trade-offs.  Some common options are discussed below.  My
recommendation is to use SMB/CIFS unless you need the feature or performance
offered by virtio-fs (and like living on the bleeding edge).


#### [Virtio-fs](https://virtio-fs.gitlab.io/)

Libvirt supports sharing [virtual
filesystems](https://libvirt.org/formatdomain.html#elementsFilesystems) using
a protocol similar to
[FUSE](https://www.kernel.org/doc/html/latest/filesystems/fuse.html) over
virtio.  It is a great option if the host and guest can support it (QEMU 5.0,
libvirt 6.2, Linux 5.4, Windows virtio-drivers 0.1.187).  It has very high
performance and supports many of the filesystem features and behaviors of a
local filesystem.  Unfortunately, it has several significant issues including
configuration difficulty, lack of support for migration or snapshot, and
Windows driver issues, each explained below:

Virtio-fs requires shared memory between the host and guest, which in turn
requires configuring a (virtual) NUMA topology with shared memory backing: See
[Sharing files with Virtio-FS](https://libvirt.org/kbase/virtiofs.html).  Also
ensure you are using a version of libvirt which includes [the apparmor policy
patch to allow libvirtd to call
virtiofsd](https://www.redhat.com/archives/libvir-list/2020-August/msg00804.html)
(6.7.0 or later).

[Migration with virtiofs device is not
supported](https://gitlab.com/libvirt/libvirt/-/commit/5c0444a38bb37ddeb7049683ef72d02beab9e617)
by libvirt, which also prevents saving and creating snapshots while the VM is
running.  This is difficult to work around since [live detach of device
'filesystem' is not
supported](https://gitlab.com/libvirt/libvirt/-/blob/v6.10.0/src/qemu/qemu_hotplug.c#L5922)
by libvirt for QEMU.

The Windows driver has released with several severe known bugs, such as:

- [Can't copy files larger than 2MiB](https://github.com/virtio-win/kvm-guest-drivers-windows/issues/527) (fixed in 0.1.190)
- [Can't remove empty directories](https://github.com/virtio-win/kvm-guest-drivers-windows/issues/497) (fixed in 0.1.190)
- [Symlinks appear as files in Windows
  guest](https://github.com/virtio-win/kvm-guest-drivers-windows/issues/499)
- [Doesn't work with `iommu_platform=on`](https://github.com/virtio-win/kvm-guest-drivers-windows/issues/483)

My offer to assist with adding tests
([virtio-win/kvm-guest-drivers-windows#531](https://github.com/virtio-win/kvm-guest-drivers-windows/issues/531)
has seen very little interest or action.  It's not clear to me who's working
on virtio-fs and how much interest it has at the moment.


#### [Virtio-9p](https://www.linux-kvm.org/page/9p_virtio)

Although it is not an option for Windows guests due to lack of a driver
([virtio-win/kvm-guest-drivers-windows#126](https://github.com/virtio-win/kvm-guest-drivers-windows/issues/126)),
it's worth nothing that virtio-9p is similar to virtio-fs except that it uses
the [9P distributed file system protocol](http://9p.cat-v.org/) which is
supported by older versions of Linux and QEMU and has the advantage of being
used and supported outside of virtualization contexts.  For a comparison of
virtio-fs and virtio-9p, see the [virtio-fs patchset on
LKML](https://lore.kernel.org/lkml/20181210171318.16998-1-vgoyal@redhat.com/).


#### [SPICE Folder Sharing](https://www.spice-space.org/spice-user-manual.html#_folder_sharing) (WebDAV)

SPICE Folder Sharing is a relatively easy way to share directories from the
host to the guest using the [WebDAV protocol](http://www.webdav.org/specs/)
over the `org.spice-space.webdav.0` virtio channel.  Many libvirt viewers
(remote-viewer, virt-viewer, Gnome Boxes) provide built-in support.  Although
virt-manager does not
([virt-manager/virt-manager#156](https://github.com/virt-manager/virt-manager/issues/156)),
it can be used to [configure folder
sharing](https://www.spice-space.org/spice-user-manual.html#_folder_sharing)
(by adding a `org.spice-space.webdav.0` channel) and other viewers used for
running the VM and serving files.  Note that users have reported [performance
is not great](https://redd.it/asw4wk) and the [SPICE WebDAV
Daemon](#spice-webdav-daemon) must be installed in the guest to share files.


#### [SMB/CIFS](https://en.wikipedia.org/wiki/Server_Message_Block)

Since Windows supports SMB/CIFS (aka "Windows File Sharing Protocol")
natively, it is relatively easy to share files between the host and guest if
networking is configured on the guest.  Either the host (with
[Samba](https://www.samba.org/) or
[KSMBD](https://www.kernel.org/doc/html/latest/filesystems/cifs/ksmbd.html))
or the guest can act as the server.  For a Linux server, see [Setting up Samba
as a Standalone
Server](https://wiki.samba.org/index.php/Setting_up_Samba_as_a_Standalone_Server).
For Windows, see [File sharing over a network in Windows
10](https://support.microsoft.com/en-us/windows/file-sharing-over-a-network-in-windows-10-b58704b2-f53a-4b82-7bc1-80f9994725bf).
Be aware that, depending on the network topology, file shares may be exposed
to other hosts on the network.  Be sure to adjust the server configuration and
add firewall rules as appropriate.


### Channels

I recommend adding the following [Channel
Devices](https://libvirt.org/formatdomain.html#channel):

- com.redhat.spice.0 (spicevmc) for the [SPICE Agent](#spice-agent)
- org.qemu.guest_agent.0 (unix) for the [QEMU Guest Agent](#qemu-guest-agent)
- org.spice-space.webdav.0 (spiceport) for SPICE Folder Sharing (WebDAV), if using.


### Notes

There are some differences between the "legacy" 0.9/0.95 version of the virtio
protocol and the "modern" 1.0 version.  Recent versions (post-2016) of QEMU
and libvirt use 1.0 by default.  For older versions, it may be necessary to
specify `disable-legacy=on,disable-modern=off` to force the modern version.
For details and steps to confirm which version is being used, see [Virtio 1.0
and Windows
Guests](https://ladipro.wordpress.com/2016/10/17/virtio1-0-and-windows-guests/).


## Guest OS Installation

I recommend configuring the guest with two SATA CD-ROM devices during
installation: One for the [Windows 10
ISO](https://www.microsoft.com/software-download/windows10ISO) or [Windows 11
ISO](https://www.microsoft.com/software-download/windows11), and one for the
[virtio-win
ISO](https://github.com/virtio-win/virtio-win-pkg-scripts/blob/master/README.md).
At the "Where would you like to install Windows?" screen, click "Load Driver"
then select the appropriate driver as described in [How to Install virtio
Drivers on KVM-QEMU Windows Virtual
Machines](https://linuxhint.com/install_virtio_drivers_kvm_qemu_windows_vm/).


### Bypass Hardware Checks

If the guest does not satisfy the [Windows 11 System
Requirements](https://support.microsoft.com/topic/86c11283-ea52-4782-9efd-7674389a7ba3),
you can [bypass the checks](https://www.bleepingcomputer.com/news/microsoft/how-to-bypass-the-windows-11-tpm-20-requirement/) by:

1. Press <kbd><kbd>Shift</kbd>-<kbd>F10</kbd></kbd> to open Command Prompt.
2. Run `regedit`.
3. Create key `HKEY_LOCAL_MACHINE\SYSTEM\Setup\LabConfig` with one or more of
   the following DWORD values:
   - `BypassRAMCheck` set to 1 to skip memory size checks.
   - `BypassSecureBootCheck` set to 1 to skip SecureBoot checks.
   - `BypassTPMCheck` set to 1 to skip TPM 2.0 checks.
4. Close `regedit`.
5. If the "This PC can't run Windows 11" screen is displayed, press the back
   button.
6. Proceed with installation as normal.

Be aware that Windows 11 is not supported in this scenario and doing so may
prevent some features from working.


### virtio-win Drivers

Drivers for VirtIO devices can be installed by running the
virtio-win-drivers-installer,
[`virtio-win-gt-x64.msi`](https://fedorapeople.org/groups/virt/virtio-win/direct-downloads/latest-virtio/virtio-win-gt-x64.msi)
([Source](https://github.com/virtio-win/virtio-win-guest-tools-installer)),
available on the virtio-win ISO) or by using Device Manager to search for
device drivers on the virtio-win ISO.

The memory ballooning service is installed by virtio-win-drivers-installer.
To install it manually (for troubleshooting or other purposes):

1. Copy `blnsrv.exe` from virtio-win.iso to somewhere permanent (since install
   command defines service using current location of exe).
2. Run `blnsrv.exe -i` as Administrator
3. Reboot (Necessary, per [Bug 612801](https://bugzilla.redhat.com/612801))

<!--
TODO: `vportXpY` are `VIOSerialPort` devices for channels.  Do they need a driver?
-->

Note that the virtio-win-drivers-installer does not currently support Windows
11/Server 2022 ([Bug 1995479](https://bugzilla.redhat.com/1995479)).  However,
it appears to work correctly for me.  It also does not support Windows 7 and
earlier
([#9](https://github.com/virtio-win/virtio-win-guest-tools-installer/issues/9)).
For these systems, the drivers must be installed manually.


#### virtio-fs

To use virtio-fs for file sharing, in addition to installing the `viofs`
driver, complete the following steps (based on a comment by
@FailSpy](https://github.com/virtio-win/kvm-guest-drivers-windows/issues/126#issuecomment-667432487)):

1. Install [WinFSP](https://github.com/billziss-gh/winfsp/releases).
2. Copy `winfsp-x64.dll` from `C:\Program Files (x86)\WinFSP\bin` to
   `C:\Program Files\Virtio-Win\VioFS`.
3. Ensure the `VirtioFSService` created by virtio-win-drivers-installer is
   stopped and has Startup Type: Manual or Disabled.  (Enabling this service
   would work, but would make shared files [only accessible to elevated
   processes](https://github.com/virtio-win/kvm-guest-drivers-windows/issues/126#issuecomment-678895188).
4. Create a scheduled task to run `virtiofs.exe` at logon using the following
   PowerShell:
   ```pwsh
$action = New-ScheduledTaskAction -Execute 'C:\Program Files\Virtio-Win\VioFS\virtiofs.exe'
$trigger = New-ScheduledTaskTrigger -AtLogon
$principal = New-ScheduledTaskPrincipal 'NT AUTHORITY\SYSTEM'
$settings = New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -ExecutionTimeLimit 0
$task = New-ScheduledTask -Action $action -Principal $principal -Trigger $trigger -Settings $settings
Register-ScheduledTask Virtio-FS -InputObject $task
   ```


### QEMU Guest Agent

The [QEMU Guest Agent](https://wiki.libvirt.org/page/Qemu_guest_agent) can be
used to [coordinate snapshot, suspend, and shutdown operations with the
guest](https://access.redhat.com/documentation/en-us/red_hat_enterprise_linux/6/html/virtualization_administration_guide/sect-qemu_guest_agent-running_the_qemu_guest_agent_on_a_windows_guest#sec-libvirt_commands_withguest_agent_Windows_guests),
including post-resume [RTC synchronization](#rtc-synchronization).  Install it
by running
[`qemu-ga-x86_64.msi`](https://fedorapeople.org/groups/virt/virtio-win/direct-downloads/latest-qemu-ga/qemu-ga-x86_64.msi)
(available in the `guest-agent` directory of the virtio-win ISO).


### QXL Driver

If the virtual machine is configured with QXL graphics instead of
`virtio-vga`, as discussed in the [Video section](#video), a QXL driver should
be installed. For Windows 8 and later, install the [QXL-WDDM-DOD
driver](https://www.spice-space.org/download/windows/qxl-wddm-dod/)
([Source](https://gitlab.freedesktop.org/spice/win32/qxl-wddm-dod)).  On
Windows 7 and earlier, the [QXL
driver](https://www.spice-space.org/download/windows/qxl/qxl-0.1-24/)
([Source](https://gitlab.freedesktop.org/spice/win32/qxl)) can be used.  The
driver can be installed from the linked MSI, or from the `qxldod`/`qxl`
directory of the virtio-win ISO.


### SPICE Agent

For clipboard sharing and display size changes, install the [SPICE
Agent](https://www.spice-space.org/download/windows/vdagent/)
([Source](https://gitlab.freedesktop.org/spice/win32/vd_agent)).

Note: Some users have reported problems on Windows 11
([spice/win32#11](https://gitlab.freedesktop.org/spice/win32/spice-nsis/-/issues/16)).
However, it has been working without issue for me.


### SPICE WebDAV Daemon

To use [SPICE folder
sharing](https://www.spice-space.org/spice-user-manual.html#_folder_sharing),
install the [SPICE WebDAV
daemon](https://www.spice-space.org/download/windows/spice-webdavd/)
([Source](https://git.gnome.org/browse/phodav/tree/spice)).


### SPICE Guest Tools

Instead of installing the drivers/agents separately, you may prefer to install
the [SPICE Guest
Tools](https://www.spice-space.org/download/windows/spice-guest-tools/spice-guest-tools-latest.exe)
([Source](https://gitlab.freedesktop.org/spice/win32/spice-nsis)) which
bundles the [virtio-win Drivers](#virtio-win-drivers), [QXL
Driver](#qxl-driver), and [SPICE Agent](#spice-agent) into a single installer.

**Warning:** It does not include the [QEMU Guest Agent](#qemu-guest-agent) and
is several years out of date at the time of this writing (last updated on
2018-01-04 as of 2021-12-05).


### QEMU Guest Tools

Another alternative to installing drivers/agents separately is to install the
[QEMU Guest
Tools](https://fedorapeople.org/groups/virt/virtio-win/direct-downloads/latest-virtio/virtio-win-guest-tools.exe)
([Source](https://github.com/virtio-win/virtio-win-guest-tools-installer))
which bundles the [virtio-win Drivers](#virtio-win-drivers), [QXL
Driver](#qxl-driver), [SPICE Agent](#spice-agent), and [QEMU Guest
Agent](#qemu-guest-agent) into a single installer.
`virtio-win-guest-tools.exe` is available in the virtio-win ISO.


## Post-Installation Tasks

### Remove CD-ROMs

Once Windows is installed, one or both CD-ROM drives can be removed.  If both
are removed, the SATA Controller may also be removed.


### virtio-scsi CD-ROM

For a low-overhead CD-ROM drive, a `virtio-scsi` drive can be added by adding
a VirtIO SCSI controller (if one is not already present) then a CD-ROM on the
SCSI bus.


### Defragment and Optimize Drives

If [discard was enabled](#discard) for the virtual disk, [Defragment and
Optimize
Drives](https://support.microsoft.com/windows/defragment-your-windows-10-pc-048aefac-7f1f-4632-d48a-9700c4ec702a)
in the Windows guest should show the drive with media type "[Thin provisioned
drive](https://docs.microsoft.com/windows-hardware/drivers/storage/thin-provisioning)"
(or "SSD", see below).  It may be useful to configure a disk optimization
schedule to trim/discard unused space in the disk image.

Jared Epp also informed me of an incompatibility between the virtio drivers
and `defrag` in Windows 10 and 11
([virtio-win/kvm-guest-drivers-windows#666](https://github.com/virtio-win/kvm-guest-drivers-windows/issues/666))
which causes defragment and optimize to take a long time and write a lot of
data (the entire image?).  [A workaround suggested by Pau
Rodriguez-Estivill](https://github.com/virtio-win/kvm-guest-drivers-windows/issues/666#issuecomment-991618301=)
is to set `rotation_rate=1` (for [SSD
detection](https://lists.gnu.org/archive/html/qemu-devel/2017-10/msg00698.html))
and `discard_granularity=0` (to change the [MODE PAGE POLICY to
"Obsolete"](https://www.seagate.com/files/staticfiles/support/docs/manual/Interface%20manuals/100293068j.pdf#page=500)?
I don't understand.  See [@prodrigestivill's
comment](https://github.com/virtio-win/kvm-guest-drivers-windows/issues/666#issuecomment-994997355=)).
Rather than adding `<qemu:arg>`, it may be possible to set these values using
`<disk>` `<target rotation_rate="1">` (as in [BZ
1498955](https://bugzilla.redhat.com/show_bug.cgi?id=1498955#c18)) and
`<disk>` `<blockio discard_granularity="0">` (as in [the libvirt patch which
added
it](https://listman.redhat.com/archives/libvir-list/2020-June/203863.html)). I
have not tested this workaround, preferring instead to disable scheduled
defrag.


## Additional Resources

* [QEMU: Preparing a Windows Guest on ArchWiki](https://wiki.archlinux.org/index.php/QEMU#Preparing_a_Windows_guest)
* [libvirt: Domain XML format](https://libvirt.org/formatdomain.html)
* [Tuning KVM](https://www.linux-kvm.org/page/Tuning_KVM)


## ChangeLog

### 2022-05-09

* Add link to [Chris
  Siebenmann's post about workarounds for snapshots of libvirt-based
  VMs](https://utcc.utoronto.ca/~cks/space/blog/linux/LibvirtUEFISnapshots).
* Note that swtpm is now packaged for Debian.

### 2022-05-06

* Discuss Windows licensing limits on sockets in CPU Topology section, thanks
  to Jared Epp.
* Discuss slow operation and excessive writes performed by defrag on Windows
  10 and 11, also thanks to Jared Epp.
* Add Memory Size section to note minimum and maximum size limits for
  different Windows editions.
* Add quote from Paolo Bonzini about virtio-blk use for high-performance.

### 2022-03-19

* Fix broken link to [my example libvirt domain XML]({% post_url
  2021-12-10-windows-11-guest-virtio-libvirt %}win11.xml).  Thanks to Peter
  Greenwood for notifying me.
* Rewrite the "Wayland Keyboard Inhibit" section to improve clarity.

### 2022-01-13

* Recommend `virtio-vga` with the `viogpudo` driver instead of QXL with the
  `qxldod` or `qxl` driver.
