<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Functional\Core;

use Friendica\Core\Worker;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Test\FixtureTestCase;
use Friendica\Util\DateTimeFormat;

class WorkerTest extends FixtureTestCase
{
	/**
	 * Adds a claimed worker queue entry and returns its id
	 */
	private function addClaimedEntry(int $retrial): int
	{
		DBA::insert('workerqueue', [
			'command'   => 'UpdateGServer',
			'parameter' => json_encode(['https://example.com']),
			'priority'  => Worker::PRIORITY_LOW,
			'created'   => DateTimeFormat::utcNow(),
			'pid'       => 12345,
			'executed'  => DateTimeFormat::utcNow(),
			'next_try'  => DBA::NULL_DATETIME,
			'retrial'   => $retrial,
			'done'      => false,
		]);

		return DBA::lastInsertId();
	}

	private function fetchEntry(int $id): array
	{
		$entry = DBA::selectFirst('workerqueue', ['id', 'priority', 'created', 'retrial', 'pid', 'executed', 'next_try', 'done'], ['id' => $id]);

		self::assertIsArray($entry);

		return $entry;
	}

	/**
	 * Deferring on behalf of a gone worker has to release the claim AND count the attempt.
	 */
	public function testDeferQueueEntryCountsTheAttempt(): void
	{
		$id = $this->addClaimedEntry(0);

		self::assertTrue(Worker::deferQueueEntry($this->fetchEntry($id)));

		$entry = $this->fetchEntry($id);

		self::assertEquals(1, $entry['retrial']);
		self::assertEquals(0, $entry['pid']);
		self::assertEquals(DBA::NULL_DATETIME, $entry['executed']);
		self::assertGreaterThan(DateTimeFormat::utcNow(), $entry['next_try']);
		self::assertFalse((bool) $entry['done']);
	}

	/**
	 * Past system.worker_defer_limit the entry must not be rescheduled again.
	 */
	public function testDeferQueueEntryStopsAtTheDeferLimit(): void
	{
		$max_level = DI::config()->get('system', 'worker_defer_limit');

		$id = $this->addClaimedEntry($max_level);

		self::assertFalse(Worker::deferQueueEntry($this->fetchEntry($id)));

		$entry = $this->fetchEntry($id);

		self::assertEquals($max_level, $entry['retrial']);
	}

	/**
	 * The explicit defer limit of a caller must not raise the configured one.
	 */
	public function testDeferQueueEntryRespectsTheCallerLimit(): void
	{
		$id = $this->addClaimedEntry(3);

		self::assertFalse(Worker::deferQueueEntry($this->fetchEntry($id), 3));
	}

	public function testDeferQueueEntryIgnoresAnEmptyEntry(): void
	{
		self::assertFalse(Worker::deferQueueEntry([]));
	}
}
