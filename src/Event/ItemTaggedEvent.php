<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Event;

use Friendica\Core\Event\AbstractEvent;

/**
 * Fired when an item is tagged (e.g. mentioned) by a community owner
 *
 * Can be used by addons to react to an item being tagged by a user.
 */
final class ItemTaggedEvent extends AbstractEvent
{
	public const NAME = 'friendica.data.item_tagged';

	/**
	 * @internal
	 *
	 * @param array<string, mixed> $item
	 * @param array<string, mixed> $user
	 */
	public function __construct(
		private readonly array $item,
		private readonly array $user,
	) {
		parent::__construct(self::NAME);
	}

	/** @return array<string, mixed> */
	public function getItemArray(): array
	{
		return $this->item;
	}

	/** @return array<string, mixed> */
	public function getUserArray(): array
	{
		return $this->user;
	}
}
