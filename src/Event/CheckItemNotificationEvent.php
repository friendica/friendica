<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Event;

use Friendica\Core\Event\AbstractEvent;

/**
 * Fired when checking item notifications for a user
 *
 * Can be used by addons to add additional profiles (e.g., social connectors) to receive notifications.
 */
final class CheckItemNotificationEvent extends AbstractEvent
{
	public const NAME = 'friendica.data.check_item_notification';

	/**
	 * @internal
	 *
	 * @param array<string> $profiles
	 */
	public function __construct(
		private readonly int $uid,
		private array $profiles,
	) {
		parent::__construct(self::NAME);
	}

	public function getUserId(): int
	{
		return $this->uid;
	}

	/** @return array<string> */
	public function getProfilesArray(): array
	{
		return $this->profiles;
	}

	/** @param array<string> $profiles */
	public function setProfilesArray(array $profiles): void
	{
		$this->profiles = $profiles;
	}
}
