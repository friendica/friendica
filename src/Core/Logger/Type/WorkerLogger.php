<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Core\Logger\Type;

use Friendica\Core\Logger\Capability\DefaultContextLogger;
use Friendica\Core\Logger\Exception\LoggerException;
use Friendica\Util\Strings;
use Psr\Log\LoggerInterface;

/**
 * A Logger for specific worker tasks, which adds a worker id to it.
 * Uses the decorator pattern (https://en.wikipedia.org/wiki/Decorator_pattern)
 */
class WorkerLogger implements LoggerInterface, DefaultContextLogger
{
	/** @var int Length of the unique worker id */
	const WORKER_ID_LENGTH = 7;

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

	private array $defaultContext = [];

	/**
	 * @param LoggerInterface $logger       The logger for worker entries
	 *
	 * @throws LoggerException
	 */
	public function __construct(LoggerInterface $logger)
	{
		$this->logger = $logger;
		try {
			$this->workerId = Strings::getRandomHex(self::WORKER_ID_LENGTH);
		} catch (\Exception $exception) {
			throw new LoggerException('Cannot generate random Hex.', $exception);
		}

		$this->defaultContext = [
			'worker_id'  => $this->workerId,
			'worker_cmd' => null,
		];
	}

	/**
	 * @return array The old context that will be replaced with the new context
	 */
	public function replaceDefaultContext(array $defaultContext): array
	{
		$oldContext           = $this->defaultContext;
		$this->defaultContext = $defaultContext;

		return $oldContext;
	}

	/**
	 * Sets the function name for additional logging
	 *
	 * @param string $functionName
	 *
	 * @throws LoggerException
	 */
	public function setFunctionName(string $functionName)
	{
		$this->functionName = $functionName;
		try {
			$this->workerId = Strings::getRandomHex(self::WORKER_ID_LENGTH);
		} catch (\Exception $exception) {
			throw new LoggerException('Cannot generate random Hex.', $exception);
		}

		$this->defaultContext = [
			'worker_id'  => $this->workerId,
			'worker_cmd' => $this->functionName,
		];
	}

	/**
	 * Adds the default context for each log entry
	 */
	private function addDefaultContext(array $context): array
	{
		return $context + $this->defaultContext;
	}

	/**
	 * Returns the worker ID
	 *
	 * @return string
	 */
	public function getWorkerId(): string
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
		$context = $this->addDefaultContext($context);
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
		$context = $this->addDefaultContext($context);
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
		$context = $this->addDefaultContext($context);
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
		$context = $this->addDefaultContext($context);
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
		$context = $this->addDefaultContext($context);
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
		$context = $this->addDefaultContext($context);
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
		$context = $this->addDefaultContext($context);
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
		$context = $this->addDefaultContext($context);
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
		$context = $this->addDefaultContext($context);
		$this->logger->log($level, $message, $context);
	}
}
