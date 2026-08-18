<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Event;

use Friendica\Core\Event\AbstractEvent;

/**
 * Fired when the profile settings are being posted.
 *
 * Can be used by addons to modify the profile settings post data.
 */
final class ProfileSettingsPostEvent extends AbstractEvent
{
	public const NAME = 'friendica.data.profile_settings_post';

	/**
	 * @internal
	 *
	 * @param array<string, mixed> $request
	 */
	public function __construct(
		private array $request,
	) {
		parent::__construct(self::NAME);
	}

	/** @return array<string, mixed> */
	public function getRequestArray(): array
	{
		return $this->request;
	}

	/** @param array<string, mixed> $request */
	public function setRequestArray(array $request): void
	{
		$this->request = $request;
	}
}
