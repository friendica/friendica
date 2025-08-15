<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Core\Logger\Type;

use Friendica\Core\Logger\Capability\IHaveCallIntrospections;
use Friendica\Core\Logger\Exception\LoggerException;
use Friendica\Core\Logger\Exception\LogLevelException;
use Psr\Log\LogLevel;

/**
 * A Logger instance for syslogging (fast, but simple)
 * @see http://php.net/manual/en/function.syslog.php
 */
class SyslogLogger extends AbstractLogger
{
	const NAME = 'syslog';

	const IDENT = 'Friendica';

	/** @var int The default syslog flags */
	const DEFAULT_FLAGS = LOG_PID | LOG_ODELAY | LOG_CONS;
	/** @var int The default syslog facility */
	const DEFAULT_FACILITY = LOG_USER;

	/**
	 * Translates LogLevel log levels to syslog log priorities.
	 * @var array<string,int>
	 */
	public const logLevels = [
		LogLevel::DEBUG     => LOG_DEBUG,
		LogLevel::INFO      => LOG_INFO,
		LogLevel::NOTICE    => LOG_NOTICE,
		LogLevel::WARNING   => LOG_WARNING,
		LogLevel::ERROR     => LOG_ERR,
		LogLevel::CRITICAL  => LOG_CRIT,
		LogLevel::ALERT     => LOG_ALERT,
		LogLevel::EMERGENCY => LOG_EMERG,
	];

	/**
	 * Translates log priorities to string outputs
	 * @var array
	 */
	protected const logToString = [
		LOG_DEBUG   => 'DEBUG',
		LOG_INFO    => 'INFO',
		LOG_NOTICE  => 'NOTICE',
		LOG_WARNING => 'WARNING',
		LOG_ERR     => 'ERROR',
		LOG_CRIT    => 'CRITICAL',
		LOG_ALERT   => 'ALERT',
		LOG_EMERG   => 'EMERGENCY'
	];

	/**
	 * Indicates what logging options will be used when generating a log message
	 * @see http://php.net/manual/en/function.openlog.php#refsect1-function.openlog-parameters
	 */
	private int $logOpts;

	/**
	 * Used to specify what type of program is logging the message
	 * @see http://php.net/manual/en/function.openlog.php#refsect1-function.openlog-parameters
	 */
	private int $logFacility;

	/**
	 * The minimum loglevel at which this logger will be triggered
	 */
	private int $logLevel;

	/**
	 * A error message of the current operation
	 */
	private string $errorMessage;

	/**
	 * {@inheritdoc}
	 *
	 * @param int $logLevel    The minimum loglevel at which this logger will be triggered
	 * @param int $logOptions
	 * @param int $logFacility
	 */
	public function __construct(string $channel, IHaveCallIntrospections $introspection, int $logLevel, int $logOptions, int $logFacility)
	{
		parent::__construct($channel, $introspection);

		$this->logOpts     = $logOptions;
		$this->logFacility = $logFacility;
		$this->logLevel    = $logLevel;
	}

	/**
	 * Adds a new entry to the syslog
	 *
	 * @param mixed  $level
	 * @param string $message
	 * @param array  $context
	 *
	 * @throws LogLevelException in case the level isn't valid
	 * @throws LoggerException In case the syslog cannot be opened for writing
	 */
	protected function addEntry($level, string $message, array $context = [])
	{
		$logLevel = $this->mapLevelToPriority($level);

		if ($logLevel > $this->logLevel) {
			return;
		}

		$formattedLog = $this->formatLog($logLevel, $message, $context);
		$this->write($logLevel, $formattedLog);
	}

	/**
	 * Maps the LogLevel (@see LogLevel) to a SysLog priority (@see http://php.net/manual/en/function.syslog.php#refsect1-function.syslog-parameters)
	 *
	 * @param string $level A LogLevel
	 *
	 * @return int The SysLog priority
	 *
	 * @throws LogLevelException If the loglevel isn't valid
	 */
	public function mapLevelToPriority(string $level): int
	{
		if (!array_key_exists($level, static::logLevels)) {
			throw new LogLevelException(sprintf('The level "%s" is not valid.', $level));
		}

		return static::logLevels[$level];
	}

	/**
	 * Closes the Syslog
	 */
	public function close()
	{
		closelog();
	}

	/**
	 * Writes a message to the syslog
	 *
	 * @see http://php.net/manual/en/function.syslog.php#refsect1-function.syslog-parameters
	 *
	 * @param int    $priority The Priority
	 * @param string $message  The message of the log
	 *
	 * @throws LoggerException In case the syslog cannot be opened/written
	 */
	private function write(int $priority, string $message)
	{
		set_error_handler([$this, 'customErrorHandler']);
		$opened = openlog(self::IDENT, $this->logOpts, $this->logFacility);
		restore_error_handler();

		if (!$opened) {
			throw new LoggerException(sprintf('Can\'t open syslog for ident "%s" and facility "%s": ' . $this->errorMessage, $this->channel, (string) $this->logFacility));
		}

		$this->syslogWrapper($priority, $message);
	}

	/**
	 * Formats a log record for the syslog output
	 *
	 * @param int    $level   The loglevel/priority
	 * @param string $message The message
	 * @param array  $context The context of this call
	 *
	 * @return string the formatted syslog output
	 */
	private function formatLog(int $level, string $message, array $context = []): string
	{
		$record = $this->introspection->getRecord();
		$record = array_merge($record, ['uid' => $this->logUid]);

		$logMessage = $this->channel . ' ';
		$logMessage .= '[' . static::logToString[$level] . ']: ';
		$logMessage .= $this->psrInterpolate($message, $context) . ' ';
		$logMessage .= $this->jsonEncodeArray($context) . ' - ';
		$logMessage .= $this->jsonEncodeArray($record);

		return $logMessage;
	}

	private function customErrorHandler($code, $msg)
	{
		$this->errorMessage = preg_replace('{^(fopen|mkdir)\(.*?\): }', '', $msg);
	}

	/**
	 * A syslog wrapper to make syslog functionality testable
	 *
	 * @param int    $level The syslog priority
	 * @param string $entry The message to send to the syslog function
	 *
	 * @throws LoggerException
	 */
	protected function syslogWrapper(int $level, string $entry)
	{
		set_error_handler([$this, 'customErrorHandler']);
		$written = syslog($level, $entry);
		restore_error_handler();

		if (!$written) {
			throw new LoggerException(sprintf('Can\'t write into syslog for ident "%s" and facility "%s": ' . $this->errorMessage, $this->channel, (string) $this->logFacility));
		}
	}
}
