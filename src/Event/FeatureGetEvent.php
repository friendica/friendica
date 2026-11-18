<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Event;

use Friendica\Core\Event\AbstractEvent;

/**
 * Fired when the list of available features for the feature settings is about to be returned.
 *
 * Can be used by addons to modify the list of available features.
 */
final class FeatureGetEvent extends AbstractEvent
{
	public const NAME = 'friendica.data.feature_get';

	/**
	 * @internal
	 *
	 * @param array<string, array{0: string, 1: list<array{0: string, 1: string, 2: string, 3: bool, 4: bool}>}> $features
	 */
	public function __construct(
		private array $features,
	) {
		parent::__construct(self::NAME);
	}

	/**
	 * @return array<string, array{0: string, 1: list<array{0: string, 1: string, 2: string, 3: bool, 4: bool}>}> The list of available features
	 */
	public function getFeatures(): array
	{
		return $this->features;
	}

	/**
	 * @param array<string, array{0: string, 1: list<array{0: string, 1: string, 2: string, 3: bool, 4: bool}>}> $features The list of available features
	 */
	public function setFeatures(array $features): void
	{
		$this->features = $features;
	}
}
