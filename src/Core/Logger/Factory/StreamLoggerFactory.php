<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Core\Logger\Factory;

use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\Logger\Capability\IHaveCallIntrospections;
use Friendica\Core\Logger\Exception\LoggerArgumentException;
use Friendica\Core\Logger\Exception\LoggerUnusableException;
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
final readonly class StreamLoggerFactory implements LoggerFactory
{
	public function __construct(private IManageConfigValues $config, private IHaveCallIntrospections $introspection, private FileSystemUtil $fileSystem) {}

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
		$logfile = (string) $this->config->get('system', 'logfile');

		if ($logfile === '') {
			throw new LoggerArgumentException('The config value "system.logfile" is empty, there is nothing to log into.');
		}

		if (! array_key_exists($logLevel, StreamLogger::levelToInt)) {
			throw new LogLevelException(sprintf('The log level "%s" is not supported by "%s".', $logLevel, StreamLogger::class));
		}

		// Opening the stream is the only reliable validation of the log target.
		// A stat based pre-check rejects two targets that createStream() opens fine:
		//  - stream wrapper URLs like "php://stdout", where file_exists() is always
		//    false because the php:// wrapper implements no url_stat() handler.
		//  - a logfile that does not exist yet, which fopen(..., 'ab') creates.
		try {
			$stream = $this->fileSystem->createStream($logfile);
		} catch (LoggerUnusableException $exception) {
			throw new LoggerArgumentException(sprintf('"%s" is not a valid logfile.', $logfile), $exception);
		}

		return new StreamLogger(
			$logChannel,
			$this->introspection,
			$stream,
			StreamLogger::levelToInt[$logLevel],
			getmypid(),
		);
	}
}
