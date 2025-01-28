<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Event;

use Friendica\Core\Config\Util\ConfigFileManager;

/**
 * Notify that the config was loaded
 *
 * @internal
 */
final class ConfigLoadedEvent implements NamedEvent
{
	public const CONFIG_LOADED = 'friendica.config_loaded';

	private string $name;

	private ConfigFileManager $config;

	public function __construct(string $name, ConfigFileManager $config)
	{
		$this->name   = $name;
		$this->config = $config;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getConfig(): ConfigFileManager
	{
		return $this->config;
	}
}
