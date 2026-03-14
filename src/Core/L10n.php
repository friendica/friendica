<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Core;

use DateTime;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\Session\Capability\IHandleSessions;
use Friendica\Database\Database;
use Friendica\Util\Strings;
use IntlDateFormatter;
use Locale;

/**
 * Provide Language, Translation, and Localization functions to the application
 * Localization can be referred to by the numeronym L10N (as in: "L", followed by ten more letters, and then "N").
 */
class L10n
{
	/** @var string The default language */
	public const DEFAULT = 'en';
	/** @var string[] The language names in their language */
	public const LANG_NAMES = [
		'ar'    => 'العربية',
		'bg'    => 'Български',
		'ca'    => 'Català',
		'cs'    => 'Česky',
		'da-dk' => 'Dansk (Danmark)',
		'de'    => 'Deutsch',
		'en-gb' => 'English (United Kingdom)',
		'en-us' => 'English (United States)',
		'en'    => 'English (Default)',
		'eo'    => 'Esperanto',
		'es'    => 'Español',
		'et'    => 'Eesti',
		'fi-fi' => 'Suomi',
		'fr'    => 'Français',
		'gd'    => 'Gàidhlig',
		'hu'    => 'Magyar',
		'is'    => 'Íslenska',
		'it'    => 'Italiano',
		'ja'    => '日本語',
		'nb-no' => 'Norsk bokmål',
		'nl'    => 'Nederlands',
		'pl'    => 'Polski',
		'pt-br' => 'Português Brasileiro',
		'ro'    => 'Română',
		'ru'    => 'Русский',
		'sv'    => 'Svenska',
		'zh-cn' => '简体中文',
	];

	public const LANG_PARENTS = [
		'en-gb' => 'en', 'da-dk' => 'da', 'fi-fi' => 'fi',
		'nb-no' => 'nb', 'pt-br' => 'pt', 'zh-cn' => 'zh',
	];

	/** @var string Undetermined language */
	public const UNDETERMINED_LANGUAGE = 'un';

	/**
	 * A string indicating the current language used for translation:
	 * - Two-letter ISO 639-1 code.
	 * - Two-letter ISO 639-1 code + dash + Two-letter ISO 3166-1 alpha-2 country code.
	 *
	 * @var string
	 */
	private $lang = '';

	private string $locale = '';

	/**
	 * An array of translation strings whose key is the neutral english message.
	 *
	 * @var array
	 */
	private $strings = [];

	/**
	 * @var Database
	 */
	private $dba;
	/**
	 * @var IManageConfigValues
	 */
	private $config;
	private IHandleSessions $session;

	public function __construct(IManageConfigValues $config, Database $dba, IHandleSessions $session, array $server, array $get)
	{
		$this->dba     = $dba;
		$this->config  = $config;
		$this->session = $session;

		$this->loadTranslationTable(L10n::detectLanguage($server, $get, $config->get('system', 'language', self::DEFAULT)));
		$this->setLocale($server);
		$this->setSessionVariable($session);
		$this->setLangFromSession($session);
	}

	/**
	 * Returns the current language code
	 *
	 * @return string Language code
	 */
	public function getCurrentLang()
	{
		return $this->lang;
	}

	/**
	 * Set the instance locale based on the HTTP Accept-Language header.
	 *
	 * Reads the `HTTP_ACCEPT_LANGUAGE` value from the provided server array
	 * and stores the accepted locale string in `$this->locale` using
	 * `Locale::acceptFromHttp()`.
	 *
	 * @param array $server The $_SERVER-like array containing HTTP headers
	 * @return void
	 */
	private function setLocale(array $server)
	{
		if (!isset($server['HTTP_ACCEPT_LANGUAGE'])) {
			return;
		}
		$this->locale = Locale::acceptFromHttp($server['HTTP_ACCEPT_LANGUAGE']);
	}

	/**
	 * Sets the language session variable
	 */
	private function setSessionVariable(IHandleSessions $session)
	{
		if ($session->get('authenticated') && !$session->get('language')) {
			$session->set('language', $this->lang);
			$session->set('locale', $this->locale);
			// we haven't loaded user data yet, but we need user language
			if ($session->get('uid')) {
				$user = $this->dba->selectFirst('user', ['language'], ['uid' => $_SESSION['uid']]);
				if ($this->dba->isResult($user)) {
					$session->set('language', $user['language']);
				}
			}
		}

		if (isset($_GET['lang'])) {
			$session->set('language', $_GET['lang']);
		}
	}

	private function setLangFromSession(IHandleSessions $session)
	{
		if ($session->get('language') !== $this->lang) {
			$this->loadTranslationTable($session->get('language') ?? $this->lang);
		}
	}

	/**
	 * Loads string translation table
	 *
	 * First addon strings are loaded, then globals
	 *
	 * Uses an App object shim since all the strings files refer to $a->strings
	 *
	 * @param string $lang language code to load
	 * @return void
	 * @throws \Exception
	 */
	private function loadTranslationTable(string $lang)
	{
		$lang = Strings::sanitizeFilePathItem($lang);

		// Don't override the language setting with empty languages
		if (empty($lang)) {
			return;
		}

		$a          = new \stdClass();
		$a->strings = [];

		$child = array_search($lang, $this::LANG_PARENTS);
		if ($child) {
			$lang = $child;
		}

		// load enabled addons strings
		$addons = array_keys($this->config->get('addons') ?? []);
		foreach ($addons as $addon) {
			$name = Strings::sanitizeFilePathItem($addon);
			if (file_exists(__DIR__ . "/../../addon/$name/lang/$lang/strings.php")) {
				include __DIR__ . "/../../addon/$name/lang/$lang/strings.php";
			}
		}

		if (file_exists(__DIR__ . "/../../view/lang/$lang/strings.php")) {
			include __DIR__ . "/../../view/lang/$lang/strings.php";
		}

		$this->lang    = $lang;
		$this->strings = $a->strings;

		unset($a);
	}

	/**
	 * Returns the preferred language from the HTTP_ACCEPT_LANGUAGE header
	 *
	 * @param string $sysLang The default fallback language
	 * @param array  $server  The $_SERVER array
	 * @param array  $get     The $_GET array
	 *
	 * @return string The two-letter language code
	 */
	public static function detectLanguage(array $server, array $get, string $sysLang = self::DEFAULT): string
	{
		$lang_variable = $server['HTTP_ACCEPT_LANGUAGE'] ?? null;

		if (empty($lang_variable)) {
			$acceptedLanguages = [];
		} else {
			$acceptedLanguages = preg_split('/,\s*/', $lang_variable);
		}

		// Add get as absolute quality accepted language (except this language isn't valid)
		if (!empty($get['lang'])) {
			$acceptedLanguages[] = $get['lang'];
		}

		// return the sys language in case there's nothing to do
		if (empty($acceptedLanguages)) {
			return $sysLang;
		}

		// Set the syslang as default fallback
		$current_lang = $sysLang;
		// start with quality zero (every guessed language is more acceptable ..)
		$current_q = 0;

		$supported = self::getSupportedLanguages();

		foreach ($acceptedLanguages as $acceptedLanguage) {
			$res = preg_match(
				'/^([a-z]{1,8}(?:-[a-z]{1,8})*)(?:;\s*q=(0(?:\.[0-9]{1,3})?|1(?:\.0{1,3})?))?$/i',
				$acceptedLanguage,
				$matches,
			);

			// Invalid language? -> skip
			if (!$res) {
				continue;
			}

			// split language codes based on it's "-"
			$lang_code = explode('-', $matches[1]);

			// determine the quality of the guess
			if (isset($matches[2])) {
				$lang_quality = (float) $matches[2];
			} else {
				// fallback so without a quality parameter, it's probably the best
				$lang_quality = 1;
			}

			// loop through each part of the code-parts
			while (count($lang_code)) {
				// try to mix them so we can get double-code parts too
				$match_lang = strtolower(join('-', $lang_code));
				if (in_array($match_lang, $supported)) {
					if ($lang_quality > $current_q) {
						$current_lang = $match_lang;
						$current_q    = $lang_quality;
						break;
					}
				}

				// remove the most right code-part
				array_pop($lang_code);
			}
		}

		return $current_lang;
	}

	private static function getSupportedLanguages(): array
	{
		$languages = [];
		foreach (glob('view/lang/*/strings.php') as $language) {
			$code = str_replace(['view/lang/', '/strings.php'], [], $language);
			if (!empty(self::LANG_PARENTS[$code])) {
				$languages[] = self::LANG_PARENTS[$code];
			}
			$languages[] = $code;
		}

		return $languages;
	}

	/**
	 * Return the localized version of the provided string with optional string interpolation
	 *
	 * This function takes a english string as parameter, and if a localized version
	 * exists for the current language, substitutes it before performing an eventual
	 * string interpolation (sprintf) with additional optional arguments.
	 *
	 * Usages:
	 * - DI::l10n()->t('This is an example')
	 * - DI::l10n()->t('URL %s returned no result', $url)
	 * - DI::l10n()->t('Current version: %s, new version: %s', $current_version, $new_version)
	 *
	 * @param string $s
	 * @param array  $vars Variables to interpolate in the translation string
	 *
	 * @return string
	 */
	public function t(string $s, ...$vars): string
	{
		if (empty($s)) {
			return '';
		}

		if (!empty($this->strings[$s])) {
			$t = $this->strings[$s];
			$s = is_array($t) ? $t[0] : $t;
		}

		if (count($vars) > 0) {
			$s = sprintf($s, ...$vars);
		}

		return $s;
	}

	/**
	 * Return the localized version of a singular/plural string with optional string interpolation
	 *
	 * This function takes two english strings as parameters, singular and plural, as
	 * well as a count. If a localized version exists for the current language, they
	 * are used instead. Discrimination between singular and plural is done using the
	 * localized function if any or the default one. Finally, a string interpolation
	 * is performed using the count as parameter.
	 *
	 * Usages:
	 * - DI::l10n()->tt('Like', 'Likes', $count)
	 * - DI::l10n()->tt("%s user deleted", "%s users deleted", count($users))
	 *
	 * @param string $singular
	 * @param string $plural
	 * @param float  $count
	 * @param array  $vars Variables to interpolate in the translation string
	 *
	 * @return string
	 * @throws \Exception
	 */
	public function tt(string $singular, string $plural, float $count, ...$vars): string
	{
		$s = null;

		if (!empty($this->strings[$singular])) {
			$t = $this->strings[$singular];
			if (is_array($t)) {
				$plural_function = 'string_plural_select_' . str_replace('-', '_', $this->lang);
				if (function_exists($plural_function)) {
					$i = $plural_function($count);
				} else {
					$i = $this->stringPluralSelectDefault($count);
				}

				if (isset($t[$i])) {
					$s = $t[$i];
				} elseif (count($t) > 0) {
					// for some languages there is only a single array item
					$s = $t[0];
				}
				// if $t is empty, skip it, because empty strings array are intended
				// to make string file smaller when there's no translation
			} else {
				$s = $t;
			}
		}

		if (is_null($s) && $this->stringPluralSelectDefault($count)) {
			$s = $plural;
		} elseif (is_null($s)) {
			$s = $singular;
		}

		// We mute errors here because the translation strings may not be referencing the count at all,
		// but we still have to try the interpolation just in case it is indeed referenced.
		$s = @sprintf($s, $count, ...$vars);

		return $s;
	}

	/**
	 * Provide a fallback which will not collide with a function defined in any language file
	 */
	private function stringPluralSelectDefault(float $n): bool
	{
		return intval($n) != 1;
	}

	/**
	 * Return installed languages codes as associative array
	 *
	 * Scans the view/lang directory for the existence of "strings.php" files, and
	 * returns an alphabetical list of their folder names (@-char language codes).
	 * Adds the english language if it's missing from the list. Folder names are
	 * replaced by nativ language names.
	 *
	 * Ex: array('de' => 'Deutsch', 'en' => 'English', 'fr' => 'Français', ...)
	 *
	 * @return array
	 */
	public function getAvailableLanguages(): array
	{
		$langs              = [];
		$strings_file_paths = glob('view/lang/*/strings.php');

		if (is_array($strings_file_paths) && count($strings_file_paths)) {
			if (!in_array('view/lang/en/strings.php', $strings_file_paths)) {
				$strings_file_paths[] = 'view/lang/en/strings.php';
			}
			asort($strings_file_paths);
			foreach ($strings_file_paths as $strings_file_path) {
				$path_array            = explode('/', $strings_file_path);
				$langs[$path_array[2]] = self::LANG_NAMES[$path_array[2]] ?? $path_array[2];
			}
		}
		return $langs;
	}

	/**
	 * Get language codes that are detectable by our language detection routines.
	 * Languages are excluded that aren't used often and that tend to false detections.
	 * The listed codes are a collection of both the official ISO 639-1 codes and
	 * the codes that are used by our built-in language detection routine.
	 * When the detection is done, the result only consists of the official ISO 639-1 codes.
	 *
	 * @return array
	 */
	public function getDetectableLanguages(): array
	{
		$additional_langs = [
			'af', 'az', 'az-Cyrl', 'az-Latn', 'be', 'bn', 'bs', 'bs-Cyrl', 'bs-Latn',
			'cy', 'da', 'el', 'el-monoton', 'el-polyton', 'en', 'eu', 'fa', 'fi',
			'ga', 'gl', 'gu', 'he', 'hi', 'hr', 'hy', 'id', 'in', 'iu', 'iw', 'jv', 'jw',
			'ka', 'km', 'ko', 'lt', 'lv', 'mo', 'ms', 'ms-Arab', 'ms-Latn', 'nb', 'nn', 'no',
			'pt', 'pt-PT', 'pt-BR', 'ro', 'sa', 'sk', 'sl', 'sq', 'sr', 'sr-Cyrl', 'sr-Latn', 'sw',
			'ta', 'th', 'tl', 'tr', 'ug', 'uk', 'uz', 'vi', 'zh', 'zh-Hant', 'zh-Hans',
		];

		if (in_array('cld2', get_loaded_extensions())) {
			$additional_langs = array_merge(
				$additional_langs,
				['dv', 'kn', 'lo', 'ml', 'or', 'pa', 'sd', 'si', 'te', 'yi'],
			);
		}

		$langs = array_merge($additional_langs, array_keys($this->getAvailableLanguages()));
		sort($langs);
		return $langs;
	}

	/**
	 * Return a list of supported languages with their two byte language codes.
	 *
	 * @param bool $international If set to true, additionally the international language name is returned as well.
	 * @return array
	 */
	public function getLanguageCodes(bool $international = false, bool $sentence_start_capitalization = false): array
	{
		$iso639 = new \Matriphe\ISO639\ISO639();

		// In ISO 639-2 undetermined languages have got the code "und".
		// There is no official code for ISO 639-1, but "un" is not assigned to any language.
		$languages = [self::UNDETERMINED_LANGUAGE => $this->t('Undetermined')];

		foreach ($this->getDetectableLanguages() as $code) {
			$code   = $this->toISO6391($code);
			$native = $iso639->nativeByCode1($code);
			if ($sentence_start_capitalization) {
				$native = Strings::ucFirst($native);
			}
			$language = $iso639->languageByCode1($code);
			if ($native != $language && $international) {
				$languages[$code] = $this->t('%s (%s)', $native, $language);
			} else {
				$languages[$code] = $native;
			}
		}

		return $languages;
	}

	/**
	 * Converts e.g. en-gb to en_GB and da-dk to da_DK, which is the format some other systems expect
	 *
	 * @param string $lang
	 * @return string
	 * */
	public function langToLocaleCode($lang)
	{
		return preg_replace_callback("/([a-z]+)-([a-z]+)/", fn ($m) => $m[1] . "_" . strtoupper($m[2]), $lang);
	}

	/**
	 * Convert the language code to ISO639-1
	 * It also converts old codes to their new counterparts.
	 *
	 * @param string $code
	 * @return string
	 */
	public function toISO6391(string $code): string
	{
		if ((strlen($code) > 2) && (substr($code, 2, 1) == '-')) {
			$code = substr($code, 0, 2);
		}
		if (in_array($code, ['nb', 'nn'])) {
			$code = 'no';
		}
		if ($code == 'in') {
			$code = 'id';
		}
		if ($code == 'iw') {
			$code = 'he';
		}
		if ($code == 'jw') {
			$code = 'jv';
		}
		if ($code == 'mo') {
			$code = 'ro';
		}
		return $code;
	}

	/**
	 * Creates a new L10n instance based on the given langauge
	 *
	 * @param string $lang The new language
	 *
	 * @return static A new L10n instance
	 * @throws \Exception
	 */
	public function withLang(string $lang): L10n
	{
		// Don't create a new instance for same language
		if ($lang === $this->lang) {
			return $this;
		}

		$newL10n = clone $this;
		$newL10n->loadTranslationTable($lang);
		return $newL10n;
	}

	/**
	 * Format a date/time string using RELATIVE_FULL date and SHORT time.
	 *
	 * This will produce relative strings where supported by the ICU implementation
	 * (for example "yesterday", "in 2 days") according to the current locale.
	 *
	 * @param string $datestring Date/time string (e.g. ISO 8601)
	 * @return string Formatted relative date/time string
	 * @throws \Exception If the date string cannot be parsed into a DateTime
	 */
	public function relativeDateTime(string $datestring): string
	{
		return $this->formatDateTime($datestring, IntlDateFormatter::RELATIVE_FULL, IntlDateFormatter::SHORT);
	}

	/**
	 * Format a date/time string using FULL date and SHORT time according to current locale.
	 *
	 * @param string $datestring Date/time string (e.g. ISO 8601)
	 * @return string Formatted date/time string
	 * @throws \Exception If the date string cannot be parsed into a DateTime
	 */
	public function fullDateTime(string $datestring): string
	{
		return $this->formatDateTime($datestring, IntlDateFormatter::FULL, IntlDateFormatter::SHORT);
	}

	/**
	 * Format a date/time string using MEDIUM date and SHORT time according to current locale.
	 *
	 * @param string $datestring Date/time string
	 * @return string Formatted date/time string
	 * @throws \Exception If the date string cannot be parsed into a DateTime
	 */
	public function dateTime(string $datestring): string
	{
		return $this->formatDateTime($datestring, IntlDateFormatter::MEDIUM, IntlDateFormatter::SHORT);
	}

	/**
	 * Format a date string (date only) using FULL date format according to current locale.
	 *
	 * @param string $datestring Date string
	 * @return string Formatted date string
	 * @throws \Exception If the date string cannot be parsed into a DateTime
	 */
	public function fullDate(string $datestring): string
	{
		return $this->formatDateTime($datestring, IntlDateFormatter::FULL, IntlDateFormatter::NONE);
	}

	/**
	 * Format a date string (date only) using MEDIUM date format according to current locale.
	 *
	 * @param string $datestring Date string
	 * @return string Formatted date string
	 * @throws \Exception If the date string cannot be parsed into a DateTime
	 */
	public function mediumDate(string $datestring): string
	{
		return $this->formatDateTime($datestring, IntlDateFormatter::MEDIUM, IntlDateFormatter::NONE);
	}

	/**
	 * Format a date string (date only) using LONG date format according to current locale.
	 *
	 * @param string $datestring Date string
	 * @return string Formatted date string
	 * @throws \Exception If the date string cannot be parsed into a DateTime
	 */
	public function longDate(string $datestring): string
	{
		return $this->formatDateTime($datestring, IntlDateFormatter::LONG, IntlDateFormatter::NONE);
	}

	/**
	 * Format a date/time string using a custom ICU pattern.
	 * @see https://unicode-org.github.io/icu/userguide/format_parse/datetime/#datetime-format-syntax
	 *
	 * The provided pattern is passed directly to the underlying
	 * `IntlDateFormatter` instance. This allows callers to specify
	 * arbitrary formatting rules beyond the standard date/time styles.
	 *
	 * @param string $datestring Date/time string (e.g. ISO 8601)
	 * @param string $pattern ICU date/time pattern
	 * @return string Formatted date/time string
	 * @throws \Exception If the date string cannot be parsed into a DateTime
	 */
	public function formatDateTimeByPattern(string $datestring, string $pattern): string
	{
		return $this->formatDateTime($datestring, IntlDateFormatter::NONE, IntlDateFormatter::NONE, $pattern);
	}

	/**
	 * General date/time formatting helper.
	 *
	 * Creates an IntlDateFormatter using the instance locale and the timezone
	 * stored in session (if any) and formats the provided date/time string.
	 *
	 * @see https://unicode-org.github.io/icu/userguide/format_parse/datetime/#datetime-format-syntax for details on the supported pattern syntax when using the $pattern parameter.
	 *
	 * @param string $datestring Date/time string (e.g. ISO 8601)
	 * @param int $dateType One of IntlDateFormatter::SHORT|MEDIUM|LONG|FULL|NONE
	 * @param int $timeType One of IntlDateFormatter::SHORT|MEDIUM|LONG|FULL|NONE
	 * @param string $pattern Optional ICU date/time pattern to use instead of the standard styles
	 * @return string Formatted date/time string
	 * @throws \Exception If the date string cannot be parsed into a DateTime
	 */
	public function formatDateTime(string $datestring, int $dateType, int $timeType, ?string $pattern = null): string
	{
		$formatter = new IntlDateFormatter(
			$this->session->get('language') ?? $this->locale ?: $this->config->get('system', 'language', 'en_US'),
			$dateType,
			$timeType,
			$this->session->get('timezone') ?? null,
			null,
			$pattern,
		);

		return $formatter->format(new DateTime($datestring));
	}
}
