<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Event;

use Friendica\Core\Event\AbstractEvent;

/**
 * Fired when formatting an item for display on the directory page
 *
 * Can be used by addons to modify the entry of the directory.
 */
final class DirectoryItemEvent extends AbstractEvent
{
	public const NAME = 'friendica.data.directory_item';

	/**
	 * @internal
	 *
	 * @param array<string, mixed> $contact
	 * @param array<string, mixed> $entry
	 */
	public function __construct(
		private readonly array $contact,
		private array $entry,
	) {
		parent::__construct(self::NAME);
	}

	/** @return array<string, mixed> */
	public function getContactArray(): array
	{
		return $this->contact;
	}

	/** @return array<string, mixed> */
	public function getEntryArray(): array
	{
		return $this->entry;
	}

	/** @param array<string, mixed> $entry */
	public function setEntryArray(array $entry): void
	{
		$this->entry = $entry;
	}
}
