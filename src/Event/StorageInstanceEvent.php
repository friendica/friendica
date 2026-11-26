<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Event;

use Friendica\Core\Event\AbstractEvent;
use Friendica\Core\Storage\Capability\ICanReadFromStorage;

/**
 * Fired when a storage backend instance is requested, to allow addons to provide one.
 */
final class StorageInstanceEvent extends AbstractEvent
{
	public const NAME = 'friendica.data.storage_instance';

	/**
	 * @internal
	 */
	public function __construct(
		private readonly string $backendName,
		private ?ICanReadFromStorage $storage = null,
	) {
		parent::__construct(self::NAME);
	}

	public function getBackendName(): string
	{
		return $this->backendName;
	}

	public function getStorage(): ?ICanReadFromStorage
	{
		return $this->storage;
	}

	public function setStorage(?ICanReadFromStorage $storage): void
	{
		$this->storage = $storage;
	}
}
