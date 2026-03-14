<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Core\Logger\Factory;

use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\Logger\Capability\LogChannel;
use Friendica\Core\Logger\Exception\LoggerArgumentException;
use Friendica\Core\Logger\Exception\LoggerException;
use Friendica\Core\Logger\Exception\LogLevelException;
use Friendica\Core\Logger\Type\StreamLogger as StreamLoggerClass;
use Friendica\Core\Logger\Util\FileSystem;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * The logger factory for the StreamLogger instance
 *
 * @deprecated 2025.07 Implement `\Friendica\Core\Logger\Factory\LoggerFactory` instead
 * @see StreamLoggerFactory
 * @see StreamLoggerClass
 */
class StreamLogger extends AbstractLoggerTypeFactory
{
	/**
	 * Creates a new PSR-3 compliant stream logger instance
	 *
	 * @param IManageConfigValues $config   The system configuration
	 * @param string|null         $logfile  (optional) A given logfile which should be used as stream (e.g. in case of
	 *                                      developer logging)
	 * @param string|null         $channel  (optional) A given channel in case it is different from the default
	 *
	 * @return LoggerInterface The PSR-3 compliant logger instance
	 *
	 * @throws LoggerException in case the logger cannot get created
	 */
	public function create(IManageConfigValues $config, string $logfile = null, string $channel = null): LoggerInterface
	{
		@trigger_error('Class `' . self::class . '` is deprecated since 2025.07 and will be removed after 5 months, implement `\Friendica\Core\Logger\Factory\LoggerFactory` instead.', E_USER_DEPRECATED);

		$fileSystem = new FileSystem();

		$logfile ??= $config->get('system', 'logfile');
		if (!@file_exists($logfile) || !@is_writable($logfile)) {
			throw new LoggerArgumentException(sprintf('%s is not a valid logfile', $logfile));
		}

		$loglevel = static::mapLegacyConfigDebugLevel($config->get('system', 'loglevel'));

		if (array_key_exists($loglevel, StreamLoggerClass::levelToInt)) {
			$loglevel = StreamLoggerClass::levelToInt[$loglevel];
		} else {
			throw new LogLevelException(sprintf('The level "%s" is not valid.', $loglevel));
		}

		$stream = $fileSystem->createStream($logfile);

		return new StreamLoggerClass($channel ?? $this->channel, $this->introspection, $stream, $loglevel, getmypid());
	}

	/**
	 * Creates a new PSR-3 compliant develop logger
	 *
	 * If you want to debug only interactions from your IP or the IP of a remote server for federation debug,
	 * you'll use this logger instance for the duration of your work.
	 *
	 * It should never get filled during normal usage of Friendica
	 *
	 * @return LoggerInterface The PSR-3 compliant logger instance
	 *
	 * @throws LoggerException
	 */
	public function createDev(IManageConfigValues $config)
	{
		$debugging   = $config->get('system', 'debugging');
		$logfile     = $config->get('system', 'dlogfile');
		$developerIp = $config->get('system', 'dlogip');

		if ((!isset($developerIp) || !$debugging)
			&& (!is_file($logfile) || is_writable($logfile))) {
			return new NullLogger();
		}

		return $this->create($config, $logfile, LogChannel::DEV);
	}
}
