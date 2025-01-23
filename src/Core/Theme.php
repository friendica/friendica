<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Core;

use Friendica\DI;
use Friendica\Model\Profile;
use Friendica\Util\Strings;

/**
 * Some functions to handle themes
 */
class Theme
{
	public static function getAllowedList(): array
	{
		$allowed_themes_str = DI::config()->get('system', 'allowed_themes');
		$allowed_themes_raw = explode(',', str_replace(' ', '', $allowed_themes_str));
		$allowed_themes     = [];
		if (count($allowed_themes_raw)) {
			foreach ($allowed_themes_raw as $theme) {
				$theme = Strings::sanitizeFilePathItem(trim($theme));
				if (strlen($theme) && is_dir("view/theme/$theme")) {
					$allowed_themes[] = $theme;
				}
			}
		}

		return array_unique($allowed_themes);
	}

	public static function setAllowedList(array $allowed_themes)
	{
		DI::config()->set('system', 'allowed_themes', implode(',', array_unique($allowed_themes)));
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
	public static function getInfo(string $theme): array
	{
		$theme = Strings::sanitizeFilePathItem($theme);

		$info = [
			'name'         => $theme,
			'description'  => "",
			'author'       => [],
			'maintainer'   => [],
			'version'      => "",
			'credits'      => "",
			'experimental' => file_exists("view/theme/$theme/experimental"),
			'unsupported'  => file_exists("view/theme/$theme/unsupported")
		];

		if (!is_file("view/theme/$theme/theme.php")) {
			return $info;
		}

		DI::profiler()->startRecording('file');
		$theme_file = file_get_contents("view/theme/$theme/theme.php");
		DI::profiler()->stopRecording();

		$result = preg_match("|/\*.*\*/|msU", $theme_file, $matches);

		if ($result) {
			$comment_lines = explode("\n", $matches[0]);
			foreach ($comment_lines as $comment_line) {
				$comment_line = trim($comment_line, "\t\n\r */");
				if (strpos($comment_line, ':') !== false) {
					list($key, $value) = array_map("trim", explode(":", $comment_line, 2));
					$key               = strtolower($key);
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
	 * Returns the theme's screenshot.
	 *
	 * The screenshot is expected as view/theme/$theme/screenshot.[png|jpg].
	 *
	 * @param string $theme The name of the theme
	 * @return string
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function getScreenshot(string $theme): string
	{
		$theme = Strings::sanitizeFilePathItem($theme);

		$exts = ['.png', '.jpg'];
		foreach ($exts as $ext) {
			if (file_exists('view/theme/' . $theme . '/screenshot' . $ext)) {
				return DI::baseUrl() . '/view/theme/' . $theme . '/screenshot' . $ext;
			}
		}
		return DI::baseUrl() . '/images/blank.png';
	}

	/**
	 * Uninstalls given theme name
	 *
	 * @param string $theme Name of theme
	 * @return bool true on success
	 */
	public static function uninstall(string $theme)
	{
		$theme = Strings::sanitizeFilePathItem($theme);

		// silently fail if theme was removed or if $theme is funky
		if (file_exists("view/theme/$theme/theme.php")) {
			include_once "view/theme/$theme/theme.php";

			$func = "{$theme}_uninstall";
			if (function_exists($func)) {
				$func();
			}

			Hook::delete(['file' => "view/theme/$theme/theme.php"]);
		}

		$allowed_themes = Theme::getAllowedList();
		$key            = array_search($theme, $allowed_themes);
		if ($key !== false) {
			unset($allowed_themes[$key]);
			Theme::setAllowedList($allowed_themes);
			return true;
		}
		return false;
	}

	/**
	 * Installs given theme name
	 *
	 * @param string $theme Name of theme
	 * @return bool true on success
	 */
	public static function install(string $theme): bool
	{
		$theme = Strings::sanitizeFilePathItem($theme);

		// silently fail if theme was removed or if $theme is funky
		if (!file_exists("view/theme/$theme/theme.php")) {
			return false;
		}

		try {
			include_once "view/theme/$theme/theme.php";

			$func = "{$theme}_install";
			if (function_exists($func)) {
				$func();
			}

			$allowed_themes   = Theme::getAllowedList();
			$allowed_themes[] = $theme;
			Theme::setAllowedList($allowed_themes);

			return true;
		} catch (\Exception $e) {
			DI::logger()->error('Theme installation failed', ['theme' => $theme, 'error' => $e->getMessage()]);
			return false;
		}
	}

	/**
	 * Get the full path to relevant theme files by filename
	 *
	 * This function searches in order in the current theme directory, in the current theme parent directory, and lastly
	 * in the base view/ folder.
	 *
	 * @param string $file Filename
	 * @return string Path to the file or empty string if the file isn't found
	 * @throws \Exception
	 */
	public static function getPathForFile(string $file): string
	{
		$appHelper = DI::appHelper();

		$theme = $appHelper->getCurrentTheme();

		$parent = Strings::sanitizeFilePathItem($appHelper->getThemeInfoValue('extends', $theme));

		$paths = [
			"view/theme/$theme/$file",
			"view/theme/$parent/$file",
			"view/$file",
		];

		foreach ($paths as $path) {
			if (file_exists($path)) {
				return $path;
			}
		}

		return '';
	}

	/**
	 * Return relative path to theme stylesheet file
	 *
	 * Provide a sane default if nothing is chosen or the specified theme does not exist.
	 *
	 * @param string $theme Theme name
	 * @return string
	 */
	public static function getStylesheetPath(string $theme): string
	{
		$theme = Strings::sanitizeFilePathItem($theme);

		if (!file_exists('view/theme/' . $theme . '/style.php')) {
			return 'view/theme/' . $theme . '/style.css';
		}

		$appHelper = DI::appHelper();

		$query_params = [];

		$puid = Profile::getThemeUid($appHelper);
		if ($puid) {
			$query_params['puid'] = $puid;
		}

		return 'view/theme/' . $theme . '/style.pcss' . (!empty($query_params) ? '?' . http_build_query($query_params) : '');
	}

	/**
	 * Returns the path of the provided theme
	 *
	 * @param string $theme Theme name
	 * @return string|null
	 */
	public static function getConfigFile(string $theme)
	{
		$theme = Strings::sanitizeFilePathItem($theme);

		$appHelper  = DI::appHelper();
		$base_theme = $appHelper->getThemeInfoValue('extends') ?? '';

		if (file_exists("view/theme/$theme/config.php")) {
			return "view/theme/$theme/config.php";
		}
		if ($base_theme && file_exists("view/theme/$base_theme/config.php")) {
			return "view/theme/$base_theme/config.php";
		}
		return null;
	}

	/**
	 * Returns the background color of the provided theme if available.
	 *
	 * @param string   $theme Theme name
	 * @param int|null $uid   Current logged-in user id
	 * @return string|null
	 */
	public static function getBackgroundColor(string $theme, int $uid = null)
	{
		$theme = Strings::sanitizeFilePathItem($theme);

		$return = null;

		// silently fail if theme was removed or if $theme is funky
		if (file_exists("view/theme/$theme/theme.php")) {
			include_once "view/theme/$theme/theme.php";

			$func = "{$theme}_get_background_color";
			if (function_exists($func)) {
				$return = $func($uid);
			}
		}

		return $return;
	}

	/**
	 * Returns the theme color of the provided theme if available.
	 *
	 * @param string   $theme
	 * @param int|null $uid   Current logged-in user id
	 * @return string|null
	 */
	public static function getThemeColor(string $theme, int $uid = null)
	{
		$theme = Strings::sanitizeFilePathItem($theme);

		$return = null;

		// silently fail if theme was removed or if $theme is funky
		if (file_exists("view/theme/$theme/theme.php")) {
			include_once "view/theme/$theme/theme.php";

			$func = "{$theme}_get_theme_color";
			if (function_exists($func)) {
				$return = $func($uid);
			}
		}

		return $return;
	}
}
