<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Service\Addon;

/**
 * Interface for an addon loader.
 */
interface AddonLoader
{
	/**
	 * @return Addon[] Returns an array of Addon instances.
	 */
	public function getAddons(array $addonNames): array;
}
