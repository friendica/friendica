<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Event;

use Friendica\Core\Event\AbstractEvent;

/**
 * This event is fired when the contact photo menu is being built.
 *
 * Can be used by addons to modify the contact photo menu.
 */
final class ContactPhotoMenuEvent extends AbstractEvent
{
	public const NAME = 'friendica.data.contact_photo_menu';

	/**
	 * @internal
	 *
	 * @param array<string, mixed> $contact
	 * @param array<string, array> $menu
	 */
	public function __construct(
		private readonly array $contact,
		private array $menu,
	) {
		parent::__construct(self::NAME);
	}

	/** @return array<string, mixed> */
	public function getContact(): array
	{
		return $this->contact;
	}

	/** @return array<string, array> */
	public function getMenu(): array
	{
		return $this->menu;
	}

	/** @param array<string, array> $menu */
	public function setMenu(array $menu): void
	{
		$this->menu = $menu;
	}
}
