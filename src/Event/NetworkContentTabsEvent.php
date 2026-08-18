<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Event;

use Friendica\Core\Event\AbstractEvent;

/**
 * This event is fired when the network timeline tabs are being rendered.
 *
 * Can be used by addons to modify the network tabs.
 */
final class NetworkContentTabsEvent extends AbstractEvent
{
	public const NAME = 'friendica.data.network_content_tabs';

	/**
	 * @internal
	 *
	 * @param array<int, array> $tabs the network tabs
	 */
	public function __construct(
		private array $tabs,
	) {
		parent::__construct(self::NAME);
	}

	/**
	 * @return array<int, array>
	 */
	public function getTabs(): array
	{
		return $this->tabs;
	}

	/**
	 * @param array<int, array> $tabs
	 */
	public function setTabs(array $tabs): void
	{
		$this->tabs = $tabs;
	}
}
