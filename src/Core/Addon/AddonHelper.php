<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Core\Addon;

use Friendica\Core\Addon\Exception\AddonInvalidConfigFileException;

/**
 * Some functions to handle addons
 */
interface AddonHelper
{
	/**
	 * Returns the absolute path to the addon folder
	 *
	 * e.g. `/var/www/html/addon`
	 */
	public function getAddonPath(): string;

	/**
	 * Returns the list of available addons.
	 *
	 * This list is made from scanning the addon/ folder.
	 * Unsupported addons are excluded unless they already are enabled or system.show_unsupported_addon is set.
	 *
	 * @return string[]
	 */
	public function getAvailableAddons(): array;

	/**
	 * Installs an addon.
	 *
	 * @param string $addonId name of the addon
	 *
	 * @return bool true on success or false on failure
	 */
	public function installAddon(string $addonId): bool;

	/**
	 * Uninstalls an addon.
	 *
	 * @param string $addonId name of the addon
	 */
	public function uninstallAddon(string $addonId): void;

	/**
	 * Load addons.
	 *
	 * @internal
	 */
	public function loadAddons(): void;

	/**
	 * Reload (uninstall and install) all installed and modified addons.
	 */
	public function reloadAddons(): void;

	/**
	 * Get the comment block of an addon as value object.
	 *
	 * @throws \Friendica\Core\Addon\Exception\InvalidAddonException if there is an error with the addon file
	 */
	public function getAddonInfo(string $addonId): AddonInfo;

	/**
	 * Returns a dependency config array for a given addon
	 *
	 * This will load a potential config-file from the static directory, like `addon/{addonId}/static/dependencies.config.php`
	 *
	 * @throws AddonInvalidConfigFileException If the config file doesn't return an array
	 *
	 * @return array the config as array or empty array if no config file was found
	 */
	public function getAddonDependencyConfig(string $addonId): array;

	/**
	 * Checks if the provided addon is enabled
	 */
	public function isAddonEnabled(string $addonId): bool;

	/**
	 * Returns a list with the IDs of the enabled addons
	 *
	 * @return string[]
	 */
	public function getEnabledAddons(): array;

	/**
	 * Returns a list with the IDs of the non-hidden enabled addons
	 *
	 * @return string[]
	 */
	public function getVisibleEnabledAddons(): array;

	/**
	 * Returns a list with the IDs of the enabled addons that provides admin settings.
	 *
	 * @return string[]
	 */
	public function getEnabledAddonsWithAdminSettings(): array;
}
