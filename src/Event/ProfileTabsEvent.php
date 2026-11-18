<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Event;

use Friendica\Core\Event\AbstractEvent;

/**
 * Fired when the profile page tabs are being built.
 *
 * Can be used by addons to modify the list of tabs shown on the profile page.
 */
final class ProfileTabsEvent extends AbstractEvent
{
	public const NAME = 'friendica.data.profile_tabs';

	/**
	 * @internal
	 *
	 * @param array<int, array<string, mixed>> $tabs
	 */
	public function __construct(
		private readonly bool $isOwner,
		private readonly string $nickname,
		private readonly string $tab,
		private array $tabs,
	) {
		parent::__construct(self::NAME);
	}

	public function isOwner(): bool
	{
		return $this->isOwner;
	}

	public function getNickname(): string
	{
		return $this->nickname;
	}

	public function getTab(): string
	{
		return $this->tab;
	}

	/** @return array<int, array<string, mixed>> */
	public function getTabsArray(): array
	{
		return $this->tabs;
	}

	/** @param array<int, array<string, mixed>> $tabs */
	public function setTabsArray(array $tabs): void
	{
		$this->tabs = $tabs;
	}
}
