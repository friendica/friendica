<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Event;

use Friendica\Core\Event\AbstractEvent;

/**
 * Fired when the app menu entries are about to be rendered.
 *
 * Can be used by addons to add menu entries.
 */
final class AppMenuEvent extends AbstractEvent
{
	public const NAME = 'friendica.data.app_menu';

	/**
	 * @internal
	 *
	 * @param array $appMenu The list of app menu HTML entries
	 */
	public function __construct(
		private array $appMenu,
	) {
		parent::__construct(self::NAME);
	}

	/**
	 * @return array The list of app menu HTML entries
	 */
	public function getAppMenuArray(): array
	{
		return $this->appMenu;
	}

	/**
	 * @param array $appMenu The list of app menu HTML entries
	 */
	public function setAppMenuArray(array $appMenu): void
	{
		$this->appMenu = $appMenu;
	}
}
