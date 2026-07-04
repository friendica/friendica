<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Content;

class ItemTestDice
{
	public function __construct(private $profiler, private $eventDispatcher, private $config, private $l10n, private $baseUrl, private readonly \Closure $mockFactory) {}

	public function create($class)
	{
		if ($class === \Friendica\Util\Profiler::class) {
			return $this->profiler;
		}

		if ($class === \Psr\EventDispatcher\EventDispatcherInterface::class) {
			return $this->eventDispatcher;
		}

		if ($class === \Friendica\Core\Config\Capability\IManageConfigValues::class) {
			return $this->config;
		}

		if ($class === \Friendica\Core\L10n::class) {
			return $this->l10n;
		}

		if ($class === \Friendica\App\BaseURL::class) {
			return $this->baseUrl;
		}

		return ($this->mockFactory)($class);
	}
}
