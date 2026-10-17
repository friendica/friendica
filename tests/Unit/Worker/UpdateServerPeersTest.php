<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Worker;

use Friendica\Worker\UpdateServerPeers;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class UpdateServerPeersTest extends TestCase
{
	/**
	 * @return array<string, array{mixed}>
	 */
	public static function validPeers(): array
	{
		return [
			'hostname'         => ['example.org'],
			'subdomain'        => ['social.example.org'],
			'punycode'         => ['xn--bcher-kva.example'],
			'unicode hostname' => ['bücher.example'],
			'with port'        => ['example.org:8080'],
		];
	}

	/**
	 * @return array<string, array{mixed}>
	 */
	public static function invalidPeers(): array
	{
		return [
			'url with schema'      => ['https://example.org'],
			'schema without slash' => ['https:example.org'],
			'bare schema'          => ['https:'],
			'other scheme'         => ['at:'],
			'empty'                => [''],
			'whitespace'           => ['exa mple.org'],
			'control character'    => ["merveill\x04s.town"],
			'null byte'            => ["example\x00.org"],
			'path'                 => ['example.org/path'],
			'userinfo'             => ['user@example.org'],
			'integer'              => [123],
			'null'                 => [null],
			'array'                => [['example.org']],
		];
	}

	/**
	 * @param mixed $peer
	 */
	#[DataProvider('validPeers')]
	public function testValidPeerIsAccepted($peer): void
	{
		$this->assertTrue(UpdateServerPeers::isValidPeer($peer));
	}

	/**
	 * A peer that is not a bare hostname must not reach `new Uri('https://' . $peer)`:
	 * it either throws a MalformedUriException or is parsed as a server named "https".
	 *
	 * @param mixed $peer
	 */
	#[DataProvider('invalidPeers')]
	public function testInvalidPeerIsRejected($peer): void
	{
		$this->assertFalse(UpdateServerPeers::isValidPeer($peer));
	}
}
