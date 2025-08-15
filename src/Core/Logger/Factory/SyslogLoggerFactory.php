<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Core\Logger\Factory;

use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\Logger\Capability\IHaveCallIntrospections;
use Friendica\Core\Logger\Exception\LogLevelException;
use Friendica\Core\Logger\Type\SyslogLogger;
use Psr\Log\LoggerInterface;

/**
 * The logger factory for the SyslogLogger instance
 *
 * @see SyslogLogger
 *
 * @internal
 */
final class SyslogLoggerFactory implements LoggerFactory
{
	private IManageConfigValues $config;

	private IHaveCallIntrospections $introspection;

	public function __construct(
		IManageConfigValues $config,
		IHaveCallIntrospections $introspection
	) {
		$this->config        = $config;
		$this->introspection = $introspection;
	}

	/**
	 * Creates and returns a PSR-3 Logger instance.
	 *
	 * Calling this method multiple times with the same parameters SHOULD return the same object.
	 *
	 * @param \Psr\Log\LogLevel::* $logLevel The log level
	 * @param \Friendica\Core\Logger\Capability\LogChannel::* $logChannel The log channel
	 *
	 * @throws LogLevelException
	 */
	public function createLogger(string $logLevel, string $logChannel): LoggerInterface
	{
		$logOpts     = (int) $this->config->get('system', 'syslog_flags')    ?? SyslogLogger::DEFAULT_FLAGS;
		$logFacility = (int) $this->config->get('system', 'syslog_facility') ?? SyslogLogger::DEFAULT_FACILITY;

		if (!array_key_exists($logLevel, SyslogLogger::logLevels)) {
			throw new LogLevelException(sprintf('The log level "%s" is not supported by "%s".', $logLevel, SyslogLogger::class));
		}

		return new SyslogLogger(
			$logChannel,
			$this->introspection,
			SyslogLogger::logLevels[$logLevel],
			$logOpts,
			$logFacility
		);
	}
}
