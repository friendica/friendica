<?php

namespace Friendica;

use Friendica\Core\Config;
use Friendica\Core\PConfig;

use Cache;
use dbm;

use Detection\MobileDetect;

use Exception;

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
class App {

	public $module_loaded = false;
	public $query_string;
	public $config;
	public $page;
	public $profile;
	public $profile_uid;
	public $user;
	public $cid;
	public $contact;
	public $contacts;
	public $page_contact;
	public $content;
	public $data = array();
	public $error = false;
	public $cmd;
	public $argv;
	public $argc;
	public $module;
	public $pager;
	public $strings;
	public $basepath;
	public $path;
	public $hooks;
	public $timezone;
	public $interactive = true;
	public $plugins;
	public $apps = array();
	public $identities;
	public $is_mobile = false;
	public $is_tablet = false;
	public $is_friendica_app;
	public $performance = array();
	public $callstack = array();
	public $theme_info = array();
	public $backend = true;
	public $nav_sel;
	public $category;
	// Allow themes to control internal parameters
	// by changing App values in theme.php

	public $sourcename = '';
	public $videowidth = 425;
	public $videoheight = 350;
	public $force_max_items = 0;
	public $theme_thread_allow = true;
	public $theme_events_in_profile = true;

	/**
	 * @brief An array for all theme-controllable parameters
	 *
	 * Mostly unimplemented yet. Only options 'template_engine' and
	 * beyond are used.
	 */
	public $theme = array(
		'sourcename' => '',
		'videowidth' => 425,
		'videoheight' => 350,
		'force_max_items' => 0,
		'thread_allow' => true,
		'stylesheet' => '',
		'template_engine' => 'smarty3',
	);

	/**
	 * @brief An array of registered template engines ('name'=>'class name')
	 */
	public $template_engines = array();

	/**
	 * @brief An array of instanced template engines ('name'=>'instance')
	 */
	public $template_engine_instance = array();
	public $process_id;
	private $ldelim = array(
		'internal' => '',
		'smarty3' => '{{'
	);
	private $rdelim = array(
		'internal' => '',
		'smarty3' => '}}'
	);
	private $scheme;
	private $hostname;
	private $db;
	private $curl_code;
	private $curl_content_type;
	private $curl_headers;
	private $cached_profile_image;
	private $cached_profile_picdate;
	private static $a;

	/**
	 * @brief App constructor.
	 *
	 * @param string $basepath Path to the app base folder
	 */
	function __construct($basepath) {

		global $default_timezone;

		$hostname = '';

		if (file_exists('.htpreconfig.php')) {
			include '.htpreconfig.php';
		}

		$this->timezone = ((x($default_timezone)) ? $default_timezone : 'UTC');

		date_default_timezone_set($this->timezone);

		$this->performance['start'] = microtime(true);
		$this->performance['database'] = 0;
		$this->performance['database_write'] = 0;
		$this->performance['network'] = 0;
		$this->performance['file'] = 0;
		$this->performance['rendering'] = 0;
		$this->performance['parser'] = 0;
		$this->performance['marktime'] = 0;
		$this->performance['markstart'] = microtime(true);

		$this->callstack['database'] = array();
		$this->callstack['database_write'] = array();
		$this->callstack['network'] = array();
		$this->callstack['file'] = array();
		$this->callstack['rendering'] = array();
		$this->callstack['parser'] = array();

		$this->config = array();
		$this->page = array();
		$this->pager = array();

		$this->query_string = '';

		$this->process_id = uniqid('log', true);

		startup();

		$this->scheme = 'http';

		if ((x($_SERVER, 'HTTPS') && $_SERVER['HTTPS']) ||
			(x($_SERVER, 'HTTP_FORWARDED') && preg_match('/proto=https/', $_SERVER['HTTP_FORWARDED'])) ||
			(x($_SERVER, 'HTTP_X_FORWARDED_PROTO') && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') ||
			(x($_SERVER, 'HTTP_X_FORWARDED_SSL') && $_SERVER['HTTP_X_FORWARDED_SSL'] == 'on') ||
			(x($_SERVER, 'FRONT_END_HTTPS') && $_SERVER['FRONT_END_HTTPS'] == 'on') ||
			(x($_SERVER, 'SERVER_PORT') && (intval($_SERVER['SERVER_PORT']) == 443)) // XXX: reasonable assumption, but isn't this hardcoding too much?
		) {
			$this->scheme = 'https';
		}

		if (x($_SERVER, 'SERVER_NAME')) {
			$this->hostname = $_SERVER['SERVER_NAME'];

			if (x($_SERVER, 'SERVER_PORT') && $_SERVER['SERVER_PORT'] != 80 && $_SERVER['SERVER_PORT'] != 443) {
				$this->hostname .= ':' . $_SERVER['SERVER_PORT'];
			}
			/*
			 * Figure out if we are running at the top of a domain
			 * or in a sub-directory and adjust accordingly
			 */

			/// @TODO This kind of escaping breaks syntax-highlightning on CoolEdit (Midnight Commander)
			$path = trim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
			if (isset($path) && strlen($path) && ($path != $this->path)) {
				$this->path = $path;
			}
		}

		if ($hostname != '') {
			$this->hostname = $hostname;
		}

		if (! static::directory_usable($basepath, false)) {
			throw new Exception('Basepath ' . $basepath . ' isn\'t usable.');
		}

		$this->basepath = rtrim($basepath, DIRECTORY_SEPARATOR);

		set_include_path(
			get_include_path() . PATH_SEPARATOR
			. $this->basepath . DIRECTORY_SEPARATOR . 'include' . PATH_SEPARATOR
			. $this->basepath . DIRECTORY_SEPARATOR . 'library' . PATH_SEPARATOR
			. $this->basepath . DIRECTORY_SEPARATOR . 'library/langdet' . PATH_SEPARATOR
			. $this->basepath);


		if (is_array($_SERVER['argv']) && $_SERVER['argc'] > 1 && substr(end($_SERVER['argv']), 0, 4) == 'http') {
			$this->set_baseurl(array_pop($_SERVER['argv']));
			$_SERVER['argc'] --;
		}

		if ((x($_SERVER, 'QUERY_STRING')) && substr($_SERVER['QUERY_STRING'], 0, 9) === 'pagename=') {
			$this->query_string = substr($_SERVER['QUERY_STRING'], 9);

			// removing trailing / - maybe a nginx problem
			$this->query_string = ltrim($this->query_string, '/');
		} elseif ((x($_SERVER, 'QUERY_STRING')) && substr($_SERVER['QUERY_STRING'], 0, 2) === 'q=') {
			$this->query_string = substr($_SERVER['QUERY_STRING'], 2);

			// removing trailing / - maybe a nginx problem
			$this->query_string = ltrim($this->query_string, '/');
		}

		if (x($_GET, 'pagename')) {
			$this->cmd = trim($_GET['pagename'], '/\\');
		} elseif (x($_GET, 'q')) {
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
			$this->argv = array('home');
			$this->module = 'home';
		}

		// See if there is any page number information, and initialise pagination
		$this->pager['page'] = ((x($_GET, 'page') && intval($_GET['page']) > 0) ? intval($_GET['page']) : 1);
		$this->pager['itemspage'] = 50;
		$this->pager['start'] = ($this->pager['page'] * $this->pager['itemspage']) - $this->pager['itemspage'];

		if ($this->pager['start'] < 0) {
			$this->pager['start'] = 0;
		}
		$this->pager['total'] = 0;

		// Detect mobile devices
		$mobile_detect = new MobileDetect();
		$this->is_mobile = $mobile_detect->isMobile();
		$this->is_tablet = $mobile_detect->isTablet();

		// Friendica-Client
		$this->is_friendica_app = ($_SERVER['HTTP_USER_AGENT'] == 'Apache-HttpClient/UNAVAILABLE (java 1.4)');

		// Register template engines
		$dc = get_declared_classes();
		foreach ($dc as $k) {
			if (in_array('ITemplateEngine', class_implements($k))) {
				$this->register_template_engine($k);
			}
		}

		self::$a = $this;
	}

	/**
	 * @brief Returns the base filesystem path of the App
	 *
	 * It first checks for the internal variable, then for DOCUMENT_ROOT and
	 * finally for PWD
	 *
	 * @return string
	 */
	public static function get_basepath() {
		if (isset($this)) {
			$basepath = $this->basepath;
		}

		if (! $basepath) {
			$basepath = Config::get('system', 'basepath');
		}

		if (! $basepath && x($_SERVER, 'DOCUMENT_ROOT')) {
			$basepath = $_SERVER['DOCUMENT_ROOT'];
		}

		if (! $basepath && x($_SERVER, 'PWD')) {
			$basepath = $_SERVER['PWD'];
		}

		return $basepath;
	}

	function get_scheme() {
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
	function get_baseurl($ssl = false) {
		// Is the function called statically?
		if (!(isset($this) && get_class($this) == __CLASS__)) {
			return self::$a->get_baseurl($ssl);
		}

		$scheme = $this->scheme;

		if (Config::get('system', 'ssl_policy') == SSL_POLICY_FULL) {
			$scheme = 'https';
		}

		//	Basically, we have $ssl = true on any links which can only be seen by a logged in user
		//	(and also the login link). Anything seen by an outsider will have it turned off.

		if (Config::get('system', 'ssl_policy') == SSL_POLICY_SELFSIGN) {
			if ($ssl) {
				$scheme = 'https';
			} else {
				$scheme = 'http';
			}
		}

		if (Config::get('config', 'hostname') != '') {
			$this->hostname = Config::get('config', 'hostname');
		}

		return $scheme . '://' . $this->hostname . ((isset($this->path) && strlen($this->path)) ? '/' . $this->path : '' );
	}

	/**
	 * @brief Initializes the baseurl components
	 *
	 * Clears the baseurl cache to prevent inconstistencies
	 *
	 * @param string $url
	 */
	function set_baseurl($url) {
		$parsed = @parse_url($url);

		if ($parsed) {
			$this->scheme = $parsed['scheme'];

			$hostname = $parsed['host'];
			if (x($parsed, 'port')) {
				$hostname .= ':' . $parsed['port'];
			}
			if (x($parsed, 'path')) {
				$this->path = trim($parsed['path'], '\\/');
			}

			if (file_exists('.htpreconfig.php')) {
				include '.htpreconfig.php';
			}

			if (Config::get('config', 'hostname') != '') {
				$this->hostname = Config::get('config', 'hostname');
			}

			if (!isset($this->hostname) OR ( $this->hostname == '')) {
				$this->hostname = $hostname;
			}
		}
	}

	function get_hostname() {
		if (Config::get('config', 'hostname') != '') {
			$this->hostname = Config::get('config', 'hostname');
		}

		return $this->hostname;
	}

	function set_hostname($h) {
		$this->hostname = $h;
	}

	function set_path($p) {
		$this->path = trim(trim($p), '/');
	}

	function get_path() {
		return $this->path;
	}

	function set_pager_total($n) {
		$this->pager['total'] = intval($n);
	}

	function set_pager_itemspage($n) {
		$this->pager['itemspage'] = ((intval($n) > 0) ? intval($n) : 0);
		$this->pager['start'] = ($this->pager['page'] * $this->pager['itemspage']) - $this->pager['itemspage'];
	}

	function set_pager_page($n) {
		$this->pager['page'] = $n;
		$this->pager['start'] = ($this->pager['page'] * $this->pager['itemspage']) - $this->pager['itemspage'];
	}

	function init_pagehead() {
		$interval = ((local_user()) ? PConfig::get(local_user(), 'system', 'update_interval') : 40000);

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

		/* put the head template at the beginning of page['htmlhead']
		 * since the code added by the modules frequently depends on it
		 * being first
		 */
		if (!isset($this->page['htmlhead'])) {
			$this->page['htmlhead'] = '';
		}

		// If we're using Smarty, then doing replace_macros() will replace
		// any unrecognized variables with a blank string. Since we delay
		// replacing $stylesheet until later, we need to replace it now
		// with another variable name
		if ($this->theme['template_engine'] === 'smarty3') {
			$stylesheet = $this->get_template_ldelim('smarty3') . '$stylesheet' . $this->get_template_rdelim('smarty3');
		} else {
			$stylesheet = '$stylesheet';
		}

		$shortcut_icon = Config::get('system', 'shortcut_icon');
		if ($shortcut_icon == '') {
			$shortcut_icon = 'images/friendica-32.png';
		}

		$touch_icon = Config::get('system', 'touch_icon');
		if ($touch_icon == '') {
			$touch_icon = 'images/friendica-128.png';
		}

		// get data wich is needed for infinite scroll on the network page
		$invinite_scroll = infinite_scroll_data($this->module);

		$tpl = get_markup_template('head.tpl');
		$this->page['htmlhead'] = replace_macros($tpl, array(
				'$baseurl' => $this->get_baseurl(), // FIXME for z_path!!!!
				'$local_user' => local_user(),
				'$generator' => 'Friendica' . ' ' . FRIENDICA_VERSION,
				'$delitem' => t('Delete this item?'),
				'$showmore' => t('show more'),
				'$showfewer' => t('show fewer'),
				'$update_interval' => $interval,
				'$shortcut_icon' => $shortcut_icon,
				'$touch_icon' => $touch_icon,
				'$stylesheet' => $stylesheet,
				'$infinite_scroll' => $invinite_scroll,
			)) . $this->page['htmlhead'];
	}

	function init_page_end() {
		if (!isset($this->page['end'])) {
			$this->page['end'] = '';
		}
		$tpl = get_markup_template('end.tpl');
		$this->page['end'] = replace_macros($tpl, array(
				'$baseurl' => $this->get_baseurl() // FIXME for z_path!!!!
			)) . $this->page['end'];
	}

	function set_curl_code($code) {
		$this->curl_code = $code;
	}

	function get_curl_code() {
		return $this->curl_code;
	}

	function set_curl_content_type($content_type) {
		$this->curl_content_type = $content_type;
	}

	function get_curl_content_type() {
		return $this->curl_content_type;
	}

	function set_curl_headers($headers) {
		$this->curl_headers = $headers;
	}

	function get_curl_headers() {
		return $this->curl_headers;
	}

	function get_cached_avatar_image($avatar_image) {
		return $avatar_image;
	}

	/**
	 * @brief Removes the baseurl from an url. This avoids some mixed content problems.
	 *
	 * @param string $orig_url
	 *
	 * @return string The cleaned url
	 */
	function remove_baseurl($orig_url) {

		// Is the function called statically?
		if (!(isset($this) && get_class($this) == __CLASS__)) {
			return self::$a->remove_baseurl($orig_url);
		}

		// Remove the hostname from the url if it is an internal link
		$nurl = normalise_link($orig_url);
		$base = normalise_link($this->get_baseurl());
		$url = str_replace($base . '/', '', $nurl);

		// if it is an external link return the orignal value
		if ($url == normalise_link($orig_url)) {
			return $orig_url;
		} else {
			return $url;
		}
	}

	/**
	 * @brief Register template engine class
	 *
	 * If $name is '', is used class static property $class::$name
	 *
	 * @param string $class
	 * @param string $name
	 */
	function register_template_engine($class, $name = '') {
		/// @TODO Really === and not just == ?
		if ($name === '') {
			$v = get_class_vars($class);
			if (x($v, 'name'))
				$name = $v['name'];
		}
		if ($name === '') {
			echo "template engine <tt>$class</tt> cannot be registered without a name.\n";
			killme();
		}
		$this->template_engines[$name] = $class;
	}

	/**
	 * @brief Return template engine instance.
	 *
	 * If $name is not defined, return engine defined by theme,
	 * or default
	 *
	 * @param strin $name Template engine name
	 * @return object Template Engine instance
	 */
	function template_engine($name = '') {
		/// @TODO really type-check included?
		if ($name !== '') {
			$template_engine = $name;
		} else {
			$template_engine = 'smarty3';
			if (x($this->theme, 'template_engine')) {
				$template_engine = $this->theme['template_engine'];
			}
		}

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
		killme();
	}

	/**
	 * @brief Returns the active template engine.
	 *
	 * @return string
	 */
	function get_template_engine() {
		return $this->theme['template_engine'];
	}

	function set_template_engine($engine = 'smarty3') {
		$this->theme['template_engine'] = $engine;
	}

	function get_template_ldelim($engine = 'smarty3') {
		return $this->ldelim[$engine];
	}

	function get_template_rdelim($engine = 'smarty3') {
		return $this->rdelim[$engine];
	}

	function save_timestamp($stamp, $value) {
		if (!isset($this->config['system']['profiler']) || !$this->config['system']['profiler']) {
			return;
		}

		$duration = (float) (microtime(true) - $stamp);

		if (!isset($this->performance[$value])) {
			// Prevent ugly E_NOTICE
			$this->performance[$value] = 0;
		}

		$this->performance[$value] += (float) $duration;
		$this->performance['marktime'] += (float) $duration;

		$callstack = $this->callstack();

		if (!isset($this->callstack[$value][$callstack])) {
			// Prevent ugly E_NOTICE
			$this->callstack[$value][$callstack] = 0;
		}

		$this->callstack[$value][$callstack] += (float) $duration;
	}

	/**
	 * @brief Log active processes into the "process" table
	 */
	function start_process() {
		$trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);

		$command = basename($trace[0]['file']);

		$this->remove_inactive_processes();

		q('START TRANSACTION');

		$r = q('SELECT `pid` FROM `process` WHERE `pid` = %d', intval(getmypid()));
		if (!dbm::is_result($r)) {
			q("INSERT INTO `process` (`pid`,`command`,`created`) VALUES (%d, '%s', '%s')", intval(getmypid()), dbesc($command), dbesc(datetime_convert()));
		}
		q('COMMIT');
	}

	/**
	 * @brief Remove inactive processes
	 */
	function remove_inactive_processes() {
		q('START TRANSACTION');

		$r = q('SELECT `pid` FROM `process`');
		if (dbm::is_result($r)) {
			foreach ($r AS $process) {
				if (!posix_kill($process['pid'], 0)) {
					q('DELETE FROM `process` WHERE `pid` = %d', intval($process['pid']));
				}
			}
		}
		q('COMMIT');
	}

	/**
	 * @brief Remove the active process from the "process" table
	 */
	function end_process() {
		q('DELETE FROM `process` WHERE `pid` = %d', intval(getmypid()));
	}

	/**
	 * @brief Returns a string with a callstack. Can be used for logging.
	 *
	 * @return string
	 */
	function callstack($depth = 4) {
		$trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $depth + 2);

		// We remove the first two items from the list since they contain data that we don't need.
		array_shift($trace);
		array_shift($trace);

		$callstack = array();
		foreach ($trace AS $func) {
			$callstack[] = $func['function'];
		}

		return implode(', ', $callstack);
	}

	function get_useragent() {
		return
			FRIENDICA_PLATFORM . " '" .
			FRIENDICA_CODENAME . "' " .
			FRIENDICA_VERSION . '-' .
			DB_UPDATE_VERSION . '; ' .
			$this->get_baseurl();
	}

	function is_friendica_app() {
		return $this->is_friendica_app;
	}

	/**
	 * @brief Checks if the site is called via a backend process
	 *
	 * This isn't a perfect solution. But we need this check very early.
	 * So we cannot wait until the modules are loaded.
	 *
	 * @return bool Is it a known backend?
	 */
	function is_backend() {
		static $backends = array();
		$backends[] = '_well_known';
		$backends[] = 'api';
		$backends[] = 'dfrn_notify';
		$backends[] = 'fetch';
		$backends[] = 'hcard';
		$backends[] = 'hostxrd';
		$backends[] = 'nodeinfo';
		$backends[] = 'noscrape';
		$backends[] = 'p';
		$backends[] = 'poco';
		$backends[] = 'post';
		$backends[] = 'proxy';
		$backends[] = 'pubsub';
		$backends[] = 'pubsubhubbub';
		$backends[] = 'receive';
		$backends[] = 'rsd_xml';
		$backends[] = 'salmon';
		$backends[] = 'statistics_json';
		$backends[] = 'xrd';

		// Check if current module is in backend or backend flag is set
		return (in_array($this->module, $backends) || $this->backend);
	}

	/**
	 * @brief Checks if the maximum number of database processes is reached
	 *
	 * @return bool Is the limit reached?
	 */
	function max_processes_reached() {

		if ($this->is_backend()) {
			$process = 'backend';
			$max_processes = Config::get('system', 'max_processes_backend');
			if (intval($max_processes) == 0) {
				$max_processes = 5;
			}
		} else {
			$process = 'frontend';
			$max_processes = Config::get('system', 'max_processes_frontend');
			if (intval($max_processes) == 0) {
				$max_processes = 20;
			}
		}

		$processlist = dbm::processlist();
		if ($processlist['list'] != '') {
			logger('Processcheck: Processes: ' . $processlist['amount'] . ' - Processlist: ' . $processlist['list'], LOGGER_DEBUG);

			if ($processlist['amount'] > $max_processes) {
				logger('Processcheck: Maximum number of processes for ' . $process . ' tasks (' . $max_processes . ') reached.', LOGGER_DEBUG);
				return true;
			}
		}
		return false;
	}

	/**
	 * @brief Checks if the minimal memory is reached
	 *
	 * @return bool Is the memory limit reached?
	 */
	public function min_memory_reached() {
		$min_memory = Config::get('system', 'min_memory', 0);
		if ($min_memory == 0) {
			return false;
		}

		if (!is_readable('/proc/meminfo')) {
			return false;
		}

		$memdata = explode("\n", file_get_contents('/proc/meminfo'));

		$meminfo = array();
		foreach ($memdata as $line) {
			list($key, $val) = explode(':', $line);
			$meminfo[$key] = (int) trim(str_replace('kB', '', $val));
			$meminfo[$key] = (int) ($meminfo[$key] / 1024);
		}

		if (!isset($meminfo['MemAvailable']) OR ! isset($meminfo['MemFree'])) {
			return false;
		}

		$free = $meminfo['MemAvailable'] + $meminfo['MemFree'];

		$reached = ($free < $min_memory);

		if ($reached) {
			logger('Minimal memory reached: ' . $free . '/' . $meminfo['MemTotal'] . ' - limit ' . $min_memory, LOGGER_DEBUG);
		}

		return $reached;
	}

	/**
	 * @brief Checks if the maximum load is reached
	 *
	 * @return bool Is the load reached?
	 */
	function maxload_reached() {

		if ($this->is_backend()) {
			$process = 'backend';
			$maxsysload = intval(Config::get('system', 'maxloadavg'));
			if ($maxsysload < 1) {
				$maxsysload = 50;
			}
		} else {
			$process = 'frontend';
			$maxsysload = intval(Config::get('system', 'maxloadavg_frontend'));
			if ($maxsysload < 1) {
				$maxsysload = 50;
			}
		}

		$load = current_load();
		if ($load) {
			if (intval($load) > $maxsysload) {
				logger('system: load ' . $load . ' for ' . $process . ' tasks (' . $maxsysload . ') too high.');
				return true;
			}
		}
		return false;
	}

	function proc_run($args) {

		if (!function_exists('proc_open')) {
			return;
		}

		// If the last worker fork was less than 2 seconds before then don't fork another one.
		// This should prevent the forking of masses of workers.
		$cachekey = 'app:proc_run:started';
		$result = Cache::get($cachekey);

		if (!is_null($result) AND ( time() - $result) < 2) {
			return;
		}

		// Set the timestamp of the last proc_run
		Cache::set($cachekey, time(), CACHE_MINUTE);

		array_unshift($args, ((x($this->config, 'php_path')) && (strlen($this->config['php_path'])) ? $this->config['php_path'] : 'php'));

		// add baseurl to args. cli scripts can't construct it
		$args[] = $this->get_baseurl();

		for ($x = 0; $x < count($args); $x ++) {
			$args[$x] = escapeshellarg($args[$x]);
		}

		$cmdline = implode($args, ' ');

		if ($this->min_memory_reached()) {
			return;
		}

		if (Config::get('system', 'proc_windows')) {
			$resource = proc_open('cmd /c start /b ' . $cmdline, array(), $foo, $this->get_basepath());
		} else {
			$resource = proc_open($cmdline . ' &', array(), $foo, $this->get_basepath());
		}
		if (!is_resource($resource)) {
			logger('We got no resource for command ' . $cmdline, LOGGER_DEBUG);
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
	static function systemuser() {
		if (!function_exists('posix_getpwuid') OR ! function_exists('posix_geteuid')) {
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
	static function directory_usable($directory, $check_writable = true) {
		if ($directory == '') {
			logger('Directory is empty. This shouldn\'t happen.', LOGGER_DEBUG);
			return false;
		}

		if (!file_exists($directory)) {
			logger('Path "' . $directory . '" does not exist for user ' . self::systemuser(), LOGGER_DEBUG);
			return false;
		}
		if (is_file($directory)) {
			logger('Path "' . $directory . '" is a file for user ' . self::systemuser(), LOGGER_DEBUG);
			return false;
		}
		if (!is_dir($directory)) {
			logger('Path "' . $directory . '" is not a directory for user ' . self::systemuser(), LOGGER_DEBUG);
			return false;
		}
		if ($check_writable AND !is_writable($directory)) {
			logger('Path "' . $directory . '" is not writable for user ' . self::systemuser(), LOGGER_DEBUG);
			return false;
		}
		return true;
	}
}
