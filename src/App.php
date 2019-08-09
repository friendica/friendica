<?php
/**
 * @file src/App.php
 */
namespace Friendica;

use Detection\MobileDetect;
use DOMDocument;
use DOMXPath;
use Exception;
use Friendica\App\Arguments;
use Friendica\App\Module;
use Friendica\Core\Config\Cache\ConfigCache;
use Friendica\Core\Config\Configuration;
use Friendica\Core\Config\PConfiguration;
use Friendica\Core\L10n\L10n;
use Friendica\Core\System;
use Friendica\Core\Theme;
use Friendica\Database\Database;
use Friendica\Database\DBA;
use Friendica\Model\Profile;
use Friendica\Module\Login;
use Friendica\Module\Special\HTTPException as ModuleHTTPException;
use Friendica\Network\HTTPException;
use Friendica\Util\BaseURL;
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
	/** @deprecated 2019.09 - use App\Arguments->getQueryString() */
	public $query_string = '';
	public $page = [];
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

	public $stylesheets = [];
	public $footerScripts = [];

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
	 * @var bool true, if the call is from an backend node (f.e. worker)
	 *
	 * @deprecated 2019.09 - use App\Module->isBackend() instead
	 */
	private $isBackend;

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
	 * @var App\Module
	 */
	private $moduleClass;

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
	 * Register a stylesheet file path to be included in the <head> tag of every page.
	 * Inclusion is done in App->initHead().
	 * The path can be absolute or relative to the Friendica installation base folder.
	 *
	 * @see initHead()
	 *
	 * @param string $path
	 */
	public function registerStylesheet($path)
	{
		if (mb_strpos($path, $this->getBasePath() . DIRECTORY_SEPARATOR) === 0) {
			$path = mb_substr($path, mb_strlen($this->getBasePath() . DIRECTORY_SEPARATOR));
		}

		$this->stylesheets[] = trim($path, '/');
	}

	/**
	 * Register a javascript file path to be included in the <footer> tag of every page.
	 * Inclusion is done in App->initFooter().
	 * The path can be absolute or relative to the Friendica installation base folder.
	 *
	 * @see initFooter()
	 *
	 * @param string $path
	 */
	public function registerFooterScript($path)
	{
		$url = str_replace($this->getBasePath() . DIRECTORY_SEPARATOR, '', $path);

		$this->footerScripts[] = trim($url, '/');
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
	public function __construct(Database $database, Configuration $config, App\Mode $mode, App\Router $router, BaseURL $baseURL, LoggerInterface $logger, Profiler $profiler, L10n $l10n, Arguments $args, Module $module)
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
		$this->isBackend = $module->isBackend();

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
		if ($this->getMode()->has(App\Mode::DBAVAILABLE)) {
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
	 * Returns the scheme of the current call
	 * @return string
	 *
	 * @deprecated 2019.06 - use BaseURL->getScheme() instead
	 */
	public function getScheme()
	{
		return $this->baseURL->getScheme();
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
	 * Initializes App->page['htmlhead'].
	 *
	 * Includes:
	 * - Page title
	 * - Favicons
	 * - Registered stylesheets (through App->registerStylesheet())
	 * - Infinite scroll data
	 * - head.tpl template
	 */
	private function initHead(App\Module $module, PConfiguration $pconfig)
	{
		$interval = ((local_user()) ? $pconfig->get(local_user(), 'system', 'update_interval') : 40000);

		// If the update is 'deactivated' set it to the highest integer number (~24 days)
		if ($interval < 0) {
			$interval = 2147483647;
		}

		if ($interval < 10000) {
			$interval = 40000;
		}

		// Default title: current module called
		if (empty($this->page['title']) && $module->getName()) {
			$this->page['title'] = ucfirst($module->getName());
		}

		// Prepend the sitename to the page title
		$this->page['title'] = $this->config->get('config', 'sitename', '') . (!empty($this->page['title']) ? ' | ' . $this->page['title'] : '');

		if (!empty(Core\Renderer::$theme['stylesheet'])) {
			$stylesheet = Core\Renderer::$theme['stylesheet'];
		} else {
			$stylesheet = $this->getCurrentThemeStylesheetPath();
		}

		$this->registerStylesheet($stylesheet);

		$shortcut_icon = $this->config->get('system', 'shortcut_icon');
		if ($shortcut_icon == '') {
			$shortcut_icon = 'images/friendica-32.png';
		}

		$touch_icon = $this->config->get('system', 'touch_icon');
		if ($touch_icon == '') {
			$touch_icon = 'images/friendica-128.png';
		}

		Core\Hook::callAll('head', $this->page['htmlhead']);

		$tpl = Core\Renderer::getMarkupTemplate('head.tpl');
		/* put the head template at the beginning of page['htmlhead']
		 * since the code added by the modules frequently depends on it
		 * being first
		 */
		$this->page['htmlhead'] = Core\Renderer::replaceMacros($tpl, [
			'$local_user'      => local_user(),
			'$generator'       => 'Friendica' . ' ' . FRIENDICA_VERSION,
			'$delitem'         => $this->l10n->t('Delete this item?'),
			'$update_interval' => $interval,
			'$shortcut_icon'   => $shortcut_icon,
			'$touch_icon'      => $touch_icon,
			'$block_public'    => intval($this->config->get('system', 'block_public')),
			'$stylesheets'     => $this->stylesheets,
		]) . $this->page['htmlhead'];
	}

	/**
	 * Initializes App->page['footer'].
	 *
	 * Includes:
	 * - Javascript homebase
	 * - Mobile toggle link
	 * - Registered footer scripts (through App->registerFooterScript())
	 * - footer.tpl template
	 */
	private function initFooter()
	{
		// If you're just visiting, let javascript take you home
		if (!empty($_SESSION['visitor_home'])) {
			$homebase = $_SESSION['visitor_home'];
		} elseif (local_user()) {
			$homebase = 'profile/' . $this->user['nickname'];
		}

		if (isset($homebase)) {
			$this->page['footer'] .= '<script>var homebase="' . $homebase . '";</script>' . "\n";
		}

		/*
		 * Add a "toggle mobile" link if we're using a mobile device
		 */
		if ($this->is_mobile || $this->is_tablet) {
			if (isset($_SESSION['show-mobile']) && !$_SESSION['show-mobile']) {
				$link = 'toggle_mobile?address=' . urlencode(curPageURL());
			} else {
				$link = 'toggle_mobile?off=1&address=' . urlencode(curPageURL());
			}
			$this->page['footer'] .= Core\Renderer::replaceMacros(Core\Renderer::getMarkupTemplate("toggle_mobile_footer.tpl"), [
				'$toggle_link' => $link,
				'$toggle_text' => $this->l10n->t('toggle mobile')
			]);
		}

		Core\Hook::callAll('footer', $this->page['footer']);

		$tpl = Core\Renderer::getMarkupTemplate('footer.tpl');
		$this->page['footer'] = Core\Renderer::replaceMacros($tpl, [
			'$footerScripts' => $this->footerScripts,
		]) . $this->page['footer'];
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
	 * @brief Checks if the maximum number of database processes is reached
	 *
	 * @return bool Is the limit reached?
	 */
	public function isMaxProcessesReached()
	{
		// Deactivated, needs more investigating if this check really makes sense
		return false;

		/*
		 * Commented out to suppress static analyzer issues
		 *
		if ($this->is_backend()) {
			$process = 'backend';
			$max_processes = $this->config->get('system', 'max_processes_backend');
			if (intval($max_processes) == 0) {
				$max_processes = 5;
			}
		} else {
			$process = 'frontend';
			$max_processes = $this->config->get('system', 'max_processes_frontend');
			if (intval($max_processes) == 0) {
				$max_processes = 20;
			}
		}

		$processlist = DBA::processlist();
		if ($processlist['list'] != '') {
			Core\Logger::log('Processcheck: Processes: ' . $processlist['amount'] . ' - Processlist: ' . $processlist['list'], Core\Logger::DEBUG);

			if ($processlist['amount'] > $max_processes) {
				Core\Logger::log('Processcheck: Maximum number of processes for ' . $process . ' tasks (' . $max_processes . ') reached.', Core\Logger::DEBUG);
				return true;
			}
		}
		return false;
		 */
	}

	/**
	 * @brief Checks if the minimal memory is reached
	 *
	 * @return bool Is the memory limit reached?
	 * @throws HTTPException\InternalServerErrorException
	 */
	public function isMinMemoryReached()
	{
		$min_memory = $this->config->get('system', 'min_memory', 0);
		if ($min_memory == 0) {
			return false;
		}

		if (!is_readable('/proc/meminfo')) {
			return false;
		}

		$memdata = explode("\n", file_get_contents('/proc/meminfo'));

		$meminfo = [];
		foreach ($memdata as $line) {
			$data = explode(':', $line);
			if (count($data) != 2) {
				continue;
			}
			list($key, $val) = $data;
			$meminfo[$key] = (int) trim(str_replace('kB', '', $val));
			$meminfo[$key] = (int) ($meminfo[$key] / 1024);
		}

		if (!isset($meminfo['MemFree'])) {
			return false;
		}

		$free = $meminfo['MemFree'];

		$reached = ($free < $min_memory);

		if ($reached) {
			Core\Logger::log('Minimal memory reached: ' . $free . '/' . $meminfo['MemTotal'] . ' - limit ' . $min_memory, Core\Logger::DEBUG);
		}

		return $reached;
	}

	/**
	 * @brief Checks if the maximum load is reached
	 *
	 * @return bool Is the load reached?
	 * @throws HTTPException\InternalServerErrorException
	 */
	public function isMaxLoadReached()
	{
		if ($this->isBackend) {
			$process = 'backend';
			$maxsysload = intval($this->config->get('system', 'maxloadavg'));
			if ($maxsysload < 1) {
				$maxsysload = 50;
			}
		} else {
			$process = 'frontend';
			$maxsysload = intval($this->config->get('system', 'maxloadavg_frontend'));
			if ($maxsysload < 1) {
				$maxsysload = 50;
			}
		}

		$load = Core\System::currentLoad();
		if ($load) {
			if (intval($load) > $maxsysload) {
				Core\Logger::log('system: load ' . $load . ' for ' . $process . ' tasks (' . $maxsysload . ') too high.');
				return true;
			}
		}
		return false;
	}

	/**
	 * Executes a child process with 'proc_open'
	 *
	 * @param string $command The command to execute
	 * @param array  $args    Arguments to pass to the command ( [ 'key' => value, 'key2' => value2, ... ]
	 * @throws HTTPException\InternalServerErrorException
	 */
	public function proc_run($command, $args)
	{
		if (!function_exists('proc_open')) {
			return;
		}

		$cmdline = $this->config->get('config', 'php_path', 'php') . ' ' . escapeshellarg($command);

		foreach ($args as $key => $value) {
			if (!is_null($value) && is_bool($value) && !$value) {
				continue;
			}

			$cmdline .= ' --' . $key;
			if (!is_null($value) && !is_bool($value)) {
				$cmdline .= ' ' . $value;
			}
		}

		if ($this->isMinMemoryReached()) {
			return;
		}

		if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
			$resource = proc_open('cmd /c start /b ' . $cmdline, [], $foo, $this->getBasePath());
		} else {
			$resource = proc_open($cmdline . ' &', [], $foo, $this->getBasePath());
		}
		if (!is_resource($resource)) {
			Core\Logger::log('We got no resource for command ' . $cmdline, Core\Logger::DEBUG);
			return;
		}
		proc_close($resource);
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

		if (empty($url) || (!Util\Strings::compareLink($url, $this->getBaseURL())) && (!preg_match("/^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})$/", $this->baseURL->getHostname()))) {
			$this->config->set('system', 'url', $this->getBaseURL());
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
	 * @param App\Module $module The determined module
	 */
	public function runFrontend(App\Module $module, App\Router $router, PConfiguration $pconfig)
	{
		$moduleName = $module->getName();

		try {
			// Missing DB connection: ERROR
			if ($this->getMode()->has(App\Mode::LOCALCONFIGPRESENT) && !$this->getMode()->has(App\Mode::DBAVAILABLE)) {
				throw new HTTPException\InternalServerErrorException('Apologies but the website is unavailable at the moment.');
			}

			// Max Load Average reached: ERROR
			if ($this->isMaxProcessesReached() || $this->isMaxLoadReached()) {
				header('Retry-After: 120');
				header('Refresh: 120; url=' . $this->baseURL->get() . "/" . $this->args->getQueryString());

				throw new HTTPException\ServiceUnavailableException('The node is currently overloaded. Please try again later.');
			}

			if (!$this->getMode()->isInstall()) {
				// Force SSL redirection
				if ($this->baseURL->checkRedirectHttps()) {
					System::externalRedirect($this->baseURL->get() . '/' . $this->args->getQueryString());
				}

				Core\Session::init();
				Core\Hook::callAll('init_1');
			}

			// Exclude the backend processes from the session management
			if (!$module->isBackend()) {
				Core\Session::start();
			} else {
				$_SESSION = [];
				Core\Worker::executeIfIdle();
			}

			if ($this->getMode()->isNormal()) {
				$requester = HTTPSignature::getSigner('', $_SERVER);
				if (!empty($requester)) {
					Profile::addVisitorCookieForHandle($requester);
				}
			}

			// ZRL
			if (!empty($_GET['zrl']) && $this->getMode()->isNormal()) {
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
						Core\Logger::log("Invalid ZRL parameter " . $_GET['zrl'], Core\Logger::DEBUG);
						throw new HTTPException\ForbiddenException();
					}
				}
			}

			if (!empty($_GET['owt']) && $this->getMode()->isNormal()) {
				$token = $_GET['owt'];
				Model\Profile::openWebAuthInit($token);
			}

			Login::sessionAuth();

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
			if ($this->getMode()->isInstall() && $moduleName !== 'install') {
				$this->internalRedirect('install');
			} elseif (!$this->getMode()->isInstall() && !$this->getMode()->has(App\Mode::MAINTENANCEDISABLED) && $moduleName !== 'maintenance') {
				$this->internalRedirect('maintenance');
			} else {
				$this->checkURL();
				Core\Update::check($this->getBasePath(), false, $this->getMode());
				Core\Addon::loadAddons();
				Core\Hook::loadHooks();
			}

			$this->page = [
				'aside' => '',
				'bottom' => '',
				'content' => '',
				'footer' => '',
				'htmlhead' => '',
				'nav' => '',
				'page_title' => '',
				'right_aside' => '',
				'template' => '',
				'title' => ''
			];

			// Compatibility with the Android Diaspora client
			if ($moduleName == 'stream') {
				$this->internalRedirect('network?order=post');
			}

			if ($moduleName == 'conversations') {
				$this->internalRedirect('message');
			}

			if ($moduleName == 'commented') {
				$this->internalRedirect('network?order=comment');
			}

			if ($moduleName == 'liked') {
				$this->internalRedirect('network?order=comment');
			}

			if ($moduleName == 'activity') {
				$this->internalRedirect('network?conv=1');
			}

			if (($moduleName == 'status_messages') && ($this->args->getCommand() == 'status_messages/new')) {
				$this->internalRedirect('bookmarklet');
			}

			if (($moduleName == 'user') && ($this->args->getCommand() == 'user/edit')) {
				$this->internalRedirect('settings');
			}

			if (($moduleName == 'tag_followings') && ($this->args->getCommand() == 'tag_followings/manage')) {
				$this->internalRedirect('search');
			}

			// Initialize module that can set the current theme in the init() method, either directly or via App->profile_uid
			$this->page['page_title'] = $moduleName;

			// determine the module class and save it to the module instance
			// @todo there's an implicit dependency due SESSION::start(), so it has to be called here (yet)
			$module = $module->determineClass($this->args, $router, $this->config);

			// Let the module run it's internal process (init, get, post, ...)
			$module->run($this->l10n, $this, $this->logger, $this->getCurrentTheme(), $_SERVER, $_POST);

		} catch(HTTPException $e) {
			ModuleHTTPException::rawContent($e);
		}

		$content = '';

		try {
			$moduleClass = $module->getClassName();

			$arr = ['content' => $content];
			Core\Hook::callAll($moduleClass . '_mod_content', $arr);
			$content = $arr['content'];
			$arr = ['content' => call_user_func([$moduleClass, 'content'])];
			Core\Hook::callAll($moduleClass . '_mod_aftercontent', $arr);
			$content .= $arr['content'];
		} catch(HTTPException $e) {
			$content = ModuleHTTPException::content($e);
		}

		// initialise content region
		if ($this->getMode()->isNormal()) {
			Core\Hook::callAll('page_content_top', $this->page['content']);
		}

		$this->page['content'] .= $content;

		/* Create the page head after setting the language
		 * and getting any auth credentials.
		 *
		 * Moved initHead() and initFooter() to after
		 * all the module functions have executed so that all
		 * theme choices made by the modules can take effect.
		 */
		$this->initHead($module, $pconfig);

		/* Build the page ending -- this is stuff that goes right before
		 * the closing </body> tag
		 */
		$this->initFooter();

		if (!$this->isAjax()) {
			Core\Hook::callAll('page_end', $this->page['content']);
		}

		// Add the navigation (menu) template
		if ($moduleName != 'install' && $moduleName != 'maintenance') {
			$this->page['htmlhead'] .= Core\Renderer::replaceMacros(Core\Renderer::getMarkupTemplate('nav_head.tpl'), []);
			$this->page['nav']       = Content\Nav::build($this);
		}

		// Build the page - now that we have all the components
		if (isset($_GET["mode"]) && (($_GET["mode"] == "raw") || ($_GET["mode"] == "minimal"))) {
			$doc = new DOMDocument();

			$target = new DOMDocument();
			$target->loadXML("<root></root>");

			$content = mb_convert_encoding($this->page["content"], 'HTML-ENTITIES', "UTF-8");

			/// @TODO one day, kill those error-surpressing @ stuff, or PHP should ban it
			@$doc->loadHTML($content);

			$xpath = new DOMXPath($doc);

			$list = $xpath->query("//*[contains(@id,'tread-wrapper-')]");  /* */

			foreach ($list as $item) {
				$item = $target->importNode($item, true);

				// And then append it to the target
				$target->documentElement->appendChild($item);
			}

			if ($_GET["mode"] == "raw") {
				header("Content-type: text/html; charset=utf-8");

				echo substr($target->saveHTML(), 6, -8);

				exit();
			}
		}

		$page    = $this->page;
		$profile = $this->profile;

		header("X-Friendica-Version: " . FRIENDICA_VERSION);
		header("Content-type: text/html; charset=utf-8");

		if ($this->config->get('system', 'hsts') && ($this->baseURL->getSSLPolicy() == BaseUrl::SSL_POLICY_FULL)) {
			header("Strict-Transport-Security: max-age=31536000");
		}

		// Some security stuff
		header('X-Content-Type-Options: nosniff');
		header('X-XSS-Protection: 1; mode=block');
		header('X-Permitted-Cross-Domain-Policies: none');
		header('X-Frame-Options: sameorigin');

		// Things like embedded OSM maps don't work, when this is enabled
		// header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; connect-src 'self'; style-src 'self' 'unsafe-inline'; font-src 'self'; img-src 'self' https: data:; media-src 'self' https:; child-src 'self' https:; object-src 'none'");

		/* We use $_GET["mode"] for special page templates. So we will check if we have
		 * to load another page template than the default one.
		 * The page templates are located in /view/php/ or in the theme directory.
		 */
		if (isset($_GET["mode"])) {
			$template = Core\Theme::getPathForFile($_GET["mode"] . '.php');
		}

		// If there is no page template use the default page template
		if (empty($template)) {
			$template = Core\Theme::getPathForFile("default.php");
		}

		// Theme templates expect $a as an App instance
		$a = $this;

		// Used as is in view/php/default.php
		$lang = $this->l10n->getCurrentLang();

		/// @TODO Looks unsafe (remote-inclusion), is maybe not but Core\Theme::getPathForFile() uses file_exists() but does not escape anything
		require_once $template;
	}

	/**
	 * Redirects to another module relative to the current Friendica base.
	 * If you want to redirect to a external URL, use System::externalRedirectTo()
	 *
	 * @param string $toUrl The destination URL (Default is empty, which is the default page of the Friendica node)
	 * @param bool $ssl if true, base URL will try to get called with https:// (works just for relative paths)
	 *
	 * @throws HTTPException\InternalServerErrorException In Case the given URL is not relative to the Friendica node
	 */
	public function internalRedirect($toUrl = '', $ssl = false)
	{
		if (!empty(parse_url($toUrl, PHP_URL_SCHEME))) {
			throw new HTTPException\InternalServerErrorException("'$toUrl is not a relative path, please use System::externalRedirectTo");
		}

		$redirectTo = $this->getBaseURL($ssl) . '/' . ltrim($toUrl, '/');
		Core\System::externalRedirect($redirectTo);
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
