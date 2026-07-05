<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Addon;

/**
 * Interface for an Addon that has to be installed.
 */
interface InstallableAddon
{
	/**
	 * Runs after AddonBootstrap::initAddon()
	 */
	public function install(): void;

	/**
	 * Runs after AddonBootstrap::initAddon()
	 */
	public function uninstall(): void;
}
