<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Core\Logger\Type;

use Friendica\Core\Logger\Capability\IHaveCallIntrospections;
use Friendica\Core\Logger\Exception\LoggerException;
use Friendica\Core\Logger\Exception\LogLevelException;
use Friendica\Util\DateTimeFormat;
use Psr\Log\LogLevel;

/**
 * A Logger instance for logging into a stream (file, stdout, stderr)
 */
class StreamLogger extends AbstractLogger
{
	const NAME = 'stream';

	/**
	 * The minimum loglevel at which this logger will be triggered
	 */
	private int $logLevel;

	/**
	 * The stream, where the current logger is writing into
	 * @var resource|null
	 */
	private $stream;

	/**
	 * The current process ID
	 */
	private int $pid;

	/**
	 * Translates LogLevel log levels to integer values
	 * @var array
	 */
	public const levelToInt = [
		LogLevel::EMERGENCY => 0,
		LogLevel::ALERT     => 1,
		LogLevel::CRITICAL  => 2,
		LogLevel::ERROR     => 3,
		LogLevel::WARNING   => 4,
		LogLevel::NOTICE    => 5,
		LogLevel::INFO      => 6,
		LogLevel::DEBUG     => 7,
	];

	/**
	 * {@inheritdoc}
	 * @param int $logLevel The minimum loglevel at which this logger will be triggered
	 *
	 * @throws LoggerException
	 */
	public function __construct(string $channel, IHaveCallIntrospections $introspection, $stream, int $logLevel, int $pid)
	{
		parent::__construct($channel, $introspection);

		$this->stream   = $stream;
		$this->pid      = $pid;
		$this->logLevel = $logLevel;
	}

	public function close()
	{
		if (is_resource($this->stream)) {
			fclose($this->stream);
		}

		$this->stream = null;
	}

	/**
	 * Adds a new entry to the log
	 *
	 * @param mixed  $level
	 * @param string $message
	 * @param array  $context
	 *
	 * @return void
	 *
	 * @throws LoggerException
	 * @throws LogLevelException
	 */
	protected function addEntry($level, string $message, array $context = [])
	{
		if (!array_key_exists($level, static::levelToInt)) {
			throw new LogLevelException(sprintf('The level "%s" is not valid.', $level));
		}

		$logLevel = static::levelToInt[$level];

		if ($logLevel > $this->logLevel) {
			return;
		}

		$formattedLog = $this->formatLog($level, $message, $context);
		fwrite($this->stream, $formattedLog);
	}

	/**
	 * Formats a log record for the syslog output
	 *
	 * @param mixed  $level   The loglevel/priority
	 * @param string $message The message
	 * @param array  $context The context of this call
	 *
	 * @return string the formatted syslog output
	 *
	 * @throws LoggerException
	 */
	private function formatLog($level, string $message, array $context = []): string
	{
		$record = $this->introspection->getRecord();
		$record = array_merge($record, ['uid' => $this->logUid, 'process_id' => $this->pid]);

		try {
			$logMessage = DateTimeFormat::utcNow(DateTimeFormat::ATOM) . ' ';
		} catch (\Exception $exception) {
			throw new LoggerException('Cannot get current datetime.', $exception);
		}
		$logMessage .= $this->channel . ' ';
		$logMessage .= '[' . strtoupper($level) . ']: ';
		$logMessage .= $this->psrInterpolate($message, $context) . ' ';
		$logMessage .= $this->jsonEncodeArray($context) . ' - ';
		$logMessage .= $this->jsonEncodeArray($record);
		$logMessage .= PHP_EOL;

		return $logMessage;
	}
}
