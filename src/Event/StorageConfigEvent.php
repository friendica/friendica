<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Event;

use Friendica\Core\Event\AbstractEvent;
use Friendica\Core\Storage\Capability\ICanConfigureStorage;

/**
 * Fired when the configuration form for a storage backend is requested, to allow addons to provide the configuration.
 */
final class StorageConfigEvent extends AbstractEvent
{
	public const NAME = 'friendica.data.storage_config';

	/**
	 * @internal
	 */
	public function __construct(
		private readonly string $backendName,
		private ?ICanConfigureStorage $config = null,
	) {
		parent::__construct(self::NAME);
	}

	public function getBackendName(): string
	{
		return $this->backendName;
	}

	public function getConfig(): ?ICanConfigureStorage
	{
		return $this->config;
	}

	public function setConfig(?ICanConfigureStorage $config): void
	{
		$this->config = $config;
	}
}
