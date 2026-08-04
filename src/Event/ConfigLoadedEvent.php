<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Event;

use Friendica\Core\Config\Util\ConfigFileManager;
use Friendica\Core\Event\AbstractEvent;

/**
 * Notify that the config was loaded
 */
final class ConfigLoadedEvent extends AbstractEvent
{
	public const NAME = 'friendica.config_loaded';

	public function __construct(private readonly ConfigFileManager $config)
	{
		parent::__construct(self::NAME);
	}

	public function getConfig(): ConfigFileManager
	{
		return $this->config;
	}
}
