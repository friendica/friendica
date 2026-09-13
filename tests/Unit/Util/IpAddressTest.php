<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\Unit\Util;

use Friendica\Util\IpAddress;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class IpAddressTest extends TestCase
{
	public static function nonPublicProvider(): array
	{
		return [
			// IPv4
			'unspecified'      => ['0.0.0.0'],
			'this network'     => ['0.1.2.3'],
			'loopback'         => ['127.0.0.1'],
			'loopback obscure' => ['127.1.2.3'],
			'private 10'       => ['10.1.2.3'],
			'private 172 low'  => ['172.16.0.1'],
			'private 172 high' => ['172.31.255.254'],
			'private 192.168'  => ['192.168.1.1'],
			'cgnat'            => ['100.64.0.1'],
			'link local'       => ['169.254.1.1'],
			'cloud metadata'   => ['169.254.169.254'],
			'ietf protocol'    => ['192.0.0.8'],
			'benchmarking'     => ['198.19.0.1'],
			'documentation'    => ['192.0.2.1'],
			'multicast'        => ['239.255.255.250'],
			'reserved'         => ['240.0.0.1'],
			'broadcast'        => ['255.255.255.255'],
			// IPv6
			'v6 unspecified'      => ['::'],
			'v6 loopback'         => ['::1'],
			'v6 unique local'     => ['fd00::1'],
			'v6 unique local fc'  => ['fc00::1'],
			'v6 link local'       => ['fe80::1'],
			'v6 link local zoned' => ['fe80::1%eth0'],
			'v6 multicast'        => ['ff02::1'],
			'v6 documentation'    => ['2001:db8::1'],
			'v6 discard'          => ['100::1'],
			// IPv6 with an embedded IPv4 address
			'v4 mapped loopback' => ['::ffff:127.0.0.1'],
			'v4 mapped private'  => ['::ffff:192.168.0.1'],
			'v4 mapped metadata' => ['::ffff:169.254.169.254'],
			'nat64 loopback'     => ['64:ff9b::127.0.0.1'],
			'6to4 private'       => ['2002:c0a8:0001::1'],
			// Not an address at all
			'empty'            => [''],
			'hostname'         => ['friendi.ca'],
			'garbage'          => ['not an ip'],
			'decimal notation' => ['2130706433'],
		];
	}

	#[DataProvider('nonPublicProvider')]
	public function testNonPublicAddressesAreRejected(string $address): void
	{
		self::assertTrue(IpAddress::isNonPublic($address), $address . ' should not be reachable');
	}

	/** Ensures public addresses remain reachable. */
	public static function publicProvider(): array
	{
		return [
			'public v4'         => ['193.99.144.80'],
			'public v4 low'     => ['1.1.1.1'],
			'public v4 8.8.8.8' => ['8.8.8.8'],
			// Boundaries of blocked ranges.
			'below 10/8'             => ['9.255.255.255'],
			'above 10/8'             => ['11.0.0.0'],
			'below 172.16/12'        => ['172.15.255.255'],
			'above 172.16/12'        => ['172.32.0.0'],
			'below 192.168/16'       => ['192.167.255.255'],
			'above 192.168/16'       => ['192.169.0.0'],
			'below 100.64/10'        => ['100.63.255.255'],
			'above 100.64/10'        => ['100.128.0.0'],
			'below 169.254/16'       => ['169.253.255.255'],
			'above 169.254/16'       => ['169.255.0.0'],
			'below multicast'        => ['223.255.255.255'],
			'below 192.0.0/24'       => ['191.255.255.255'],
			'above 192.0.2/24'       => ['192.0.3.1'],
			'public v6'              => ['2a01:4f8:c17:b8f::1'],
			'public v6 dns'          => ['2606:4700:4700::1111'],
			'v4 mapped public'       => ['::ffff:8.8.8.8'],
			'nat64 public'           => ['64:ff9b::8.8.8.8'],
			'6to4 public'            => ['2002:0808:0808::1'],
			'above documentation v6' => ['2001:db9::1'],
		];
	}

	#[DataProvider('publicProvider')]
	public function testPublicAddressesAreAccepted(string $address): void
	{
		self::assertFalse(IpAddress::isNonPublic($address), $address . ' should be reachable');
	}
}
