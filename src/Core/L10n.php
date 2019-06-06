<?php
/**
 * @file src/Core/L10n.php
 */
namespace Friendica\Core;

use Friendica\BaseObject;
use Friendica\Database\DBA;
use Friendica\Util\Strings;

/**
 * Provide Language, Translation, and Localization functions to the application
 * Localization can be referred to by the numeronym L10N (as in: "L", followed by ten more letters, and then "N").
 */
class L10n extends BaseObject
{
	/**
	 * A string indicating the current language used for translation:
	 * - Two-letter ISO 639-1 code.
	 * - Two-letter ISO 639-1 code + dash + Two-letter ISO 3166-1 alpha-2 country code.
	 * @var string
	 */
	private static $lang = '';
	/**
	 * A language code saved for later after pushLang() has been called.
	 *
	 * @var string
	 */
	private static $langSave = '';

	/**
	 * An array of translation strings whose key is the neutral english message.
	 *
	 * @var array
	 */
	private static $strings = [];
	/**
	 * An array of translation strings saved for later after pushLang() has been called.
	 *
	 * @var array
	 */
	private static $stringsSave = [];

	/**
	 * Detects the language and sets the translation table
	 */
	public static function init()
	{
		$lang = self::detectLanguage();
		self::loadTranslationTable($lang);
	}

	/**
	 * Returns the current language code
	 *
	 * @return string Language code
	 */
	public static function getCurrentLang()
	{
		return self::$lang;
	}

	/**
	 * Sets the language session variable
	 */
	public static function setSessionVariable()
	{
		if (!empty($_SESSION['authenticated']) && empty($_SESSION['language'])) {
			$_SESSION['language'] = self::$lang;
			// we haven't loaded user data yet, but we need user language
			if (!empty($_SESSION['uid'])) {
				$user = DBA::selectFirst('user', ['language'], ['uid' => $_SESSION['uid']]);
				if (DBA::isResult($user)) {
					$_SESSION['language'] = $user['language'];
				}
			}
		}

		if (isset($_GET['lang'])) {
			$_SESSION['language'] = $_GET['lang'];
		}
	}

	public static function setLangFromSession()
	{
		if (!empty($_SESSION['language']) && $_SESSION['language'] !== self::$lang) {
			self::loadTranslationTable($_SESSION['language']);
		}
	}

	/**
	 * @brief Returns the preferred language from the HTTP_ACCEPT_LANGUAGE header
	 * @return string The two-letter language code
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function detectLanguage()
	{
		$lang_list = [];

		if (!empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
			// break up string into pieces (languages and q factors)
			preg_match_all('/([a-z]{1,8}(-[a-z]{1,8})?)\s*(;\s*q\s*=\s*(1|0\.[0-9]+))?/i', $_SERVER['HTTP_ACCEPT_LANGUAGE'], $lang_parse);

			if (count($lang_parse[1])) {
				// go through the list of prefered languages and add a generic language
				// for sub-linguas (e.g. de-ch will add de) if not already in array
				for ($i = 0; $i < count($lang_parse[1]); $i++) {
					$lang_list[] = strtolower($lang_parse[1][$i]);
					if (strlen($lang_parse[1][$i])>3) {
						$dashpos = strpos($lang_parse[1][$i], '-');
						if (!in_array(substr($lang_parse[1][$i], 0, $dashpos), $lang_list)) {
							$lang_list[] = strtolower(substr($lang_parse[1][$i], 0, $dashpos));
						}
					}
				}
			}
		}

		if (isset($_GET['lang'])) {
			$lang_list = [$_GET['lang']];
		}

		// check if we have translations for the preferred languages and pick the 1st that has
		foreach ($lang_list as $lang) {
			if ($lang === 'en' || (file_exists("view/lang/$lang") && is_dir("view/lang/$lang"))) {
				$preferred = $lang;
				break;
			}
		}
		if (isset($preferred)) {
			return $preferred;
		}

		// in case none matches, get the system wide configured language, or fall back to English
		return Config::get('system', 'language', 'en');
	}

	/**
	 * This function should be called before formatting messages in a specific target language
	 * different from the current user/system language.
	 *
	 * It saves the current translation strings in a separate variable and loads new translations strings.
	 *
	 * If called repeatedly, it won't save the translation strings again, just load the new ones.
	 *
	 * @see   popLang()
	 * @brief Stores the current language strings and load a different language.
	 * @param string $lang Language code
	 * @throws \Exception
	 */
	public static function pushLang($lang)
	{
		if (!self::$lang) {
			self::init();
		}

		if ($lang === self::$lang) {
			return;
		}

		if (!self::$langSave) {
			self::$langSave = self::$lang;
			self::$stringsSave = self::$strings;
		}

		self::loadTranslationTable($lang);
	}

	/**
	 * Restores the original user/system language after having used pushLang()
	 */
	public static function popLang()
	{
		if (!self::$langSave) {
			return;
		}

		self::$strings = self::$stringsSave;
		self::$lang = self::$langSave;

		self::$stringsSave = [];
		self::$langSave = '';
	}

	/**
	 * Loads string translation table
	 *
	 * First addon strings are loaded, then globals
	 *
	 * Uses an App object shim since all the strings files refer to $a->strings
	 *
	 * @param string $lang language code to load
	 * @throws \Exception
	 */
	private static function loadTranslationTable($lang)
	{
		$lang = Strings::sanitizeFilePathItem($lang);

		if ($lang === self::$lang) {
			return;
		}

		$a = new \stdClass();
		$a->strings = [];

		// load enabled addons strings
		$addons = DBA::select('addon', ['name'], ['installed' => true]);
		while ($p = DBA::fetch($addons)) {
			$name = Strings::sanitizeFilePathItem($p['name']);
			if (file_exists("addon/$name/lang/$lang/strings.php")) {
				include "addon/$name/lang/$lang/strings.php";
			}
		}

		if (file_exists("view/lang/$lang/strings.php")) {
			include "view/lang/$lang/strings.php";
		}

		self::$lang = $lang;
		self::$strings = $a->strings;

		unset($a);
	}

	/**
	 * @brief Return the localized version of the provided string with optional string interpolation
	 *
	 * This function takes a english string as parameter, and if a localized version
	 * exists for the current language, substitutes it before performing an eventual
	 * string interpolation (sprintf) with additional optional arguments.
	 *
	 * Usages:
	 * - L10n::t('This is an example')
	 * - L10n::t('URL %s returned no result', $url)
	 * - L10n::t('Current version: %s, new version: %s', $current_version, $new_version)
	 *
	 * @param string $s
	 * @param array  $vars Variables to interpolate in the translation string
	 * @return string
	 */
	public static function t($s, ...$vars)
	{
		if (empty($s)) {
			return '';
		}

		if (!self::$lang) {
			self::init();
		}

		if (!empty(self::$strings[$s])) {
			$t = self::$strings[$s];
			$s = is_array($t) ? $t[0] : $t;
		}

		if (count($vars) > 0) {
			$s = sprintf($s, ...$vars);
		}

		return $s;
	}

	/**
	 * @brief Return the localized version of a singular/plural string with optional string interpolation
	 *
	 * This function takes two english strings as parameters, singular and plural, as
	 * well as a count. If a localized version exists for the current language, they
	 * are used instead. Discrimination between singular and plural is done using the
	 * localized function if any or the default one. Finally, a string interpolation
	 * is performed using the count as parameter.
	 *
	 * Usages:
	 * - L10n::tt('Like', 'Likes', $count)
	 * - L10n::tt("%s user deleted", "%s users deleted", count($users))
	 *
	 * @param string $singular
	 * @param string $plural
	 * @param int    $count
	 * @return string
	 * @throws \Exception
	 */
	public static function tt($singular, $plural, $count)
	{
		if (!is_numeric($count)) {
			Logger::log('Non numeric count called by ' . System::callstack(20));
		}

		if (!self::$lang) {
			self::init();
		}

		if (!empty(self::$strings[$singular])) {
			$t = self::$strings[$singular];
			if (is_array($t)) {
				$plural_function = 'string_plural_select_' . str_replace('-', '_', self::$lang);
				if (function_exists($plural_function)) {
					$i = $plural_function($count);
				} else {
					$i = self::stringPluralSelectDefault($count);
				}

				// for some languages there is only a single array item
				if (!isset($t[$i])) {
					$s = $t[0];
				} else {
					$s = $t[$i];
				}
			} else {
				$s = $t;
			}
		} elseif (self::stringPluralSelectDefault($count)) {
			$s = $plural;
		} else {
			$s = $singular;
		}

		$s = @sprintf($s, $count);

		return $s;
	}

	/**
	 * Provide a fallback which will not collide with a function defined in any language file
	 *
	 * @param int $n
	 * @return bool
	 */
	private static function stringPluralSelectDefault($n)
	{
		return $n != 1;
	}

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
	public static function getAvailableLanguages()
	{
		$langs = [];
		$strings_file_paths = glob('view/lang/*/strings.php');

		if (is_array($strings_file_paths) && count($strings_file_paths)) {
			if (!in_array('view/lang/en/strings.php', $strings_file_paths)) {
				$strings_file_paths[] = 'view/lang/en/strings.php';
			}
			asort($strings_file_paths);
			foreach ($strings_file_paths as $strings_file_path) {
				$path_array = explode('/', $strings_file_path);
				$langs[$path_array[2]] = $path_array[2];
			}
		}
		return $langs;
	}

	/**
	 * @brief Translate days and months names.
	 *
	 * @param string $s String with day or month name.
	 * @return string Translated string.
	 */
	public static function getDay($s)
	{
		$ret = str_replace(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'],
			[self::t('Monday'), self::t('Tuesday'), self::t('Wednesday'), self::t('Thursday'), self::t('Friday'), self::t('Saturday'), self::t('Sunday')],
			$s);

		$ret = str_replace(['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
			[self::t('January'), self::t('February'), self::t('March'), self::t('April'), self::t('May'), self::t('June'), self::t('July'), self::t('August'), self::t('September'), self::t('October'), self::t('November'), self::t('December')],
			$ret);

		return $ret;
	}

	/**
	 * @brief Translate short days and months names.
	 *
	 * @param string $s String with short day or month name.
	 * @return string Translated string.
	 */
	public static function getDayShort($s)
	{
		$ret = str_replace(['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
			[self::t('Mon'), self::t('Tue'), self::t('Wed'), self::t('Thu'), self::t('Fri'), self::t('Sat'), self::t('Sun')],
			$s);

		$ret = str_replace(['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
			[self::t('Jan'), self::t('Feb'), self::t('Mar'), self::t('Apr'), self::t('May'), ('Jun'), self::t('Jul'), self::t('Aug'), self::t('Sep'), self::t('Oct'), self::t('Nov'), self::t('Dec')],
			$ret);

		return $ret;
	}
}
