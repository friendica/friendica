<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\Unit\Contact\FriendSuggest\Factory;

use Friendica\Contact\FriendSuggest\Factory\FriendSuggest;
use Friendica\Contact\FriendSuggest\Entity;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class FriendSuggestTest extends TestCase
{
	public static function dataCreate()
	{
		return [
			'default' => [
				'input' => [
					'uid'     => 12,
					'cid'     => 13,
					'name'    => 'test',
					'url'     => 'https://friendica.local/profile/test',
					'request' => 'https://friendica.local/dfrn_request/test',
					'photo'   => 'https://friendica.local/photo/profile/test',
					'note'    => 'a common note',
					'created' => '2021-10-12 12:23:00',
				],
				'assertion' => new Entity\FriendSuggest(
					12,
					13,
					'test',
					'https://friendica.local/profile/test',
					'https://friendica.local/dfrn_request/test',
					'https://friendica.local/photo/profile/test',
					'a common note',
					new \DateTime('2021-10-12 12:23:00', new \DateTimeZone('UTC')),
				),
			],
			'full' => [
				'input' => [
					'uid'     => 12,
					'cid'     => 13,
					'name'    => 'test',
					'url'     => 'https://friendica.local/profile/test',
					'request' => 'https://friendica.local/dfrn_request/test',
					'photo'   => 'https://friendica.local/photo/profile/test',
					'note'    => 'a common note',
					'created' => '2021-10-12 12:23:00',
					'id'      => 666,
				],
				'assertion' => new Entity\FriendSuggest(
					12,
					13,
					'test',
					'https://friendica.local/profile/test',
					'https://friendica.local/dfrn_request/test',
					'https://friendica.local/photo/profile/test',
					'a common note',
					new \DateTime('2021-10-12 12:23:00', new \DateTimeZone('UTC')),
					666,
				),
			],
		];
	}

	private function assertFriendSuggest(Entity\FriendSuggest $expected, Entity\FriendSuggest $friendSuggest): void
	{
		self::assertSame($expected->id, $friendSuggest->id);
		self::assertSame($expected->uid, $friendSuggest->uid);
		self::assertSame($expected->cid, $friendSuggest->cid);
		self::assertSame($expected->name, $friendSuggest->name);
		self::assertSame($expected->url, $friendSuggest->url);
		self::assertSame($expected->request, $friendSuggest->request);
		self::assertSame($expected->photo, $friendSuggest->photo);
		self::assertSame($expected->note, $friendSuggest->note);
		self::assertSame($expected->created->getTimestamp(), $friendSuggest->created->getTimestamp());
	}

	private function assertCreatedBetween(\DateTime $created, \DateTime $from, \DateTime $to): void
	{
		self::assertGreaterThanOrEqual($from->getTimestamp(), $created->getTimestamp());
		self::assertLessThanOrEqual($to->getTimestamp(), $created->getTimestamp());
	}

	public function testCreateNew(): void
	{
		$factory = new FriendSuggest(new NullLogger());

		$createdBefore = new \DateTime('now', new \DateTimeZone('UTC'));

		$friendSuggest = $factory->createNew(12, 13);
		$createdAfter  = new \DateTime('now', new \DateTimeZone('UTC'));

		$this->assertFriendSuggest(
			new Entity\FriendSuggest(
				12,
				13,
				'',
				'',
				'',
				'',
				'',
				$friendSuggest->created,
				null,
			),
			$friendSuggest,
		);
		$this->assertCreatedBetween($friendSuggest->created, $createdBefore, $createdAfter);
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('dataCreate')]
	public function testCreateFromTableRow(array $input, Entity\FriendSuggest $assertion): void
	{
		$factory = new FriendSuggest(new NullLogger());

		$this->assertFriendSuggest($assertion, $factory->createFromTableRow($input));
	}

	public function testCreateFromMinimalTableRowDefaultsMissingFields(): void
	{
		$factory = new FriendSuggest(new NullLogger());

		$createdBefore = new \DateTime('now', new \DateTimeZone('UTC'));

		$friendSuggest = $factory->createFromTableRow(['id' => 20]);
		$createdAfter  = new \DateTime('now', new \DateTimeZone('UTC'));

		$this->assertFriendSuggest(new Entity\FriendSuggest(
			0,
			0,
			'',
			'',
			'',
			'',
			'',
			$friendSuggest->created,
			20,
		), $friendSuggest);
		$this->assertCreatedBetween($friendSuggest->created, $createdBefore, $createdAfter);
	}

	public function testCreateEmpty(): void
	{
		$factory = new FriendSuggest(new NullLogger());

		$createdBefore = new \DateTime('now', new \DateTimeZone('UTC'));

		$friendSuggest = $factory->createEmpty(66);
		$createdAfter  = new \DateTime('now', new \DateTimeZone('UTC'));

		$this->assertFriendSuggest(new Entity\FriendSuggest(
			0,
			0,
			'',
			'',
			'',
			'',
			'',
			$friendSuggest->created,
			66,
		), $friendSuggest);
		$this->assertCreatedBetween($friendSuggest->created, $createdBefore, $createdAfter);
	}
}
