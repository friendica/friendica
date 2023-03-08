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

namespace Friendica\Test\src\Core\Logger;

use Friendica\Test\MockedTest;
use Friendica\Core\Logger\Type\ProfilerLogger;
use Friendica\Util\Profiler;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class ProfilerLoggerTest extends MockedTest
{
	use LoggerDataTrait;

	/**
	 * @var LoggerInterface|MockInterface
	 */
	private $logger;
	/**
	 * @var Profiler|MockInterface
	 */
	private $profiler;

	protected function setUp(): void
	{
		parent::setUp();

		$this->logger = \Mockery::mock(LoggerInterface::class);
		$this->profiler = \Mockery::mock(Profiler::class);
	}

	/**
	 * Test if the profiler is profiling data
	 * @dataProvider dataTests
	 * @doesNotPerformAssertions
	 */
	public function testProfiling($function, $message, array $context)
	{
		$logger = new ProfilerLogger($this->logger, $this->profiler);

		$this->logger->shouldReceive($function)->with($message, $context)->once();
		$this->profiler->shouldReceive('startRecording')->with('file')->once();
		$this->profiler->shouldReceive('stopRecording');
		$this->profiler->shouldReceive('saveTimestamp');
		$logger->$function($message, $context);
	}

	/**
	 * Test the log() function
	 * @doesNotPerformAssertions
	 */
	public function testProfilingLog()
	{
		$logger = new ProfilerLogger($this->logger, $this->profiler);

		$this->logger->shouldReceive('log')->with(LogLevel::WARNING, 'test', ['a' => 'context'])->once();
		$this->profiler->shouldReceive('startRecording')->with('file')->once();
		$this->profiler->shouldReceive('stopRecording');
		$this->profiler->shouldReceive('saveTimestamp');

		$logger->log(LogLevel::WARNING, 'test', ['a' => 'context']);
	}
}
