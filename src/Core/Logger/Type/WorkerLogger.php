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

namespace Friendica\Core\Logger\Type;

use Friendica\Util\Strings;
use Psr\Log\LoggerInterface;

/**
 * A Logger for specific worker tasks, which adds an additional woker-id to it.
 * Uses the decorator pattern (https://en.wikipedia.org/wiki/Decorator_pattern)
 */
class WorkerLogger implements LoggerInterface
{
	/**
	 * @var LoggerInterface The original Logger instance
	 */
	private $logger;

	/**
	 * @var string the current worker ID
	 */
	private $workerId;

	/**
	 * @var string The called function name
	 */
	private $functionName;

	/**
	 * @param LoggerInterface $logger The logger for worker entries
	 * @param string $functionName The current function name of the worker
	 * @param int $idLength The length of the generated worker ID
	 */
	public function __construct(LoggerInterface $logger, $functionName = '', $idLength = 7)
	{
		$this->logger = $logger;
		$this->functionName = $functionName;
		$this->workerId = Strings::getRandomHex($idLength);
	}

	/**
	 * Sets the function name for additional logging
	 *
	 * @param string $functionName
	 */
	public function setFunctionName(string $functionName)
	{
		$this->functionName = $functionName;
	}

	/**
	 * Adds the worker context for each log entry
	 *
	 * @param array $context
	 */
	private function addContext(array &$context)
	{
		$context['worker_id'] = $this->workerId;
		$context['worker_cmd'] = $this->functionName;
	}

	/**
	 * Returns the worker ID
	 *
	 * @return string
	 */
	public function getWorkerId()
	{
		return $this->workerId;
	}

	/**
	 * System is unusable.
	 *
	 * @param string $message
	 * @param array $context
	 *
	 * @return void
	 */
	public function emergency($message, array $context = [])
	{
		$this->addContext($context);
		$this->logger->emergency($message, $context);
	}

	/**
	 * Action must be taken immediately.
	 *
	 * Example: Entire website down, database unavailable, etc. This should
	 * trigger the SMS alerts and wake you up.
	 *
	 * @param string $message
	 * @param array $context
	 *
	 * @return void
	 */
	public function alert($message, array $context = [])
	{
		$this->addContext($context);
		$this->logger->alert($message, $context);
	}

	/**
	 * Critical conditions.
	 *
	 * Example: Application component unavailable, unexpected exception.
	 *
	 * @param string $message
	 * @param array $context
	 *
	 * @return void
	 */
	public function critical($message, array $context = [])
	{
		$this->addContext($context);
		$this->logger->critical($message, $context);
	}

	/**
	 * Runtime errors that do not require immediate action but should typically
	 * be logged and monitored.
	 *
	 * @param string $message
	 * @param array $context
	 *
	 * @return void
	 */
	public function error($message, array $context = [])
	{
		$this->addContext($context);
		$this->logger->error($message, $context);
	}

	/**
	 * Exceptional occurrences that are not errors.
	 *
	 * Example: Use of deprecated APIs, poor use of an API, undesirable things
	 * that are not necessarily wrong.
	 *
	 * @param string $message
	 * @param array $context
	 *
	 * @return void
	 */
	public function warning($message, array $context = [])
	{
		$this->addContext($context);
		$this->logger->warning($message, $context);
	}

	/**
	 * Normal but significant events.
	 *
	 * @param string $message
	 * @param array $context
	 *
	 * @return void
	 */
	public function notice($message, array $context = [])
	{
		$this->addContext($context);
		$this->logger->notice($message, $context);
	}

	/**
	 * Interesting events.
	 *
	 * Example: User logs in, SQL logs.
	 *
	 * @param string $message
	 * @param array $context
	 *
	 * @return void
	 */
	public function info($message, array $context = [])
	{
		$this->addContext($context);
		$this->logger->info($message, $context);
	}

	/**
	 * Detailed debug information.
	 *
	 * @param string $message
	 * @param array $context
	 *
	 * @return void
	 */
	public function debug($message, array $context = [])
	{
		$this->addContext($context);
		$this->logger->debug($message, $context);
	}

	/**
	 * Logs with an arbitrary level.
	 *
	 * @param mixed $level
	 * @param string $message
	 * @param array $context
	 *
	 * @return void
	 */
	public function log($level, $message, array $context = [])
	{
		$this->addContext($context);
		$this->logger->log($level, $message, $context);
	}
}
