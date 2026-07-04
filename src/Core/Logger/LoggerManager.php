<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Core\Logger;

use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\Logger\Capability\LogChannel;
use Friendica\Core\Logger\Factory\LoggerFactory;
use Friendica\Core\Logger\Type\ProfilerLogger;
use Friendica\Core\Logger\Type\WorkerLogger;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;

/**
 * Manager for the core logging instances
 *
 * @internal
 */
final class LoggerManager
{
	/**
	 * Workaround: $logger must be static
	 * because Dice always creates a new LoggerManager object
	 *
	 * @var LoggerInterface|null
	 */
	private static $logger = null;

	/**
	 * Workaround: $logChannel must be static
	 * because Dice always creates a new LoggerManager object
	 */
	private static string $logChannel = LogChannel::DEFAULT;

	private readonly bool $debug;

	private readonly string $logLevel;

	private readonly bool $profiling;

	public function __construct(private readonly IManageConfigValues $config, private readonly LoggerFactory $factory)
	{
		$this->debug     = (bool) $this->config->get('system', 'debugging', false);
		$this->logLevel  = (string) $this->config->get('system', 'loglevel', LogLevel::NOTICE);
		$this->profiling = (bool) $this->config->get('system', 'profiling', false);
	}

	public function changeLogChannel(string $logChannel): void
	{
		self::$logChannel = $logChannel;
		self::$logger     = null;
	}

	/**
	 * (Creates and) Returns the logger instance
	 */
	public function getLogger(): LoggerInterface
	{
		if (self::$logger === null) {
			self::$logger = $this->createLogger();
		}

		return self::$logger;
	}

	private function createLogger(): LoggerInterface
	{
		// Always create NullLogger if debug is disabled
		if ($this->debug === false) {
			$logger = new NullLogger();
		} else {
			$logger = $this->factory->createLogger($this->logLevel, self::$logChannel);
		}

		if ($this->profiling === true) {
			$profiler = new Profiler($this->config);

			$logger = new ProfilerLogger($logger, $profiler);
		}

		// Decorate Logger as WorkerLogger for BC
		if (self::$logChannel === LogChannel::WORKER || self::$logChannel === LogChannel::JETSTREAM) {
			$logger = new WorkerLogger($logger);
		}

		return $logger;
	}
}
