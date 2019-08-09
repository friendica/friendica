<?php

namespace Friendica\Core\L10n;

use Friendica\App\Module;
use Friendica\Core\Config\Configuration;
use Friendica\Core\Hook;
use Friendica\Core\Session;
use Friendica\Database\Database;
use Friendica\Util\Strings;

/**
 * Provide Language, Translation, and Localization functions to the application
 * Localization can be referred to by the numeronym L10N (as in: "L", followed by ten more letters, and then "N").
 */
class L10n
{
	const DEFAULT_LANG = 'en';

	/**
	 * A string indicating the current language used for translation:
	 * - Two-letter ISO 639-1 code.
	 * - Two-letter ISO 639-1 code + dash + Two-letter ISO 3166-1 alpha-2 country code.
	 *
	 * @var string
	 */
	private $lang = '';

	/**
	 * The addons, which languages are loaded
	 *
	 * @var array
	 */
	private $addons = [];

	/**
	 * An array of translation strings whose key is the neutral english message.
	 *
	 * @var array
	 */
	private $strings = [];

	public function __construct(string $lang = self::DEFAULT_LANG, array $addons = [])
	{
		$this->lang   = Strings::sanitizeFilePathItem($lang);
		$this->addons = $addons;
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
	 * Load the user specific language environment
	 *
	 * 1) Based on a force language setting ($_GET)
	 * 2) Based on the user language setting
	 * 3) Auto-detected through the system
	 *
	 * @param Database $dba     The database connection of Friendica
	 * @param array    $server  The $_SERVER variables
	 * @param array    $get     The $_GET variables
	 *
	 * @return L10n The user specific language settings
	 * @throws \Exception
	 */
	public function userLanguage(Database $dba, Module $module, array $server, array $get)
	{
		if ($module->isBackend()) {
			return $this;
		}

		// @todo move Session start outside of this function (bad dependency)
		Session::start();

		if (!empty($get['lang'])) {
			Session::set('language', $get['lang']);
		} elseif (Session::get('authenticated') && !Session::get('language')) {
			Session::set('language', $this->lang);
			// we haven't loaded user data yet, but we need user language
			if (Session::get('uid')) {
				$user = $dba->selectFirst('user', ['language'], ['uid' => Session::get('uid')]);
				if ($dba->isResult($user)) {
					Session::set('language', $user['language']);
				}
			}
		}

		// Returns a new class for user languages (is called once each run during dependency injection)
		if (Session::get('language')) {
			return new L10n(
				Session::get('language'),
				$this->getAddonNames($dba)
			);
		} else {
			return new L10n(
				$this->detectLanguage($server, $get, $this->lang),
				$this->getAddonNames($dba)
			);
		}
	}

	/**
	 * Loads the system wide language environment
	 *
	 * @param Configuration $config The Friendica configurations
	 *
	 * @return L10n The system specific language instance
	 */
	public function systemLanguage(Configuration $config, Database $dba, array $server, array $get)
	{
		return new L10n($config->get('system', 'language', $this->detectLanguage($server, $get)), $this->getAddonNames($dba));
	}

	/**
	 * Returns all addons with language specific settings
	 *
	 * @param Database $dba The database connection of Friendica
	 *
	 * @return array The addons with language specific settings
	 * @throws \Exception in case an DBA exception occured
	 */
	private function getAddonNames(Database $dba)
	{
		$addons = [];

		// load enabled addons strings
		$stmtAddon = $dba->select('addon', ['name'], ['installed' => true]);
		while ($addon = $dba->fetch($stmtAddon)) {
			$name = Strings::sanitizeFilePathItem($addon['name']);
			if (file_exists("addon/$name/lang/$this->lang/strings.php")) {
				$addons[] = $name;
			}
		}

		return $addons;
	}

	/**
	 * Loads string translation table
	 *
	 * First addon strings are loaded, then globals
	 *
	 * Uses an App object shim since all the strings files refer to $a->strings
	 *
	 * @return L10n the L10n with loaded strings
	 */
	public function load()
	{
		$a          = new \stdClass();
		$a->strings = [];

		foreach ($this->addons as $name) {
			if (file_exists("addon/$name/lang/$this->lang/strings.php")) {
				include "addon/$name/lang/$this->lang/strings.php";
			}
		}

		if (file_exists("view/lang/$this->lang/strings.php")) {
			include "view/lang/$this->lang/strings.php";
		}

		$this->strings = $a->strings;

		unset($a);

		return $this;
	}

	/**
	 * @brief Returns the preferred language from the HTTP_ACCEPT_LANGUAGE header
	 *
	 * @param string $sysLang The default fallback language
	 *
	 * @return string The two-letter language code
	 */
	private function detectLanguage(array $server, array $get, string $sysLang = self::DEFAULT_LANG)
	{
		$lang_list = [];

		if (!empty($server['HTTP_ACCEPT_LANGUAGE'])) {
			// break up string into pieces (languages and q factors)
			preg_match_all('/([a-z]{1,8}(-[a-z]{1,8})?)\s*(;\s*q\s*=\s*(1|0\.[0-9]+))?/i', $server['HTTP_ACCEPT_LANGUAGE'], $lang_parse);

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

		if (isset($get['lang'])) {
			$lang_list = [$get['lang']];
		}

		// check if we have translations for the preferred languages and pick the 1st that has
		foreach ($lang_list as $lang) {
			if ($lang === self::DEFAULT_LANG || (file_exists("view/lang/$lang") && is_dir("view/lang/$lang"))) {
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
	 *
	 * @return string
	 */
	public function t($s, ...$vars)
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
	 *
	 * @return string
	 * @throws \Exception
	 */
	public function tt(string $singular, string $plural, int $count)
	{
		if (!empty($this->strings[$singular])) {
			$t = $this->strings[$singular];
			if (is_array($t)) {
				$plural_function = 'string_plural_select_' . str_replace('-', '_', $this->lang);
				if (function_exists($plural_function)) {
					$i = $plural_function($count);
				} else {
					$i = $this->stringPluralSelectDefault($count);
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
		} elseif ($this->stringPluralSelectDefault($count)) {
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
	 *
	 * @return bool
	 */
	private function stringPluralSelectDefault($n)
	{
		return $n != 1;
	}

	/**
	 * Return installed languages codes as associative array
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
		$langs              = [];
		$strings_file_paths = glob('view/lang/*/strings.php');

		if (is_array($strings_file_paths) && count($strings_file_paths)) {
			if (!in_array('view/lang/en/strings.php', $strings_file_paths)) {
				$strings_file_paths[] = 'view/lang/en/strings.php';
			}
			asort($strings_file_paths);
			foreach ($strings_file_paths as $strings_file_path) {
				$path_array            = explode('/', $strings_file_path);
				$langs[$path_array[2]] = $path_array[2];
			}
		}
		return $langs;
	}

	/**
	 * Translate days and months names.
	 *
	 * @param string $s String with day or month name.
	 *
	 * @return string Translated string.
	 */
	public function getDay($s)
	{
		$ret = str_replace(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'],
			[$this->t('Monday'), $this->t('Tuesday'), $this->t('Wednesday'), $this->t('Thursday'), $this->t('Friday'), $this->t('Saturday'), $this->t('Sunday')],
			$s);

		$ret = str_replace(['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
			[$this->t('January'), $this->t('February'), $this->t('March'), $this->t('April'), $this->t('May'), $this->t('June'), $this->t('July'), $this->t('August'), $this->t('September'), $this->t('October'), $this->t('November'), $this->t('December')],
			$ret);

		return $ret;
	}

	/**
	 * Translate short days and months names.
	 *
	 * @param string $s String with short day or month name.
	 *
	 * @return string Translated string.
	 */
	public function getDayShort($s)
	{
		$ret = str_replace(['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
			[$this->t('Mon'), $this->t('Tue'), $this->t('Wed'), $this->t('Thu'), $this->t('Fri'), $this->t('Sat'), $this->t('Sun')],
			$s);

		$ret = str_replace(['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
			[$this->t('Jan'), $this->t('Feb'), $this->t('Mar'), $this->t('Apr'), $this->t('May'), $this->t('Jun'), $this->t('Jul'), $this->t('Aug'), $this->t('Sep'), $this->t('Oct'), $this->t('Nov'), $this->t('Dec')],
			$ret);

		return $ret;
	}

	/**
	 * Load poke verbs
	 *
	 * @return array index is present tense verb
	 *                 value is array containing past tense verb, translation of present, translation of past
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @hook poke_verbs pokes array
	 */
	public function getPokeVerbs()
	{
		// index is present tense verb
		// value is array containing past tense verb, translation of present, translation of past
		$arr = [
			'poke'   => ['poked', $this->t('poke'), $this->t('poked')],
			'ping'   => ['pinged', $this->t('ping'), $this->t('pinged')],
			'prod'   => ['prodded', $this->t('prod'), $this->t('prodded')],
			'slap'   => ['slapped', $this->t('slap'), $this->t('slapped')],
			'finger' => ['fingered', $this->t('finger'), $this->t('fingered')],
			'rebuff' => ['rebuffed', $this->t('rebuff'), $this->t('rebuffed')],
		];

		Hook::callAll('poke_verbs', $arr);

		return $arr;
	}
}
