<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Event;

use Friendica\Core\Event\AbstractEvent;

/**
 * Fired before unfollowing a remote contact for a user.
 *
 * Can be used by addons to handle non-native network remote contact (like the AT Protocol)
 * by reporting whether the unfollow was successful.
 */
final class UnfollowContactEvent extends AbstractEvent
{
	public const NAME = 'friendica.data.unfollow_contact';

	/**
	 * @internal
	 *
	 * @param array<string, mixed> $contact
	 */
	public function __construct(
		private readonly array $contact,
		private readonly int $uid,
		private ?bool $result = null,
	) {
		parent::__construct(self::NAME);
	}

	/** @return array<string, mixed> */
	public function getContactArray(): array
	{
		return $this->contact;
	}

	public function getUid(): int
	{
		return $this->uid;
	}

	public function getResult(): ?bool
	{
		return $this->result;
	}

	public function setResult(?bool $result): void
	{
		$this->result = $result;
	}
}
