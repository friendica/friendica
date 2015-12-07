<?php

require_once("include/dba.php");

/**
 * translation support
 */


/**
 *
 * Get the language setting directly from system variables, bypassing get_config()
 * as database may not yet be configured.
 *
 * If possible, we use the value from the browser.
 *
 */


if(! function_exists('get_browser_language')) {
function get_browser_language() {

	if (x($_SERVER,'HTTP_ACCEPT_LANGUAGE')) {
		// break up string into pieces (languages and q factors)
		preg_match_all('/([a-z]{1,8}(-[a-z]{1,8})?)\s*(;\s*q\s*=\s*(1|0\.[0-9]+))?/i',
			$_SERVER['HTTP_ACCEPT_LANGUAGE'], $lang_parse);

		if (count($lang_parse[1])) {
			// create a list like "en" => 0.8
			$langs = array_combine($lang_parse[1], $lang_parse[4]);

			// set default to 1 for any without q factor
			foreach ($langs as $lang => $val) {
				if ($val === '') $langs[$lang] = 1;
			}

			// sort list based on value
			arsort($langs, SORT_NUMERIC);
		}
	}

	if(isset($langs) && count($langs)) {
		foreach ($langs as $lang => $v) {
			if(file_exists("view/$lang") && is_dir("view/$lang")) {
				$preferred = $lang;
				break;
			}
		}
	}

	if(isset($preferred))
		return $preferred;

    $a = get_app();
	return ((isset($a->config['system']['language'])) ? $a->config['system']['language'] : 'en');
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
	global $a;

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

	if(file_exists("view/$lang/strings.php")) {
		include("view/$lang/strings.php");
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
 * Return installed languages as associative array
 * [
 * 		lang => lang,
 * 		...
 * ]
 */
function get_avaiable_languages() {
	$lang_choices = array();
	$langs = glob('view/*/strings.php'); /**/

	if(is_array($langs) && count($langs)) {
		if(! in_array('view/en/strings.php',$langs))
			$langs[] = 'view/en/';
		asort($langs);
		foreach($langs as $l) {
			$t = explode("/",$l);
			$lang_choices[$t[1]] = $t[1];
		}
	}
	return $lang_choices;
}
