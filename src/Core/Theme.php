<?php

/**
 * @file src/Core/Theme.php
 */

namespace Friendica\Core;

use Friendica\Content\Text;
use Friendica\Core\System;

require_once 'boot.php';

/**
 * Some functions to handle themes
 */
class Theme
{
	/**
	 * @brief Parse theme comment in search of theme infos.
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
	public static function getInfo($theme)
	{
		$info = [
			'name' => $theme,
			'description' => "",
			'author' => [],
			'maintainer' => [],
			'version' => "",
			'credits' => "",
			'experimental' => file_exists("view/theme/$theme/experimental"),
			'unsupported' => file_exists("view/theme/$theme/unsupported")
		];

		if (!is_file("view/theme/$theme/theme.php")) {
			return $info;
		}

		$a = get_app();
		$stamp1 = microtime(true);
		$theme_file = file_get_contents("view/theme/$theme/theme.php");
		$a->saveTimestamp($stamp1, "file");

		$result = preg_match("|/\*.*\*/|msU", $theme_file, $matches);

		if ($result) {
			$comment_lines = explode("\n", $matches[0]);
			foreach ($comment_lines as $comment_line) {
				$comment_line = trim($comment_line, "\t\n\r */");
				if ($comment_line != "") {
					list($key, $value) = array_map("trim", explode(":", $comment_line, 2));
					$key = strtolower($key);
					if ($key == "author") {
						$result = preg_match("|([^<]+)<([^>]+)>|", $value, $matches);
						if ($result) {
							$info['author'][] = ['name' => $matches[1], 'link' => $matches[2]];
						} else {
							$info['author'][] = ['name' => $value];
						}
					} elseif ($key == "maintainer") {
						$result = preg_match("|([^<]+)<([^>]+)>|", $value, $matches);
						if ($result) {
							$info['maintainer'][] = ['name' => $matches[1], 'link' => $matches[2]];
						} else {
							$info['maintainer'][] = ['name' => $value];
						}
					} elseif (array_key_exists($key, $info)) {
						$info[$key] = $value;
					}
				}
			}
		}
		return $info;
	}

	/**
	 * @brief Returns the theme's screenshot.
	 *
	 * The screenshot is expected as view/theme/$theme/screenshot.[png|jpg].
	 *
	 * @param sring $theme The name of the theme
	 * @return string
	 */
	public static function getScreenshot($theme)
	{
		$exts = ['.png', '.jpg'];
		foreach ($exts as $ext) {
			if (file_exists('view/theme/' . $theme . '/screenshot' . $ext)) {
				return(System::baseUrl() . '/view/theme/' . $theme . '/screenshot' . $ext);
			}
		}
		return(System::baseUrl() . '/images/blank.png');
	}

	// install and uninstall theme
	public static function uninstall($theme)
	{
		Text::logger("Addons: uninstalling theme " . $theme);

		include_once "view/theme/$theme/theme.php";
		if (function_exists("{$theme}_uninstall")) {
			$func = "{$theme}_uninstall";
			$func();
		}
	}

	public static function install($theme)
	{
		// silently fail if theme was removed

		if (!file_exists("view/theme/$theme/theme.php")) {
			return false;
		}

		Text::logger("Addons: installing theme $theme");

		include_once "view/theme/$theme/theme.php";

		if (function_exists("{$theme}_install")) {
			$func = "{$theme}_install";
			$func();
			return true;
		} else {
			Text::logger("Addons: FAILED installing theme $theme");
			return false;
		}
	}

	/**
	 * @brief Get the full path to relevant theme files by filename
	 *
	 * This function search in the theme directory (and if not present in global theme directory)
	 * if there is a directory with the file extension and  for a file with the given
	 * filename.
	 *
	 * @param string $file Filename
	 * @param string $root Full root path
	 * @return string Path to the file or empty string if the file isn't found
	 */
	public static function getPathForFile($file, $root = '')
	{
		$file = basename($file);

		// Make sure $root ends with a slash / if it's not blank
		if ($root !== '' && $root[strlen($root) - 1] !== '/') {
			$root = $root . '/';
		}
		$theme_info = get_app()->theme_info;
		if (is_array($theme_info) && array_key_exists('extends', $theme_info)) {
			$parent = $theme_info['extends'];
		} else {
			$parent = 'NOPATH';
		}
		$theme = get_app()->getCurrentTheme();
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

	/**
	 * @brief Return relative path to theme stylesheet file
	 *
	 * Provide a sane default if nothing is chosen or the specified theme does not exist.
	 *
	 * @param string $theme Theme name
	 *
	 * @return string
	 */
	public static function getStylesheetPath($theme)
	{
		$a = get_app();

		$opts = (($a->profile_uid) ? '?f=&puid=' . $a->profile_uid : '');
		if (file_exists('view/theme/' . $theme . '/style.php')) {
			return 'view/theme/' . $theme . '/style.pcss' . $opts;
		}

		return 'view/theme/' . $theme . '/style.css';
	}
}
