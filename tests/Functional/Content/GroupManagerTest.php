<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\Functional\Content;

use Friendica\Content\GroupManager;
use Friendica\Core\Protocol;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Test\FixtureTestCase;
use Friendica\Util\Strings;
use PHPUnit\Framework\Attributes\DataProvider;

class GroupManagerTest extends FixtureTestCase
{
	private function getGroupManager(): GroupManager
	{
		return DI::getDice()->create(GroupManager::class);
	}

	public function testGetLinkForLocalGroup(): void
	{
		$contact = [
			'id'   => 123,
			'url'  => DI::baseUrl() . '/profile/localgroup',
			'addr' => 'localgroup@' . DI::baseUrl()->getHost(),
		];

		// Contact::isLocal() takes the base url from the public contact
		DBA::insert('contact', [
			'uid'     => 0,
			'name'    => 'localgroup',
			'nick'    => 'localgroup',
			'url'     => $contact['url'],
			'nurl'    => Strings::normaliseLink($contact['url']),
			'baseurl' => (string) DI::baseUrl(),
			'network' => Protocol::DFRN,
		]);

		self::assertSame('group/localgroup', $this->getGroupManager()->getLink($contact));
	}

	public function testGetLinkForRemoteGroup(): void
	{
		$contact = [
			'id'   => 123,
			'url'  => 'https://example.org/c/remotegroup',
			'addr' => 'remotegroup@example.org',
		];

		self::assertSame('group/remotegroup%40example.org', $this->getGroupManager()->getLink($contact));
	}

	public function testGetLinkWithoutAddress(): void
	{
		$contact = [
			'id'   => 123,
			'url'  => 'https://example.org/c/remotegroup',
			'addr' => '',
		];

		self::assertSame('group/123', $this->getGroupManager()->getLink($contact));
	}

	public static function dataServerDescription(): array
	{
		return [
			'lemmy-appended-description' => [
				'expected' => 'Lemmy Server',
				'name'     => 'Lemmy Server - A place for discussions about everything',
				'info'     => 'A place for discussions about everything',
			],
			'appended-description-with-html' => [
				'expected' => 'Lemmy Server',
				'name'     => 'Lemmy Server - A place for discussions about everything',
				'info'     => '<p>A place for discussions about <strong>everything</strong></p>',
			],
			'appended-shortened-description' => [
				'expected' => 'Lemmy Server',
				'name'     => 'Lemmy Server - A place for discussions about everything',
				'info'     => 'A place for discussions about everything!',
			],
			'dash-in-site-name' => [
				'expected' => 'Forum - Cats',
				'name'     => 'Forum - Cats - A place for discussions about everything',
				'info'     => 'A place for discussions about everything',
			],
			'dash-in-description' => [
				'expected' => 'Forum',
				'name'     => 'Forum - Cats - A place for discussions about everything',
				'info'     => 'Cats - A place for discussions about everything',
			],
			'dash-without-description' => [
				'expected' => 'Forum - Cats',
				'name'     => 'Forum - Cats',
				'info'     => 'A place for discussions about everything',
			],
			'empty-info' => [
				'expected' => 'Forum - Cats',
				'name'     => 'Forum - Cats',
				'info'     => '',
			],
			'empty-name' => [
				'expected' => 'example.org',
				'name'     => '',
				'info'     => 'A place for discussions about everything',
			],
		];
	}

	#[DataProvider('dataServerDescription')]
	public function testGetServerDescription(string $expected, string $name, string $info): void
	{
		$description = $this->getGroupManager()->getServerDescription(['site_name' => $name, 'info' => $info], 'example.org');

		self::assertSame($expected, $description['name']);
		self::assertSame('example.org', $description['host']);
		self::assertSame(trim(html_entity_decode(strip_tags($info))), $description['info']);
	}
}
