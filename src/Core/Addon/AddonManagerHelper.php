<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Core\Addon;

use Friendica\Core\Addon\Exception\AddonInvalidConfigFileException;
use Friendica\Core\Addon\Exception\InvalidAddonException;
use Friendica\Core\Cache\Capability\ICanCache;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Database\Database;
use Friendica\Util\Profiler;
use Friendica\Util\Strings;
use Psr\Log\LoggerInterface;

/**
 * helper functions to handle addons
 *
 * @internal
 */
final class AddonManagerHelper implements AddonHelper
{
	private string $addonPath;

	private Database $database;

	private IManageConfigValues $config;

	private ICanCache $cache;

	private LoggerInterface $logger;

	private Profiler $profiler;

	/** @var string[] */
	private array $addons = [];

	public function __construct(
		string $addonPath,
		Database $database,
		IManageConfigValues $config,
		ICanCache $cache,
		LoggerInterface $logger,
		Profiler $profiler
	) {
		$this->addonPath = $addonPath;
		$this->database  = $database;
		$this->config    = $config;
		$this->cache     = $cache;
		$this->logger    = $logger;
		$this->profiler  = $profiler;
	}
	/**
	 * Returns the absolute path to the addon folder
	 *
	 * e.g. `/var/www/html/addon`
	 */
	public function getAddonPath(): string
	{
		return $this->addonPath;
	}

	/**
	 * Returns the list of available addons.
	 *
	 * This list is made from scanning the addon/ folder.
	 * Unsupported addons are excluded unless they already are enabled or system.show_unsupported_addon is set.
	 *
	 * @return string[]
	 */
	public function getAvailableAddons(): array
	{
		$dirs = scandir($this->getAddonPath());

		if (!is_array($dirs)) {
			return [];
		}

		$files = [];

		foreach ($dirs as $dirname) {
			// ignore hidden files and folders
			// @TODO: Replace with str_starts_with() when PHP 8.0 is the minimum version
			if (strncmp($dirname, '.', 1) === 0) {
				continue;
			}

			if (!is_dir($this->getAddonPath() . '/' . $dirname)) {
				continue;
			}

			$files[] = $dirname;
		}

		$addons = [];

		foreach ($files as $addonId) {
			try {
				$addonInfo = $this->getAddonInfo($addonId);
			} catch (InvalidAddonException $th) {
				$this->logger->error('Invalid addon found: ' . $addonId, ['exception' => $th]);

				// skip invalid addons
				continue;
			}

			if (
				$this->config->get('system', 'show_unsupported_addons')
				|| strtolower($addonInfo->getStatus()) !== 'unsupported'
				|| $this->isAddonEnabled($addonId)
			) {
				$addons[] = $addonId;
			}
		}

		return $addons;
	}

	/**
	 * Installs an addon.
	 *
	 * @param string $addonId name of the addon
	 *
	 * @return bool true on success or false on failure
	 */
	public function installAddon(string $addonId): bool
	{
		$addonId = Strings::sanitizeFilePathItem($addonId);

		$addon_file_path = $this->getAddonPath() . '/' . $addonId . '/' . $addonId . '.php';

		// silently fail if addon was removed or if $addonId is funky
		if (!file_exists($addon_file_path)) {
			return false;
		}

		$this->logger->debug("Addon {addon}: {action}", ['action' => 'install', 'addon' => $addonId]);

		$timestamp = @filemtime($addon_file_path);

		@include_once($addon_file_path);

		if (function_exists($addonId . '_install')) {
			$func = $addonId . '_install';
			$func();
		}

		$this->config->set('addons', $addonId, [
			'last_update' => $timestamp,
			'admin'       => function_exists($addonId . '_addon_admin'),
		]);

		if (!$this->isAddonEnabled($addonId)) {
			$this->addons[] = $addonId;
		}

		return true;
	}

	/**
	 * Uninstalls an addon.
	 *
	 * @param string $addonId name of the addon
	 */
	public function uninstallAddon(string $addonId): void
	{
		$addonId = Strings::sanitizeFilePathItem($addonId);

		$this->logger->debug("Addon {addon}: {action}", ['action' => 'uninstall', 'addon' => $addonId]);
		$this->config->delete('addons', $addonId);

		$addon_file_path = $this->getAddonPath() . '/' . $addonId . '/' . $addonId . '.php';

		@include_once($addon_file_path);

		if (function_exists($addonId . '_uninstall')) {
			$func = $addonId . '_uninstall';
			$func();
		}

		// Remove registered hooks for the addon
		// Handles both relative and absolute file paths
		$condition = ['`file` LIKE ?', "%/$addonId/$addonId.php"];

		$result = $this->database->delete('hook', $condition);

		if ($result) {
			$this->cache->delete('routerDispatchData');
		}

		unset($this->addons[array_search($addonId, $this->addons)]);
	}

	/**
	 * Load addons.
	 *
	 * @internal
	 */
	public function loadAddons(): void
	{
		$this->addons = array_keys(array_filter($this->config->get('addons') ?? []));
	}

	/**
	 * Reload (uninstall and install) all installed and modified addons.
	 */
	public function reloadAddons(): void
	{
		$addons = array_filter($this->config->get('addons') ?? []);

		foreach ($addons as $addonName => $data) {
			$addonId = Strings::sanitizeFilePathItem(trim($addonName));

			$addon_file_path = $this->getAddonPath() . '/' . $addonId . '/' . $addonId . '.php';

			if (!file_exists($addon_file_path)) {
				continue;
			}

			if (array_key_exists('last_update', $data) && intval($data['last_update']) === filemtime($addon_file_path)) {
				// Addon unmodified, skipping
				continue;
			}

			$this->logger->debug("Addon {addon}: {action}", ['action' => 'reload', 'addon' => $addonId]);

			$this->uninstallAddon($addonId);
			$this->installAddon($addonId);
		}
	}

	/**
	 * Get the comment block of an addon as value object.
	 *
	 * @throws \Friendica\Core\Addon\Exception\InvalidAddonException if there is an error with the addon file
	 */
	public function getAddonInfo(string $addonId): AddonInfo
	{
		$default = [
			'id'   => $addonId,
			'name' => $addonId,
		];

		$addonFile = $this->getAddonPath() . "/$addonId/$addonId.php";

		if (!is_file($addonFile)) {
			return AddonInfo::fromArray($default);
		}

		$this->profiler->startRecording('file');

		$raw = file_get_contents($addonFile);

		$this->profiler->stopRecording();

		if ($raw === false) {
			throw new InvalidAddonException('Could not read addon file: ' . $addonFile);
		}

		$result = preg_match("|/\*.*\*/|msU", $raw, $matches);

		if ($result === false || $result === 0 || !is_array($matches) || count($matches) < 1) {
			throw new InvalidAddonException('Could not find valid comment block in addon file: ' . $addonFile);
		}

		return AddonInfo::fromString($addonId, $matches[0]);
	}

	/**
	 * Returns a dependency config array for a given addon
	 *
	 * This will load a potential config-file from the static directory, like `addon/{addonId}/static/dependencies.config.php`
	 *
	 * @throws AddonInvalidConfigFileException If the config file doesn't return an array
	 *
	 * @return array the config as array or empty array if no config file was found
	 */
	public function getAddonDependencyConfig(string $addonId): array
	{
		$addonId = Strings::sanitizeFilePathItem(trim($addonId));

		$configFile = $this->getAddonPath() . '/' . $addonId . '/static/dependencies.config.php';

		if (!file_exists($configFile)) {
			return [];
		}

		$config = include($configFile);

		if (!is_array($config)) {
			throw new AddonInvalidConfigFileException('Error loading config file ' . $configFile);
		}

		return $config;
	}

	/**
	 * Checks if the provided addon is enabled
	 */
	public function isAddonEnabled(string $addonId): bool
	{
		return in_array($addonId, $this->addons);
	}

	/**
	 * Returns a list with the IDs of the enabled addons
	 *
	 * @return string[]
	 */
	public function getEnabledAddons(): array
	{
		return $this->addons;
	}

	/**
	 * Returns a list with the IDs of the non-hidden enabled addons
	 *
	 * @return string[]
	 */
	public function getVisibleEnabledAddons(): array
	{
		$visible_addons = [];
		$addons         = array_filter($this->config->get('addons') ?? []);

		foreach ($addons as $name => $data) {
			$visible_addons[] = $name;
		}

		return $visible_addons;
	}

	/**
	 * Returns a list with the IDs of the enabled addons that provides admin settings.
	 *
	 * @return string[]
	 */
	public function getEnabledAddonsWithAdminSettings(): array
	{
		$addons_admin = [];
		$addons       = array_filter($this->config->get('addons') ?? []);

		ksort($addons);

		foreach ($addons as $name => $data) {
			if (array_key_exists('admin', $data) && $data['admin'] === true) {
				$addons_admin[] = $name;
			}
		}

		return $addons_admin;
	}
}
