<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Event;

use Friendica\Core\Event\AbstractEvent;

/**
 * Fired before adding a new contact for a user.
 *
 * Can be used by addons to handle non-native network remote contact (like the AT Protocol)
 * by filling the contact record or to abort the follow process.
 */
final class FollowContactEvent extends AbstractEvent
{
	public const NAME = 'friendica.data.follow_contact';

	/**
	 * @internal
	 *
	 * @param array<string, mixed> $contact
	 */
	public function __construct(
		private readonly string $url,
		private readonly int $uid,
		private array $contact,
		private bool $aborted = false,
	) {
		parent::__construct(self::NAME);
	}

	public function getUrl(): string
	{
		return $this->url;
	}

	public function getUid(): int
	{
		return $this->uid;
	}

	/** @return array<string, mixed> */
	public function getContactArray(): array
	{
		return $this->contact;
	}

	/** @param array<string, mixed> $contact */
	public function setContactArray(array $contact): void
	{
		$this->contact = $contact;
	}

	public function isAborted(): bool
	{
		return $this->aborted;
	}

	public function setAborted(): void
	{
		$this->aborted = true;
	}
}
