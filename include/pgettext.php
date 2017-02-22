<?php

/**
 * @brief translation support
 *
 * Get the language setting directly from system variables, bypassing get_config()
 * as database may not yet be configured.
 *
 * If possible, we use the value from the browser.
 *
 */

use \Friendica\Core\Config;

require_once("include/dba.php");

if(! function_exists('get_browser_language')) {
/**
 * @brief get the prefered language from the HTTP_ACCEPT_LANGUAGE header
 */
function get_browser_language() {

	if (x($_SERVER,'HTTP_ACCEPT_LANGUAGE')) {
		// break up string into pieces (languages and q factors)
		preg_match_all('/([a-z]{1,8}(-[a-z]{1,8})?)\s*(;\s*q\s*=\s*(1|0\.[0-9]+))?/i',
			$_SERVER['HTTP_ACCEPT_LANGUAGE'], $lang_parse);

		$lang_list = [];
		if (count($lang_parse[1])) {
			// go through the list of prefered languages and add a generic language
			// for sub-linguas (e.g. de-ch will add de) if not already in array
			for ($i=0; $i<count($lang_parse[1]); $i++) {
				$lang_list[] = strtolower($lang_parse[1][$i]);
				if ( strlen($lang_parse[1][$i])>3 ) {
					$dashpos = strpos($lang_parse[1][$i], '-');
					if (! in_array(substr($lang_parse[1][$i], 0, $dashpos), $lang_list ) ) {
						$lang_list[] = strtolower(substr($lang_parse[1][$i], 0, $dashpos));
					}
				}
			}
		}
	}

	// check if we have translations for the preferred languages and pick the 1st that has
	for ($i=0; $i<count($lang_list); $i++) {
		$lang = $lang_list[$i];
		if(file_exists("view/lang/$lang") && is_dir("view/lang/$lang")) {
			$preferred = $lang;
			break;
		}
	}
	if (isset($preferred)) {
		return $preferred;
	}

	// in case none matches, get the system wide configured language, or fall back to English
	return Config::get('system', 'language', 'en');
}}


function push_lang($language) {
	global $lang, $a;

	$a->langsave = $lang;

	if($language === $lang)
		return;

	if(isset($a->strings) && count($a->strings)) {
		$a->stringsave = $a->strings;
	}
	$a->strings = array();
	load_translation_table($language);
	$lang = $language;
}

function pop_lang() {
	global $lang, $a;

	if($lang === $a->langsave)
		return;

	if(isset($a->stringsave))
		$a->strings = $a->stringsave;
	else
		$a->strings = array();

	$lang = $a->langsave;
}


// l

if(! function_exists('load_translation_table')) {
/**
 * load string translation table for alternate language
 *
 * first plugin strings are loaded, then globals
 *
 * @param string $lang language code to load
 */
function load_translation_table($lang) {
	$a = get_app();

	$a->strings = array();
	// load enabled plugins strings
	$plugins = q("SELECT name FROM addon WHERE installed=1;");
	if ($plugins!==false) {
		foreach($plugins as $p) {
			$name = $p['name'];
			if(file_exists("addon/$name/lang/$lang/strings.php")) {
				include("addon/$name/lang/$lang/strings.php");
			}
		}
	}

	if(file_exists("view/lang/$lang/strings.php")) {
		include("view/lang/$lang/strings.php");
	}

}}

// translate string if translation exists

if(! function_exists('t')) {
function t($s) {

	$a = get_app();

	if(x($a->strings,$s)) {
		$t = $a->strings[$s];
		return is_array($t)?$t[0]:$t;
	}
	return $s;
}}

if(! function_exists('tt')){
function tt($singular, $plural, $count){
	global $lang;
	$a = get_app();

	if(x($a->strings,$singular)) {
		$t = $a->strings[$singular];
		$f = 'string_plural_select_' . str_replace('-','_',$lang);
		if(! function_exists($f))
			$f = 'string_plural_select_default';
		$k = $f($count);
		return is_array($t)?$t[$k]:$t;
	}

	if ($count!=1){
		return $plural;
	} else {
		return $singular;
	}
}}

// provide a fallback which will not collide with
// a function defined in any language file

if(! function_exists('string_plural_select_default')) {
function string_plural_select_default($n) {
	return ($n != 1);
}}



/**
 * @brief Return installed languages codes as associative array
 *
 * Scans the view/lang directory for the existence of "strings.php" files, and
 * returns an alphabetical list of their folder names (@-char language codes).
 * Adds the english language if it's missing from the list.
 *
 * Ex: array('de' => 'de', 'en' => 'en', 'fr' => 'fr', ...)
 *
 * @return array
 */
function get_available_languages() {
	$langs = array();
	$strings_file_paths = glob('view/lang/*/strings.php');

	if (is_array($strings_file_paths) && count($strings_file_paths)) {
		if (!in_array('view/lang/en/strings.php', $strings_file_paths)) {
			$strings_file_paths[] = 'view/lang/en/strings.php';
		}
		asort($strings_file_paths);
		foreach($strings_file_paths as $strings_file_path) {
			$path_array = explode('/', $strings_file_path);
			$langs[$path_array[2]] = $path_array[2];
		}
	}
	return $langs;
}
