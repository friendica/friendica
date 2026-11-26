<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Event;

use Friendica\Core\Event\AbstractEvent;

/**
 * Fired to check if a feature is enabled for a user.
 *
 * Can be used by addons to modify whether the feature is considered enabled.
 */
final class FeatureEnabledEvent extends AbstractEvent
{
	public const NAME = 'friendica.data.feature_enabled';

	/**
	 * @internal
	 */
	public function __construct(
		private readonly int $uid,
		private readonly string $feature,
		private bool $enabled = false,
	) {
		parent::__construct(self::NAME);
	}

	public function getUid(): int
	{
		return $this->uid;
	}

	public function getFeature(): string
	{
		return $this->feature;
	}

	public function isEnabled(): bool
	{
		return $this->enabled;
	}

	public function setEnabled(bool $enabled): void
	{
		$this->enabled = $enabled;
	}
}
