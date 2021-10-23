<?php
/**
 * @copyright Copyright (C) 2010-2021, the Friendica project
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

namespace Friendica\Test\src\Core\Logger;

use Friendica\Test\MockedTest;
use Friendica\Core\Logger\Type\VoidLogger;
use Psr\Log\LogLevel;

class VoidLoggerTest extends MockedTest
{
	use LoggerDataTrait;

	/**
	 * Test if the profiler is profiling data
	 * @dataProvider dataTests
	 * @doesNotPerformAssertions
	 */
	public function testNormal($function, $message, array $context)
	{
		$logger = new VoidLogger();
		$logger->$function($message, $context);
	}

	/**
	 * Test the log() function
	 * @doesNotPerformAssertions
	 */
	public function testProfilingLog()
	{
		$logger = new VoidLogger();
		$logger->log(LogLevel::WARNING, 'test', ['a' => 'context']);
	}
}
