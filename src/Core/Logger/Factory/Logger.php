<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Core\Logger\Factory;

use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\Hooks\Capability\ICanCreateInstances;
use Friendica\Core\Logger\Capability\LogChannel;
use Friendica\Core\Logger\Type\ProfilerLogger as ProfilerLoggerClass;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Throwable;

/**
 * The logger factory for the core logging instances
 *
 * @deprecated 2025.02 Implement `\Friendica\Core\Logger\Factory\LoggerFactory` instead
 */
class Logger
{
	/** @var string The channel */
	protected $channel;

	public function __construct(string $channel = LogChannel::DEFAULT)
	{
		@trigger_error('Class `' . __CLASS__ . '` is deprecated since 2025.02 and will be removed after 5 months, implement `\Friendica\Core\Logger\Factory\LoggerFactory` instead.', E_USER_DEPRECATED);

		$this->channel = $channel;
	}

	public function create(ICanCreateInstances $instanceCreator, IManageConfigValues $config, Profiler $profiler): LoggerInterface
	{
		if (empty($config->get('system', 'debugging') ?? false)) {
			return new NullLogger();
		}

		$name = $config->get('system', 'logger_config') ?? '';

		try {
			/** @var LoggerInterface $logger */
			$logger = $instanceCreator->create(LoggerInterface::class, $name, [$this->channel]);
			if ($config->get('system', 'profiling') ?? false) {
				return new ProfilerLoggerClass($logger, $profiler);
			} else {
				return $logger;
			}
		} catch (Throwable $e) {
			// No logger ...
			return new NullLogger();
		}
	}
}
