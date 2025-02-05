<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Core;

use Friendica\DI;
use Psr\Log\LoggerInterface;

/**
 * Logger functions
 *
 * @deprecated 2025.02 Use constructor injection or `DI::logger()` instead
 */
class Logger
{
	private static function getInstance(): LoggerInterface
	{
		return DI::logger();
	}

	/**
	 * System is unusable.
	 *
	 * @see LoggerInterface::emergency()
	 *
	 * @param string $message Message to log
	 * @param array  $context Optional variables
	 * @return void
	 * @throws \Exception
	 */
	public static function emergency(string $message, array $context = [])
	{
		@trigger_error('Class `' . __CLASS__ . '` is deprecated since 2025.02 and will be removed after 5 months, use constructor injection or `DI::logger()` instead.', E_USER_DEPRECATED);

		self::getInstance()->emergency($message, $context);
	}

	/**
	 * Action must be taken immediately.
	 * @see LoggerInterface::alert()
	 *
	 * Example: Entire website down, database unavailable, etc. This should
	 * trigger the SMS alerts and wake you up.
	 *
	 * @param string $message Message to log
	 * @param array  $context Optional variables
	 * @return void
	 * @throws \Exception
	 */
	public static function alert(string $message, array $context = [])
	{
		@trigger_error('Class `' . __CLASS__ . '` is deprecated since 2025.02 and will be removed after 5 months, use constructor injection or `DI::logger()` instead.', E_USER_DEPRECATED);

		self::getInstance()->alert($message, $context);
	}

	/**
	 * Critical conditions.
	 * @see LoggerInterface::critical()
	 *
	 * Example: Application component unavailable, unexpected exception.
	 *
	 * @param string $message Message to log
	 * @param array  $context Optional variables
	 * @return void
	 * @throws \Exception
	 */
	public static function critical(string $message, array $context = [])
	{
		@trigger_error('Class `' . __CLASS__ . '` is deprecated since 2025.02 and will be removed after 5 months, use constructor injection or `DI::logger()` instead.', E_USER_DEPRECATED);

		self::getInstance()->critical($message, $context);
	}

	/**
	 * Runtime errors that do not require immediate action but should typically
	 * be logged and monitored.
	 * @see LoggerInterface::error()
	 *
	 * @param string $message Message to log
	 * @param array  $context Optional variables
	 * @return void
	 * @throws \Exception
	 */
	public static function error(string $message, array $context = [])
	{
		@trigger_error('Class `' . __CLASS__ . '` is deprecated since 2025.02 and will be removed after 5 months, use constructor injection or `DI::logger()` instead.', E_USER_DEPRECATED);

		self::getInstance()->error($message, $context);
	}

	/**
	 * Exceptional occurrences that are not errors.
	 * @see LoggerInterface::warning()
	 *
	 * Example: Use of deprecated APIs, poor use of an API, undesirable things
	 * that are not necessarily wrong.
	 *
	 * @param string $message Message to log
	 * @param array  $context Optional variables
	 * @return void
	 * @throws \Exception
	 */
	public static function warning(string $message, array $context = [])
	{
		@trigger_error('Class `' . __CLASS__ . '` is deprecated since 2025.02 and will be removed after 5 months, use constructor injection or `DI::logger()` instead.', E_USER_DEPRECATED);

		self::getInstance()->warning($message, $context);
	}

	/**
	 * Normal but significant events.
	 * @see LoggerInterface::notice()
	 *
	 * @param string $message Message to log
	 * @param array  $context Optional variables
	 * @return void
	 * @throws \Exception
	 */
	public static function notice(string $message, array $context = [])
	{
		@trigger_error('Class `' . __CLASS__ . '` is deprecated since 2025.02 and will be removed after 5 months, use constructor injection or `DI::logger()` instead.', E_USER_DEPRECATED);

		self::getInstance()->notice($message, $context);
	}

	/**
	 * Interesting events.
	 * @see LoggerInterface::info()
	 *
	 * Example: User logs in, SQL logs.
	 *
	 * @param string $message
	 * @param array  $context
	 *
	 * @return void
	 * @throws \Exception
	 */
	public static function info(string $message, array $context = [])
	{
		@trigger_error('Class `' . __CLASS__ . '` is deprecated since 2025.02 and will be removed after 5 months, use constructor injection or `DI::logger()` instead.', E_USER_DEPRECATED);

		self::getInstance()->info($message, $context);
	}

	/**
	 * Detailed debug information.
	 * @see LoggerInterface::debug()
	 *
	 * @param string $message Message to log
	 * @param array  $context Optional variables
	 * @return void
	 * @throws \Exception
	 */
	public static function debug(string $message, array $context = [])
	{
		@trigger_error('Class `' . __CLASS__ . '` is deprecated since 2025.02 and will be removed after 5 months, use constructor injection or `DI::logger()` instead.', E_USER_DEPRECATED);

		self::getInstance()->debug($message, $context);
	}
}
