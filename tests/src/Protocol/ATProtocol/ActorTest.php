<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Protocol\ATProtocol;

use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\PConfig\Capability\IManagePersonalConfigValues;
use Friendica\Database\Database;
use Friendica\Network\HTTPClient\Capability\ICanSendHttpRequests;
use Friendica\Protocol\ATProtocol;
use Friendica\Protocol\ATProtocol\Actor;
use Friendica\Test\MockedTestCase;
use Psr\Log\NullLogger;

class ActorTest extends MockedTestCase
{
	public static function dataGetProfileLink(): array
	{
		return [
			'did:plc' => [
				'expected' => 'https://bsky.app/profile/did:plc:ulsdjrbuee46yrgb37vfrz23',
				'did'      => 'did:plc:ulsdjrbuee46yrgb37vfrz23',
				'web'      => 'https://bsky.app',
			],
			'did:web' => [
				'expected' => 'https://bsky.app/profile/did:web:example.com',
				'did'      => 'did:web:example.com',
				'web'      => 'https://bsky.app',
			],
			'other frontend' => [
				'expected' => 'https://deer.social/profile/did:plc:abc',
				'did'      => 'did:plc:abc',
				'web'      => 'https://deer.social',
			],
			'no frontend' => [
				'expected' => '',
				'did'      => 'did:plc:abc',
				'web'      => null,
			],
			'unknown frontend' => [
				'expected' => '',
				'did'      => 'did:plc:abc',
				'web'      => 'https://unknown.example',
			],
			'http url' => [
				'expected' => '',
				'did'      => 'https://mastodon.example/@user',
				'web'      => 'https://bsky.app',
			],
			'at url' => [
				'expected' => '',
				'did'      => 'at://did:plc:abc/app.bsky.actor.profile/self',
				'web'      => 'https://bsky.app',
			],
			'acct' => [
				'expected' => '',
				'did'      => 'acct:user@example.com',
				'web'      => 'https://bsky.app',
			],
			'empty' => [
				'expected' => '',
				'did'      => '',
				'web'      => 'https://bsky.app',
			],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('dataGetProfileLink')]
	public function testGetProfileLink(string $expected, string $did, ?string $web): void
	{
		self::assertSame($expected, $this->createActor($web)->getProfileLink($did));
	}

	private function createActor(?string $web): Actor
	{
		$config = $this->createMock(IManageConfigValues::class);
		$config->method('get')
			->willReturnCallback(static function (string $cat, string $key) use ($web) {
				if ($cat !== 'atprotocol') {
					return null;
				}

				return match ($key) {
					'web'       => $web,
					'frontends' => [
						'https://bsky.app'    => ['Bluesky', 'https://bsky.app/profile/{did}', 'https://bsky.app/profile/{did}/post/{rkey}'],
						'https://deer.social' => ['Deer', 'https://deer.social/profile/{did}', 'https://deer.social/profile/{did}/post/{rkey}'],
					],
					default => null,
				};
			});

		$pConfig = $this->createMock(IManagePersonalConfigValues::class);
		$pConfig->method('get')->willReturn(null);

		$atprotocol = new ATProtocol(
			new NullLogger(),
			$this->createStub(Database::class),
			$config,
			$pConfig,
			$this->createStub(ICanSendHttpRequests::class),
		);

		return new Actor(new NullLogger(), $atprotocol, $config);
	}
}
