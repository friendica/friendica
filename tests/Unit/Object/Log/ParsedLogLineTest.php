<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\Unit\Object\Log;

use Friendica\Object\Log\ParsedLogLine;
use PHPUnit\Framework\TestCase;

/**
 * Log parser testing class
 */
class ParsedLogLineTest extends TestCase
{
	public static function do_log_line($logline, $expected_data)
	{
		$parsed = new ParsedLogLine(0, $logline);
		foreach ($expected_data as $k => $v) {
			self::assertSame($parsed->$k, $v, '"' . $k . '" does not match expectation');
		}
	}

	/**
	 * test parsing a generic log line
	 */
	public function testGenericLogLine(): void
	{
		self::do_log_line(
			'2021-05-24T15:40:01Z worker [WARNING]: Spool file does not start with "item-" {"file":".","worker_id":"560c8b6","worker_cmd":"SpoolPost"} - {"file":"SpoolPost.php","line":40,"function":"execute","uid":"fd8c37","process_id":20846}',
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

	/**
	 * test parsing a log line with empty data
	 */
	public function testEmptyDataLogLine(): void
	{
		self::do_log_line(
			'2021-05-24T15:23:58Z index [INFO]: No HTTP_SIGNATURE header [] - {"file":"HTTPSignature.php","line":476,"function":"getSigner","uid":"0a3934","process_id":14826}',
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

	/**
	 * test parsing a log line with various " - " in it
	 */
	public function testTrickyDashLogLine(): void
	{
		self::do_log_line(
			'2021-05-24T15:30:01Z worker [NOTICE]: Load: 0.01/20 - processes: 0/1/6 (0:0, 30:1) - maximum: 10/10 {"worker_id":"ece8fc8","worker_cmd":"Cron"} - {"file":"Worker.php","line":786,"function":"tooMuchWorkers","uid":"364d3c","process_id":20754}',
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

	/**
	 * test non conforming log line
	 */
	public function testNonConformingLogLine(): void
	{
		self::do_log_line(
			'this log line is not formatted as expected',
			[
				'date'    => null,
				'context' => null,
				'level'   => null,
				'message' => 'this log line is not formatted as expected',
				'data'    => null,
				'source'  => null,
			],
		);
	}

	/**
	 * test missing source
	 */
	public function testMissingSource(): void
	{
		self::do_log_line(
			'2021-05-24T15:30:01Z worker [NOTICE]: Load: 0.01/20 - processes: 0/1/6 (0:0, 30:1) - maximum: 10/10 {"worker_id":"ece8fc8","worker_cmd":"Cron"}',
			[
				'date'    => '2021-05-24T15:30:01Z',
				'context' => 'worker',
				'level'   => 'NOTICE',
				'message' => 'Load: 0.01/20 - processes: 0/1/6 (0:0, 30:1) - maximum: 10/10',
				'data'    => '{"worker_id":"ece8fc8","worker_cmd":"Cron"}',
				'source'  => null,
			],
		);
	}

	/**
	 * test missing data
	 */
	public function testMissingData(): void
	{
		self::do_log_line(
			'2021-05-24T15:30:01Z worker [NOTICE]: Load: 0.01/20 - processes: 0/1/6 (0:0, 30:1) - maximum: 10/10 - {"file":"Worker.php","line":786,"function":"tooMuchWorkers","uid":"364d3c","process_id":20754}',
			[
				'date'    => '2021-05-24T15:30:01Z',
				'context' => 'worker',
				'level'   => 'NOTICE',
				'message' => 'Load: 0.01/20 - processes: 0/1/6 (0:0, 30:1) - maximum: 10/10',
				'data'    => null,
				'source'  => '{"file":"Worker.php","line":786,"function":"tooMuchWorkers","uid":"364d3c","process_id":20754}',
			],
		);
	}

	/**
	 * test missing data and source
	 */
	public function testMissingDataAndSource(): void
	{
		self::do_log_line(
			'2021-05-24T15:30:01Z worker [NOTICE]: Load: 0.01/20 - processes: 0/1/6 (0:0, 30:1) - maximum: 10/10',
			[
				'date'    => '2021-05-24T15:30:01Z',
				'context' => 'worker',
				'level'   => 'NOTICE',
				'message' => 'Load: 0.01/20 - processes: 0/1/6 (0:0, 30:1) - maximum: 10/10',
				'data'    => null,
				'source'  => null,
			],
		);
	}

	/**
	 * test missing source and invalid data
	 */
	public function testMissingSourceAndInvalidData(): void
	{
		self::do_log_line(
			'2021-05-24T15:30:01Z worker [NOTICE]: Load: 0.01/20 - processes: 0/1/6 (0:0, 30:1) - maximum: 10/10 {"invalidjson {really',
			[
				'date'    => '2021-05-24T15:30:01Z',
				'context' => 'worker',
				'level'   => 'NOTICE',
				'message' => 'Load: 0.01/20 - processes: 0/1/6 (0:0, 30:1) - maximum: 10/10 {"invalidjson {really',
				'data'    => null,
				'source'  => null,
			],
		);
	}
}
