<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Util;

/** Classifies IP addresses without external dependencies. */
final class IpAddress
{
	/** @var array<string, string[]> Non-public CIDR ranges by address family. */
	private const NON_PUBLIC_RANGES = [
		'v4' => [
			'0.0.0.0/8',          // "This network" (RFC 1122)
			'10.0.0.0/8',         // Private (RFC 1918)
			'100.64.0.0/10',      // Carrier grade NAT (RFC 6598)
			'127.0.0.0/8',        // Loopback (RFC 1122)
			'169.254.0.0/16',     // Link local, includes the cloud metadata service (RFC 3927)
			'172.16.0.0/12',      // Private (RFC 1918)
			'192.0.0.0/24',       // IETF protocol assignments (RFC 6890)
			'192.0.2.0/24',       // Documentation (RFC 5737)
			'192.168.0.0/16',     // Private (RFC 1918)
			'198.18.0.0/15',      // Benchmarking (RFC 2544)
			'198.51.100.0/24',    // Documentation (RFC 5737)
			'203.0.113.0/24',     // Documentation (RFC 5737)
			'224.0.0.0/4',        // Multicast (RFC 5771)
			'240.0.0.0/4',        // Reserved, includes the broadcast address (RFC 1112)
		],
		'v6' => [
			'::/128',             // Unspecified (RFC 4291)
			'::1/128',            // Loopback (RFC 4291)
			'100::/64',           // Discard only (RFC 6666)
			'2001:db8::/32',      // Documentation (RFC 3849)
			'fc00::/7',           // Unique local (RFC 4193)
			'fe80::/10',          // Link local (RFC 4291)
			'ff00::/8',           // Multicast (RFC 4291)
		],
	];

	/** @var array<string, int> IPv6 CIDR ranges mapped to their embedded IPv4 byte offset. */
	private const EMBEDDED_IPV4_RANGES = [
		'::ffff:0:0/96' => 12, // IPv4 mapped (RFC 4291)
		'64:ff9b::/96'  => 12, // NAT64 (RFC 6052)
		'2002::/16'     => 2,  // 6to4 (RFC 3056)
	];

	/** Checks whether an address is invalid or non-public. */
	public static function isNonPublic(string $address): bool
	{
		// An IPv6 address may carry a zone index ("fe80::1%eth0") which inet_pton rejects
		$address = explode('%', $address, 2)[0];

		$binary = @inet_pton($address);
		if ($binary === false) {
			return true;
		}

		$family = strlen($binary) === 4 ? 'v4' : 'v6';

		foreach (self::NON_PUBLIC_RANGES[$family] as $range) {
			if (self::matchesCidr($binary, $range)) {
				return true;
			}
		}

		if ($family === 'v6') {
			foreach (self::EMBEDDED_IPV4_RANGES as $range => $offset) {
				if (self::matchesCidr($binary, $range)) {
					return self::isNonPublic(inet_ntop(substr($binary, $offset, 4)));
				}
			}
		}

		return false;
	}

	/**
	 * @param string $binary Packed in_addr representation, as returned by inet_pton()
	 * @param string $cidr   Range in CIDR notation
	 */
	private static function matchesCidr(string $binary, string $cidr): bool
	{
		[$subnet, $bits] = explode('/', $cidr);

		$subnetBinary = inet_pton($subnet);
		if ($subnetBinary === false || strlen($subnetBinary) !== strlen($binary)) {
			return false;
		}

		$bits      = (int) $bits;
		$fullBytes = intdiv($bits, 8);
		$extraBits = $bits % 8;

		if ($fullBytes > 0 && strncmp($binary, $subnetBinary, $fullBytes) !== 0) {
			return false;
		}

		if ($extraBits === 0) {
			return true;
		}

		$mask = ~((1 << (8 - $extraBits)) - 1) & 0xFF;

		return (ord($binary[$fullBytes]) & $mask) === (ord($subnetBinary[$fullBytes]) & $mask);
	}
}
