<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\Unit\Util;

use Friendica\Util\Network;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/** Tests URL schemes accepted by the HTTP client. */
class OutboundSchemeTest extends TestCase
{
	public static function requestableProvider(): array
	{
		return [
			// Fediverse actors and objects
			'mastodon actor'      => ['https://mastodon.social/users/Gargron'],
			'mastodon status'     => ['https://mastodon.social/users/Gargron/statuses/109252195269200000'],
			'mastodon webfinger'  => ['https://mastodon.social/.well-known/webfinger?resource=acct%3AGargron%40mastodon.social'],
			'friendica profile'   => ['https://friendica.example/profile/tobias'],
			'friendica objects'   => ['https://friendica.example/objects/1a2b3c4d-5e6f-7890-abcd-ef1234567890'],
			'diaspora receive'    => ['https://diaspora.example/receive/users/abcdef0123456789'],
			'pleroma object'      => ['https://pleroma.example/objects/0193d5f2-2b62-7c9c-9c4c-0e0d0a0b0c0d'],
			'peertube video'      => ['https://peertube.example/videos/watch/9f8b0c1d-2e3f-4a5b-6c7d-8e9f0a1b2c3d'],
			'lemmy community'     => ['https://lemmy.example/c/friendica'],
			'nodeinfo well-known' => ['https://friendi.ca/.well-known/nodeinfo'],
			'host-meta'           => ['https://friendi.ca/.well-known/host-meta'],
			// Feeds and media, the other big outbound consumers
			'atom feed'       => ['https://example.org/feed/atom'],
			'rss with query'  => ['https://example.org/?feed=rss2&cat=7'],
			'avatar'          => ['https://cdn.example.org/avatars/1234/original.png'],
			'oembed endpoint' => ['https://example.org/oembed?url=https%3A%2F%2Fexample.org%2Fp%2F1&format=json'],
			// Shapes that must not be rejected by accident
			'plain http'        => ['http://legacy.example.org/profile/user'],
			'explicit port'     => ['https://example.org:8443/users/alice'],
			'idn host'          => ['https://präsenz.example/profile/user'],
			'punycode host'     => ['https://xn--prsenz-tya.example/profile/user'],
			'unicode path'      => ['https://example.org/profile/josé'],
			'percent encoded'   => ['https://example.org/tag/%C3%BCber'],
			'fragment'          => ['https://example.org/display/abc#comment-1'],
			'onion service'     => ['http://friendicaxyz234567.onion/profile/user'],
			'i2p service'       => ['http://friendica.i2p/profile/user'],
			'trailing dot host' => ['https://example.org./users/alice'],
		];
	}

	#[DataProvider('requestableProvider')]
	public function testFederatedUrlsStayRequestable(string $url): void
	{
		self::assertTrue(Network::isValidHttpUrl($url), $url . ' must stay requestable');
	}

	public static function notRequestableProvider(): array
	{
		return [
			// Non-http identifiers, resolved before any HTTP request happens
			'acct'         => ['acct:Gargron@mastodon.social'],
			'mailto'       => ['mailto:user@example.org'],
			'at protocol'  => ['at://did:plc:geqiabvo4b4jnfv2paplzcge/app.bsky.feed.post/abc123'],
			'did'          => ['did:plc:geqiabvo4b4jnfv2paplzcge'],
			'diaspora uri' => ['diaspora://alice@example.org/post/guid'],
			'tag uri'      => ['tag:example.org,2026-08:objectId=1:objectType=Note'],
			'urn uuid'     => ['urn:uuid:1a2b3c4d-5e6f-7890-abcd-ef1234567890'],
			// Schemes curl speaks but the node has no business speaking
			'file'   => ['file:///etc/passwd'],
			'ftp'    => ['ftp://example.org/pub/file'],
			'gopher' => ['gopher://example.org/1'],
			'dict'   => ['dict://example.org:2628/d:word'],
			'ldap'   => ['ldap://example.org/o=org'],
			// Not a network location at all
			'javascript'  => ['javascript:alert(1)'],
			'data'        => ['data:text/html,<script>alert(1)</script>'],
			'no scheme'   => ['example.org/users/alice'],
			'scheme only' => ['https://'],
			'empty'       => [''],
		];
	}

	#[DataProvider('notRequestableProvider')]
	public function testNonHttpIdentifiersAreNotRequested(string $url): void
	{
		self::assertFalse(Network::isValidHttpUrl($url), $url . ' must not be requested over HTTP');
	}
}
