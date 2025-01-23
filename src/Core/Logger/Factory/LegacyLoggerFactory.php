<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Core\Logger\Factory;

use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\Hooks\Capability\ICanCreateInstances;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

/**
 * Bridge for the legacy Logger factory.
 *
 * This class can be removed after the following classes are replaced or
 * refactored implementing the `\Friendica\Core\Logger\Factory\LoggerFactory`:
 *
 * - Friendica\Core\Logger\Factory\StreamLogger
 * - Friendica\Core\Logger\Factory\SyslogLogger
 * - monolog addon: Friendica\Addon\monolog\src\Factory\Monolog
 *
 * @see \Friendica\Core\Logger\Factory\StreamLogger
 * @see \Friendica\Core\Logger\Factory\SyslogLogger
 *
 * @internal
 */
final class LegacyLoggerFactory implements LoggerFactory
{
	private ICanCreateInstances $instanceCreator;

	private IManageConfigValues $config;

	private Profiler $profiler;

	public function __construct(ICanCreateInstances $instanceCreator, IManageConfigValues $config, Profiler $profiler)
	{
		$this->instanceCreator = $instanceCreator;
		$this->config          = $config;
		$this->profiler        = $profiler;
	}

	/**
	 * Creates and returns a PSR-3 Logger instance.
	 *
	 * Calling this method multiple times with the same parameters SHOULD return the same object.
	 *
	 * @param \Psr\Log\LogLevel::* $logLevel The log level
	 * @param \Friendica\Core\Logger\Capability\LogChannel::* $logChannel The log channel
	 */
	public function createLogger(string $logLevel, string $logChannel): LoggerInterface
	{
		$factory = new Logger($logChannel);

		return $factory->create($this->instanceCreator, $this->config, $this->profiler);
	}
}
