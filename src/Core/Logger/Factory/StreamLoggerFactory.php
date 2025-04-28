<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Core\Logger\Factory;

use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\Logger\Capability\IHaveCallIntrospections;
use Friendica\Core\Logger\Exception\LoggerArgumentException;
use Friendica\Core\Logger\Exception\LogLevelException;
use Friendica\Core\Logger\Type\StreamLogger;
use Friendica\Core\Logger\Util\FileSystemUtil;
use Psr\Log\LoggerInterface;

/**
 * The logger factory for the StreamLogger instance
 *
 * @see StreamLogger
 *
 * @internal
 */
final class StreamLoggerFactory implements LoggerFactory
{
	private IManageConfigValues $config;

	private IHaveCallIntrospections $introspection;

	private FileSystemUtil $fileSystem;

	public function __construct(
		IManageConfigValues $config,
		IHaveCallIntrospections $introspection,
		FileSystemUtil $fileSystem
	) {
		$this->config        = $config;
		$this->introspection = $introspection;
		$this->fileSystem    = $fileSystem;
	}

	/**
	 * Creates and returns a PSR-3 Logger instance.
	 *
	 * Calling this method multiple times with the same parameters SHOULD return the same object.
	 *
	 * @param \Psr\Log\LogLevel::* $logLevel The log level
	 * @param \Friendica\Core\Logger\Capability\LogChannel::* $logChannel The log channel
	 *
	 * @throws LoggerArgumentException
	 * @throws LogLevelException
	 */
	public function createLogger(string $logLevel, string $logChannel): LoggerInterface
	{
		$logfile = $this->config->get('system', 'logfile');

		if (!file_exists($logfile) || !is_writable($logfile)) {
			throw new LoggerArgumentException(sprintf('"%s" is not a valid logfile.', $logfile));
		}

		if (! array_key_exists($logLevel, StreamLogger::levelToInt)) {
			throw new LogLevelException(sprintf('The log level "%s" is not supported by "%s".', $logLevel, StreamLogger::class));
		}

		return new StreamLogger(
			$logChannel,
			$this->introspection,
			$this->fileSystem->createStream($logfile),
			StreamLogger::levelToInt[$logLevel],
			getmypid()
		);
	}
}
