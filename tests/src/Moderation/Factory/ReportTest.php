<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Moderation\Factory;

use Friendica\Moderation\Collection;
use Friendica\Moderation\Factory;
use Friendica\Moderation\Entity;
use Friendica\Test\MockedTestCase;
use Friendica\Util\Clock\FrozenClock;
use Friendica\Util\DateTimeFormat;
use Psr\Clock\ClockInterface;
use Psr\Log\NullLogger;

class ReportTest extends MockedTestCase
{
	public static function dataCreateFromTableRow(): array
	{
		$clock = new FrozenClock();

		// We need to strip the microseconds part to match database stored timestamps
		$nowSeconds = $clock->now()->setTime(
			$clock->now()->format('H'),
			$clock->now()->format('i'),
			$clock->now()->format('s'),
		);

		return [
			'default' => [
				'clock' => $clock,
				'row'   => [
					'id'              => 11,
					'reporter-id'     => 12,
					'uid'             => null,
					'cid'             => 13,
					'gsid'            => 14,
					'comment'         => '',
					'forward'         => false,
					'category-id'     => Entity\Report::CATEGORY_SPAM,
					'public-remarks'  => '',
					'private-remarks' => '',
					'last-editor-uid' => null,
					'assigned-uid'    => null,
					'status'          => Entity\Report::STATUS_OPEN,
					'resolution'      => null,
					'created'         => $nowSeconds->format(DateTimeFormat::MYSQL),
					'edited'          => null,
				],
				'posts'     => new Collection\Report\Posts(),
				'rules'     => new Collection\Report\Rules(),
				'assertion' => new Entity\Report(
					12,
					13,
					14,
					$nowSeconds,
					Entity\Report::CATEGORY_SPAM,
					null,
					'',
					false,
					new Collection\Report\Posts(),
					new Collection\Report\Rules(),
					'',
					'',
					null,
					Entity\Report::STATUS_OPEN,
					null,
					null,
					null,
					11,
				),
			],
			'full' => [
				'clock' => $clock,
				'row'   => [
					'id'              => 11,
					'reporter-id'     => 42,
					'uid'             => 12,
					'cid'             => 13,
					'gsid'            => 14,
					'comment'         => 'Report',
					'forward'         => true,
					'category-id'     => Entity\Report::CATEGORY_VIOLATION,
					'public-remarks'  => 'Public remarks',
					'private-remarks' => 'Private remarks',
					'last-editor-uid' => 15,
					'assigned-uid'    => 16,
					'status'          => Entity\Report::STATUS_CLOSED,
					'resolution'      => Entity\Report::RESOLUTION_ACCEPTED,
					'created'         => '2021-10-12 12:23:00',
					'edited'          => '2021-12-10 21:08:00',
				],
				'posts' => new Collection\Report\Posts([
					new Entity\Report\Post(89),
					new Entity\Report\Post(90),
				]),
				'rules' => new Collection\Report\Rules([
					new Entity\Report\Rule(1, 'No hate speech'),
					new Entity\Report\Rule(3, 'No commercial promotion'),
				]),
				'assertion' => new Entity\Report(
					42,
					13,
					14,
					new \DateTimeImmutable('2021-10-12 12:23:00', new \DateTimeZone('UTC')),
					Entity\Report::CATEGORY_VIOLATION,
					12,
					'Report',
					true,
					new Collection\Report\Posts([
						new Entity\Report\Post(89),
						new Entity\Report\Post(90),
					]),
					new Collection\Report\Rules([
						new Entity\Report\Rule(1, 'No hate speech'),
						new Entity\Report\Rule(3, 'No commercial promotion'),
					]),
					'Public remarks',
					'Private remarks',
					new \DateTimeImmutable('2021-12-10 21:08:00', new \DateTimeZone('UTC')),
					Entity\Report::STATUS_CLOSED,
					Entity\Report::RESOLUTION_ACCEPTED,
					16,
					15,
					11,
				),
			],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('dataCreateFromTableRow')]
	public function testCreateFromTableRow(ClockInterface $clock, array $row, Collection\Report\Posts $posts, Collection\Report\Rules $rules, Entity\Report $assertion): void
	{
		$factory = new Factory\Report(new NullLogger(), $clock);

		$this->assertEquals($factory->createFromTableRow($row, $posts, $rules), $assertion);
	}

	public static function dataCreateFromReportsRequest(): array
	{
		$clock = new FrozenClock();

		return [
			'default' => [
				'clock'      => $clock,
				'rules'      => [],
				'reporterId' => 12,
				'cid'        => 13,
				'gsid'       => 14,
				'comment'    => '',
				'category'   => 'spam',
				'forward'    => false,
				'postUriIds' => [],
				'ruleIds'    => [],
				'uid'        => null,
				'assertion'  => new Entity\Report(
					12,
					13,
					14,
					$clock->now(),
					Entity\Report::CATEGORY_SPAM,
				),
			],
			'full' => [
				'clock'      => $clock,
				'rules'      => ['', 'Rule 1', 'Rule 2', 'Rule 3'],
				'reporterId' => 12,
				'cid'        => 13,
				'gsid'       => 14,
				'comment'    => 'Report',
				'category'   => 'violation',
				'forward'    => true,
				'postUriIds' => [89, 90],
				'ruleIds'    => [1, 3],
				'uid'        => 42,
				'assertion'  => new Entity\Report(
					12,
					13,
					14,
					$clock->now(),
					Entity\Report::CATEGORY_VIOLATION,
					42,
					'Report',
					true,
					new Collection\Report\Posts([
						new Entity\Report\Post(89),
						new Entity\Report\Post(90),
					]),
					new Collection\Report\Rules([
						new Entity\Report\Rule(1, 'Rule 1'),
						new Entity\Report\Rule(3, 'Rule 3'),
					]),
				),
			],
			'forced-violation' => [
				'clock'      => $clock,
				'rules'      => ['', 'Rule 1', 'Rule 2', 'Rule 3'],
				'reporterId' => 12,
				'cid'        => 13,
				'gsid'       => 14,
				'comment'    => 'Report',
				'category'   => 'other',
				'forward'    => false,
				'postUriIds' => [],
				'ruleIds'    => [2, 3],
				'uid'        => null,
				'assertion'  => new Entity\Report(
					12,
					13,
					14,
					$clock->now(),
					Entity\Report::CATEGORY_VIOLATION,
					null,
					'Report',
					false,
					new Collection\Report\Posts(),
					new Collection\Report\Rules([
						new Entity\Report\Rule(2, 'Rule 2'),
						new Entity\Report\Rule(3, 'Rule 3'),
					]),
				),
			],
			'unknown-category' => [
				'clock'      => $clock,
				'rules'      => ['', 'Rule 1', 'Rule 2', 'Rule 3'],
				'reporterId' => 12,
				'cid'        => 13,
				'gsid'       => 14,
				'comment'    => '',
				'category'   => 'unknown',
				'forward'    => false,
				'postUriIds' => [],
				'ruleIds'    => [],
				'uid'        => null,
				'assertion'  => new Entity\Report(
					12,
					13,
					14,
					$clock->now(),
					Entity\Report::CATEGORY_OTHER,
				),
			],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('dataCreateFromReportsRequest')]
	public function testCreateFromReportsRequest(ClockInterface $clock, array $rules, int $reporterId, int $cid, int $gsid, string $comment, string $category, bool $forward, array $postUriIds, array $ruleIds, ?int $uid, Entity\Report $assertion): void
	{
		$factory = new Factory\Report(new NullLogger(), $clock);

		$this->assertEquals($factory->createFromReportsRequest($rules, $reporterId, $cid, $gsid, $comment, $category, $forward, $postUriIds, $ruleIds, $uid), $assertion);
	}
}
