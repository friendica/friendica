<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\Unit\Model\Log;

use Friendica\Util\ReversedFileReader;
use Friendica\Model\Log\ParsedLogIterator;
use PHPUnit\Framework\TestCase;

/**
 * Parsed log iterator testing class
 */
class ParsedLogIteratorTest extends TestCase
{
	protected $pli;

	public static function assertParsed($parsed, $expected_data)
	{
		foreach ($expected_data as $k => $v) {
			self::assertSame($parsed->$k, $v, '"' . $k . '" does not match expectation');
		}
	}

	protected function setUp(): void
	{
		$logfile = dirname(__DIR__) . '/../../Fixtures/log/friendica.log.txt';

		$reader    = new ReversedFileReader();
		$this->pli = new ParsedLogIterator($reader);
		$this->pli->open($logfile);
	}

	public function testIsIterable(): void
	{
		self::assertIsIterable($this->pli);
	}

	public function testEverything(): void
	{
		self::assertCount(3, iterator_to_array($this->pli, false));
	}

	public function testLimit(): void
	{
		$this->pli->withLimit(2);
		self::assertCount(2, iterator_to_array($this->pli, false));
	}

	public function testFilterByLevel(): void
	{
		$this->pli->withFilters(['level' => 'INFO']);
		$pls = iterator_to_array($this->pli, false);
		self::assertCount(1, $pls);
		self::assertParsed(
			$pls[0],
			[
				'date'    => '2021-05-24T15:23:58Z',
				'context' => 'index',
				'level'   => 'INFO',
				'message' => 'No HTTP_SIGNATURE header',
				'data'    => null,
				'source'  => '{"file":"HTTPSignature.php","line":476,"function":"getSigner","uid":"0a3934","process_id":14826}',
			],
		);
	}

	public function testFilterByContext(): void
	{
		$this->pli->withFilters(['context' => 'worker']);
		$pls = iterator_to_array($this->pli, false);
		self::assertCount(2, $pls);
		self::assertParsed(
			$pls[0],
			[
				'date'    => '2021-05-24T15:40:01Z',
				'context' => 'worker',
				'level'   => 'WARNING',
				'message' => 'Spool file does not start with "item-"',
				'data'    => '{"file":".","worker_id":"560c8b6","worker_cmd":"SpoolPost"}',
				'source'  => '{"file":"SpoolPost.php","line":40,"function":"execute","uid":"fd8c37","process_id":20846}',
			],
		);
	}

	public function testFilterCombined(): void
	{
		$this->pli->withFilters(['level' => 'NOTICE', 'context' => 'worker']);
		$pls = iterator_to_array($this->pli, false);
		self::assertCount(1, $pls);
		self::assertParsed(
			$pls[0],
			[
				'date'    => '2021-05-24T15:30:01Z',
				'context' => 'worker',
				'level'   => 'NOTICE',
				'message' => 'Load: 0.01/20 - processes: 0/1/6 (0:0, 30:1) - maximum: 10/10',
				'data'    => '{"worker_id":"ece8fc8","worker_cmd":"Cron"}',
				'source'  => '{"file":"Worker.php","line":786,"function":"tooMuchWorkers","uid":"364d3c","process_id":20754}',
			],
		);
	}

	public function testSearch(): void
	{
		$this->pli->withSearch("maximum");
		$pls = iterator_to_array($this->pli, false);
		self::assertCount(1, $pls);
		self::assertParsed(
			$pls[0],
			[
				'date'    => '2021-05-24T15:30:01Z',
				'context' => 'worker',
				'level'   => 'NOTICE',
				'message' => 'Load: 0.01/20 - processes: 0/1/6 (0:0, 30:1) - maximum: 10/10',
				'data'    => '{"worker_id":"ece8fc8","worker_cmd":"Cron"}',
				'source'  => '{"file":"Worker.php","line":786,"function":"tooMuchWorkers","uid":"364d3c","process_id":20754}',
			],
		);
	}

	public function testFilterAndSearch(): void
	{
		$this->pli
			->withFilters(['context' => 'worker'])
			->withSearch("header");
		$pls = iterator_to_array($this->pli, false);
		self::assertCount(0, $pls);
	}

	public function testLimitCountsMatchingLines(): void
	{
		$this->pli
			->withLimit(1)
			->withFilters(['level' => 'INFO']);
		$pls = iterator_to_array($this->pli, false);
		self::assertCount(1, $pls);
		self::assertParsed($pls[0], ['message' => 'No HTTP_SIGNATURE header']);
	}

	public function testScanLimit(): void
	{
		$this->pli->withScanLimit(2);
		self::assertCount(2, iterator_to_array($this->pli, false));
		self::assertTrue($this->pli->isScanLimitReached());
	}

	public function testScanLimitNotReached(): void
	{
		$this->pli->withScanLimit(3);
		self::assertCount(3, iterator_to_array($this->pli, false));
		self::assertFalse($this->pli->isScanLimitReached());
	}

	public function testTimeoutNotReached(): void
	{
		$this->pli->withTimeout(20);
		self::assertCount(3, iterator_to_array($this->pli, false));
		self::assertFalse($this->pli->isTimeoutReached());
	}

	public function testExcludedRequestId(): void
	{
		$logfile = dirname(__DIR__) . '/../../Fixtures/log/request-id.friendica.log.txt';

		$reader = new ReversedFileReader();
		$pli    = new ParsedLogIterator($reader);
		$pli->open($logfile);

		$pli->withSearch('6ab8ad2540896');
		self::assertCount(3, iterator_to_array($pli, false));

		$pli->withExcludedRequestId('6ab8ad3827519');
		$pls = iterator_to_array($pli, false);
		self::assertCount(1, $pls);
		self::assertParsed($pls[0], ['message' => 'Fetched replies']);
	}

	public function testExcludedRequestIdCombinedWithLimitAndScanLimit(): void
	{
		$logfile = dirname(__DIR__) . '/../../Fixtures/log/request-id.friendica.log.txt';

		$reader = new ReversedFileReader();
		$pli    = new ParsedLogIterator($reader);
		$pli->open($logfile);

		$pli
			->withLimit(1)
			->withScanLimit(2)
			->withSearch('6ab8ad2540896')
			->withExcludedRequestId('6ab8ad3827519');

		$pls = iterator_to_array($pli, false);
		self::assertCount(1, $pls);
		self::assertParsed($pls[0], ['message' => 'Fetched replies']);
		self::assertFalse($pli->isScanLimitReached());
	}

	public function testRewindResetsLimitFlags(): void
	{
		$this->pli->withScanLimit(2);
		iterator_to_array($this->pli, false);
		self::assertTrue($this->pli->isScanLimitReached());

		// rewind() gives every pass its own fresh scan/timeout budget and flags,
		// so raising the limit before a second pass must clear the stale flag.
		$this->pli->withScanLimit(3);
		$this->pli->rewind();
		self::assertCount(3, iterator_to_array($this->pli, false));
		self::assertFalse($this->pli->isScanLimitReached());
	}

	public function testEmptyLogFile(): void
	{
		$logfile = dirname(__DIR__) . '/../../Fixtures/log/empty.friendica.log.txt';

		$reader = new ReversedFileReader();
		$pli    = new ParsedLogIterator($reader);
		$pli->open($logfile);

		$pls = iterator_to_array($pli, false);
		self::assertCount(0, $pls);
	}
}
