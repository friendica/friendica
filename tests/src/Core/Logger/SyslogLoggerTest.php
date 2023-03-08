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

use Friendica\Core\Logger\Exception\LoggerArgumentException;
use Friendica\Core\Logger\Exception\LoggerException;
use Friendica\Core\Logger\Exception\LogLevelException;
use Friendica\Core\Logger\Type\SyslogLogger;
use Psr\Log\LogLevel;

class SyslogLoggerTest extends AbstractLoggerTest
{
	/**
	 * @var SyslogLoggerWrapper
	 */
	private $logger;

	protected function setUp(): void
	{
		parent::setUp();

		$this->introspection->shouldReceive('addClasses')->with([SyslogLogger::class]);
		$this->config->shouldReceive('get')->with('system', 'syslog_flags')->andReturn(SyslogLogger::DEFAULT_FLAGS)
					 ->once();
		$this->config->shouldReceive('get')->with('system', 'syslog_facility')
					 ->andReturn(SyslogLogger::DEFAULT_FACILITY)->once();
	}

	/**
	 * {@inheritdoc}
	 */
	protected function getContent()
	{
		return $this->logger->getContent();
	}

	/**
	 * {@inheritdoc}
	 */
	protected function getInstance($level = LogLevel::DEBUG)
	{
		$this->logger = new SyslogLoggerWrapper('test', $this->config, $this->introspection, $level);

		return $this->logger;
	}


	/**
	 * Test when the minimum level is not valid
	 */
	public function testWrongMinimumLevel()
	{
		$this->expectException(LogLevelException::class);
		$this->expectExceptionMessageMatches("/The level \".*\" is not valid./");
		
		$logger = new SyslogLoggerWrapper('test', $this->config, $this->introspection, 'NOPE');
	}

	/**
	 * Test when the minimum level is not valid
	 */
	public function testWrongLogLevel()
	{
		$this->expectException(LogLevelException::class);
		$this->expectExceptionMessageMatches("/The level \".*\" is not valid./");

		$logger = new SyslogLoggerWrapper('test', $this->config, $this->introspection);

		$logger->log('NOPE', 'a test');
	}

	/**
	 * Test the close() method
	 * @doesNotPerformAssertions
	 */
	public function testClose()
	{
		$logger = new SyslogLoggerWrapper('test', $this->config, $this->introspection);
		$logger->emergency('test');
		$logger->close();
		// Reopened itself
		$logger->emergency('test');
	}
}
