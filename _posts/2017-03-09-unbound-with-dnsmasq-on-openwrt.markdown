---
layout: post
date: 2017-03-09 22:43:26-07:00
updated: 2021-09-08 11:23:08-06:00
title: Unbound with Dnsmasq on OpenWrt
description: "A script and walkthrough for installing the Unbound DNS resolver \
with Dnsmasq on OpenWrt."
tags: [ dns, openwrt, sysadmin ]
---
This post describes one way to set up [Unbound](https://www.unbound.net/) as a
validating, recursive, caching DNS resolver on a router running
[OpenWrt](https://openwrt.org/).  The setup includes forwarding to
[Dnsmasq](http://www.thekelleys.org.uk/dnsmasq/doc.html) for local names.

**IMPORTANT:** This post is of historical interest only.  OpenWrt 18.06 added
support for UCI-based configuration of Unbound and OpenWrt 21.02 added support
for the `dhcp_link` option.  Configuring Unbound as described in the [Parallel
dnsmasq section of
README.md](https://github.com/openwrt/packages/blob/master/net/unbound/files/README.md#parallel-dnsmasq)
should now be sufficient.

**History:** This post was initially written as the [Unbound HOWTO on the old
OpenWrt wiki](https://wiki.openwrt.org/doc/howto/unbound) for Chaos Calmer
(15.05.1).  It was moved to [Example 2 on the Unbound services page on the new
wiki](https://openwrt.org/docs/guide-user/services/dns/unbound?rev=1576631346#example_2_-_parallel_unbound_primary_and_dnsmasq_only_local)
and updated to work on LEDE 17.01 and OpenWrt 18.06, then subsequently removed
when it became too outdated.

<!--more-->

## Why Unbound?

By default, OpenWrt uses Dnsmasq for DNS forwarding (and DHCP serving).  This
works well for most cases.  One notable issue is that it requires a separate
recursive DNS resolver, usually provided by an ISP or public DNS provider, to
resolve requests.  This can be a problem due to performance, hijacking,
trustworthiness, misconfiguration, lack of DNSSEC support, or many other
reasons.  Running a recursive resolver, such as Unbound, is one solution to
those problems.

## Prerequisites

The following steps assume that OpenWrt has been installed on a device and
configured as desired, including the network configuration.  If not, consult
the [Quick Start Guide](https://openwrt.org/docs/guide-quick-start/begin_here)
for instructions.

The later steps require accessing the device using a terminal.  See
[SSH Access for
Newcomers](https://openwrt.org/docs/guide-quick-start/sshadministration).


## Installation and Configuration

The installation and configuration instructions below are written in the form
of a shell script for precision and clarity to a technical audience.  The
script can be saved and executed, although it is recommended to run commands
and make edits individually both for better understanding and because the
script is written to favor readability and clarity of instruction at the cost
of thorough error handling and robustness.

Note that the choice of port 53535 is arbitrary.  Similar tutorials often use
5353 or 5355 (which can conflict with MDNS).

``` sh
#!/bin/sh
# Steps to configure unbound on OpenWrt with dnsmasq for dynamic DNS
# Note:  Clarity of instruction is favored over script speed or robustness.
#        It is not idempotent.

# Show commands as executed, error out on failure or undefined variables
set -eux

# Note the local domain (Network -> DHCP & DNS -> General Settings)
lan_domain=$(uci get 'dhcp.@dnsmasq[0].domain')

# Note the LAN network address (Network -> Interfaces -> LAN -> IPv4 address)
lan_address=$(uci get network.lan.ipaddr)

# Update the package list (System -> Software -> Update lists)
opkg update

# Install unbound (System -> Software -> Find package: unbound -> Install)
opkg install unbound # Ignore error that it can't listen on port 53

# Move dnsmasq to port 53535 where it will still serve local DNS from DHCP
# Network -> DHCP & DNS -> Advanced Settings -> DNS server port to 53535
uci set 'dhcp.@dnsmasq[0].port=53535'

# Configure dnsmasq to send a DNS Server DHCP option with its LAN IP
# since it does not do this by default when port is configured.
uci add_list "dhcp.lan.dhcp_option=option:dns-server,$lan_address"

# Configure Unbound from unbound.conf, instead of generating it from UCI
# Services -> Recursive DNS -> Manual Conf
uci set 'unbound.@unbound[0].manual_conf=1'

# Save & Apply (will restart dnsmasq, DNS unreachable until unbound is up)
uci commit

# Allow unbound to query dnsmasq on the loopback address
# by adding 'do-not-query-localhost: no' to server section
sed -i '/^server:/a\	do-not-query-localhost: no' /etc/unbound/unbound.conf

# Convert the network address to a Reverse DNS domain
# https://en.wikipedia.org/wiki/Reverse_DNS_lookup
case $(uci get network.lan.netmask) in
    255.255.255.0) ip_to_rdns='\3.\2.\1.in-addr.arpa' ;;
    255.255.0.0) ip_to_rdns='\2.\1.in-addr.arpa' ;;
    255.0.0.0) ip_to_rdns='\1.in-addr.arpa' ;;
    *) echo 'More complex rDNS configuration required.' >&2 ; exit 1 ;;
esac
lan_rdns_domain=$(echo "$lan_address" | \
    sed -E "s/^([0-9]+)\.([0-9]+)\.([0-9]+)\.([0-9]+)$/$ip_to_rdns/")

# Check if the local addresses are in a private address range (very common)
case "$lan_address" in
    0.*) ip_to_priv_rdns='0.in-addr.arpa.' ;;
    10.*) ip_to_priv_rdns='10.in-addr.arpa.' ;;
    169.254.*) ip_to_priv_rdns='254.169.in-addr.arpa.' ;;
    172.1[6-9].*|172.2[0-9].*|172.3[0-1].*) ip_to_priv_rdns='\2.172.in-addr.arpa.' ;;
    192.0.2.*) ip_to_priv_rdns='2.0.192.in-addr.arpa.' ;;
    192.168.*) ip_to_priv_rdns='168.192.in-addr.arpa.' ;;
    198.51.100.*) ip_to_priv_rdns='100.51.198.in-addr.arpa.' ;;
    203.0.113.*) ip_to_priv_rdns='113.0.203.in-addr.arpa.' ;;
esac
if [ -n "${ip_to_priv_rdns-}" ] ; then
    # Disable default "does not exist" reply for private address ranges
    # by adding 'local-zone "$lan_domain" nodefault' to server section
    # Note that this must be on RFC 1918/5735/5737 boundary,
    # this is only equal to $lan_rdns_domain when netmask covers whole range.
    lan_priv_rdns_domain=$(echo "$lan_address" | \
        sed -E "s/^([0-9]+)\.([0-9]+)\.([0-9]+)\.([0-9]+)$/$ip_to_priv_rdns/")
    sed -i "/^server:/a\	local-zone: \"$lan_priv_rdns_domain\" nodefault"  \
        /etc/unbound/unbound.conf
fi

# Ignore DNSSEC chain of trust for the local domain
# by adding 'domain-insecure: "$lan_domain"' to server section
sed -i "/^server:/a\	domain-insecure: \"$lan_domain\"" /etc/unbound/unbound.conf

# Ignore DNSSEC chain of trust for the local reverse domain
# by adding 'domain-insecure: "$lan_rdns_domain"' to server section
sed -i "/^server:/a\	domain-insecure: \"$lan_rdns_domain\"" /etc/unbound/unbound.conf

# Add a forward zone for the local domain to forward requests to dnsmasq
cat >> /etc/unbound/unbound.conf <<DNS_FORWARD_ZONE
forward-zone:
	name: "$lan_domain"
	forward-addr: 127.0.0.1@53535
DNS_FORWARD_ZONE

# Add a forward zone for the local reverse domain to forward requests to dnsmasq
cat >> /etc/unbound/unbound.conf <<RDNS_FORWARD_ZONE
forward-zone:
	name: "$lan_rdns_domain"
	forward-addr: 127.0.0.1@53535
RDNS_FORWARD_ZONE

# Optionally enable DNS Rebinding protection by uncommenting private-address
# configuration and adding 'private-domain: "$lan_domain"' to server section
sed -E -i \
    -e 's/(# )?private-address:/private-address:/' \
    -e "/^server:/a\	private-domain: \"$lan_domain\"" \
    /etc/unbound/unbound.conf

# Restart (or start) unbound (System -> Startup -> unbound -> Restart)
/etc/init.d/unbound restart
```

The resulting configuration (with defaults and comments removed) should look
something like:

	server:
		do-not-query-localhost: no
		domain-insecure: "0.168.192.in-addr.arpa"
		domain-insecure: "example.local"
		local-zone: "168.192.in-addr.arpa." nodefault
		private-address: 10.0.0.0/8
		private-address: 169.254.0.0/16
		private-address: 172.16.0.0/12
		private-address: 192.168.0.0/16
		private-address: fd00::/8
		private-address: fe80::/10
		private-domain: "example.local"
	forward-zone:
		name: "example.local"
		forward-addr: 127.0.0.1@53535
	forward-zone:
		name: "0.168.192.in-addr.arpa"
		forward-addr: 127.0.0.1@53535

The above [script and configuration are also available as a
Gist](https://gist.github.com/kevinoid/00656e6e4815e3ffe25dabe252e0f1e3).


## Troubleshooting

After completing the above steps, DNS should be working for both local and
global addresses.  If it is not, here are some suggested troubleshooting
steps:

Resolution can be attempted from the OpenWrt system by running `nslookup
openwrt.org 127.0.0.1` and `nslookup openwrt.org 127.0.0.1:53535`.
Unfortunately, the nslookup output does not distinguish between no response
and a negative response, which significantly reduces its usefulness for
debugging.  A much more powerful lookup tool is DiG from the `bind-dig`
package.  To use it run `dig openwrt.org @127.0.0.1`, add `-p 53535` to query
the Dnsmasq port, or add `-x` with an IP in place of the domain to do a
reverse lookup.

### No Response

If Unbound is not responding to any request, try restarting the service with
`/etc/init.d/unbound restart` and checking the system log for
errors `logread | tail`.

### Negative Response for Local Only

If the local domain or addresses result in negative responses, check that they
are resolved correctly by Dnsmasq on port 53535.  If so, check that the domain
appears in `domain-insecure`, `local-zone` (which may be a suffix and must
match a predefined zone), and as a `name` in `stub-zone`.

### Failures for DNSSEC-Secured Domains

If domains which use DNSSEC fail to resolve while other domains work, check
that the system time is correct.  Time skew can cause validation failures.  If
the time is incorrect, check the [NTP client
configuration](https://openwrt.org/docs/guide-user/advanced/ntp_configuration).


## Further Additions

### IPv6

It is relatively straightforward to extend the above configuration for IPv6.
Forward resolution (from local domain to IPv6 address) does not require any
additional changes to Unbound, although it may require configuration changes to
Dnsmasq.  See [IPv6
DNS](https://openwrt.org/docs/guide-user/network/ipv6/ipv6.dns).

To configure reverse DNS for IPv6:

* Determine the rDNS domain from the IPv6 address prefix by reversing the
  nibbles and appending ".ip6.arpa".
* Add `domain-insecure: $lan6_rdns_domain`.
* Add `local-zone: $lan6_rdns_domain nodefault` if it is in a private range
  (be sure to use a [preconfigured
  range](https://nlnetlabs.nl/documentation/unbound/unbound.conf/).
* Add a `stub-zone` with `name: "$lan6_rdns_domain"` as above.

The difficulty of adding IPv6 is that Dnsmasq is compiled without DHCPv6
support and [does not resolve its own name due to
\#17457](https://dev.openwrt.org/ticket/17457) so the value of forwarding IPv6
reverse requests is currently rather limited.  IPv6 address configuration is
also more variable and more difficult to detect.

## Article Changes

### 2018-08-25

* Change `stub-zone`s to `forward-zone`s for compatibility with Dnsmasq 2.79
  and later which "Always return `SERVFAIL` for DNS queries without the
  recursion desired bit set, UNLESS acting as an authoritative DNS server."
  Since Dnsmasq `auth-server` and `auth-zone` are not configurable via UCI, it
  can not be made authoritative without manual configuration so Unbound must
  send RD queries.
