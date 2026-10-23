<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Core\Logger\Factory;

use Friendica\Core\Config\Capability\IManageConfigValues;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Delegates the creation of a logger based on config to other factories
 *
 * @internal
 */
final class DelegatingLoggerFactory implements LoggerFactory
{
	/** @var array<string,LoggerFactory> */
	private array $factories = [];

	public function __construct(private readonly IManageConfigValues $config) {}

	public function registerFactory(string $name, LoggerFactory $factory): void
	{
		$this->factories[$name] = $factory;
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
		$factoryName = $this->config->get('system', 'logger_config') ?? '';

		if (!array_key_exists($factoryName, $this->factories)) {
			$this->reportFallback(sprintf(
				'There is no logger registered for the config value "system.logger_config" = "%s".',
				$factoryName,
			));

			return new NullLogger();
		}

		$factory = $this->factories[$factoryName];

		try {
			$logger = $factory->createLogger($logLevel, $logChannel);
		} catch (\Throwable $exception) {
			$this->reportFallback(sprintf(
				'The logger "%s" could not be created: %s',
				$factoryName,
				$exception->getMessage(),
			));

			return new NullLogger();
		}

		return $logger;
	}

	/**
	 * Reports that logging has been silently disabled.
	 *
	 * An instance falling back to a NullLogger looks exactly like an idle one.
	 * The logger cannot report its own failure, so this goes to PHP's error log.
	 */
	private function reportFallback(string $message): void
	{
		error_log('Friendica: logging is disabled, no log entries will be written. ' . $message);
	}
}
