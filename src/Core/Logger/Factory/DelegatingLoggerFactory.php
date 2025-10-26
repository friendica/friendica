<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
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
	private IManageConfigValues $config;

	/** @var array<string,LoggerFactory> */
	private array $factories = [];

	public function __construct(IManageConfigValues $config)
	{
		$this->config = $config;
	}

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

		/**
		 * @deprecated 2025.07 The value `monolog` for `system.logger_config` inside the `config/local.config.php` file is deprecated, please use `stream` or `syslog` instead.
		 */
		if ($factoryName === 'monolog') {
			@trigger_error('The config `system.logger_config` with value `monolog` is deprecated since 2025.07 and will stop working in 5 months, please change the value to `stream` or `syslog` in the `config/local.config.php` file.', \E_USER_DEPRECATED);

			$factoryName = 'stream';
		}

		if (!array_key_exists($factoryName, $this->factories)) {
			return new NullLogger();
		}

		$factory = $this->factories[$factoryName];

		try {
			$logger = $factory->createLogger($logLevel, $logChannel);
		} catch (\Throwable $th) {
			return new NullLogger();
		}

		return $logger;
	}
}
