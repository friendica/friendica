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
final class ConfigLoadedEvent extends Event
{
	public const CONFIG_LOADED = 'friendica.config_loaded';

	private ConfigFileManager $config;

	public function __construct(string $name, ConfigFileManager $config)
	{
		parent::__construct($name);

		$this->config = $config;
	}

	public function getConfig(): ConfigFileManager
	{
		return $this->config;
	}
}
