<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Core\Logger\Type;

use Friendica\Core\Logger\Exception\LoggerException;
use Friendica\Util\Strings;
use Psr\Log\LoggerInterface;

/**
 * A Logger for specific jetstream tasks, which adds a jetstream id to it.
 * Uses the decorator pattern (https://en.wikipedia.org/wiki/Decorator_pattern)
 */
class JetstreamLogger implements LoggerInterface
{
	/** @var int Length of the unique jetstream id */
	const JETSTREAM_ID_LENGTH = 7;

	/**
	 * @var LoggerInterface The original Logger instance
	 */
	private $logger;

	/**
	 * @var string the current jetstream ID
	 */
	private $jetstreamId;

	/**
	 * @param LoggerInterface $logger       The logger for jetstream tasks
	 *
	 * @throws LoggerException
	 */
	public function __construct(LoggerInterface $logger)
	{
		$this->logger = $logger;
		try {
			$this->jetstreamId = Strings::getRandomHex(self::JETSTREAM_ID_LENGTH);
		} catch (\Exception $exception) {
			throw new LoggerException('Cannot generate random Hex.', $exception);
		}
	}

	/**
	 * Adds the jetstream context for each log entry
	 *
	 * @param array $context
	 */
	private function addContext(array &$context)
	{
		$context['jetstream_id'] = $this->jetstreamId;
	}

	/**
	 * Returns the jetstream ID
	 *
	 * @return string
	 */
	public function getJetstreamId(): string
	{
		return $this->jetstreamId;
	}

	public function setJetstreamId(string $jetstreamId): void
	{
		$this->jetstreamId = $jetstreamId;
	}

	public function initJetstreamId(): void
	{
		try {
			$this->jetstreamId = Strings::getRandomHex(self::JETSTREAM_ID_LENGTH);
		} catch (\Exception $exception) {
			throw new LoggerException('Cannot generate random Hex.', $exception);
		}
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
