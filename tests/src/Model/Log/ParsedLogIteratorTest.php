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

namespace Friendica\Test\src\Model\Log;

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
			self::assertSame($parsed->$k, $v, '"'.$k.'" does not match expectation');
		}
	}

	protected function setUp(): void
	{
		$logfile = dirname(__DIR__) . '/../../datasets/log/friendica.log.txt';

		$reader    = new ReversedFileReader();
		$this->pli = new ParsedLogIterator($reader);
		$this->pli->open($logfile);
	}

	public function testIsIterable()
	{
		self::assertIsIterable($this->pli);
	}

	public function testEverything()
	{
		self::assertCount(3, iterator_to_array($this->pli, false));
	}

	public function testLimit()
	{
		$this->pli->withLimit(2);
		self::assertCount(2, iterator_to_array($this->pli, false));
	}

	public function testFilterByLevel()
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
			]
		);
	}

	public function testFilterByContext()
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
				'message' => 'Spool file does does not start with "item-"',
				'data'    => '{"file":".","worker_id":"560c8b6","worker_cmd":"SpoolPost"}',
				'source'  => '{"file":"SpoolPost.php","line":40,"function":"execute","uid":"fd8c37","process_id":20846}',
			]
		);
	}

	public function testFilterCombined()
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
			]
		);
	}

	public function testSearch()
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
			]
		);
	}

	public function testFilterAndSearch()
	{
		$this->pli
			->withFilters(['context' => 'worker'])
			->withSearch("header");
		$pls = iterator_to_array($this->pli, false);
		self::assertCount(0, $pls);
	}

	public function testEmptyLogFile()
	{
		$logfile = dirname(__DIR__) . '/../../datasets/log/empty.friendica.log.txt';

		$reader = new ReversedFileReader();
		$pli    = new ParsedLogIterator($reader);
		$pli->open($logfile);

		$pls = iterator_to_array($pli, false);
		self::assertCount(0, $pls);
	}
}
