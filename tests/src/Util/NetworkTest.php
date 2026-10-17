<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Util;

use Dice\Dice;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\DI;
use Friendica\Util\Network;
use PHPUnit\Framework\TestCase;

/**
 * Network utility test class
 */
class NetworkTest extends TestCase
{
	public function testValidUri(): void
	{
		self::assertNotNull(Network::createUriFromString('https://friendi.ca'));
		self::assertNotNull(Network::createUriFromString('magnet:?xs=https%3A%2F%2Ftube.jeena.net%2Flazy-static%2Ftorrents%2F04bec7a8-34de-4847-b080-6ee00c4b3d49-1080-hls.torrent&xt=urn:btih:5def5a24dfa7307e999a0d4f0fcc29c3e2b13be2&dn=My+fediverse+setup+-+I+host+everything+myself&tr=https%3A%2F%2Ftube.jeena.net%2Ftracker%2Fannounce&tr=wss%3A%2F%2Ftube.jeena.net%3A443%2Ftracker%2Fsocket&ws=https%3A%2F%2Ftube.jeena.net%2Fstatic%2Fstreaming-playlists%2Fhls%2F23989f41-e230-4dbf-9111-936bc730bf50%2Fe5905de3-e488-4bb8-a1e8-eb7a53ac24ad-1080-fragmented.mp4'));
		self::assertNotNull(Network::createUriFromString('did:plc:geqiabvo4b4jnfv2paplzcge'));
		self::assertNull(Network::createUriFromString('https://'));
		self::assertNull(Network::createUriFromString(''));
		self::assertNull(Network::createUriFromString(null));
	}

	/**
	 * Test for isValidAtUrl function
	 * Tests valid and invalid AT Protocol URLs
	 */
	public function testIsValidAtUrl(): void
	{
		// Valid AT Protocol URL with proper format at://repo/collection/rkey
		self::assertTrue(Network::isValidAtUrl('at://did:plc:geqiabvo4b4jnfv2paplzcge/app.bsky.feed.post/abc123'));

		// Another valid AT Protocol URL
		self::assertTrue(Network::isValidAtUrl('at://did:key:z6MkhaXgBZDvotDkL5257faWYB3cbRwEeAe1YvyQbxC/app.bsky.actor.profile/self'));

		// HTTP URL (not AT Protocol)
		self::assertFalse(Network::isValidAtUrl('https://example.com'));

		// HTTP URL (valid URL but wrong protocol)
		self::assertFalse(Network::isValidAtUrl('http://test.example.com/profile'));

		// Malformed AT URL - missing parts
		self::assertFalse(Network::isValidAtUrl('at://did:plc:abc/collection'));

		// Malformed AT URL - too many parts
		self::assertFalse(Network::isValidAtUrl('at://did:plc:abc/collection/rkey/extra'));

		// Invalid format - no at:// prefix
		self::assertFalse(Network::isValidAtUrl('did:plc:geqiabvo4b4jnfv2paplzcge/app.bsky.feed.post/abc123'));

		// Empty string
		self::assertFalse(Network::isValidAtUrl(''));

		// Whitespace only
		self::assertFalse(Network::isValidAtUrl('   '));
	}

	/** @var Dice|null */
	private $previousDice;

	protected function tearDown(): void
	{
		// Don't leak the mocked Dice into tests which rely on the DI state left by earlier tests
		if ($this->previousDice !== null) {
			$property = new \ReflectionProperty(DI::class, 'dice');
			$property->setValue(null, $this->previousDice);
			$this->previousDice = null;
		}

		\Mockery::close();

		parent::tearDown();
	}

	private function useEmailDomainConfig(string $allowed, string $disallowed): void
	{
		$config = \Mockery::mock(IManageConfigValues::class);
		$config->shouldReceive('get')->with('system', 'allowed_email')->andReturn($allowed);
		$config->shouldReceive('get')->with('system', 'disallowed_email')->andReturn($disallowed);
		$dice = \Mockery::mock(Dice::class);
		$dice->shouldReceive('create')->with(IManageConfigValues::class)->andReturn($config);

		$property = new \ReflectionProperty(DI::class, 'dice');
		$this->previousDice ??= $property->getValue();

		DI::init($dice, true);
	}

	public function testEmailDomainAllowedWithoutLists(): void
	{
		$this->useEmailDomainConfig('', '');

		self::assertTrue(Network::isEmailDomainAllowed('user@example.org'));
	}

	public function testEmailDomainAllowedList(): void
	{
		$this->useEmailDomainConfig('example.org, *.example.com', '');

		self::assertTrue(Network::isEmailDomainAllowed('user@example.org'));
		self::assertTrue(Network::isEmailDomainAllowed('user@mail.example.com'));
		self::assertFalse(Network::isEmailDomainAllowed('user@evil.com'));
		self::assertFalse(Network::isEmailDomainAllowed('user@example.org.evil.com'));
	}

	public function testEmailDomainDisallowedList(): void
	{
		$this->useEmailDomainConfig('', 'spam.example, *.junk.example');

		self::assertFalse(Network::isEmailDomainAllowed('user@spam.example'));
		self::assertFalse(Network::isEmailDomainAllowed('user@mail.junk.example'));
		self::assertTrue(Network::isEmailDomainAllowed('user@example.org'));
	}

	public function testEmailDomainDisallowedOverridesAllowed(): void
	{
		$this->useEmailDomainConfig('*.edu', 'spam.edu');

		self::assertTrue(Network::isEmailDomainAllowed('user@uni.edu'));
		self::assertFalse(Network::isEmailDomainAllowed('user@spam.edu'));
		self::assertFalse(Network::isEmailDomainAllowed('user@example.org'));
	}

	public function testEmailDomainIsCaseInsensitive(): void
	{
		$this->useEmailDomainConfig('*.Example.ORG', '');

		self::assertTrue(Network::isEmailDomainAllowed('User@Mail.EXAMPLE.org'));
	}

	public function testEmailDomainInvalidAddresses(): void
	{
		$this->useEmailDomainConfig('', '');

		self::assertFalse(Network::isEmailDomainAllowed('no-at-sign'));
		self::assertFalse(Network::isEmailDomainAllowed('user@'));
		self::assertFalse(Network::isEmailDomainAllowed(''));
	}

	public function testEmailDomainUsesLastAtSign(): void
	{
		$this->useEmailDomainConfig('example.org', '');

		self::assertFalse(Network::isEmailDomainAllowed('example.org@evil.com'));
		self::assertTrue(Network::isEmailDomainAllowed('"a@b"@example.org'));
	}
}
