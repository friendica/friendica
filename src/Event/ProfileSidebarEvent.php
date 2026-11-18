<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Event;

use Friendica\Core\Event\AbstractEvent;

/**
 * Fired when generating the sidebar "short" profile for a page.
 *
 * Can be used by addons to modify the profile record and the generated entry HTML.
 */
final class ProfileSidebarEvent extends AbstractEvent
{
	public const NAME = 'friendica.data.profile_sidebar';

	/**
	 * @internal
	 *
	 * @param array<string, mixed> $profile
	 */
	public function __construct(
		private readonly array $profile,
		private string $entry,
	) {
		parent::__construct(self::NAME);
	}

	/** @return array<string, mixed> */
	public function getProfileArray(): array
	{
		return $this->profile;
	}

	public function getEntry(): string
	{
		return $this->entry;
	}

	public function setEntry(string $entry): void
	{
		$this->entry = $entry;
	}
}
