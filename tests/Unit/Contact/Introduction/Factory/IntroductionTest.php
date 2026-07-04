<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\Unit\Contact\Introduction\Factory;

use Friendica\Contact\Introduction\Factory\Introduction;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class IntroductionTest extends TestCase
{
	public static function dataRow()
	{
		return [
			'default' => [
				'input' => [
					'uid'         => 42,
					'suggest-cid' => 13,
					'contact-id'  => 24,
					'knowyou'     => 1,
					'note'        => 'a note',
					'hash'        => '12345',
					'datetime'    => '1970-01-01 00:00:00',
					'ignore'      => 0,
					'id'          => 56,
				],
				'assertion' => [
					'uid'         => 42,
					'suggest-cid' => 13,
					'contact-id'  => 24,
					'knowyou'     => true,
					'note'        => 'a note',
					'hash'        => '12345',
					'datetime'    => new \DateTime('1970-01-01 00:00:00', new \DateTimeZone('UTC')),
					'ignore'      => false,
					'id'          => 56,
				],
			],
			'empty' => [
				'input' => [
				],
				'assertion' => [
					'uid'         => 0,
					'contact-id'  => 0,
					'suggest-cid' => null,
					'knowyou'     => false,
					'note'        => '',
					'ignore'      => false,
					'id'          => null,
				],
			],
		];
	}

	public function assertIntro(\Friendica\Contact\Introduction\Entity\Introduction $intro, array $assertion)
	{
		self::assertEquals($intro->id, $assertion['id'] ?? null);
		self::assertEquals($intro->uid, $assertion['uid'] ?? 0);
		self::assertEquals($intro->cid, $assertion['contact-id'] ?? 0);
		self::assertEquals($intro->sid, $assertion['suggest-cid'] ?? null);
		self::assertEquals($intro->knowyou, $assertion['knowyou'] ?? false);
		self::assertEquals($intro->note, $assertion['note'] ?? '');
		if (isset($assertion['hash'])) {
			self::assertEquals($intro->hash, $assertion['hash']);
		} else {
			self::assertIsString($intro->hash);
		}
		if (isset($assertion['datetime'])) {
			self::assertEquals($intro->datetime, $assertion['datetime']);
		} else {
			self::assertInstanceOf(\DateTime::class, $intro->datetime);
		}
		self::assertEquals($intro->ignore, $assertion['ignore'] ?? false);
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('dataRow')]
	public function testCreateFromTableRow(array $input, array $assertion): void
	{
		$factory = new Introduction(new NullLogger());

		$intro = $factory->createFromTableRow($input);
		$this->assertIntro($intro, $assertion);
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('dataRow')]
	public function testCreateNew(array $input, array $assertion): void
	{
		$factory = new Introduction(new NullLogger());

		$intro = $factory->createNew($input['uid'] ?? 0, $input['cid'] ?? 0, $input['note'] ?? '');

		$this->assertIntro($intro, [
			'uid'        => $input['uid']  ?? 0,
			'contact-id' => $input['cid']  ?? 0,
			'note'       => $input['note'] ?? '',
		]);
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('dataRow')]
	public function testCreateDummy(array $input, array $assertion): void
	{
		$factory = new Introduction(new NullLogger());

		$intro = $factory->createDummy($input['id'] ?? null);

		$this->assertIntro($intro, ['id' => $input['id'] ?? null]);
	}
}
