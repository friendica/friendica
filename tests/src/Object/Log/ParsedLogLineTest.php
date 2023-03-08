<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Friendica\Test\src\Object\Log;

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
			self::assertSame($parsed->$k, $v, '"'.$k.'" does not match expectation');
		}
	}

	/**
	 * test parsing a generic log line
	 */
	public function testGenericLogLine()
	{
		self::do_log_line(
			'2021-05-24T15:40:01Z worker [WARNING]: Spool file does does not start with "item-" {"file":".","worker_id":"560c8b6","worker_cmd":"SpoolPost"} - {"file":"SpoolPost.php","line":40,"function":"execute","uid":"fd8c37","process_id":20846}',
			[
				'date'    => '2021-05-24T15:40:01Z',
				'context' => 'worker',
				'level'   => 'WARNING',
				'message' => 'Spool file does does not start with "item-"',
				'data'    => '{"file":".","worker_id":"560c8b6","worker_cmd":"SpoolPost"}',
				'source'  => '{"file":"SpoolPost.php","line":40,"function":"execute","uid":"fd8c37","process_id":20846}',
			]
		);
	}

	/**
	 * test parsing a log line with empty data
	 */
	public function testEmptyDataLogLine()
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
			]
		);
	}

	/**
	 * test parsing a log line with various " - " in it
	 */
	public function testTrickyDashLogLine()
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
			]
		);
	}

	/**
	 * test non conforming log line
	 */
	public function testNonConformingLogLine()
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
			]
		);
	}

	/**
	 * test missing source
	 */
	public function testMissingSource()
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
			]
		);
	}

	/**
	 * test missing data
	 */
	public function testMissingData()
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
			]
		);
	}

	/**
	 * test missing data and source
	 */
	public function testMissingDataAndSource()
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
			]
		);
	}

	/**
	 * test missing source and invalid data
	 */
	public function testMissingSourceAndInvalidData()
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
			]
		);
	}
}
