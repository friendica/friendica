<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Event;

use Friendica\Core\Event\AbstractEvent;

/**
 * Fired when the user tabs of the moderation panel are about to be rendered.
 *
 * Can be used by addons to add, change or remove user tabs.
 */
final class ModerationUsersTabsEvent extends AbstractEvent
{
	public const NAME = 'friendica.data.moderation_users_tabs';

	/**
	 * @internal
	 *
	 * @param array  $tabs        The list of user tabs
	 * @param string $selectedTab The id of the currently selected tab
	 */
	public function __construct(
		private array $tabs,
		private readonly string $selectedTab,
	) {
		parent::__construct(self::NAME);
	}

	/**
	 * @return array The list of user tabs
	 */
	public function getTabsArray(): array
	{
		return $this->tabs;
	}

	public function getSelectedTab(): string
	{
		return $this->selectedTab;
	}

	/**
	 * @param array $tabs The list of user tabs
	 */
	public function setTabsArray(array $tabs): void
	{
		$this->tabs = $tabs;
	}
}
