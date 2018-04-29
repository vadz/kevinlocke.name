---
layout: post
date: 2018-04-28 19:34:59-06:00
title: Raspberry Pi SD Card Storage Considerations
description: "A collection of research related to SD Card hardware, \
partitioning, and filesystems for use with the Raspberry Pi.  Most results \
are generally applicable to MMC storage for embedded computing."
tags: [ linux, sysadmin ]
---

After a recent SD Card failure on a [Raspberry
Pi](https://www.raspberrypi.org/), I decided to research storage devices and
configurations to improve performance and device lifetime.  This post contains
the results of that research.

<!--more-->

## SD Card Types and Reliability

As a result of an [enlightening comment chain on Hacker
News about SD Card reliability](https://news.ycombinator.com/item?id=16776344)
I started researching common NAND flash storage technologies for representing
bits in flash cells.  In decreasing order of cost/reliability:

Single-Level Cell (SLC)
: Stores one bit per cell.

Multi-Level Cell (MLC)
: Stores two bits per cell.

Triple-Level Cell (TLC)
: Stores three bits per cell.

Due to the high cost of SLC, there are some intermediate technologies which
use MLC flash cells with firmware that only stores one bit per cell instead of
two.  This results in better reliability and longevity than traditional MLC at
cheaper cost than SLC:

[advancedMLC (aMLC)](http://www.atpinc.com/downloadlog/d9527296dd36b46c)
: ATP Electronics name for MLC with one bit per cell.

[SLC Lite (pSLC)](https://na.industrial.panasonic.com/blog/sd-cards-memory-types-explained)
: Panasonic name for MLC with one bit per cell.

For my current project I decided to use an 8GB ATP aMLC card (AF8GSD3A or
AF8GUD3A with an adapter - both are available from
[Digi-Key](https://www.digikey.com/product-detail/en/atp-electronics-inc/AF8GUD3A-OEM/AF8GUD3A-OEM-ND/5361063),
[Arrow](https://www.arrow.com/en/products/af8gud3a-waaxx/atp-electronics), and
other suppliers).

## Logical Volumes and Filesystems

For my current project, power failures and hard resets are not uncommon.  I
need a storage configuration which performs well on an SD Card and is
reasonably resistant to corruption after power failure.  [eMMC/SSD File System
Tuning Methodology (2013) by Cogent Embedded,
Inc.](https://elinux.org/images/b/b6/EMMC-SSD_File_System_Tuning_Methodology_v1.0.pdf)
is a wonderful source of information for this purpose.

### F2FS

The most performant configuration appears to be a single partition with
[F2FS](https://f2fs.wiki.kernel.org/), a filesystem which is optimized for
flash storage.  Unfortunately, as noted in the "Power-Fail Tolerance" section,
F2FS is unsuitable in the presence of power failure.  Although it now includes
an fsck utility, ["[the] initial version of the tool does not fix any
inconsistency"](https://git.kernel.org/pub/scm/linux/kernel/git/torvalds/linux.git/tree/Documentation/filesystems/f2fs.txt?id=v4.16#n324).

### BTRFS

[lockheed on Unix SE provided a corruption-resistant configuration using
BTRFS RAID](https://unix.stackexchange.com/a/186954).  This approach looks
promising, with the adjustment noted in the comments to use the BTRFS DUP
Profile instead of RAID1.  As I understand it, the primary difference is that
[the BTRFS DUP profile will only read one copy when not
corrupted](https://unix.stackexchange.com/a/439909) and that the [distribution
of the data copies on disk may
differ](https://www.reddit.com/r/btrfs/comments/7es9bx/can_you_use_dup_with_2_disks_or_more/dq7r3xt).
However, if the SD Card deduplicates data internally this approach will not
actually result in any redundancy (as noted in the [DUP Profiles on a Single
Device section of the mkfs.btrfs man
page](https://btrfs.wiki.kernel.org/index.php/Manpage/mkfs.btrfs#DUP_PROFILES_ON_A_SINGLE_DEVICE)).
I do not think SD cards currently deduplicate data internally, but this is a
significant concern.

Note that BTRFS DUP/RAID can be useful because the filesystem checksums
indicate corruption.  [Using generic software RAID1 across partitions would
not reduce corruption](https://superuser.com/a/310737) because it does not
have a way to indicate which read is bad, so it was not considered.

### ext4

ext4 is a very widely deployed filesystem and the default of most Raspberry Pi
distributions.  "eMMC/SSD File System Tuning Methodology" notes that ext4
tolerated power failures quite well, while BTRFS did not.  This result may
have changed due to BTRFS improvements since 2013 and with the use of DUP (or
RAID1 across partitions) as described above.  It may also have different
results when using the [ext4 `metadata_csum` feature for metadata
checksums](https://ext4.wiki.kernel.org/index.php/Ext4_Metadata_Checksums).
However, I have not conducted a comparison.

There are also other application-specific features to consider between ext4
and BTRFS.  For example, BTRFS supports filesystem snapshots, subvolumes, and
compression.  Also, ext4 is built-in to the Raspberry Pi Foundation-provided
kernel builds while BTRFS is not, thus necessitating an initramfs to boot from
a BTRFS root filesystem (see
[raspberrypi/linux#1550](https://github.com/raspberrypi/linux/issues/1550),
[raspberrypi/linux#1761](https://github.com/raspberrypi/linux/issues/1761)).
Keeping such an initramfs updated to match the kernel is also complicated on
the Pi and requires custom scripting or manual filename changes on update (see
[raspberrypi/firmware#608](https://github.com/raspberrypi/firmware/issues/608)
and
[RPi-Distro/firmware#1](https://github.com/RPi-Distro/firmware/issues/1#issuecomment-292915191)
\- note that the referenced `rpi-initramfs-tools` package has not yet been
created).

Conclusion: Use ext4 with `metadata_csum` or BTRFS with DUP profile for
metadata (and data, if warranted) based on application-specific
considerations and willingness to deal with initramfs issues.

### Read-Only Filesystems

Another option for reducing or mitigating corruption is to use a read-only
filesystem (or a writable filesystem mounted read-only).  This can be done on
a per-directory basis (e.g. read-only root with read-write `/var`) or using an
overlay filesystem such as [unionfs](http://unionfs.filesystems.org/) with
either read-write partitions or tmpfs for ephemeral information.  However,
this adds configuration complexity in addition to more complicated failure
scenarios.

## Partition and Filesystem Alignment

For optimal performance and lifetime, partitions and filesystem structures
should be aligned to the [erase
block](https://flashdba.com/2014/06/20/understanding-flash-blocks-pages-and-program-erases/)
size.  This size is occasionally listed on the spec sheet for the SD card.
More commonly the
[`preferred_erase_size`](https://git.kernel.org/pub/scm/linux/kernel/git/torvalds/linux.git/tree/Documentation/mmc/mmc-dev-attrs.txt)
(or
[`discard_granularity`](https://git.kernel.org/pub/scm/linux/kernel/git/torvalds/linux.git/tree/Documentation/block/queue-sysfs.txt#n23))
reported for the device in sysfs could be used.  It is also often possible to
[use `flashbench` to empirically determine the erase block
size](https://superuser.com/a/992088) by measuring the device performance.

For the ext4 filesystem, there may be benefits to [configuring the stride
and/or stripe width to match the erase block
size](https://wiki.gentoo.org/wiki/SSD#Formatting).  [Various methods for
determining the ext4 stride and/or stripe size based on the flash
media](https://thelastmaimou.wordpress.com/2013/05/04/magic-soup-ext4-with-ssd-stripes-and-strides/)
exist.  I have insufficient understanding of the implications of stride and
stripe size settings to know whether this is a good idea and haven't seen any
benchmarks to compare performance.

## I/O Schedulers

[Complete Fairness Queueing
(CFQ)](https://git.kernel.org/pub/scm/linux/kernel/git/torvalds/linux.git/tree/Documentation/block/cfq-iosched.txt?id=v4.16)
has been the Linux default I/O scheduler [since
2.6.18](https://git.kernel.org/pub/scm/linux/kernel/git/torvalds/linux.git/commit/?id=b17fd9bceb99610f6dc7998c9a4ed6b71520be2b).
It is a good default, and it provides [some behavior optimizations on
non-rotational
media](https://git.kernel.org/pub/scm/linux/kernel/git/torvalds/linux.git/tree/Documentation/block/cfq-iosched.txt?id=v4.16#n252).
However, both "eMMC/SSD File System Tuning Methodology" and [Phoronix Linux
3.16: Deadline I/O Scheduler Generally Leads With A
SSD](https://www.phoronix.com/scan.php?page=article&item=linux_316_iosched&num=1)
found that both `noop` and `deadline` outperformed `cfq`.  A caveat is that
neither `deadline` nor `noop` support I/O prioritization (e.g.
[`ionice`](https://manpages.debian.org/unstable/util-linux/ionice.1.en.html)).
If prioritization is not required, some performance can be gained by changing
the I/O scheduler.  This change can be accomplished to all non-rotational
media by placing the following content in a udev rule file (e.g.
`/etc/udev/rules.d/60-nonrotational-iosched.rules`):

    ACTION=="add|change", KERNEL=="mmcblk[0-9]", ATTR{queue/scheduler}="deadline"
    ACTION=="add|change", KERNEL=="sd[a-z]", ATTR{queue/rotational}=="0", ATTR{queue/scheduler}="deadline"
