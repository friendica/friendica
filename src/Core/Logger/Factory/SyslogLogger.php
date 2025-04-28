<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Core\Logger\Factory;

use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\Logger\Exception\LoggerException;
use Friendica\Core\Logger\Exception\LogLevelException;
use Friendica\Core\Logger\Type\SyslogLogger as SyslogLoggerClass;
use Psr\Log\LoggerInterface;

/**
 * The logger factory for the SyslogLogger instance
 *
 * @deprecated 2025.02 Implement `\Friendica\Core\Logger\Factory\LoggerFactory` instead
 * @see SyslogLoggerFactory
 * @see SyslogLoggerClass
 */
class SyslogLogger extends AbstractLoggerTypeFactory
{
	/**
	 * Creates a new PSR-3 compliant syslog logger instance
	 *
	 * @param IManageConfigValues $config The system configuration
	 *
	 * @return LoggerInterface The PSR-3 compliant logger instance
	 *
	 * @throws LoggerException in case the logger cannot get created
	 */
	public function create(IManageConfigValues $config): LoggerInterface
	{
		@trigger_error('Class `' . __CLASS__ . '` is deprecated since 2025.02 and will be removed after 5 months, implement `\Friendica\Core\Logger\Factory\LoggerFactory` instead.', E_USER_DEPRECATED);

		$logOpts     = $config->get('system', 'syslog_flags')    ?? SyslogLoggerClass::DEFAULT_FLAGS;
		$logFacility = $config->get('system', 'syslog_facility') ?? SyslogLoggerClass::DEFAULT_FACILITY;
		$loglevel    = SyslogLogger::mapLegacyConfigDebugLevel($config->get('system', 'loglevel'));

		if (array_key_exists($loglevel, SyslogLoggerClass::logLevels)) {
			$loglevel = SyslogLoggerClass::logLevels[$loglevel];
		} else {
			throw new LogLevelException(sprintf('The level "%s" is not valid.', $loglevel));
		}

		return new SyslogLoggerClass($this->channel, $this->introspection, $loglevel, $logOpts, $logFacility);
	}
}
