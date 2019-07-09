<?php
/**
 * @file src/Core/L10n.php
 */
namespace Friendica\Core;

use Friendica\Core\L10n\L10n as L10nClass;

/**
 * Provide Language, Translation, and Localization functions to the application
 * Localization can be referred to by the numeronym L10N (as in: "L", followed by ten more letters, and then "N").
 */
class L10n
{
	/**
	 * @var L10nClass
	 */
	private static $l10n;

	/**
	 * Initializes the L10n static wrapper with the instance
	 *
	 * @param L10nClass $l10n The l10n class
	 */
	public static function init(L10nClass $l10n)
	{
		self::$l10n = $l10n;
	}

	/**
	 * Returns the current language code
	 *
	 * @return string Language code
	 */
	public static function getCurrentLang()
	{
		return self::$l10n->getCurrentLang();
	}

	/**
	 * @brief Returns the preferred language from the HTTP_ACCEPT_LANGUAGE header
	 *
	 * @param string $sysLang The default fallback language
	 *
	 * @return string The two-letter language code
	 */
	public static function detectLanguage(string $sysLang = 'en')
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
					if (strlen($lang_parse[1][$i]) > 3) {
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
		return $sysLang;
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
		self::$l10n->pushLang($lang);
	}

	/**
	 * Restores the original user/system language after having used pushLang()
	 */
	public static function popLang()
	{
		self::$l10n->popLang();
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
		return self::$l10n->t($s, $vars);
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
	public static function tt(string $singular, string $plural, int $count)
	{
		return self::$l10n->tt($singular, $plural, $count);
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
		return self::$l10n::getAvailableLanguages();
	}

	/**
	 * @brief Translate days and months names.
	 *
	 * @param string $s String with day or month name.
	 * @return string Translated string.
	 */
	public static function getDay($s)
	{
		return self::$l10n->getDay($s);
	}

	/**
	 * @brief Translate short days and months names.
	 *
	 * @param string $s String with short day or month name.
	 * @return string Translated string.
	 */
	public static function getDayShort($s)
	{
		return self::$l10n->getDayShort($s);
	}
}
