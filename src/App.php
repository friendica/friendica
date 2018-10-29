<?php
/**
 * @file src/App.php
 */
namespace Friendica;

use Detection\MobileDetect;
use DOMDocument;
use DOMXPath;
use Exception;
use Friendica\Database\DBA;
use Friendica\Network\HTTPException\InternalServerErrorException;

require_once 'boot.php';

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
	public $module_loaded = false;
	public $module_class = null;
	public $query_string = '';
	public $config = [];
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
	public $error = false;
	public $cmd = '';
	public $argv;
	public $argc;
	public $module;
	public $timezone;
	public $interactive = true;
	public $identities;
	public $is_mobile = false;
	public $is_tablet = false;
	public $performance = [];
	public $callstack = [];
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
	 * @var string The App base path
	 */
	private $basePath;

	/**
	 * @var string The App URL path
	 */
	private $urlPath;

	/**
	 * @var bool true, if the call is from the Friendica APP, otherwise false
	 */
	private $isFriendicaApp;

	/**
	 * @var bool true, if the call is from an backend node (f.e. worker)
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
		$url = str_replace($this->getBasePath() . DIRECTORY_SEPARATOR, '', $path);

		$this->stylesheets[] = trim($url, '/');
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

	/**
	 * @brief An array for all theme-controllable parameters
	 *
	 * Mostly unimplemented yet. Only options 'template_engine' and
	 * beyond are used.
	 */
	public $theme = [
		'sourcename' => '',
		'videowidth' => 425,
		'videoheight' => 350,
		'force_max_items' => 0,
		'stylesheet' => '',
		'template_engine' => 'smarty3',
	];

	/**
	 * @brief An array of registered template engines ('name'=>'class name')
	 */
	public $template_engines = [];

	/**
	 * @brief An array of instanced template engines ('name'=>'instance')
	 */
	public $template_engine_instance = [];
	public $process_id;
	public $queue;
	private $ldelim = [
		'internal' => '',
		'smarty3' => '{{'
	];
	private $rdelim = [
		'internal' => '',
		'smarty3' => '}}'
	];
	private $scheme;
	private $hostname;

	/**
	 * @brief App constructor.
	 *
	 * @param string $basePath  Path to the app base folder
	 * @param bool   $isBackend Whether it is used for backend or frontend (Default true=backend)
	 *
	 * @throws Exception if the Basepath is not usable
	 */
	public function __construct($basePath, $isBackend = true)
	{
		if (!static::isDirectoryUsable($basePath, false)) {
			throw new Exception('Basepath ' . $basePath . ' isn\'t usable.');
		}

		BaseObject::setApp($this);

		$this->basePath = rtrim($basePath, DIRECTORY_SEPARATOR);
		$this->checkBackend($isBackend);
		$this->checkFriendicaApp();

		$this->performance['start'] = microtime(true);
		$this->performance['database'] = 0;
		$this->performance['database_write'] = 0;
		$this->performance['cache'] = 0;
		$this->performance['cache_write'] = 0;
		$this->performance['network'] = 0;
		$this->performance['file'] = 0;
		$this->performance['rendering'] = 0;
		$this->performance['parser'] = 0;
		$this->performance['marktime'] = 0;
		$this->performance['markstart'] = microtime(true);

		$this->callstack['database'] = [];
		$this->callstack['database_write'] = [];
		$this->callstack['cache'] = [];
		$this->callstack['cache_write'] = [];
		$this->callstack['network'] = [];
		$this->callstack['file'] = [];
		$this->callstack['rendering'] = [];
		$this->callstack['parser'] = [];

		$this->mode = new App\Mode($basePath);

		$this->reload();

		set_time_limit(0);

		// This has to be quite large to deal with embedded private photos
		ini_set('pcre.backtrack_limit', 500000);

		$this->scheme = 'http';

		if (!empty($_SERVER['HTTPS']) ||
			!empty($_SERVER['HTTP_FORWARDED']) && preg_match('/proto=https/', $_SERVER['HTTP_FORWARDED']) ||
			!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https' ||
			!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] == 'on' ||
			!empty($_SERVER['FRONT_END_HTTPS']) && $_SERVER['FRONT_END_HTTPS'] == 'on' ||
			!empty($_SERVER['SERVER_PORT']) && (intval($_SERVER['SERVER_PORT']) == 443) // XXX: reasonable assumption, but isn't this hardcoding too much?
		) {
			$this->scheme = 'https';
		}

		if (!empty($_SERVER['SERVER_NAME'])) {
			$this->hostname = $_SERVER['SERVER_NAME'];

			if (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] != 80 && $_SERVER['SERVER_PORT'] != 443) {
				$this->hostname .= ':' . $_SERVER['SERVER_PORT'];
			}
		}

		set_include_path(
			get_include_path() . PATH_SEPARATOR
			. $this->getBasePath() . DIRECTORY_SEPARATOR . 'include' . PATH_SEPARATOR
			. $this->getBasePath(). DIRECTORY_SEPARATOR . 'library' . PATH_SEPARATOR
			. $this->getBasePath());

		if (!empty($_SERVER['QUERY_STRING']) && strpos($_SERVER['QUERY_STRING'], 'pagename=') === 0) {
			$this->query_string = substr($_SERVER['QUERY_STRING'], 9);
		} elseif (!empty($_SERVER['QUERY_STRING']) && strpos($_SERVER['QUERY_STRING'], 'q=') === 0) {
			$this->query_string = substr($_SERVER['QUERY_STRING'], 2);
		}

		// removing trailing / - maybe a nginx problem
		$this->query_string = ltrim($this->query_string, '/');

		if (!empty($_GET['pagename'])) {
			$this->cmd = trim($_GET['pagename'], '/\\');
		} elseif (!empty($_GET['q'])) {
			$this->cmd = trim($_GET['q'], '/\\');
		}

		// fix query_string
		$this->query_string = str_replace($this->cmd . '&', $this->cmd . '?', $this->query_string);

		// unix style "homedir"
		if (substr($this->cmd, 0, 1) === '~') {
			$this->cmd = 'profile/' . substr($this->cmd, 1);
		}

		// Diaspora style profile url
		if (substr($this->cmd, 0, 2) === 'u/') {
			$this->cmd = 'profile/' . substr($this->cmd, 2);
		}

		/*
		 * Break the URL path into C style argc/argv style arguments for our
		 * modules. Given "http://example.com/module/arg1/arg2", $this->argc
		 * will be 3 (integer) and $this->argv will contain:
		 *   [0] => 'module'
		 *   [1] => 'arg1'
		 *   [2] => 'arg2'
		 *
		 *
		 * There will always be one argument. If provided a naked domain
		 * URL, $this->argv[0] is set to "home".
		 */

		$this->argv = explode('/', $this->cmd);
		$this->argc = count($this->argv);
		if ((array_key_exists('0', $this->argv)) && strlen($this->argv[0])) {
			$this->module = str_replace('.', '_', $this->argv[0]);
			$this->module = str_replace('-', '_', $this->module);
		} else {
			$this->argc = 1;
			$this->argv = ['home'];
			$this->module = 'home';
		}

		// Detect mobile devices
		$mobile_detect = new MobileDetect();
		$this->is_mobile = $mobile_detect->isMobile();
		$this->is_tablet = $mobile_detect->isTablet();

		$this->isAjax = strtolower(defaults($_SERVER, 'HTTP_X_REQUESTED_WITH', '')) == 'xmlhttprequest';

		// Register template engines
		$this->registerTemplateEngine('Friendica\Render\FriendicaSmartyEngine');
	}

	/**
	 * Returns the Mode of the Application
	 *
	 * @return App\Mode The Application Mode
	 *
	 * @throws InternalServerErrorException when the mode isn't created
	 */
	public function getMode()
	{
		if (empty($this->mode)) {
			throw new InternalServerErrorException('Mode of the Application is not defined');
		}

		return $this->mode;
	}

	/**
	 * Reloads the whole app instance
	 */
	public function reload()
	{
		// The order of the following calls is important to ensure proper initialization
		$this->loadConfigFiles();

		$this->loadDatabase();

		$this->getMode()->determine($this->getBasePath());

		$this->determineURLPath();

		Core\Config::load();

		if ($this->getMode()->has(App\Mode::DBAVAILABLE)) {
			Core\Hook::loadHooks();

			$this->loadAddonConfig();
		}

		$this->loadDefaultTimezone();

		Core\L10n::init();

		$this->process_id = Core\System::processID('log');
	}

	/**
	 * Load the configuration files
	 *
	 * First loads the default value for all the configuration keys, then the legacy configuration files, then the
	 * expected local.ini.php
	 */
	private function loadConfigFiles()
	{
		$this->loadConfigFile($this->getBasePath() . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.ini.php');
		$this->loadConfigFile($this->getBasePath() . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'settings.ini.php');

		// Legacy .htconfig.php support
		if (file_exists($this->getBasePath() . DIRECTORY_SEPARATOR . '.htpreconfig.php')) {
			$a = $this;
			include $this->getBasePath() . DIRECTORY_SEPARATOR . '.htpreconfig.php';
		}

		// Legacy .htconfig.php support
		if (file_exists($this->getBasePath() . DIRECTORY_SEPARATOR . '.htconfig.php')) {
			$a = $this;

			include $this->getBasePath() . DIRECTORY_SEPARATOR . '.htconfig.php';

			$this->setConfigValue('database', 'hostname', $db_host);
			$this->setConfigValue('database', 'username', $db_user);
			$this->setConfigValue('database', 'password', $db_pass);
			$this->setConfigValue('database', 'database', $db_data);
			if (isset($a->config['system']['db_charset'])) {
				$this->setConfigValue('database', 'charset', $a->config['system']['db_charset']);
			}

			unset($db_host, $db_user, $db_pass, $db_data);

			if (isset($default_timezone)) {
				$this->setConfigValue('system', 'default_timezone', $default_timezone);
				unset($default_timezone);
			}

			if (isset($pidfile)) {
				$this->setConfigValue('system', 'pidfile', $pidfile);
				unset($pidfile);
			}

			if (isset($lang)) {
				$this->setConfigValue('system', 'language', $lang);
				unset($lang);
			}
		}

		if (file_exists($this->getBasePath() . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'local.ini.php')) {
			$this->loadConfigFile($this->getBasePath() . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'local.ini.php', true);
		}
	}

	/**
	 * Tries to load the specified configuration file into the App->config array.
	 * Doesn't overwrite previously set values by default to prevent default config files to supersede DB Config.
	 *
	 * The config format is INI and the template for configuration files is the following:
	 *
	 * <?php return <<<INI
	 *
	 * [section]
	 * key = value
	 *
	 * INI;
	 * // Keep this line
	 *
	 * @param string $filepath
	 * @param bool $overwrite Force value overwrite if the config key already exists
	 * @throws Exception
	 */
	public function loadConfigFile($filepath, $overwrite = false)
	{
		if (!file_exists($filepath)) {
			throw new Exception('Error parsing non-existent config file ' . $filepath);
		}

		$contents = include($filepath);

		$config = parse_ini_string($contents, true, INI_SCANNER_TYPED);

		if ($config === false) {
			throw new Exception('Error parsing config file ' . $filepath);
		}

		foreach ($config as $category => $values) {
			foreach ($values as $key => $value) {
				if ($overwrite) {
					$this->setConfigValue($category, $key, $value);
				} else {
					$this->setDefaultConfigValue($category, $key, $value);
				}
			}
		}
	}

	/**
	 * Loads addons configuration files
	 *
	 * First loads all activated addons default configuration throught the load_config hook, then load the local.ini.php
	 * again to overwrite potential local addon configuration.
	 */
	private function loadAddonConfig()
	{
		// Loads addons default config
		Core\Addon::callHooks('load_config');

		// Load the local addon config file to overwritten default addon config values
		if (file_exists($this->getBasePath() . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'addon.ini.php')) {
			$this->loadConfigFile($this->getBasePath() . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'addon.ini.php', true);
		}
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
		if ($this->getConfigValue('system', 'default_timezone')) {
			$this->timezone = $this->getConfigValue('system', 'default_timezone');
		} else {
			global $default_timezone;
			$this->timezone = !empty($default_timezone) ? $default_timezone : 'UTC';
		}

		if ($this->timezone) {
			date_default_timezone_set($this->timezone);
		}
	}

	/**
	 * Figure out if we are running at the top of a domain or in a sub-directory and adjust accordingly
	 */
	private function determineURLPath()
	{
		/* Relative script path to the web server root
		 * Not all of those $_SERVER properties can be present, so we do by inverse priority order
		 */
		$relative_script_path = '';
		$relative_script_path = defaults($_SERVER, 'REDIRECT_URL'       , $relative_script_path);
		$relative_script_path = defaults($_SERVER, 'REDIRECT_URI'       , $relative_script_path);
		$relative_script_path = defaults($_SERVER, 'REDIRECT_SCRIPT_URL', $relative_script_path);
		$relative_script_path = defaults($_SERVER, 'SCRIPT_URL'         , $relative_script_path);

		$this->urlPath = $this->getConfigValue('system', 'urlpath');

		/* $relative_script_path gives /relative/path/to/friendica/module/parameter
		 * QUERY_STRING gives pagename=module/parameter
		 *
		 * To get /relative/path/to/friendica we perform dirname() for as many levels as there are slashes in the QUERY_STRING
		 */
		if (!empty($relative_script_path)) {
			// Module
			if (!empty($_SERVER['QUERY_STRING'])) {
				$path = trim(dirname($relative_script_path, substr_count(trim($_SERVER['QUERY_STRING'], '/'), '/') + 1), '/');
			} else {
				// Root page
				$path = trim($relative_script_path, '/');
			}

			if ($path && $path != $this->urlPath) {
				$this->urlPath = $path;
			}
		}
	}

	public function loadDatabase()
	{
		if (DBA::connected()) {
			return;
		}

		$db_host = $this->getConfigValue('database', 'hostname');
		$db_user = $this->getConfigValue('database', 'username');
		$db_pass = $this->getConfigValue('database', 'password');
		$db_data = $this->getConfigValue('database', 'database');
		$charset = $this->getConfigValue('database', 'charset');

		// Use environment variables for mysql if they are set beforehand
		if (!empty(getenv('MYSQL_HOST'))
			&& (!empty(getenv('MYSQL_USERNAME')) || !empty(getenv('MYSQL_USER')))
			&& getenv('MYSQL_PASSWORD') !== false
			&& !empty(getenv('MYSQL_DATABASE')))
		{
			$db_host = getenv('MYSQL_HOST');
			if (!empty(getenv('MYSQL_PORT'))) {
				$db_host .= ':' . getenv('MYSQL_PORT');
			}
			if (!empty(getenv('MYSQL_USERNAME'))) {
				$db_user = getenv('MYSQL_USERNAME');
			} else {
				$db_user = getenv('MYSQL_USER');
			}
			$db_pass = (string) getenv('MYSQL_PASSWORD');
			$db_data = getenv('MYSQL_DATABASE');
		}

		$stamp1 = microtime(true);

		if (DBA::connect($db_host, $db_user, $db_pass, $db_data, $charset)) {
			// Loads DB_UPDATE_VERSION constant
			Database\DBStructure::definition(false);
		}

		unset($db_host, $db_user, $db_pass, $db_data, $charset);

		$this->saveTimestamp($stamp1, 'network');
	}

	/**
	 * @brief Returns the base filesystem path of the App
	 *
	 * It first checks for the internal variable, then for DOCUMENT_ROOT and
	 * finally for PWD
	 *
	 * @return string
	 */
	public function getBasePath()
	{
		$basepath = $this->basePath;

		if (!$basepath) {
			$basepath = Core\Config::get('system', 'basepath');
		}

		if (!$basepath && !empty($_SERVER['DOCUMENT_ROOT'])) {
			$basepath = $_SERVER['DOCUMENT_ROOT'];
		}

		if (!$basepath && !empty($_SERVER['PWD'])) {
			$basepath = $_SERVER['PWD'];
		}

		return self::getRealPath($basepath);
	}

	/**
	 * @brief Returns a normalized file path
	 *
	 * This is a wrapper for the "realpath" function.
	 * That function cannot detect the real path when some folders aren't readable.
	 * Since this could happen with some hosters we need to handle this.
	 *
	 * @param string $path The path that is about to be normalized
	 * @return string normalized path - when possible
	 */
	public static function getRealPath($path)
	{
		$normalized = realpath($path);

		if (!is_bool($normalized)) {
			return $normalized;
		} else {
			return $path;
		}
	}

	public function getScheme()
	{
		return $this->scheme;
	}

	/**
	 * @brief Retrieves the Friendica instance base URL
	 *
	 * This function assembles the base URL from multiple parts:
	 * - Protocol is determined either by the request or a combination of
	 * system.ssl_policy and the $ssl parameter.
	 * - Host name is determined either by system.hostname or inferred from request
	 * - Path is inferred from SCRIPT_NAME
	 *
	 * Note: $ssl parameter value doesn't directly correlate with the resulting protocol
	 *
	 * @param bool $ssl Whether to append http or https under SSL_POLICY_SELFSIGN
	 * @return string Friendica server base URL
	 */
	public function getBaseURL($ssl = false)
	{
		$scheme = $this->scheme;

		if (Core\Config::get('system', 'ssl_policy') == SSL_POLICY_FULL) {
			$scheme = 'https';
		}

		//	Basically, we have $ssl = true on any links which can only be seen by a logged in user
		//	(and also the login link). Anything seen by an outsider will have it turned off.

		if (Core\Config::get('system', 'ssl_policy') == SSL_POLICY_SELFSIGN) {
			if ($ssl) {
				$scheme = 'https';
			} else {
				$scheme = 'http';
			}
		}

		if (Core\Config::get('config', 'hostname') != '') {
			$this->hostname = Core\Config::get('config', 'hostname');
		}

		return $scheme . '://' . $this->hostname . (!empty($this->getURLPath()) ? '/' . $this->getURLPath() : '' );
	}

	/**
	 * @brief Initializes the baseurl components
	 *
	 * Clears the baseurl cache to prevent inconsistencies
	 *
	 * @param string $url
	 */
	public function setBaseURL($url)
	{
		$parsed = @parse_url($url);
		$hostname = '';

		if (!empty($parsed)) {
			if (!empty($parsed['scheme'])) {
				$this->scheme = $parsed['scheme'];
			}

			if (!empty($parsed['host'])) {
				$hostname = $parsed['host'];
			}

			if (!empty($parsed['port'])) {
				$hostname .= ':' . $parsed['port'];
			}
			if (!empty($parsed['path'])) {
				$this->urlPath = trim($parsed['path'], '\\/');
			}

			if (file_exists($this->getBasePath() . DIRECTORY_SEPARATOR . '.htpreconfig.php')) {
				include $this->getBasePath() . DIRECTORY_SEPARATOR . '.htpreconfig.php';
			}

			if (Core\Config::get('config', 'hostname') != '') {
				$this->hostname = Core\Config::get('config', 'hostname');
			}

			if (!isset($this->hostname) || ($this->hostname == '')) {
				$this->hostname = $hostname;
			}
		}
	}

	public function getHostName()
	{
		if (Core\Config::get('config', 'hostname') != '') {
			$this->hostname = Core\Config::get('config', 'hostname');
		}

		return $this->hostname;
	}

	public function getURLPath()
	{
		return $this->urlPath;
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
	public function initHead()
	{
		$interval = ((local_user()) ? Core\PConfig::get(local_user(), 'system', 'update_interval') : 40000);

		// If the update is 'deactivated' set it to the highest integer number (~24 days)
		if ($interval < 0) {
			$interval = 2147483647;
		}

		if ($interval < 10000) {
			$interval = 40000;
		}

		// compose the page title from the sitename and the
		// current module called
		if (!$this->module == '') {
			$this->page['title'] = $this->config['sitename'] . ' (' . $this->module . ')';
		} else {
			$this->page['title'] = $this->config['sitename'];
		}

		if (!empty($this->theme['stylesheet'])) {
			$stylesheet = $this->theme['stylesheet'];
		} else {
			$stylesheet = $this->getCurrentThemeStylesheetPath();
		}

		$this->registerStylesheet($stylesheet);

		$shortcut_icon = Core\Config::get('system', 'shortcut_icon');
		if ($shortcut_icon == '') {
			$shortcut_icon = 'images/friendica-32.png';
		}

		$touch_icon = Core\Config::get('system', 'touch_icon');
		if ($touch_icon == '') {
			$touch_icon = 'images/friendica-128.png';
		}

		Core\Addon::callHooks('head', $this->page['htmlhead']);

		$tpl = Content\Text::getMarkupTemplate('head.tpl');
		/* put the head template at the beginning of page['htmlhead']
		 * since the code added by the modules frequently depends on it
		 * being first
		 */
		$this->page['htmlhead'] = $this->replaceMacros($tpl, [
			'$baseurl'         => $this->getBaseURL(),
			'$local_user'      => local_user(),
			'$generator'       => 'Friendica' . ' ' . FRIENDICA_VERSION,
			'$delitem'         => Core\L10n::t('Delete this item?'),
			'$showmore'        => Core\L10n::t('show more'),
			'$showfewer'       => Core\L10n::t('show fewer'),
			'$update_interval' => $interval,
			'$shortcut_icon'   => $shortcut_icon,
			'$touch_icon'      => $touch_icon,
			'$block_public'    => intval(Core\Config::get('system', 'block_public')),
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
	public function initFooter()
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
				$link = 'toggle_mobile?address=' . curPageURL();
			} else {
				$link = 'toggle_mobile?off=1&address=' . curPageURL();
			}
			$this->page['footer'] .= $this->replaceMacros(Content\Text::getMarkupTemplate("toggle_mobile_footer.tpl"), [
				'$toggle_link' => $link,
				'$toggle_text' => Core\L10n::t('toggle mobile')
			]);
		}

		Core\Addon::callHooks('footer', $this->page['footer']);

		$tpl = Content\Text::getMarkupTemplate('footer.tpl');
		$this->page['footer'] = $this->replaceMacros($tpl, [
			'$baseurl' => $this->getBaseURL(),
			'$footerScripts' => $this->footerScripts,
		]) . $this->page['footer'];
	}

	/**
	 * @brief Removes the base url from an url. This avoids some mixed content problems.
	 *
	 * @param string $origURL
	 *
	 * @return string The cleaned url
	 */
	public function removeBaseURL($origURL)
	{
		// Remove the hostname from the url if it is an internal link
		$nurl = Content\Text::normaliseLink($origURL);
		$base = Content\Text::normaliseLink($this->getBaseURL());
		$url = str_replace($base . '/', '', $nurl);

		// if it is an external link return the orignal value
		if ($url == Content\Text::normaliseLink($origURL)) {
			return $origURL;
		} else {
			return $url;
		}
	}

	/**
	 * @brief Register template engine class
	 *
	 * @param string $class
	 */
	private function registerTemplateEngine($class)
	{
		$v = get_class_vars($class);
		if (!empty($v['name'])) {
			$name = $v['name'];
			$this->template_engines[$name] = $class;
		} else {
			echo "template engine <tt>$class</tt> cannot be registered without a name.\n";
			die();
		}
	}

	/**
	 * @brief Return template engine instance.
	 *
	 * If $name is not defined, return engine defined by theme,
	 * or default
	 *
	 * @return object Template Engine instance
	 */
	public function getTemplateEngine()
	{
		$template_engine = defaults($this->theme, 'template_engine', 'smarty3');

		if (isset($this->template_engines[$template_engine])) {
			if (isset($this->template_engine_instance[$template_engine])) {
				return $this->template_engine_instance[$template_engine];
			} else {
				$class = $this->template_engines[$template_engine];
				$obj = new $class;
				$this->template_engine_instance[$template_engine] = $obj;
				return $obj;
			}
		}

		echo "template engine <tt>$template_engine</tt> is not registered!\n";
		exit();
	}

	/**
	 * @brief Returns the active template engine.
	 *
	 * @return string the active template engine
	 */
	public function getActiveTemplateEngine()
	{
		return $this->theme['template_engine'];
	}

	/**
	 * sets the active template engine
	 *
	 * @param string $engine the template engine (default is Smarty3)
	 */
	public function setActiveTemplateEngine($engine = 'smarty3')
	{
		$this->theme['template_engine'] = $engine;
	}

	/**
	 * Gets the right delimiter for a template engine
	 *
	 * Currently:
	 * Internal = ''
	 * Smarty3 = '{{'
	 *
	 * @param string $engine The template engine (default is Smarty3)
	 *
	 * @return string the right delimiter
	 */
	public function getTemplateLeftDelimiter($engine = 'smarty3')
	{
		return $this->ldelim[$engine];
	}

	/**
	 * Gets the left delimiter for a template engine
	 *
	 * Currently:
	 * Internal = ''
	 * Smarty3 = '}}'
	 *
	 * @param string $engine The template engine (default is Smarty3)
	 *
	 * @return string the left delimiter
	 */
	public function getTemplateRightDelimiter($engine = 'smarty3')
	{
		return $this->rdelim[$engine];
	}

	/**
	 * Saves a timestamp for a value - f.e. a call
	 * Necessary for profiling Friendica
	 *
	 * @param int $timestamp the Timestamp
	 * @param string $value A value to profile
	 */
	public function saveTimestamp($timestamp, $value)
	{
		if (!isset($this->config['system']['profiler']) || !$this->config['system']['profiler']) {
			return;
		}

		$duration = (float) (microtime(true) - $timestamp);

		if (!isset($this->performance[$value])) {
			// Prevent ugly E_NOTICE
			$this->performance[$value] = 0;
		}

		$this->performance[$value] += (float) $duration;
		$this->performance['marktime'] += (float) $duration;

		$callstack = Core\System::callstack();

		if (!isset($this->callstack[$value][$callstack])) {
			// Prevent ugly E_NOTICE
			$this->callstack[$value][$callstack] = 0;
		}

		$this->callstack[$value][$callstack] += (float) $duration;
	}

	/**
	 * Returns the current UserAgent as a String
	 *
	 * @return string the UserAgent as a String
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
	 * Checks, if the call is from the Friendica App
	 *
	 * Reason:
	 * The friendica client has problems with the GUID in the notify. this is some workaround
	 */
	private function checkFriendicaApp()
	{
		// Friendica-Client
		$this->isFriendicaApp = isset($_SERVER['HTTP_USER_AGENT']) && $_SERVER['HTTP_USER_AGENT'] == 'Apache-HttpClient/UNAVAILABLE (java 1.4)';
	}

	/**
	 * 	Is the call via the Friendica app? (not a "normale" call)
	 *
	 * @return bool true if it's from the Friendica app
	 */
	public function isFriendicaApp()
	{
		return $this->isFriendicaApp;
	}

	/**
	 * @brief Checks if the site is called via a backend process
	 *
	 * This isn't a perfect solution. But we need this check very early.
	 * So we cannot wait until the modules are loaded.
	 *
	 * @param string $backend true, if the backend flag was set during App initialization
	 *
	 */
	private function checkBackend($backend) {
		static $backends = [
			'_well_known',
			'api',
			'dfrn_notify',
			'fetch',
			'hcard',
			'hostxrd',
			'nodeinfo',
			'noscrape',
			'p',
			'poco',
			'post',
			'proxy',
			'pubsub',
			'pubsubhubbub',
			'receive',
			'rsd_xml',
			'salmon',
			'statistics_json',
			'xrd',
		];

		// Check if current module is in backend or backend flag is set
		$this->isBackend = (in_array($this->module, $backends) || $backend || $this->isBackend);
	}

	/**
	 * Returns true, if the call is from a backend node (f.e. from a worker)
	 *
	 * @return bool Is it a known backend?
	 */
	public function isBackend()
	{
		return $this->isBackend;
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
			$max_processes = Core\Config::get('system', 'max_processes_backend');
			if (intval($max_processes) == 0) {
				$max_processes = 5;
			}
		} else {
			$process = 'frontend';
			$max_processes = Core\Config::get('system', 'max_processes_frontend');
			if (intval($max_processes) == 0) {
				$max_processes = 20;
			}
		}

		$processlist = DBA::processlist();
		if ($processlist['list'] != '') {
			Content\Text::logger('Processcheck: Processes: ' . $processlist['amount'] . ' - Processlist: ' . $processlist['list'], LOGGER_DEBUG);

			if ($processlist['amount'] > $max_processes) {
				Content\Text::logger('Processcheck: Maximum number of processes for ' . $process . ' tasks (' . $max_processes . ') reached.', LOGGER_DEBUG);
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
	 */
	public function isMinMemoryReached()
	{
		$min_memory = Core\Config::get('system', 'min_memory', 0);
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

		if (!isset($meminfo['MemAvailable']) || !isset($meminfo['MemFree'])) {
			return false;
		}

		$free = $meminfo['MemAvailable'] + $meminfo['MemFree'];

		$reached = ($free < $min_memory);

		if ($reached) {
			Content\Text::logger('Minimal memory reached: ' . $free . '/' . $meminfo['MemTotal'] . ' - limit ' . $min_memory, LOGGER_DEBUG);
		}

		return $reached;
	}

	/**
	 * @brief Checks if the maximum load is reached
	 *
	 * @return bool Is the load reached?
	 */
	public function isMaxLoadReached()
	{
		if ($this->isBackend()) {
			$process = 'backend';
			$maxsysload = intval(Core\Config::get('system', 'maxloadavg'));
			if ($maxsysload < 1) {
				$maxsysload = 50;
			}
		} else {
			$process = 'frontend';
			$maxsysload = intval(Core\Config::get('system', 'maxloadavg_frontend'));
			if ($maxsysload < 1) {
				$maxsysload = 50;
			}
		}

		$load = Core\System::currentLoad();
		if ($load) {
			if (intval($load) > $maxsysload) {
				Content\Text::logger('system: load ' . $load . ' for ' . $process . ' tasks (' . $maxsysload . ') too high.');
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
	 */
	public function proc_run($command, $args)
	{
		if (!function_exists('proc_open')) {
			return;
		}

		$cmdline = $this->getConfigValue('config', 'php_path', 'php') . ' ' . escapeshellarg($command);

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
			Content\Text::logger('We got no resource for command ' . $cmdline, LOGGER_DEBUG);
			return;
		}
		proc_close($resource);
	}

	/**
	 * @brief Returns the system user that is executing the script
	 *
	 * This mostly returns something like "www-data".
	 *
	 * @return string system username
	 */
	private static function getSystemUser()
	{
		if (!function_exists('posix_getpwuid') || !function_exists('posix_geteuid')) {
			return '';
		}

		$processUser = posix_getpwuid(posix_geteuid());
		return $processUser['name'];
	}

	/**
	 * @brief Checks if a given directory is usable for the system
	 *
	 * @return boolean the directory is usable
	 */
	public static function isDirectoryUsable($directory, $check_writable = true)
	{
		if ($directory == '') {
			Content\Text::logger('Directory is empty. This shouldn\'t happen.', LOGGER_DEBUG);
			return false;
		}

		if (!file_exists($directory)) {
			Content\Text::logger('Path "' . $directory . '" does not exist for user ' . self::getSystemUser(), LOGGER_DEBUG);
			return false;
		}

		if (is_file($directory)) {
			Content\Text::logger('Path "' . $directory . '" is a file for user ' . self::getSystemUser(), LOGGER_DEBUG);
			return false;
		}

		if (!is_dir($directory)) {
			Content\Text::logger('Path "' . $directory . '" is not a directory for user ' . self::getSystemUser(), LOGGER_DEBUG);
			return false;
		}

		if ($check_writable && !is_writable($directory)) {
			Content\Text::logger('Path "' . $directory . '" is not writable for user ' . self::getSystemUser(), LOGGER_DEBUG);
			return false;
		}

		return true;
	}

	/**
	 * @param string $cat     Config category
	 * @param string $k       Config key
	 * @param mixed  $default Default value if it isn't set
	 *
	 * @return string Returns the value of the Config entry
	 */
	public function getConfigValue($cat, $k, $default = null)
	{
		$return = $default;

		if ($cat === 'config') {
			if (isset($this->config[$k])) {
				$return = $this->config[$k];
			}
		} else {
			if (isset($this->config[$cat][$k])) {
				$return = $this->config[$cat][$k];
			}
		}

		return $return;
	}

	/**
	 * Sets a default value in the config cache. Ignores already existing keys.
	 *
	 * @param string $cat Config category
	 * @param string $k   Config key
	 * @param mixed  $v   Default value to set
	 */
	private function setDefaultConfigValue($cat, $k, $v)
	{
		if (!isset($this->config[$cat][$k])) {
			$this->setConfigValue($cat, $k, $v);
		}
	}

	/**
	 * Sets a value in the config cache. Accepts raw output from the config table
	 *
	 * @param string $cat Config category
	 * @param string $k   Config key
	 * @param mixed  $v   Value to set
	 */
	public function setConfigValue($cat, $k, $v)
	{
		// Only arrays are serialized in database, so we have to unserialize sparingly
		$value = is_string($v) && preg_match("|^a:[0-9]+:{.*}$|s", $v) ? unserialize($v) : $v;

		if ($cat === 'config') {
			$this->config[$k] = $value;
		} else {
			if (!isset($this->config[$cat])) {
				$this->config[$cat] = [];
			}

			$this->config[$cat][$k] = $value;
		}
	}

	/**
	 * Deletes a value from the config cache
	 *
	 * @param string $cat Config category
	 * @param string $k   Config key
	 */
	public function deleteConfigValue($cat, $k)
	{
		if ($cat === 'config') {
			if (isset($this->config[$k])) {
				unset($this->config[$k]);
			}
		} else {
			if (isset($this->config[$cat][$k])) {
				unset($this->config[$cat][$k]);
			}
		}
	}


	/**
	 * Retrieves a value from the user config cache
	 *
	 * @param int    $uid     User Id
	 * @param string $cat     Config category
	 * @param string $k       Config key
	 * @param mixed  $default Default value if key isn't set
	 *
	 * @return string The value of the config entry
	 */
	public function getPConfigValue($uid, $cat, $k, $default = null)
	{
		$return = $default;

		if (isset($this->config[$uid][$cat][$k])) {
			$return = $this->config[$uid][$cat][$k];
		}

		return $return;
	}

	/**
	 * Sets a value in the user config cache
	 *
	 * Accepts raw output from the pconfig table
	 *
	 * @param int    $uid User Id
	 * @param string $cat Config category
	 * @param string $k   Config key
	 * @param mixed  $v   Value to set
	 */
	public function setPConfigValue($uid, $cat, $k, $v)
	{
		// Only arrays are serialized in database, so we have to unserialize sparingly
		$value = is_string($v) && preg_match("|^a:[0-9]+:{.*}$|s", $v) ? unserialize($v) : $v;

		if (!isset($this->config[$uid]) || !is_array($this->config[$uid])) {
			$this->config[$uid] = [];
		}

		if (!isset($this->config[$uid][$cat]) || !is_array($this->config[$uid][$cat])) {
			$this->config[$uid][$cat] = [];
		}

		$this->config[$uid][$cat][$k] = $value;
	}

	/**
	 * Deletes a value from the user config cache
	 *
	 * @param int    $uid User Id
	 * @param string $cat Config category
	 * @param string $k   Config key
	 */
	public function deletePConfigValue($uid, $cat, $k)
	{
		if (isset($this->config[$uid][$cat][$k])) {
			unset($this->config[$uid][$cat][$k]);
		}
	}

	/**
	 * Generates the site's default sender email address
	 *
	 * @return string
	 */
	public function getSenderEmailAddress()
	{
		$sender_email = Core\Config::get('config', 'sender_email');
		if (empty($sender_email)) {
			$hostname = $this->getHostName();
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
	 */
	public function getCurrentTheme()
	{
		if ($this->getMode()->isInstall()) {
			return '';
		}

		//// @TODO Compute the current theme only once (this behavior has
		/// already been implemented, but it didn't work well -
		/// https://github.com/friendica/friendica/issues/5092)
		$this->computeCurrentTheme();

		return $this->currentTheme;
	}

	/**
	 * Computes the current theme name based on the node settings, the user settings and the device type
	 *
	 * @throws Exception
	 */
	private function computeCurrentTheme()
	{
		$system_theme = Core\Config::get('system', 'theme');
		if (!$system_theme) {
			throw new Exception(Core\L10n::t('No system theme config value set.'));
		}

		// Sane default
		$this->currentTheme = $system_theme;

		$allowed_themes = explode(',', Core\Config::get('system', 'allowed_themes', $system_theme));

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
			$system_mobile_theme = Core\Config::get('system', 'mobile-theme');
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

		if ($theme_name
			&& in_array($theme_name, $allowed_themes)
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
	 * Returns the value of a argv key
	 * TODO there are a lot of $a->argv usages in combination with defaults() which can be replaced with this method
	 *
	 * @param int $position the position of the argument
	 * @param mixed $default the default value if not found
	 *
	 * @return mixed returns the value of the argument
	 */
	public function getArgumentValue($position, $default = '')
	{
		if (array_key_exists($position, $this->argv)) {
			return $this->argv[$position];
		}

		return $default;
	}

	/**
	 * Sets the base url for use in cmdline programs which don't have
	 * $_SERVER variables
	 */
	public function checkURL()
	{
		$url = Core\Config::get('system', 'url');

		// if the url isn't set or the stored url is radically different
		// than the currently visited url, store the current value accordingly.
		// "Radically different" ignores common variations such as http vs https
		// and www.example.com vs example.com.
		// We will only change the url to an ip address if there is no existing setting

		if (empty($url) || (!Content\Text::linkCompare($url, $this->getBaseURL())) && (!preg_match("/^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})$/", $this->getHostName()))) {
			Core\Config::set('system', 'url', $this->getBaseURL());
		}
	}

	/**
	 * Frontend App script
	 *
	 * The App object behaves like a container and a dispatcher at the same time, including a representation of the
	 * request and a representation of the response.
	 *
	 * This probably should change to limit the size of this monster method.
	 */
	public function runFrontend()
	{
		// Missing DB connection: ERROR
		if ($this->getMode()->has(App\Mode::LOCALCONFIGPRESENT) && !$this->getMode()->has(App\Mode::DBAVAILABLE)) {
			Core\System::httpExit(500, ['title' => 'Error 500 - Internal Server Error', 'description' => 'Apologies but the website is unavailable at the moment.']);
		}

		// Max Load Average reached: ERROR
		if ($this->isMaxProcessesReached() || $this->isMaxLoadReached()) {
			header('Retry-After: 120');
			header('Refresh: 120; url=' . $this->getBaseURL() . "/" . $this->query_string);

			Core\System::httpExit(503, ['title' => 'Error 503 - Service Temporarily Unavailable', 'description' => 'Core\System is currently overloaded. Please try again later.']);
		}

		if (strstr($this->query_string, '.well-known/host-meta') && ($this->query_string != '.well-known/host-meta')) {
			Core\System::httpExit(404);
		}

		if (!$this->getMode()->isInstall()) {
			// Force SSL redirection
			if (Core\Config::get('system', 'force_ssl') && ($this->getScheme() == "http")
				&& intval(Core\Config::get('system', 'ssl_policy')) == SSL_POLICY_FULL
				&& strpos($this->getBaseURL(), 'https://') === 0
				&& $_SERVER['REQUEST_METHOD'] == 'GET') {
				header('HTTP/1.1 302 Moved Temporarily');
				header('Location: ' . $this->getBaseURL() . '/' . $this->query_string);
				exit();
			}

			Core\Session::init();
			Core\Addon::callHooks('init_1');
		}

		// Exclude the backend processes from the session management
		if (!$this->isBackend()) {
			$stamp1 = microtime(true);
			session_start();
			$this->saveTimestamp($stamp1, 'parser');
			Core\L10n::setSessionVariable();
			Core\L10n::setLangFromSession();
		} else {
			$_SESSION = [];
			Core\Worker::executeIfIdle();
		}

		// ZRL
		if (!empty($_GET['zrl']) && $this->getMode()->isNormal()) {
			$this->query_string = Model\Profile::stripZrls($this->query_string);
			if (!local_user()) {
				// Only continue when the given profile link seems valid
				// Valid profile links contain a path with "/profile/" and no query parameters
				if ((parse_url($_GET['zrl'], PHP_URL_QUERY) == "") &&
					strstr(parse_url($_GET['zrl'], PHP_URL_PATH), "/profile/")) {
					if (defaults($_SESSION, "visitor_home", "") != $_GET["zrl"]) {
						$_SESSION['my_url'] = $_GET['zrl'];
						$_SESSION['authenticated'] = 0;
					}
					Model\Profile::zrlInit($this);
				} else {
					// Someone came with an invalid parameter, maybe as a DDoS attempt
					// We simply stop processing here
					Content\Text::logger("Invalid ZRL parameter " . $_GET['zrl'], LOGGER_DEBUG);
					Core\System::httpExit(403, ['title' => '403 Forbidden']);
				}
			}
		}

		if (!empty($_GET['owt']) && $this->getMode()->isNormal()) {
			$token = $_GET['owt'];
			$this->query_string = Model\Profile::stripQueryParam($this->query_string, 'owt');
			Model\Profile::openWebAuthInit($token);
		}

		Module\Login::sessionAuth();

		if (empty($_SESSION['authenticated'])) {
			header('X-Account-Management-Status: none');
		}

		$_SESSION['sysmsg']       = defaults($_SESSION, 'sysmsg'      , []);
		$_SESSION['sysmsg_info']  = defaults($_SESSION, 'sysmsg_info' , []);
		$_SESSION['last_updated'] = defaults($_SESSION, 'last_updated', []);

		/*
		 * check_config() is responsible for running update scripts. These automatically
		 * update the DB schema whenever we push a new one out. It also checks to see if
		 * any addons have been added or removed and reacts accordingly.
		 */

		// in install mode, any url loads install module
		// but we need "view" module for stylesheet
		if ($this->getMode()->isInstall() && $this->module != 'view') {
			$this->module = 'install';
		} elseif (!$this->getMode()->has(App\Mode::MAINTENANCEDISABLED) && $this->module != 'view') {
			$this->module = 'maintenance';
		} else {
			$this->checkURL();
			check_db(false);
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

		if (strlen($this->module)) {
			// Compatibility with the Android Diaspora client
			if ($this->module == 'stream') {
				$this->internalRedirect('network?f=&order=post');
			}

			if ($this->module == 'conversations') {
				$this->internalRedirect('message');
			}

			if ($this->module == 'commented') {
				$this->internalRedirect('network?f=&order=comment');
			}

			if ($this->module == 'liked') {
				$this->internalRedirect('network?f=&order=comment');
			}

			if ($this->module == 'activity') {
				$this->internalRedirect('network/?f=&conv=1');
			}

			if (($this->module == 'status_messages') && ($this->cmd == 'status_messages/new')) {
				$this->internalRedirect('bookmarklet');
			}

			if (($this->module == 'user') && ($this->cmd == 'user/edit')) {
				$this->internalRedirect('settings');
			}

			if (($this->module == 'tag_followings') && ($this->cmd == 'tag_followings/manage')) {
				$this->internalRedirect('search');
			}

			// Compatibility with the Firefox App
			if (($this->module == "users") && ($this->cmd == "users/sign_in")) {
				$this->module = "login";
			}

			$privateapps = Core\Config::get('config', 'private_addons', false);
			if (Core\Addon::isEnabled($this->module) && file_exists("addon/{$this->module}/{$this->module}.php")) {
				//Check if module is an app and if public access to apps is allowed or not
				if ((!local_user()) && Core\Hook::isAddonApp($this->module) && $privateapps) {
					info(Core\L10n::t("You must be logged in to use addons. "));
				} else {
					include_once "addon/{$this->module}/{$this->module}.php";
					if (function_exists($this->module . '_module')) {
						LegacyModule::setModuleFile("addon/{$this->module}/{$this->module}.php");
						$this->module_class = 'Friendica\\LegacyModule';
						$this->module_loaded = true;
					}
				}
			}

			// Controller class routing
			if (! $this->module_loaded && class_exists('Friendica\\Module\\' . ucfirst($this->module))) {
				$this->module_class = 'Friendica\\Module\\' . ucfirst($this->module);
				$this->module_loaded = true;
			}

			/* If not, next look for a 'standard' program module in the 'mod' directory
			 * We emulate a Module class through the LegacyModule class
			 */
			if (! $this->module_loaded && file_exists("mod/{$this->module}.php")) {
				LegacyModule::setModuleFile("mod/{$this->module}.php");
				$this->module_class = 'Friendica\\LegacyModule';
				$this->module_loaded = true;
			}

			/* The URL provided does not resolve to a valid module.
			 *
			 * On Dreamhost sites, quite often things go wrong for no apparent reason and they send us to '/internal_error.html'.
			 * We don't like doing this, but as it occasionally accounts for 10-20% or more of all site traffic -
			 * we are going to trap this and redirect back to the requested page. As long as you don't have a critical error on your page
			 * this will often succeed and eventually do the right thing.
			 *
			 * Otherwise we are going to emit a 404 not found.
			 */
			if (! $this->module_loaded) {
				// Stupid browser tried to pre-fetch our Javascript img template. Don't log the event or return anything - just quietly exit.
				if (!empty($_SERVER['QUERY_STRING']) && preg_match('/{[0-9]}/', $_SERVER['QUERY_STRING']) !== 0) {
					exit();
				}

				if (!empty($_SERVER['QUERY_STRING']) && ($_SERVER['QUERY_STRING'] === 'q=internal_error.html') && isset($dreamhost_error_hack)) {
					Content\Text::logger('index.php: dreamhost_error_hack invoked. Original URI =' . $_SERVER['REQUEST_URI']);
					$this->internalRedirect($_SERVER['REQUEST_URI']);
				}

				Content\Text::logger('index.php: page not found: ' . $_SERVER['REQUEST_URI'] . ' ADDRESS: ' . $_SERVER['REMOTE_ADDR'] . ' QUERY: ' . $_SERVER['QUERY_STRING'], LOGGER_DEBUG);

				header($_SERVER["SERVER_PROTOCOL"] . ' 404 ' . Core\L10n::t('Not Found'));
				$tpl = Content\Text::getMarkupTemplate("404.tpl");
				$this->page['content'] = $this->replaceMacros($tpl, [
					'$message' =>  Core\L10n::t('Page not found.')
				]);
			}
		}

		// Load current theme info
		$theme_info_file = 'view/theme/' . $this->getCurrentTheme() . '/theme.php';
		if (file_exists($theme_info_file)) {
			require_once $theme_info_file;
		}

		// initialise content region
		if ($this->getMode()->isNormal()) {
			Core\Addon::callHooks('page_content_top', $this->page['content']);
		}

		// Call module functions
		if ($this->module_loaded) {
			$this->page['page_title'] = $this->module;
			$placeholder = '';

			Core\Addon::callHooks($this->module . '_mod_init', $placeholder);

			call_user_func([$this->module_class, 'init']);

			// "rawContent" is especially meant for technical endpoints.
			// This endpoint doesn't need any theme initialization or other comparable stuff.
			if (!$this->error) {
				call_user_func([$this->module_class, 'rawContent']);
			}

			if (function_exists(str_replace('-', '_', $this->getCurrentTheme()) . '_init')) {
				$func = str_replace('-', '_', $this->getCurrentTheme()) . '_init';
				$func($this);
			}

			if (! $this->error && $_SERVER['REQUEST_METHOD'] === 'POST') {
				Core\Addon::callHooks($this->module . '_mod_post', $_POST);
				call_user_func([$this->module_class, 'post']);
			}

			if (! $this->error) {
				Core\Addon::callHooks($this->module . '_mod_afterpost', $placeholder);
				call_user_func([$this->module_class, 'afterpost']);
			}

			if (! $this->error) {
				$arr = ['content' => $this->page['content']];
				Core\Addon::callHooks($this->module . '_mod_content', $arr);
				$this->page['content'] = $arr['content'];
				$arr = ['content' => call_user_func([$this->module_class, 'content'])];
				Core\Addon::callHooks($this->module . '_mod_aftercontent', $arr);
				$this->page['content'] .= $arr['content'];
			}

			if (function_exists(str_replace('-', '_', $this->getCurrentTheme()) . '_content_loaded')) {
				$func = str_replace('-', '_', $this->getCurrentTheme()) . '_content_loaded';
				$func($this);
			}
		}

		/* Create the page head after setting the language
		 * and getting any auth credentials.
		 *
		 * Moved initHead() and initFooter() to after
		 * all the module functions have executed so that all
		 * theme choices made by the modules can take effect.
		 */
		$this->initHead();

		/* Build the page ending -- this is stuff that goes right before
		 * the closing </body> tag
		 */
		$this->initFooter();

		/* now that we've been through the module content, see if the page reported
		 * a permission problem and if so, a 403 response would seem to be in order.
		 */
		if (stristr(implode("", $_SESSION['sysmsg']), Core\L10n::t('Permission denied'))) {
			header($_SERVER["SERVER_PROTOCOL"] . ' 403 ' . Core\L10n::t('Permission denied.'));
		}

		// Report anything which needs to be communicated in the notification area (before the main body)
		Core\Addon::callHooks('page_end', $this->page['content']);

		// Add the navigation (menu) template
		if ($this->module != 'install' && $this->module != 'maintenance') {
			$this->page['htmlhead'] .= $this->replaceMacros(Content\Text::getMarkupTemplate('nav_head.tpl'), []);
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
		}

		if (isset($_GET["mode"]) && ($_GET["mode"] == "raw")) {
			header("Content-type: text/html; charset=utf-8");

			echo substr($target->saveHTML(), 6, -8);

			exit();
		}

		$page    = $this->page;
		$profile = $this->profile;

		header("X-Friendica-Version: " . FRIENDICA_VERSION);
		header("Content-type: text/html; charset=utf-8");

		if (Core\Config::get('system', 'hsts') && (Core\Config::get('system', 'ssl_policy') == SSL_POLICY_FULL)) {
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
		$lang = Core\L10n::getCurrentLang();

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
	 * @throws InternalServerErrorException In Case the given URL is not relative to the Friendica node
	 */
	public function internalRedirect($toUrl = '', $ssl = false)
	{
		if (filter_var($toUrl, FILTER_VALIDATE_URL)) {
			throw new InternalServerErrorException("'$toUrl is not a relative path, please use System::externalRedirectTo");
		}

		$redirectTo = $this->getBaseURL($ssl) . '/' . ltrim($toUrl, '/');
		Core\System::externalRedirect($redirectTo);
	}

	/**
	 * Automatically redirects to relative or absolute URL
	 * Should only be used if it isn't clear if the URL is either internal or external
	 *
	 * @param string $toUrl The target URL
	 *
	 */
	public function redirect($toUrl)
	{
		if (filter_var($toUrl, FILTER_VALIDATE_URL)) {
			Core\System::externalRedirect($toUrl);
		} else {
			$this->internalRedirect($toUrl);
		}
	}

	/**
     * This is our template processor
     *
     * @param string|FriendicaSmarty $s the string requiring macro substitution,
     *				or an instance of FriendicaSmarty
    * @param array $r key value pairs (search => replace)
    * @return string substituted string
    */
    public static function replaceMacros($s, $r)
    {
        $stamp1 = microtime(true);

        $a = get_app();

        // pass $baseurl to all templates
        $r['$baseurl'] = System::baseUrl();

        $t = $a->getTemplateEngine();
        try {
            $output = $t->replaceMacros($s, $r);
        } catch (Exception $e) {
            echo "<pre><b>" . __FUNCTION__ . "</b>: " . $e->getMessage() . "</pre>";
            killme();
        }

        $a->saveTimestamp($stamp1, "rendering");

        return $output;
    }
}
