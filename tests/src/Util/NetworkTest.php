<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Util;

use Friendica\Util\Network;
use PHPUnit\Framework\TestCase;

/**
 * Network utility test class
 */
class NetworkTest extends TestCase
{
	public function testValidUri()
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
	public function testIsValidAtUrl()
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
}
