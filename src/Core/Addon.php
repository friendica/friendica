<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Core;

use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Util\Strings;

/**
 * Some functions to handle addons
 */
class Addon
{
	/**
	 * The addon sub-directory
	 * @var string
	 */
	const DIRECTORY = 'addon';

	/**
	 * List of the names of enabled addons
	 *
	 * @var array
	 */
	private static $addons = [];

	/**
	 * Returns the list of available addons with their current status and info.
	 * This list is made from scanning the addon/ folder.
	 * Unsupported addons are excluded unless they already are enabled or system.show_unsupported_addon is set.
	 *
	 * @return array
	 * @throws \Exception
	 */
	public static function getAvailableList(): array
	{
		$addons = [];
		$files  = glob('addon/*/');
		if (is_array($files)) {
			foreach ($files as $file) {
				if (is_dir($file)) {
					list($tmp, $addon) = array_map('trim', explode('/', $file));
					$info              = self::getInfo($addon);

					if (DI::config()->get('system', 'show_unsupported_addons')
						|| strtolower($info['status']) != 'unsupported'
						|| self::isEnabled($addon)
					) {
						$addons[] = [$addon, (self::isEnabled($addon) ? 'on' : 'off'), $info];
					}
				}
			}
		}

		return $addons;
	}

	/**
	 * Returns a list of addons that can be configured at the node level.
	 * The list is formatted for display in the admin panel aside.
	 *
	 * @return array
	 * @throws \Exception
	 */
	public static function getAdminList(): array
	{
		$addons_admin = [];
		$addons       = array_filter(DI::config()->get('addons') ?? []);

		ksort($addons);
		foreach ($addons as $name => $data) {
			if (empty($data['admin'])) {
				continue;
			}

			$addons_admin[$name] = [
				'url'   => 'admin/addons/' . $name,
				'name'  => $name,
				'class' => 'addon'
			];
		}

		return $addons_admin;
	}


	/**
	 * Synchronize addons:
	 *
	 * system.addon contains a comma-separated list of names
	 * of addons which are used on this system.
	 * Go through the database list of already installed addons, and if we have
	 * an entry, but it isn't in the config list, call the uninstall procedure
	 * and mark it uninstalled in the database (for now we'll remove it).
	 * Then go through the config list and if we have a addon that isn't installed,
	 * call the install procedure and add it to the database.
	 *
	 */
	public static function loadAddons()
	{
		self::$addons = array_keys(array_filter(DI::config()->get('addons') ?? []));
	}

	/**
	 * uninstalls an addon.
	 *
	 * @param string $addon name of the addon
	 * @return void
	 * @throws \Exception
	 */
	public static function uninstall(string $addon)
	{
		$addon = Strings::sanitizeFilePathItem($addon);

		DI::logger()->debug("Addon {addon}: {action}", ['action' => 'uninstall', 'addon' => $addon]);
		DI::config()->delete('addons', $addon);

		@include_once('addon/' . $addon . '/' . $addon . '.php');
		if (function_exists($addon . '_uninstall')) {
			$func = $addon . '_uninstall';
			$func();
		}

		// Handles both relative and absolute file paths
		Hook::delete(['`file` LIKE ?', "%addon/$addon/$addon.php"]);

		unset(self::$addons[array_search($addon, self::$addons)]);
	}

	/**
	 * installs an addon.
	 *
	 * @param string $addon name of the addon
	 * @return bool
	 * @throws \Exception
	 */
	public static function install(string $addon): bool
	{
		$addon = Strings::sanitizeFilePathItem($addon);

		$addon_file_path = 'addon/' . $addon . '/' . $addon . '.php';

		// silently fail if addon was removed of if $addon is funky
		if (!file_exists($addon_file_path)) {
			return false;
		}

		DI::logger()->debug("Addon {addon}: {action}", ['action' => 'install', 'addon' => $addon]);
		$t = @filemtime($addon_file_path);
		@include_once($addon_file_path);
		if (function_exists($addon . '_install')) {
			$func = $addon . '_install';
			$func();
		}

		DI::config()->set('addons', $addon, [
			'last_update' => $t,
			'admin'       => function_exists($addon . '_addon_admin'),
		]);

		if (!self::isEnabled($addon)) {
			self::$addons[] = $addon;
		}

		return true;
	}

	/**
	 * reload all updated addons
	 *
	 * @return void
	 * @throws \Exception
	 *
	 */
	public static function reload()
	{
		$addons = array_filter(DI::config()->get('addons') ?? []);

		foreach ($addons as $name => $data) {
			$addonname       = Strings::sanitizeFilePathItem(trim($name));
			$addon_file_path = 'addon/' . $addonname . '/' . $addonname . '.php';
			if (file_exists($addon_file_path) && $data['last_update'] == filemtime($addon_file_path)) {
				// Addon unmodified, skipping
				continue;
			}

			DI::logger()->debug("Addon {addon}: {action}", ['action' => 'reload', 'addon' => $name]);

			self::uninstall($name);
			self::install($name);
		}
	}

	/**
	 * Parse addon comment in search of addon infos.
	 *
	 * like
	 * \code
	 *   * Name: addon
	 *   * Description: An addon which plugs in
	 * . * Version: 1.2.3
	 *   * Author: John <profile url>
	 *   * Author: Jane <email>
	 *   * Maintainer: Jess <email>
	 *   *
	 *   *\endcode
	 * @param string $addon the name of the addon
	 * @return array with the addon information
	 * @throws \Exception
	 */
	public static function getInfo(string $addon): array
	{
		$addon = Strings::sanitizeFilePathItem($addon);

		$info = [
			'name'        => $addon,
			'description' => "",
			'author'      => [],
			'maintainer'  => [],
			'version'     => "",
			'status'      => ""
		];

		if (!is_file("addon/$addon/$addon.php")) {
			return $info;
		}

		DI::profiler()->startRecording('file');
		$f = file_get_contents("addon/$addon/$addon.php");
		DI::profiler()->stopRecording();

		$r = preg_match("|/\*.*\*/|msU", $f, $m);

		if ($r) {
			$ll = explode("\n", $m[0]);
			foreach ($ll as $l) {
				$l = trim($l, "\t\n\r */");
				if ($l != "") {
					$addon_info = array_map("trim", explode(":", $l, 2));
					if (count($addon_info) < 2) {
						continue;
					}

					list($type, $v) = $addon_info;
					$type           = strtolower($type);
					if ($type == "author" || $type == "maintainer") {
						$r = preg_match("|([^<]+)<([^>]+)>|", $v, $m);
						if ($r) {
							if (!empty($m[2]) && empty(parse_url($m[2], PHP_URL_SCHEME))) {
								$contact = Contact::getByURL($m[2], false);
								if (!empty($contact['url'])) {
									$m[2] = $contact['url'];
								}
							}
							$info[$type][] = ['name' => $m[1], 'link' => $m[2]];
						} else {
							$info[$type][] = ['name' => $v];
						}
					} else {
						if (array_key_exists($type, $info)) {
							$info[$type] = $v;
						}
					}
				}
			}
		}
		return $info;
	}

	/**
	 * Checks if the provided addon is enabled
	 *
	 * @param string $addon
	 * @return boolean
	 */
	public static function isEnabled(string $addon): bool
	{
		return in_array($addon, self::$addons);
	}

	/**
	 * Returns a list of the enabled addon names
	 *
	 * @return array
	 */
	public static function getEnabledList(): array
	{
		return self::$addons;
	}

	/**
	 * Returns the list of non-hidden enabled addon names
	 *
	 * @return array
	 * @throws \Exception
	 */
	public static function getVisibleList(): array
	{
		$visible_addons = [];
		$addons         = array_filter(DI::config()->get('addons') ?? []);

		foreach ($addons as $name => $data) {
			$visible_addons[] = $name;
		}

		return $visible_addons;
	}
}
