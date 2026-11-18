<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Event;

use Friendica\Core\Event\AbstractEvent;

/**
 * This event is fired before the profile sidebar entry is being built.
 *
 * Can be used by addons to modify the profile record for the sidebar entry.
 */
final class ProfileSidebarStartEvent extends AbstractEvent
{
	public const NAME = 'friendica.data.profile_sidebar_start';

	/**
	 * @internal
	 *
	 * @param array<string, mixed> $profile
	 */
	public function __construct(
		private array $profile,
	) {
		parent::__construct(self::NAME);
	}

	/** @return array<string, mixed> */
	public function getProfileArray(): array
	{
		return $this->profile;
	}

	/** @param array<string, mixed> $profile */
	public function setProfileArray(array $profile): void
	{
		$this->profile = $profile;
	}
}
