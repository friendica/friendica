<?php
/**
 * @file src/Core/Addon.php
 */
namespace Friendica\Core;

use Friendica\App;
use Friendica\Core\Config;
use Friendica\Core\System;
use Friendica\Database\DBM;
use dba;

require_once 'include/dba.php';

/**
 * Some functions to handle addons
 */
class Addon
{
	/**
	 * @brief uninstalls an addon.
	 *
	 * @param string $addon name of the addon
	 * @return boolean
	 */
	public static function uninstall($addon)
	{
		logger("Addons: uninstalling " . $addon);
		dba::delete('addon', ['name' => $addon]);

		@include_once('addon/' . $addon . '/' . $addon . '.php');
		if (function_exists($addon . '_uninstall')) {
			$func = $addon . '_uninstall';
			$func();
		}
	}

	/**
	 * @brief installs an addon.
	 *
	 * @param string $addon name of the addon
	 * @return bool
	 */
	public static function install($addon)
	{
		// silently fail if addon was removed

		if (!file_exists('addon/' . $addon . '/' . $addon . '.php')) {
			return false;
		}
		logger("Addons: installing " . $addon);
		$t = @filemtime('addon/' . $addon . '/' . $addon . '.php');
		@include_once('addon/' . $addon . '/' . $addon . '.php');
		if (function_exists($addon . '_install')) {
			$func = $addon . '_install';
			$func();

			$addon_admin = (function_exists($addon."_addon_admin") ? 1 : 0);

			dba::insert('addon', ['name' => $addon, 'installed' => true,
						'timestamp' => $t, 'plugin_admin' => $addon_admin]);

			// we can add the following with the previous SQL
			// once most site tables have been updated.
			// This way the system won't fall over dead during the update.

			if (file_exists('addon/' . $addon . '/.hidden')) {
				dba::update('addon', ['hidden' => true], ['name' => $addon]);
			}
			return true;
		} else {
			logger("Addons: FAILED installing " . $addon);
			return false;
		}
	}

	/**
	 * reload all updated addons
	 */
	public static function reload()
	{
		$addons = Config::get('system', 'addon');
		if (strlen($addons)) {
			$r = dba::select('addon', [], ['installed' => 1]);
			if (DBM::isResult($r)) {
				$installed = $r;
			} else {
				$installed = [];
			}

			$addon_list = explode(',', $addons);

			if (count($addon_list)) {
				foreach ($addon_list as $addon) {
					$addon = trim($addon);
					$fname = 'addon/' . $addon . '/' . $addon . '.php';

					if (file_exists($fname)) {
						$t = @filemtime($fname);
						foreach ($installed as $i) {
							if (($i['name'] == $addon) && ($i['timestamp'] != $t)) {
								logger('Reloading addon: ' . $i['name']);
								@include_once($fname);

								if (function_exists($addon . '_uninstall')) {
									$func = $addon . '_uninstall';
									$func();
								}
								if (function_exists($addon . '_install')) {
									$func = $addon . '_install';
									$func();
								}
								dba::update('addon', ['timestamp' => $t], ['id' => $i['id']]);
							}
						}
					}
				}
			}
		}
	}

	/**
	 * @brief check if addon is enabled
	 *
	 * @param string $addon
	 * @return boolean
	 */
	public static function isEnabled($addon)
	{
		return dba::exists('addon', ['installed' => true, 'name' => $addon]);
	}


	/**
	 * @brief registers a hook.
	 *
	 * @param string $hook the name of the hook
	 * @param string $file the name of the file that hooks into
	 * @param string $function the name of the function that the hook will call
	 * @param int $priority A priority (defaults to 0)
	 * @return mixed|bool
	 */
	public static function registerHook($hook, $file, $function, $priority = 0)
	{
		$condition = ['hook' => $hook, 'file' => $file, 'function' => $function];
		$exists = dba::exists('hook', $condition);
		if ($exists) {
			return true;
		}

		$r = dba::insert('hook', ['hook' => $hook, 'file' => $file, 'function' => $function, 'priority' => $priority]);

		return $r;
	}

	/**
	 * @brief unregisters a hook.
	 *
	 * @param string $hook the name of the hook
	 * @param string $file the name of the file that hooks into
	 * @param string $function the name of the function that the hook called
	 * @return array
	 */
	public static function unregisterHook($hook, $file, $function)
	{
		$condition = ['hook' => $hook, 'file' => $file, 'function' => $function];
		$r = dba::delete('hook', $condition);
		return $r;
	}

	/**
	 * Load hooks
	 */
	public static function loadHooks()
	{
		$a = get_app();
		$a->hooks = [];
		$r = dba::select('hook', ['hook', 'file', 'function'], [], ['order' => ['priority' => 'desc', 'file']]);

		while ($rr = dba::fetch($r)) {
			if (! array_key_exists($rr['hook'], $a->hooks)) {
				$a->hooks[$rr['hook']] = [];
			}
			$a->hooks[$rr['hook']][] = [$rr['file'],$rr['function']];
		}
		dba::close($r);
	}

	/**
	 * @brief Calls a hook.
	 *
	 * Use this function when you want to be able to allow a hook to manipulate
	 * the provided data.
	 *
	 * @param string $name of the hook to call
	 * @param string|array &$data to transmit to the callback handler
	 */
	public static function callHooks($name, &$data = null)
	{
		$a = get_app();

		if (is_array($a->hooks) && array_key_exists($name, $a->hooks)) {
			foreach ($a->hooks[$name] as $hook) {
				self::callSingleHook($a, $name, $hook, $data);
			}
		}
	}

	/**
	 * @brief Calls a single hook.
	 *
	 * @param string $name of the hook to call
	 * @param array $hook Hook data
	 * @param string|array &$data to transmit to the callback handler
	 */
	public static function callSingleHook($a, $name, $hook, &$data = null)
	{
		// Don't run a theme's hook if the user isn't using the theme
		if (strpos($hook[0], 'view/theme/') !== false && strpos($hook[0], 'view/theme/'.current_theme()) === false) {
			return;
		}

		@include_once($hook[0]);
		if (function_exists($hook[1])) {
			$func = $hook[1];
			$func($a, $data);
		} else {
			// remove orphan hooks
			$condition = ['hook' => $name, 'file' => $hook[0], 'function' => $hook[1]];
			dba::delete('hook', $condition);
		}
	}

	/**
	 * check if an app_menu hook exist for addon $name.
	 * Return true if the addon is an app
	 */
	public static function isApp($name)
	{
		$a = get_app();

		if (is_array($a->hooks) && (array_key_exists('app_menu', $a->hooks))) {
			foreach ($a->hooks['app_menu'] as $hook) {
				if ($hook[0] == 'addon/'.$name.'/'.$name.'.php') {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * @brief Parse addon comment in search of addon infos.
	 *
	 * like
	 * \code
	 *...* Name: addon
	*   * Description: An addon which plugs in
	* . * Version: 1.2.3
	*   * Author: John <profile url>
	*   * Author: Jane <email>
	*   *
	*  *\endcode
	* @param string $addon the name of the addon
	* @return array with the addon information
	*/

	public static function getInfo($addon)
	{
		$a = get_app();

		$info=[
			'name' => $addon,
			'description' => "",
			'author' => [],
			'version' => "",
			'status' => ""
		];

		if (!is_file("addon/$addon/$addon.php")) {
			return $info;
		}

		$stamp1 = microtime(true);
		$f = file_get_contents("addon/$addon/$addon.php");
		$a->save_timestamp($stamp1, "file");

		$r = preg_match("|/\*.*\*/|msU", $f, $m);

		if ($r) {
			$ll = explode("\n", $m[0]);
			foreach ($ll as $l) {
				$l = trim($l, "\t\n\r */");
				if ($l != "") {
					list($k,$v) = array_map("trim", explode(":", $l, 2));
					$k= strtolower($k);
					if ($k == "author") {
						$r=preg_match("|([^<]+)<([^>]+)>|", $v, $m);
						if ($r) {
							$info['author'][] = ['name'=>$m[1], 'link'=>$m[2]];
						} else {
							$info['author'][] = ['name'=>$v];
						}
					} else {
						if (array_key_exists($k, $info)) {
							$info[$k]=$v;
						}
					}
				}
			}
		}
		return $info;
	}
}
