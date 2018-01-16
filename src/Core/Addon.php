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

class Addon
{
	/**
	 * @brief uninstalls an addon.
	 *
	 * @param string $plugin name of the addon
	 * @return boolean
	 */
	public static function uninstallPlugin($plugin)
	{
		logger("Addons: uninstalling " . $plugin);
		dba::delete('addon', ['name' => $plugin]);

		@include_once('addon/' . $plugin . '/' . $plugin . '.php');
		if (function_exists($plugin . '_uninstall')) {
			$func = $plugin . '_uninstall';
			$func();
		}
	}

	/**
	 * @brief installs an addon.
	 *
	 * @param string $plugin name of the addon
	 * @return bool
	 */
	public static function installPlugin($plugin)
	{
		// silently fail if plugin was removed

		if (!file_exists('addon/' . $plugin . '/' . $plugin . '.php')) {
			return false;
		}
		logger("Addons: installing " . $plugin);
		$t = @filemtime('addon/' . $plugin . '/' . $plugin . '.php');
		@include_once('addon/' . $plugin . '/' . $plugin . '.php');
		if (function_exists($plugin . '_install')) {
			$func = $plugin . '_install';
			$func();

			$plugin_admin = (function_exists($plugin."_plugin_admin") ? 1 : 0);

			dba::insert('addon', ['name' => $plugin, 'installed' => true,
						'timestamp' => $t, 'plugin_admin' => $plugin_admin]);

			// we can add the following with the previous SQL
			// once most site tables have been updated.
			// This way the system won't fall over dead during the update.

			if (file_exists('addon/' . $plugin . '/.hidden')) {
				dba::update('addon', ['hidden' => true], ['name' => $plugin]);
			}
			return true;
		} else {
			logger("Addons: FAILED installing " . $plugin);
			return false;
		}
	}

	/**
	 * Reload all updated plugins
	 */
	public static function reload()
	{
		$plugins = Config::get('system', 'addon');
		if (strlen($plugins)) {
			$r = q("SELECT * FROM `addon` WHERE `installed` = 1");
			if (DBM::is_result($r)) {
				$installed = $r;
			} else {
				$installed = [];
			}

			$parr = explode(',', $plugins);

			if (count($parr)) {
				foreach ($parr as $pl) {
					$pl = trim($pl);

					$fname = 'addon/' . $pl . '/' . $pl . '.php';

					if (file_exists($fname)) {
						$t = @filemtime($fname);
						foreach ($installed as $i) {
							if (($i['name'] == $pl) && ($i['timestamp'] != $t)) {
								logger('Reloading plugin: ' . $i['name']);
								@include_once($fname);

								if (function_exists($pl . '_uninstall')) {
									$func = $pl . '_uninstall';
									$func();
								}
								if (function_exists($pl . '_install')) {
									$func = $pl . '_install';
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
	 * Check if addon is enabled
	 *
	 * @param string $plugin
	 * @return boolean
	 */
	public static function isEnabled($plugin)
	{
		return dba::exists('addon', ['installed' => true, 'name' => $plugin]);
	}


	/**
	 * Registers a hook.
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
	 * Unregisters a hook.
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
	 * Calls a hook.
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
	 * Check if an app_menu hook exists for plugin
	 *
	 * @param string $name app name
	 * @return boolean
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
	 * Parse plugin comment in search of plugin infos.
	 *
	 * like
	 * \code
	 *...* Name: Plugin
	*   * Description: A plugin which plugs in
	* . * Version: 1.2.3
	*   * Author: John <profile url>
	*   * Author: Jane <email>
	*   *
	*  *\endcode
	* @param string $plugin the name of the plugin
	* @return array with the plugin information
	*/
	public static function getPluginInfo($plugin)
	{
		$a = get_app();

		$info=[
			'name' => $plugin,
			'description' => "",
			'author' => [],
			'version' => "",
			'status' => ""
		];

		if (!is_file("addon/$plugin/$plugin.php")) {
			return $info;
		}

		$stamp1 = microtime(true);
		$f = file_get_contents("addon/$plugin/$plugin.php");
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


	/**
	 * Parse theme comment in search of theme infos.
	 *
	 * like
	 * \code
	 * ..* Name: My Theme
	 *   * Description: My Cool Theme
	 * . * Version: 1.2.3
	 *   * Author: John <profile url>
	 *   * Maintainer: Jane <profile url>
	 *   *
	 * \endcode
	 * @param string $theme the name of the theme
	 * @return array
	 */
	public static function getThemeInfo($theme)
	{
		$info=[
			'name' => $theme,
			'description' => "",
			'author' => [],
			'maintainer' => [],
			'version' => "",
			'credits' => "",
			'experimental' => false,
			'unsupported' => false
		];

		if (file_exists("view/theme/$theme/experimental")) {
			$info['experimental'] = true;
		}

		if (file_exists("view/theme/$theme/unsupported")) {
			$info['unsupported'] = true;
		}

		if (!is_file("view/theme/$theme/theme.php")) {
			return $info;
		}

		$a = get_app();
		$stamp1 = microtime(true);
		$f = file_get_contents("view/theme/$theme/theme.php");
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
					} elseif ($k == "maintainer") {
						$r=preg_match("|([^<]+)<([^>]+)>|", $v, $m);
						if ($r) {
							$info['maintainer'][] = ['name'=>$m[1], 'link'=>$m[2]];
						} else {
							$info['maintainer'][] = ['name'=>$v];
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

	/**
	 * Returns the theme's screenshot.
	 *
	 * The screenshot is expected as view/theme/$theme/screenshot.[png|jpg].
	 *
	 * @param sring $theme The name of the theme
	 * @return string
	 */
	public static function getThemeScreenshot($theme)
	{
		$exts = ['.png','.jpg'];
		foreach ($exts as $ext) {
			if (file_exists('view/theme/' . $theme . '/screenshot' . $ext)) {
				return(System::baseUrl() . '/view/theme/' . $theme . '/screenshot' . $ext);
			}
		}
		return(System::baseUrl() . '/images/blank.png');
	}

	// install and uninstall theme
	/**
	 * Uninstall theme
	 *
	 * @param string $theme theme
	 */
	public static function uninstallTheme($theme)
	{
		logger("Addons: uninstalling theme " . $theme);

		include_once("view/theme/$theme/theme.php");
		if (function_exists("{$theme}_uninstall")) {
			$func = "{$theme}_uninstall";
			$func();
		}
	}

	/**
	 * Install theme
	 *
	 * @param string $theme theme
	 */
	public static function installTheme($theme)
	{
		// silently fail if theme was removed

		if (!file_exists("view/theme/$theme/theme.php")) {
			return false;
		}

		logger("Addons: installing theme $theme");

		include_once "view/theme/$theme/theme.php";

		if (function_exists("{$theme}_install")) {
			$func = "{$theme}_install";
			$func();
			return true;
		} else {
			logger("Addons: FAILED installing theme $theme");
			return false;
		}
	}

	/**
	 * Get the full path to relevant theme files by filename
	 *
	 * This function search in the theme directory (and if not present in global theme directory)
	 * if there is a directory with the file extension and  for a file with the given
	 * filename.
	 *
	 * @param string $file Filename
	 * @param string $root Full root path
	 * @return string Path to the file or empty string if the file isn't found
	 */
	public static function themeInclude($file, $root = '')
	{
		$file = basename($file);

		// Make sure $root ends with a slash / if it's not blank
		if ($root !== '' && $root[strlen($root)-1] !== '/') {
			$root = $root . '/';
		}

		$theme_info = get_app()->theme_info;
		if (is_array($theme_info) && array_key_exists('extends', $theme_info)) {
			$parent = $theme_info['extends'];
		} else {
			$parent = 'NOPATH';
		}
		$theme = current_theme();
		$thname = $theme;
		$ext = substr($file, strrpos($file, '.') + 1);
		$paths = [
			"{$root}view/theme/$thname/$ext/$file",
			"{$root}view/theme/$parent/$ext/$file",
			"{$root}view/$ext/$file",
		];
		foreach ($paths as $p) {
			// strpos() is faster than strstr when checking if one string is in another (http://php.net/manual/en/function.strstr.php)
			if (strpos($p, 'NOPATH') !== false) {
				continue;
			} elseif (file_exists($p)) {
				return $p;
			}
		}
		return '';
	}
}
