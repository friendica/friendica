<?php

namespace Friendica\Util\Logger;

use Friendica\Core\System;
use Psr\Log\LoggerInterface;

/**
 * A Logger for specific Worker, which adds an additional id to it.
 *
 * uses the Decorator pattern (https://en.wikipedia.org/wiki/Decorator_pattern)
 */
class WorkerLogger implements LoggerInterface
{
	/**
	 * @var LoggerInterface The original logger
	 */
	private $logger;

	/**
	 * @var string The worker ID
	 */
	private $workerId;

	public function __construct(LoggerInterface $logger)
	{
		$this->logger = $logger;
		$this->workerId = System::processID("wrk");
	}

	/**
	 * @return string The worker I
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
	public function emergency($message, array $context = array())
	{
		$context['worker'] = $this->workerId;
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
	public function alert($message, array $context = array())
	{
		$context['worker'] = $this->workerId;
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
	public function critical($message, array $context = array())
	{
		$context['worker'] = $this->workerId;
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
	public function error($message, array $context = array())
	{
		$context['worker'] = $this->workerId;
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
	public function warning($message, array $context = array())
	{
		$context['worker'] = $this->workerId;
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
	public function notice($message, array $context = array())
	{
		$context['worker'] = $this->workerId;
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
	public function info($message, array $context = array())
	{
		$context['worker'] = $this->workerId;
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
	public function debug($message, array $context = array())
	{
		$context['worker'] = $this->workerId;
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
	public function log($level, $message, array $context = array())
	{
		$context['worker'] = $this->workerId;
		$this->logger->log($level, $message, $context);
	}
}
