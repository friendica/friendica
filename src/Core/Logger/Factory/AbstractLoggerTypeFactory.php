<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Core\Logger\Factory;

use Friendica\Core\Logger\Capability\IHaveCallIntrospections;
use Psr\Log\LogLevel;

/**
 * Abstract class for creating logger types, which includes common necessary logic/content
 *
 * @deprecated 2026.01 Implement `\Friendica\Core\Logger\Factory\LoggerFactory` instead
 */
abstract class AbstractLoggerTypeFactory
{
	/** @var string */
	protected $channel;
	/** @var IHaveCallIntrospections */
	protected $introspection;

	/**
	 * @param string $channel The channel for the logger
	 */
	public function __construct(IHaveCallIntrospections $introspection, string $channel)
	{
		@trigger_error('Class `' . self::class . '` is deprecated since 2026.01 and will be removed after 5 months, implement `\Friendica\Core\Logger\Factory\LoggerFactory` instead.', E_USER_DEPRECATED);

		$this->channel       = $channel;
		$this->introspection = $introspection;
	}

	/**
	 * Mapping a legacy level to the PSR-3 compliant levels
	 *
	 * @see https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-3-logger-interface.md#5-psrlogloglevel
	 *
	 * @param string $level the level to be mapped
	 *
	 * @return string the PSR-3 compliant level
	 */
	protected static function mapLegacyConfigDebugLevel(string $level): string
	{
		switch ($level) {
			// legacy WARNING
			case "0":
				return LogLevel::ERROR;
				// legacy INFO
			case "1":
				return LogLevel::WARNING;
				// legacy TRACE
			case "2":
				return LogLevel::NOTICE;
				// legacy DEBUG
			case "3":
				return LogLevel::INFO;
				// legacy DATA
			case "4":
				// legacy ALL
			case "5":
				return LogLevel::DEBUG;
				// default if nothing set
			default:
				return $level;
		}
	}
}
