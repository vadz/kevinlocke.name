---
layout: post
date: 2012-12-06 09:11:13-07:00
title: Using Debian-packaged Ruby Gems
description: "A discussion of how to make use of both Debian-packaged and \
non-packaged Ruby Gems on a Debian (and probably Ubuntu) system."
tags: [ debian, ruby, ruby-gems ]
---
Ruby software is commonly distributed as "gems", packages containing Ruby
applications and/or libraries, which can be installed using the
[RubyGems](https://rubygems.org/) package manager, typically run as a command
named `gem`.  On Debian systems, some gems are also available as Debian
packages through the Debian package repositories.  For Ruby developers on
Debian, it is almost inevitable that some gems will be installed through
RubyGems and some will be installed through the Debian package managers (and
possibly some installed through both).  This post discusses some tips for
minimizing the pain of this situation.

<!--more-->

Although the tips in this post have only been tested on Debian, they should be
applicable to Debian-based distributions (such as Ubuntu and its derivatives)
with only minimal changes, if any.

## Why Use RubyGems and Debian Packages?

First, a quick digression:  Why bother using both RubyGems and Debian packages?
Why not choose one and stick with it?  Half of the reason is simple, Debian
packages only exist for a small subset of available gems.  The other half is
not as simple, and indeed it would be possible to avoid installing Debian
packages for gems.  However, using the Debian packages for gems carries the
advantages of using any other Debian package:  Integration-testing, compliance
with Debian Policy (including the FHS), security support from the Debian
Security Team, management using the Debian package managers, bug-tracking
through bugs.debian.org, etc.

For the above reasons, and my personal biases, I'll assume that if there is a
Debian package available for a gem (and for the RubyGems package manager
itself) that the Debian package will be used.  Although, if a newer version of
a gem than the one available as a Debian package is required, it's easy enough
to install that version using RubyGems.

## Informing RubyGems of Debian Packages

In the default installation, RubyGems is not aware of any gems which have been
installed through the Debian package managers.  Although this doesn't cause
any serious problems, since the installation locations are different, it may
result in duplicate copies of gems being installed, causing confusion and
wasting space and bandwidth.  The solution to this problem is very simple,
ensure that RubyGems is installed from a Debian package (either the
[rubygems](https://packages.debian.org/wheezy/rubygems) package for Ruby 1.8 or
by using the Debian package for Ruby 1.9, which includes RubyGems), then
install the
[rubygems-integration](https://packages.debian.org/wheezy/rubygems-integration)
package through a Debian package manager (e.g. `apt-get install
rubygems-integration`).

Once this is done, RubyGems will use already-installed Debian package gems to
satisfy dependencies and won't install a second copy of such gems.  Note
however that this will not, as far as I am aware, cause `gem` to install a
Debian package to satisfy a dependency if one is available.  The user must
first install the Debian package, if one is available, for it to be used.

## Installing to /usr/local

As an administrator, I strongly prefer to keep all non-packaged software in
/usr/local (or user home directories for user-specific software).  This
provides a clear distinction between software/files which I have to keep an
eye on (to maintain, update, clean, remove, and provide security support) and
those which I can defer to their Debian maintainers.  However, there are valid
reasons for keeping the default installation location, some of which are
discussed in [bug
448639](https://bugs.debian.org/cgi-bin/bugreport.cgi?bug=448639), and users
who find these more compelling may freely skip this section.

There are a few ways to change the installation location, called the "gem
path" in the RubyGems documentation, although none of them are without
drawbacks.  Unfortunately, there is no supported way to change the system-wide
default RubyGems installation location, that I am aware of.

### Manually Specify the Installation Dir

The first option is simply to include
`--install-dir=/usr/local/lib/ruby/gems/1.9` whenever `gem` is invoked.  This
could be accomplished with a shell alias, a wrapper script, or a very large
amount of discipline (for single-user systems).

### Set the `GEM_PATH`

Another option is to set the `GEM_PATH` environment variable.  This could be
set system-wide in `/etc/environment` (or `/etc/profile` or `/etc/bash.bashrc`
for bash) or in user logon scripts.

### Modify the RubyGems Source

The most fool-proof option, and the one with the highest maintenance burden,
is to edit the RubyGems source to change the default install location.  This
is relatively simple, although it does require re-editing the source after
each upgrade and comes with some risks for users not familiar with Ruby.

To change the system-wide default installation directory (if the
`rubygems-integration` package is installed), edit
`/usr/lib/ruby/vendor_ruby/rubygems/defaults/operating_system.rb:4` as
follows:

``` ruby
  def default_dir
#    File.join('/', 'var', 'lib', 'gems', Gem::ConfigMap[:ruby_version])
    File.join('/', 'usr', 'local', 'lib', 'ruby', 'gems', Gem::ConfigMap[:ruby_version])
  end
```

If the `rubygems-integration` package is not installed, edit
`/usr/lib/ruby/1.9.1/rubygems/defaults.rb` and change the following lines at
the end of `self.default_dir` as follows:

``` ruby
#    @default_dir ||= File.join(*path)
    @default_dir ||= File.join('/', 'usr', 'local', 'lib', 'ruby', 'gems', ConfigMap[:ruby_version])
```

With those edits made, the default installation directory will be
`/usr/local/lib/ruby/gems/1.9/`.  Remember to re-edit the files after an
upgrading `rubygems-integration` (or `libruby1.9.1` if `rubygems-integration`
is not installed).  As a reminder, it may be useful to place a hold on the
package in the Debian package management system to require explicitly
upgrading the package (e.g. `aptitude hold rubygems-integration`).

## Conclusion

That's it.  Those minor issues aside, everything seems to work quite well.
Enjoy writing Ruby on Debian!
