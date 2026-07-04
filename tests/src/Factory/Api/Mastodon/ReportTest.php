<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Factory\Api\Mastodon;

use Friendica\Database\Database;
use Friendica\Factory\Api\Mastodon\Account;
use Friendica\Factory\Api\Mastodon\Report;
use Friendica\Factory\Api\Mastodon\Status;
use Friendica\Moderation\Collection\Report\Posts;
use Friendica\Moderation\Collection\Report\Rules;
use Friendica\Moderation\Entity\Report as ReportEntity;
use Friendica\Moderation\Entity\Report\Post as ReportPost;
use Friendica\Moderation\Entity\Report\Rule as ReportRule;
use Friendica\Object\Api\Mastodon\Account as MstdnAccount;
use Friendica\Object\Api\Mastodon\Status as MstdnStatus;
use Friendica\Test\MockedTestCase;
use Psr\Log\LoggerInterface;

class ReportTest extends MockedTestCase
{
	public function testCreateFromReportEntityMapsFields(): void
	{
		$logger         = \Mockery::mock(LoggerInterface::class);
		$database       = \Mockery::mock(Database::class);
		$accountFactory = \Mockery::mock(Account::class);
		$statusFactory  = \Mockery::mock(Status::class);

		$factory = new Report($logger, $database, $accountFactory, $statusFactory);

		$created = new \DateTimeImmutable('2026-01-01 10:00:00', new \DateTimeZone('UTC'));
		$edited  = new \DateTimeImmutable('2026-01-02 10:00:00', new \DateTimeZone('UTC'));

		$report = new ReportEntity(
			reporterCid: 10,
			cid: 20,
			gsid: 30,
			created: $created,
			category: ReportEntity::CATEGORY_VIOLATION,
			reporterUid: 99,
			comment: 'Needs moderation',
			forward: true,
			posts: new Posts([
				new ReportPost(1001),
				new ReportPost(1002),
			]),
			rules: new Rules([
				new ReportRule(7, 'Be kind'),
			]),
			edited: $edited,
			status: ReportEntity::STATUS_CLOSED,
			id: 42,
		);

		$reporterAccount = \Mockery::mock(MstdnAccount::class);
		$reporterAccount->shouldReceive('toArray')->once()->andReturn(['id' => 'acc-10']);
		$targetAccount = \Mockery::mock(MstdnAccount::class);
		$targetAccount->shouldReceive('toArray')->once()->andReturn(['id' => 'acc-20']);

		$accountFactory->shouldReceive('createFromContactId')->with(10, 99)->once()->andReturn($reporterAccount);
		$accountFactory->shouldReceive('createFromContactId')->with(20, 99)->once()->andReturn($targetAccount);

		$database->shouldReceive('selectToArray')->with('report-post', ['uri-id'], ['rid' => 42])->once()->andReturn([
			['uri-id' => 1001],
			['uri-id' => 1002],
		]);
		$database->shouldReceive('selectToArray')->with('report-rule', ['line-id', 'text'], ['rid' => 42])->once()->andReturn([
			['line-id' => 7, 'text' => 'Be kind'],
		]);

		$statusA = \Mockery::mock(MstdnStatus::class);
		$statusA->shouldReceive('toArray')->once()->andReturn(['id' => 'status-1001']);
		$statusB = \Mockery::mock(MstdnStatus::class);
		$statusB->shouldReceive('toArray')->once()->andReturn(['id' => 'status-1002']);

		$statusFactory->shouldReceive('createFromUriId')->with(1001, 99)->once()->andReturn($statusA);
		$statusFactory->shouldReceive('createFromUriId')->with(1002, 99)->once()->andReturn($statusB);

		$result = $factory->createFromReportEntity($report)->toArray();

		self::assertSame('42', $result['id']);
		self::assertTrue($result['action_taken']);
		self::assertNotNull($result['action_taken_at']);
		self::assertSame('violation', $result['category']);
		self::assertSame('Needs moderation', $result['comment']);
		self::assertTrue($result['forwarded']);
		self::assertSame(['id' => 'acc-10'], $result['account']);
		self::assertSame(['id' => 'acc-20'], $result['target_account']);
		self::assertNull($result['assigned_account']);
		self::assertNull($result['action_taken_by_account']);
		self::assertSame([['id' => 'status-1001'], ['id' => 'status-1002']], $result['statuses']);
		self::assertSame([['line-id' => 7, 'text' => 'Be kind']], $result['rules']);
	}

	public function testCreateFromReportEntitySkipsBrokenDependencies(): void
	{
		$logger         = \Mockery::mock(LoggerInterface::class);
		$database       = \Mockery::mock(Database::class);
		$accountFactory = \Mockery::mock(Account::class);
		$statusFactory  = \Mockery::mock(Status::class);

		$factory = new Report($logger, $database, $accountFactory, $statusFactory);

		$created = new \DateTimeImmutable('2026-01-03 10:00:00', new \DateTimeZone('UTC'));

		$report = new ReportEntity(
			reporterCid: 11,
			cid: 21,
			gsid: 31,
			created: $created,
			category: ReportEntity::CATEGORY_OTHER,
			reporterUid: null,
			comment: '',
			forward: false,
			posts: new Posts([
				new ReportPost(2001),
			]),
			rules: new Rules([]),
			edited: null,
			status: ReportEntity::STATUS_OPEN,
			id: 43,
		);

		$accountFactory->shouldReceive('createFromContactId')->with(11, 0)->once()->andThrow(new \RuntimeException('missing'));
		$accountFactory->shouldReceive('createFromContactId')->with(21, 0)->once()->andThrow(new \RuntimeException('missing'));

		$database->shouldReceive('selectToArray')->with('report-post', ['uri-id'], ['rid' => 43])->once()->andReturn([
			['uri-id' => 2001],
		]);
		$database->shouldReceive('selectToArray')->with('report-rule', ['line-id', 'text'], ['rid' => 43])->once()->andReturn([]);

		$statusFactory->shouldReceive('createFromUriId')->with(2001, 0)->once()->andThrow(new \RuntimeException('status failed'));

		$result = $factory->createFromReportEntity($report)->toArray();

		self::assertSame('43', $result['id']);
		self::assertFalse($result['action_taken']);
		self::assertNull($result['action_taken_at']);
		self::assertSame('other', $result['category']);
		self::assertFalse($result['forwarded']);
		self::assertNull($result['account']);
		self::assertNull($result['target_account']);
		self::assertSame([], $result['statuses']);
		self::assertSame([], $result['rules']);
	}
}
