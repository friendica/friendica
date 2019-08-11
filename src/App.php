<?php
/**
 * @file src/App.php
 */
namespace Friendica;

use Detection\MobileDetect;
use Exception;
use Friendica\App\Arguments;
use Friendica\Core\Config\Cache\ConfigCache;
use Friendica\Core\Config\Configuration;
use Friendica\Core\Frontend;
use Friendica\Core\L10n\L10n;
use Friendica\Core\Process;
use Friendica\Core\Theme;
use Friendica\Database\Database;
use Friendica\Database\DBA;
use Friendica\Network\HTTPException;
use Friendica\Util\BaseURL;
use Friendica\Util\ConfigFileLoader;
use Friendica\Util\Profiler;
use Friendica\Util\Strings;
use Psr\Log\LoggerInterface;

/**
 *
 * class: App
 *
 * @brief Our main application structure for the life of this page.
 *
 * Primarily deals with the URL that got us here
 * and tries to make some sense of it, and
 * stores our page contents and config storage
 * and anything else that might need to be passed around
 * before we spit the page out.
 *
 */
class App
{
	/** @deprecated 2019.09 - use App\Arguments->getQueryString() */
	public $query_string = '';
	/**
	 * @var Frontend The Frontend Page
	 *               All Parts of the frontend page are per array accessible (compatibility)
	 * @deprecated 2019.09 - use Frontend instead
	 */
	public $page;
	public $profile;
	public $profile_uid;
	public $user;
	public $cid;
	public $contact;
	public $contacts;
	public $page_contact;
	public $content;
	public $data = [];
	/** @deprecated 2019.09 - use App\Arguments->getCommand() */
	public $cmd = '';
	/** @deprecated 2019.09 - use App\Arguments->getArgv() or Arguments->get() */
	public $argv;
	/** @deprecated 2019.09 - use App\Arguments->getArgc() */
	public $argc;
	/** @deprecated 2019.09 - Use App\Module->getName() instead */
	public $module;
	public $timezone;
	public $interactive = true;
	public $identities;
	public $is_mobile = false;
	public $is_tablet = false;
	public $theme_info = [];
	public $category;
	// Allow themes to control internal parameters
	// by changing App values in theme.php

	public $sourcename = '';
	public $videowidth = 425;
	public $videoheight = 350;
	public $force_max_items = 0;
	public $theme_events_in_profile = true;

	/**
	 * @var App\Mode The Mode of the Application
	 */
	private $mode;

	/**
	 * @var App\Router
	 */
	private $router;

	/**
	 * @var BaseURL
	 */
	private $baseURL;

	/**
	 * @var string The name of the current theme
	 */
	private $currentTheme;

	/**
	 * @var bool check if request was an AJAX (xmlhttprequest) request
	 */
	private $isAjax;

	/**
	 * @var MobileDetect
	 */
	public $mobileDetect;

	/**
	 * @var Configuration The config
	 */
	private $config;

	/**
	 * @var LoggerInterface The logger
	 */
	private $logger;

	/**
	 * @var Profiler The profiler of this app
	 */
	private $profiler;

	/**
	 * @var Database The Friendica database connection
	 */
	private $database;

	/**
	 * @var L10n The translator
	 */
	private $l10n;

	/**
	 * @var App\Arguments
	 */
	private $args;
	/**
	 * @var Process
	 */
	private $process;

	/**
	 * Returns the current config cache of this node
	 *
	 * @return ConfigCache
	 */
	public function getConfigCache()
	{
		return $this->config->getCache();
	}

	/**
	 * Returns the current config of this node
	 *
	 * @return Configuration
	 */
	public function getConfig()
	{
		return $this->config;
	}

	/**
	 * The basepath of this app
	 *
	 * @return string
	 */
	public function getBasePath()
	{
		// Don't use the basepath of the config table for basepath (it should always be the config-file one)
		return $this->config->getCache()->get('system', 'basepath');
	}

	/**
	 * The Logger of this app
	 *
	 * @return LoggerInterface
	 */
	public function getLogger()
	{
		return $this->logger;
	}

	/**
	 * The profiler of this app
	 *
	 * @return Profiler
	 */
	public function getProfiler()
	{
		return $this->profiler;
	}

	/**
	 * Returns the Mode of the Application
	 *
	 * @return App\Mode The Application Mode
	 */
	public function getMode()
	{
		return $this->mode;
	}

	/**
	 * Returns the Database of the Application
	 *
	 * @return Database
	 */
	public function getDBA()
	{
		return $this->database;
	}

	/**
	 * @deprecated 2019.09 - use Frontend->registerStylesheet instead
	 * @see Frontend::registerStylesheet()
	 */
	public function registerStylesheet($path)
	{
		$this->page->registerStylesheet($path);
	}

	/**
	 * @deprecated 2019.09 - use Frontend->registerFooterScript instead
	 * @see Frontend::registerFooterScript()
	 */
	public function registerFooterScript($path)
	{
		$this->page->registerFooterScript($path);
	}

	public $queue;

	/**
	 * @brief App constructor.
	 *
	 * @param Database $database The Friendica Database
	 * @param Configuration    $config    The Configuration
	 * @param App\Mode         $mode      The mode of this Friendica app
	 * @param App\Router       $router    The router of this Friendica app
	 * @param BaseURL          $baseURL   The full base URL of this Friendica app
	 * @param LoggerInterface  $logger    The current app logger
	 * @param Profiler         $profiler  The profiler of this application
	 * @param L10n             $l10n      The translator instance
	 *
	 * @throws Exception if the Basepath is not usable
	 */
	public function __construct(Database $database, Configuration $config, App\Mode $mode, App\Router $router, BaseURL $baseURL, LoggerInterface $logger, Profiler $profiler, L10n $l10n, Arguments $args, Frontend $frontend, Process $process)
	{
		$this->database = $database;
		$this->config   = $config;
		$this->mode     = $mode;
		$this->router   = $router;
		$this->baseURL  = $baseURL;
		$this->profiler = $profiler;
		$this->logger   = $logger;
		$this->l10n     = $l10n;
		$this->args = $args;
		$this->page = $frontend;
		$this->process = $process;

		$this->profiler->reset();

		$this->reload();

		set_time_limit(0);

		// This has to be quite large to deal with embedded private photos
		ini_set('pcre.backtrack_limit', 500000);

		set_include_path(
			get_include_path() . PATH_SEPARATOR
			. $this->getBasePath() . DIRECTORY_SEPARATOR . 'include' . PATH_SEPARATOR
			. $this->getBasePath() . DIRECTORY_SEPARATOR . 'library' . PATH_SEPARATOR
			. $this->getBasePath());

		$this->cmd = $args->getCommand();
		$this->argv = $args->getArgv();
		$this->argc = $args->getArgc();
		$this->query_string = $args->getQueryString();

		// Detect mobile devices
		$mobile_detect = new MobileDetect();

		$this->mobileDetect = $mobile_detect;

		$this->is_mobile = $mobile_detect->isMobile();
		$this->is_tablet = $mobile_detect->isTablet();

		$this->isAjax = strtolower(defaults($_SERVER, 'HTTP_X_REQUESTED_WITH', '')) == 'xmlhttprequest';

		// Register template engines
		Core\Renderer::registerTemplateEngine('Friendica\Render\FriendicaSmartyEngine');
	}

	/**
	 * Reloads the whole app instance
	 */
	public function reload()
	{
		if ($this->mode->has(App\Mode::DBAVAILABLE)) {
			$this->profiler->update($this->config);

			Core\Hook::loadHooks();
			$loader = new ConfigFileLoader($this->getBasePath());
			Core\Hook::callAll('load_config', $loader);
		}

		$this->loadDefaultTimezone();
	}

	/**
	 * Loads the default timezone
	 *
	 * Include support for legacy $default_timezone
	 *
	 * @global string $default_timezone
	 */
	private function loadDefaultTimezone()
	{
		if ($this->config->get('system', 'default_timezone')) {
			$this->timezone = $this->config->get('system', 'default_timezone');
		} else {
			global $default_timezone;
			$this->timezone = !empty($default_timezone) ? $default_timezone : 'UTC';
		}

		if ($this->timezone) {
			date_default_timezone_set($this->timezone);
		}
	}

	/**
	 * Retrieves the Friendica instance base URL
	 *
	 * @param bool $ssl Whether to append http or https under BaseURL::SSL_POLICY_SELFSIGN
	 *
	 * @return string Friendica server base URL
	 *
	 * @deprecated 2019.09 - use BaseUrl->get($ssl) instead
	 */
	public function getBaseURL($ssl = false)
	{
		return $this->baseURL->get($ssl);
	}

	/**
	 * @brief Initializes the baseurl components
	 *
	 * Clears the baseurl cache to prevent inconsistencies
	 *
	 * @param string $url
	 *
	 * @deprecated 2019.06 - use BaseURL->saveByURL($url) instead
	 */
	public function setBaseURL($url)
	{
		$this->baseURL->saveByURL($url);
	}

	/**
	 * Returns the current hostname
	 *
	 * @return string
	 *
	 * @deprecated 2019.06 - use BaseURL->getHostname() instead
	 */
	public function getHostName()
	{
		return $this->baseURL->getHostname();
	}

	/**
	 * Returns the sub-path of the full URL
	 *
	 * @return string
	 *
	 * @deprecated 2019.06 - use BaseURL->getUrlPath() instead
	 */
	public function getURLPath()
	{
		return $this->baseURL->getUrlPath();
	}

	/**
	 * @brief Removes the base url from an url. This avoids some mixed content problems.
	 *
	 * @param string $origURL
	 *
	 * @return string The cleaned url
	 * @throws HTTPException\InternalServerErrorException
	 */
	public function removeBaseURL($origURL)
	{
		// Remove the hostname from the url if it is an internal link
		$nurl = Util\Strings::normaliseLink($origURL);
		$base = Util\Strings::normaliseLink($this->getBaseURL());
		$url = str_replace($base . '/', '', $nurl);

		// if it is an external link return the orignal value
		if ($url == Util\Strings::normaliseLink($origURL)) {
			return $origURL;
		} else {
			return $url;
		}
	}

	/**
	 * Returns the current UserAgent as a String
	 *
	 * @return string the UserAgent as a String
	 * @throws HTTPException\InternalServerErrorException
	 */
	public function getUserAgent()
	{
		return
			FRIENDICA_PLATFORM . " '" .
			FRIENDICA_CODENAME . "' " .
			FRIENDICA_VERSION . '-' .
			DB_UPDATE_VERSION . '; ' .
			$this->getBaseURL();
	}

	/**
	 * @deprecated 2019.09 - use Process->isMaxProcessReached() instead
	 * @see Process::isMaxProcessesReached()
	 */
	public function isMaxProcessesReached()
	{
		return $this->process->isMaxProcessesReached();
	}

	/**
	 * @deprecated 2019.09 - use Process->isMinMemoryReached instead
	 * @see Process::isMinMemoryReached()
	 */
	public function isMinMemoryReached()
	{
		return $this->process->isMinMemoryReached();
	}

	/**
	 * @deprecated 2019.09 - use Process->isMaxLoadReached instead
	 * @see Process::isMaxLoadReached()
	 */
	public function isMaxLoadReached()
	{
		return $this->process->isMaxLoadReached();
	}

	/**
	 * @deprecated 2019.09 - use Process->run() instead
	 * @see Process::run()
	 */
	public function proc_run($command, $args)
	{
		$this->process->run($command, $args);
	}

	/**
	 * Generates the site's default sender email address
	 *
	 * @return string
	 * @throws HTTPException\InternalServerErrorException
	 */
	public function getSenderEmailAddress()
	{
		$sender_email = $this->config->get('config', 'sender_email');
		if (empty($sender_email)) {
			$hostname = $this->baseURL->getHostname();
			if (strpos($hostname, ':')) {
				$hostname = substr($hostname, 0, strpos($hostname, ':'));
			}

			$sender_email = 'noreply@' . $hostname;
		}

		return $sender_email;
	}

	/**
	 * Returns the current theme name.
	 *
	 * @return string the name of the current theme
	 * @throws HTTPException\InternalServerErrorException
	 */
	public function getCurrentTheme()
	{
		if ($this->getMode()->isInstall()) {
			return '';
		}

		if (!$this->currentTheme) {
			$this->computeCurrentTheme();
		}

		return $this->currentTheme;
	}

	public function setCurrentTheme($theme)
	{
		$this->currentTheme = $theme;
	}

	/**
	 * Computes the current theme name based on the node settings, the user settings and the device type
	 *
	 * @throws Exception
	 */
	private function computeCurrentTheme()
	{
		$system_theme = $this->config->get('system', 'theme');
		if (!$system_theme) {
			throw new Exception($this->l10n->t('No system theme config value set.'));
		}

		// Sane default
		$this->currentTheme = $system_theme;

		$page_theme = null;
		// Find the theme that belongs to the user whose stuff we are looking at
		if ($this->profile_uid && ($this->profile_uid != local_user())) {
			// Allow folks to override user themes and always use their own on their own site.
			// This works only if the user is on the same server
			$user = DBA::selectFirst('user', ['theme'], ['uid' => $this->profile_uid]);
			if (DBA::isResult($user) && !Core\PConfig::get(local_user(), 'system', 'always_my_theme')) {
				$page_theme = $user['theme'];
			}
		}

		$user_theme = Core\Session::get('theme', $system_theme);

		// Specific mobile theme override
		if (($this->is_mobile || $this->is_tablet) && Core\Session::get('show-mobile', true)) {
			$system_mobile_theme = $this->config->get('system', 'mobile-theme');
			$user_mobile_theme = Core\Session::get('mobile-theme', $system_mobile_theme);

			// --- means same mobile theme as desktop
			if (!empty($user_mobile_theme) && $user_mobile_theme !== '---') {
				$user_theme = $user_mobile_theme;
			}
		}

		if ($page_theme) {
			$theme_name = $page_theme;
		} else {
			$theme_name = $user_theme;
		}

		$theme_name = Strings::sanitizeFilePathItem($theme_name);
		if ($theme_name
			&& in_array($theme_name, Theme::getAllowedList())
			&& (file_exists('view/theme/' . $theme_name . '/style.css')
			|| file_exists('view/theme/' . $theme_name . '/style.php'))
		) {
			$this->currentTheme = $theme_name;
		}
	}

	/**
	 * @brief Return full URL to theme which is currently in effect.
	 *
	 * Provide a sane default if nothing is chosen or the specified theme does not exist.
	 *
	 * @return string
	 * @throws HTTPException\InternalServerErrorException
	 */
	public function getCurrentThemeStylesheetPath()
	{
		return Core\Theme::getStylesheetPath($this->getCurrentTheme());
	}

	/**
	 * Check if request was an AJAX (xmlhttprequest) request.
	 *
	 * @return boolean true if it was an AJAX request
	 */
	public function isAjax()
	{
		return $this->isAjax;
	}

	/**
	 * @deprecated use Arguments->get() instead
	 *
	 * @see App\Arguments
	 */
	public function getArgumentValue($position, $default = '')
	{
		return $this->args->get($position, $default);
	}

	/**
	 * @deprecated 2019.09 - use BaseUrl->redirect() instead
	 * @see BaseURL::redirect()
	 */
	public function internalRedirect($toUrl = '', $ssl = false)
	{
		$this->baseURL->redirect($toUrl, $ssl);
	}

	/**
	 * Automatically redirects to relative or absolute URL
	 * Should only be used if it isn't clear if the URL is either internal or external
	 *
	 * @param string $toUrl The target URL
	 * @throws HTTPException\InternalServerErrorException
	 */
	public function redirect($toUrl)
	{
		if (!empty(parse_url($toUrl, PHP_URL_SCHEME))) {
			Core\System::externalRedirect($toUrl);
		} else {
			$this->internalRedirect($toUrl);
		}
	}
}
