<?php
/**
 * @file src/Core/Theme.php
 */
namespace Friendica\Core;

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

        if (file_exists("view/theme/$theme/experimental"))
            $info['experimental'] = true;
        if (file_exists("view/theme/$theme/unsupported"))
            $info['unsupported'] = true;

        if (!is_file("view/theme/$theme/theme.php")) return $info;

        $a = get_app();
        $stamp1 = microtime(true);
        $f = file_get_contents("view/theme/$theme/theme.php");
        $a->save_timestamp($stamp1, "file");

        $r = preg_match("|/\*.*\*/|msU", $f, $m);

        if ($r) {
            $ll = explode("\n", $m[0]);
            foreach ( $ll as $l ) {
                $l = trim($l,"\t\n\r */");
                if ($l != "") {
                    list($k, $v) = array_map("trim", explode(":", $l, 2));
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
                            $info[$k] = $v;
                        }
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
        $exts = ['.png','.jpg'];
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
        logger("Addons: uninstalling theme " . $theme);

        include_once("view/theme/$theme/theme.php");
        if (function_exists("{$theme}_uninstall")) {
            $func = "{$theme}_uninstall";
            $func();
        }
    }

    public static function install($theme)
    {
        // silently fail if theme was removed

        if (! file_exists("view/theme/$theme/theme.php")) {
            return false;
        }

        logger("Addons: installing theme $theme");

        include_once("view/theme/$theme/theme.php");

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
        if ($root !== '' && $root[strlen($root)-1] !== '/') {
            $root = $root . '/';
        }
        $theme_info = get_app()->theme_info;
        if (is_array($theme_info) && array_key_exists('extends',$theme_info)) {
            $parent = $theme_info['extends'];
        } else {
            $parent = 'NOPATH';
        }
        $theme = current_theme();
        $thname = $theme;
        $ext = substr($file,strrpos($file,'.')+1);
        $paths = [
            "{$root}view/theme/$thname/$ext/$file",
            "{$root}view/theme/$parent/$ext/$file",
            "{$root}view/$ext/$file",
        ];
        foreach ($paths as $p) {
            // strpos() is faster than strstr when checking if one string is in another (http://php.net/manual/en/function.strstr.php)
            if (strpos($p,'NOPATH') !== false) {
                continue;
            } elseif (file_exists($p)) {
                return $p;
            }
        }
        return '';
    }
}
