<?php
/**
 * @file src/App.php
 */
namespace Friendica;

use Exception;
use Friendica\App\Arguments;
use Friendica\App\BaseURL;
use Friendica\App\Page;
use Friendica\App\Authentication;
use Friendica\Core\Config\Cache\ConfigCache;
use Friendica\Core\Config\IConfiguration;
use Friendica\Core\Config\IPConfiguration;
use Friendica\Core\L10n\L10n;
use Friendica\Core\System;
use Friendica\Core\Theme;
use Friendica\Database\Database;
use Friendica\Model\Profile;
use Friendica\Module\Special\HTTPException as ModuleHTTPException;
use Friendica\Network\HTTPException;
use Friendica\Util\ConfigFileLoader;
use Friendica\Util\HTTPSignature;
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
	/**
	 * @var Page The current page environment
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
	/** @deprecated 2019.09 - use App\Arguments->getArgv() or Arguments->get() */
	public $argv;
	/** @deprecated 2019.09 - use App\Arguments->getArgc() */
	public $argc;
	public $timezone;
	public $interactive = true;
	public $identities;
	public $theme_info = [];
	public $category;
	// Allow themes to control internal parameters
	// by changing App values in theme.php

	public $sourcename              = '';
	public $videowidth              = 425;
	public $videoheight             = 350;
	public $force_max_items         = 0;
	public $theme_events_in_profile = true;
	public $queue;

	/**
	 * @var App\Mode The Mode of the Application
	 */
	private $mode;

	/**
	 * @var BaseURL
	 */
	private $baseURL;

	/** @var string The name of the current theme */
	private $currentTheme;
	/** @var string The name of the current mobile theme */
	private $currentMobileTheme;

	/**
	 * @var IConfiguration The config
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
	 * @var Core\Process The process methods
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
	 * @param Database        $database The Friendica Database
	 * @param IConfiguration   $config   The Configuration
	 * @param App\Mode        $mode     The mode of this Friendica app
	 * @param BaseURL         $baseURL  The full base URL of this Friendica app
	 * @param LoggerInterface $logger   The current app logger
	 * @param Profiler        $profiler The profiler of this application
	 * @param L10n            $l10n     The translator instance
	 * @param App\Arguments   $args     The Friendica Arguments of the call
	 * @param Core\Process    $process  The process methods
	 */
	public function __construct(Database $database, IConfiguration $config, App\Mode $mode, BaseURL $baseURL, LoggerInterface $logger, Profiler $profiler, L10n $l10n, Arguments $args, App\Page $page, Core\Process $process)
	{
		$this->database = $database;
		$this->config   = $config;
		$this->mode     = $mode;
		$this->baseURL  = $baseURL;
		$this->profiler = $profiler;
		$this->logger   = $logger;
		$this->l10n     = $l10n;
		$this->args     = $args;
		$this->process  = $process;

		$this->argv         = $args->getArgv();
		$this->argc         = $args->getArgc();
		$this->page         = $page;

		$this->load();
	}

	/**
	 * Load the whole app instance
	 */
	public function load()
	{
		set_time_limit(0);

		// This has to be quite large to deal with embedded private photos
		ini_set('pcre.backtrack_limit', 500000);

		set_include_path(
			get_include_path() . PATH_SEPARATOR
			. $this->getBasePath() . DIRECTORY_SEPARATOR . 'include' . PATH_SEPARATOR
			. $this->getBasePath() . DIRECTORY_SEPARATOR . 'library' . PATH_SEPARATOR
			. $this->getBasePath());

		$this->profiler->reset();

		if ($this->mode->has(App\Mode::DBAVAILABLE)) {
			$this->profiler->update($this->config);

			Core\Hook::loadHooks();
			$loader = new ConfigFileLoader($this->getBasePath());
			Core\Hook::callAll('load_config', $loader);
		}

		$this->loadDefaultTimezone();
		// Register template engines
		Core\Renderer::registerTemplateEngine('Friendica\Render\FriendicaSmartyEngine');
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
			$this->baseURL->get();
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
	 * Returns the current theme name. May be overriden by the mobile theme name.
	 *
	 * @return string
	 * @throws Exception
	 */
	public function getCurrentTheme()
	{
		if ($this->mode->isInstall()) {
			return '';
		}

		// Specific mobile theme override
		if (($this->mode->isMobile() || $this->mode->isTablet()) && Core\Session::get('show-mobile', true)) {
			$user_mobile_theme = $this->getCurrentMobileTheme();

			// --- means same mobile theme as desktop
			if (!empty($user_mobile_theme) && $user_mobile_theme !== '---') {
				return $user_mobile_theme;
			}
		}

		if (!$this->currentTheme) {
			$this->computeCurrentTheme();
		}

		return $this->currentTheme;
	}

	/**
	 * Returns the current mobile theme name.
	 *
	 * @return string
	 * @throws Exception
	 */
	public function getCurrentMobileTheme()
	{
		if ($this->mode->isInstall()) {
			return '';
		}

		if (is_null($this->currentMobileTheme)) {
			$this->computeCurrentMobileTheme();
		}

		return $this->currentMobileTheme;
	}

	public function setCurrentTheme($theme)
	{
		$this->currentTheme = $theme;
	}

	public function setCurrentMobileTheme($theme)
	{
		$this->currentMobileTheme = $theme;
	}

	/**
	 * Computes the current theme name based on the node settings, the page owner settings and the user settings
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
		$this->setCurrentTheme($system_theme);

		$page_theme = null;
		// Find the theme that belongs to the user whose stuff we are looking at
		if ($this->profile_uid && ($this->profile_uid != local_user())) {
			// Allow folks to override user themes and always use their own on their own site.
			// This works only if the user is on the same server
			$user = $this->database->selectFirst('user', ['theme'], ['uid' => $this->profile_uid]);
			if ($this->database->isResult($user) && !Core\PConfig::get(local_user(), 'system', 'always_my_theme')) {
				$page_theme = $user['theme'];
			}
		}

		$theme_name = $page_theme ?: Core\Session::get('theme', $system_theme);

		$theme_name = Strings::sanitizeFilePathItem($theme_name);
		if ($theme_name
		    && in_array($theme_name, Theme::getAllowedList())
		    && (file_exists('view/theme/' . $theme_name . '/style.css')
		        || file_exists('view/theme/' . $theme_name . '/style.php'))
		) {
			$this->setCurrentTheme($theme_name);
		}
	}

	/**
	 * Computes the current mobile theme name based on the node settings, the page owner settings and the user settings
	 */
	private function computeCurrentMobileTheme()
	{
		$system_mobile_theme = $this->config->get('system', 'mobile-theme', '');

		// Sane default
		$this->setCurrentMobileTheme($system_mobile_theme);

		$page_mobile_theme = null;
		// Find the theme that belongs to the user whose stuff we are looking at
		if ($this->profile_uid && ($this->profile_uid != local_user())) {
			// Allow folks to override user themes and always use their own on their own site.
			// This works only if the user is on the same server
			if (!Core\PConfig::get(local_user(), 'system', 'always_my_theme')) {
				$page_mobile_theme = Core\PConfig::get($this->profile_uid, 'system', 'mobile-theme');
			}
		}

		$mobile_theme_name = $page_mobile_theme ?: Core\Session::get('mobile-theme', $system_mobile_theme);

		$mobile_theme_name = Strings::sanitizeFilePathItem($mobile_theme_name);
		if ($mobile_theme_name == '---'
			||
			in_array($mobile_theme_name, Theme::getAllowedList())
			&& (file_exists('view/theme/' . $mobile_theme_name . '/style.css')
				|| file_exists('view/theme/' . $mobile_theme_name . '/style.php'))
		) {
			$this->setCurrentMobileTheme($mobile_theme_name);
		}
	}

	/**
	 * @brief Return full URL to theme which is currently in effect.
	 *
	 * Provide a sane default if nothing is chosen or the specified theme does not exist.
	 *
	 * @return string
	 * @throws Exception
	 */
	public function getCurrentThemeStylesheetPath()
	{
		return Core\Theme::getStylesheetPath($this->getCurrentTheme());
	}

	/**
	 * Sets the base url for use in cmdline programs which don't have
	 * $_SERVER variables
	 */
	public function checkURL()
	{
		$url = $this->config->get('system', 'url');

		// if the url isn't set or the stored url is radically different
		// than the currently visited url, store the current value accordingly.
		// "Radically different" ignores common variations such as http vs https
		// and www.example.com vs example.com.
		// We will only change the url to an ip address if there is no existing setting

		if (empty($url) || (!Util\Strings::compareLink($url, $this->baseURL->get())) && (!preg_match("/^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})$/", $this->baseURL->getHostname()))) {
			$this->config->set('system', 'url', $this->baseURL->get());
		}
	}

	/**
	 * Frontend App script
	 *
	 * The App object behaves like a container and a dispatcher at the same time, including a representation of the
	 * request and a representation of the response.
	 *
	 * This probably should change to limit the size of this monster method.
	 *
	 * @param App\Module     $module The determined module
	 * @param App\Router     $router
	 * @param IPConfiguration $pconfig
	 * @param Authentication $auth The Authentication backend of the node
	 * @throws HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public function runFrontend(App\Module $module, App\Router $router, IPConfiguration $pconfig, Authentication $auth)
	{
		$moduleName = $module->getName();

		try {
			// Missing DB connection: ERROR
			if ($this->mode->has(App\Mode::LOCALCONFIGPRESENT) && !$this->mode->has(App\Mode::DBAVAILABLE)) {
				throw new HTTPException\InternalServerErrorException('Apologies but the website is unavailable at the moment.');
			}

			// Max Load Average reached: ERROR
			if ($this->process->isMaxProcessesReached() || $this->process->isMaxLoadReached()) {
				header('Retry-After: 120');
				header('Refresh: 120; url=' . $this->baseURL->get() . "/" . $this->args->getQueryString());

				throw new HTTPException\ServiceUnavailableException('The node is currently overloaded. Please try again later.');
			}

			if (!$this->mode->isInstall()) {
				// Force SSL redirection
				if ($this->baseURL->checkRedirectHttps()) {
					System::externalRedirect($this->baseURL->get() . '/' . $this->args->getQueryString());
				}

				Core\Hook::callAll('init_1');
			}

			// Exclude the backend processes from the session management
			if ($this->mode->isBackend()) {
				Core\Worker::executeIfIdle();
			}

			if ($this->mode->isNormal()) {
				$requester = HTTPSignature::getSigner('', $_SERVER);
				if (!empty($requester)) {
					Profile::addVisitorCookieForHandle($requester);
				}
			}

			// ZRL
			if (!empty($_GET['zrl']) && $this->mode->isNormal()) {
				if (!local_user()) {
					// Only continue when the given profile link seems valid
					// Valid profile links contain a path with "/profile/" and no query parameters
					if ((parse_url($_GET['zrl'], PHP_URL_QUERY) == "") &&
					    strstr(parse_url($_GET['zrl'], PHP_URL_PATH), "/profile/")) {
						if (Core\Session::get('visitor_home') != $_GET["zrl"]) {
							Core\Session::set('my_url', $_GET['zrl']);
							Core\Session::set('authenticated', 0);
						}

						Model\Profile::zrlInit($this);
					} else {
						// Someone came with an invalid parameter, maybe as a DDoS attempt
						// We simply stop processing here
						$this->logger->debug('Invalid ZRL parameter.', ['zrl' => $_GET['zrl']]);
						throw new HTTPException\ForbiddenException();
					}
				}
			}

			if (!empty($_GET['owt']) && $this->mode->isNormal()) {
				$token = $_GET['owt'];
				Model\Profile::openWebAuthInit($token);
			}

			$auth->withSession($this);

			if (empty($_SESSION['authenticated'])) {
				header('X-Account-Management-Status: none');
			}

			$_SESSION['sysmsg']       = Core\Session::get('sysmsg', []);
			$_SESSION['sysmsg_info']  = Core\Session::get('sysmsg_info', []);
			$_SESSION['last_updated'] = Core\Session::get('last_updated', []);

			/*
			 * check_config() is responsible for running update scripts. These automatically
			 * update the DB schema whenever we push a new one out. It also checks to see if
			 * any addons have been added or removed and reacts accordingly.
			 */

			// in install mode, any url loads install module
			// but we need "view" module for stylesheet
			if ($this->mode->isInstall() && $moduleName !== 'install') {
				$this->baseURL->redirect('install');
			} elseif (!$this->mode->isInstall() && !$this->mode->has(App\Mode::MAINTENANCEDISABLED) && $moduleName !== 'maintenance') {
				$this->baseURL->redirect('maintenance');
			} else {
				$this->checkURL();
				Core\Update::check($this->getBasePath(), false, $this->mode);
				Core\Addon::loadAddons();
				Core\Hook::loadHooks();
			}

			// Compatibility with the Android Diaspora client
			if ($moduleName == 'stream') {
				$this->baseURL->redirect('network?order=post');
			}

			if ($moduleName == 'conversations') {
				$this->baseURL->redirect('message');
			}

			if ($moduleName == 'commented') {
				$this->baseURL->redirect('network?order=comment');
			}

			if ($moduleName == 'liked') {
				$this->baseURL->redirect('network?order=comment');
			}

			if ($moduleName == 'activity') {
				$this->baseURL->redirect('network?conv=1');
			}

			if (($moduleName == 'status_messages') && ($this->args->getCommand() == 'status_messages/new')) {
				$this->baseURL->redirect('bookmarklet');
			}

			if (($moduleName == 'user') && ($this->args->getCommand() == 'user/edit')) {
				$this->baseURL->redirect('settings');
			}

			if (($moduleName == 'tag_followings') && ($this->args->getCommand() == 'tag_followings/manage')) {
				$this->baseURL->redirect('search');
			}

			// Initialize module that can set the current theme in the init() method, either directly or via App->profile_uid
			$this->page['page_title'] = $moduleName;

			// determine the module class and save it to the module instance
			// @todo there's an implicit dependency due SESSION::start(), so it has to be called here (yet)
			$module = $module->determineClass($this->args, $router, $this->config);

			// Let the module run it's internal process (init, get, post, ...)
			$module->run($this->l10n, $this->baseURL, $this->logger, $_SERVER, $_POST);
		} catch (HTTPException $e) {
			ModuleHTTPException::rawContent($e);
		}

		$this->page->run($this, $this->baseURL, $this->mode, $module, $this->l10n, $this->config, $pconfig);
	}

	/**
	 * Automatically redirects to relative or absolute URL
	 * Should only be used if it isn't clear if the URL is either internal or external
	 *
	 * @param string $toUrl The target URL
	 *
	 * @throws HTTPException\InternalServerErrorException
	 */
	public function redirect($toUrl)
	{
		if (!empty(parse_url($toUrl, PHP_URL_SCHEME))) {
			Core\System::externalRedirect($toUrl);
		} else {
			$this->baseURL->redirect($toUrl);
		}
	}
}
