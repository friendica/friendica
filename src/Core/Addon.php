<?php
/**
 * @copyright Copyright (C) 2010-2022, the Friendica project
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Friendica\Core;

use Friendica\Database\DBA;
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
	public static function getAvailableList()
	{
		$addons = [];
		$files = glob('addon/*/');
		if (is_array($files)) {
			foreach ($files as $file) {
				if (is_dir($file)) {
					list($tmp, $addon) = array_map('trim', explode('/', $file));
					$info = self::getInfo($addon);

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
	public static function getAdminList()
	{
		$addons_admin = [];
		$addonsAdminStmt = DBA::select('addon', ['name'], ['plugin_admin' => 1], ['order' => ['name']]);
		while ($addon = DBA::fetch($addonsAdminStmt)) {
			$addons_admin[$addon['name']] = [
				'url' => 'admin/addons/' . $addon['name'],
				'name' => $addon['name'],
				'class' => 'addon'
			];
		}
		DBA::close($addonsAdminStmt);

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
		$installed_addons = DBA::selectToArray('addon', ['name'], ['installed' => true]);
		self::$addons = array_column($installed_addons, 'name');
	}

	/**
	 * uninstalls an addon.
	 *
	 * @param string $addon name of the addon
	 * @return void
	 * @throws \Exception
	 */
	public static function uninstall($addon)
	{
		$addon = Strings::sanitizeFilePathItem($addon);

		Logger::notice("Addon {addon}: {action}", ['action' => 'uninstall', 'addon' => $addon]);
		DBA::delete('addon', ['name' => $addon]);

		@include_once('addon/' . $addon . '/' . $addon . '.php');
		if (function_exists($addon . '_uninstall')) {
			$func = $addon . '_uninstall';
			$func();
		}

		Hook::delete(['file' => 'addon/' . $addon . '/' . $addon . '.php']);

		unset(self::$addons[array_search($addon, self::$addons)]);
	}

	/**
	 * installs an addon.
	 *
	 * @param string $addon name of the addon
	 * @return bool
	 * @throws \Exception
	 */
	public static function install($addon)
	{
		$addon = Strings::sanitizeFilePathItem($addon);

		$addon_file_path = 'addon/' . $addon . '/' . $addon . '.php';

		// silently fail if addon was removed of if $addon is funky
		if (!file_exists($addon_file_path)) {
			return false;
		}

		Logger::notice("Addon {addon}: {action}", ['action' => 'install', 'addon' => $addon]);
		$t = @filemtime($addon_file_path);
		@include_once($addon_file_path);
		if (function_exists($addon . '_install')) {
			$func = $addon . '_install';
			$func(DI::app());
		}

		DBA::insert('addon', [
			'name' => $addon,
			'installed' => true,
			'timestamp' => $t,
			'plugin_admin' => function_exists($addon . '_addon_admin'),
			'hidden' => file_exists('addon/' . $addon . '/.hidden')
		]);

		if (!self::isEnabled($addon)) {
			self::$addons[] = $addon;
		}

		return true;
	}

	/**
	 * reload all updated addons
	 */
	public static function reload()
	{
		$addons = DBA::selectToArray('addon', [], ['installed' => true]);

		foreach ($addons as $addon) {
			$addonname = Strings::sanitizeFilePathItem(trim($addon['name']));
			$addon_file_path = 'addon/' . $addonname . '/' . $addonname . '.php';
			if (file_exists($addon_file_path) && $addon['timestamp'] == filemtime($addon_file_path)) {
				// Addon unmodified, skipping
				continue;
			}

			Logger::notice("Addon {addon}: {action}", ['action' => 'reload', 'addon' => $addon['name']]);

			self::uninstall($addon['name']);
			self::install($addon['name']);
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
	public static function getInfo($addon)
	{
		$addon = Strings::sanitizeFilePathItem($addon);

		$info = [
			'name' => $addon,
			'description' => "",
			'author' => [],
			'maintainer' => [],
			'version' => "",
			'status' => ""
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
					$type = strtolower($type);
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
	public static function isEnabled($addon)
	{
		return in_array($addon, self::$addons);
	}

	/**
	 * Returns a list of the enabled addon names
	 *
	 * @return array
	 */
	public static function getEnabledList()
	{
		return self::$addons;
	}

	/**
	 * Returns the list of non-hidden enabled addon names
	 *
	 * @return array
	 * @throws \Exception
	 */
	public static function getVisibleList()
	{
		$visible_addons = [];
		$stmt = DBA::select('addon', ['name'], ['hidden' => false, 'installed' => true]);
		if (DBA::isResult($stmt)) {
			foreach (DBA::toArray($stmt) as $addon) {
				$visible_addons[] = $addon['name'];
			}
		}

		return $visible_addons;
	}
}
