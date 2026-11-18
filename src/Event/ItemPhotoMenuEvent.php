<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Event;

use Friendica\Core\Event\AbstractEvent;

/**
 * Fired when building the photo menu of an item
 *
 * Can be used by addons to add or modify the menu entries of an item.
 */
final class ItemPhotoMenuEvent extends AbstractEvent
{
	public const NAME = 'friendica.data.item_photo_menu';

	/**
	 * @internal
	 *
	 * @param array<string, mixed> $item
	 * @param array<string, string> $menu
	 */
	public function __construct(
		private readonly array $item,
		private array $menu,
	) {
		parent::__construct(self::NAME);
	}

	/** @return array<string, mixed> */
	public function getItemArray(): array
	{
		return $this->item;
	}

	/** @return array<string, string> */
	public function getMenuArray(): array
	{
		return $this->menu;
	}

	/** @param array<string, string> $menu */
	public function setMenuArray(array $menu): void
	{
		$this->menu = $menu;
	}
}
