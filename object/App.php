<?php

/**
 *
 * class: App
 *
 * Our main application structure for the life of this page
 * Primarily deals with the URL that got us here
 * and tries to make some sense of it, and
 * stores our page contents and config storage
 * and anything else that might need to be passed around
 * before we spit the page out.
 *
 */

if(! class_exists('App')) {
	class App {

		public  $module_loaded = false;
		public  $query_string;
		public  $config;
		public  $page;
		public  $profile;
		public  $user;
		public  $cid;
		public  $contact;
		public  $contacts;
		public  $page_contact;
		public  $content;
		public  $data = array();
		public  $error = false;
		public  $cmd;
		public  $argv;
		public  $argc;
		public  $module;
		public  $pager;
		public  $strings;
		public  $path;
		public  $hooks;
		public  $timezone;
		public  $interactive = true;
		public  $plugins;
		public  $apps = array();
		public  $identities;
		public	$is_mobile;
		public	$is_tablet;
		public	$performance = array();

		public $nav_sel;

		public $category;


		// Allow themes to control internal parameters
		// by changing App values in theme.php

		public	$sourcename = '';
		public	$videowidth = 425;
		public	$videoheight = 350;
		public	$force_max_items = 0;
		public	$theme_thread_allow = true;

		// An array for all theme-controllable parameters
		// Mostly unimplemented yet. Only options 'stylesheet' and
		// beyond are used.

		public	$theme = array(
			'sourcename' => '',
			'videowidth' => 425,
			'videoheight' => 350,
			'force_max_items' => 0,
			'thread_allow' => true,
			'stylesheet' => '',
			'template_engine' => 'smarty3',
		);

		// array of registered template engines ('name'=>'class name')
		public $template_engines = array();
		// array of instanced template engines ('name'=>'instance')
		public $template_engine_instance = array();

		// Used for reducing load to the ostatus completion
		public $last_ostatus_conversation_url;

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
		private $baseurl;
		private $db;

		private $curl_code;
		private $curl_content_type;
		private $curl_headers;

		private $cached_profile_image;
		private $cached_profile_picdate;

		function __construct() {

			global $default_timezone, $argv, $argc;

			$hostname = "";

			if (file_exists(".htpreconfig.php"))
				@include(".htpreconfig.php");

			$this->timezone = ((x($default_timezone)) ? $default_timezone : 'UTC');

			date_default_timezone_set($this->timezone);

			$this->performance["start"] = microtime(true);
			$this->performance["database"] = 0;
			$this->performance["network"] = 0;
			$this->performance["file"] = 0;
			$this->performance["rendering"] = 0;
			$this->performance["parser"] = 0;
			$this->performance["marktime"] = 0;
			$this->performance["markstart"] = microtime(true);

			$this->config = array();
			$this->page = array();
			$this->pager= array();

			$this->query_string = '';

			startup();

			set_include_path(
					'include' . PATH_SEPARATOR
					. 'library' . PATH_SEPARATOR
					. 'library/phpsec' . PATH_SEPARATOR
					. 'library/langdet' . PATH_SEPARATOR
					. '.' );


			$this->scheme = 'http';
			if(x($_SERVER,'HTTPS') && $_SERVER['HTTPS'])
				$this->scheme = 'https';
			elseif(x($_SERVER,'SERVER_PORT') && (intval($_SERVER['SERVER_PORT']) == 443))
				$this->scheme = 'https';

			if(x($_SERVER,'SERVER_NAME')) {
				$this->hostname = $_SERVER['SERVER_NAME'];

				// See bug 437 - this didn't work so disabling it
				//if(stristr($this->hostname,'xn--')) {
					// PHP or webserver may have converted idn to punycode, so
					// convert punycode back to utf-8
				//	require_once('library/simplepie/idn/idna_convert.class.php');
				//	$x = new idna_convert();
				//	$this->hostname = $x->decode($_SERVER['SERVER_NAME']);
				//}

				if(x($_SERVER,'SERVER_PORT') && $_SERVER['SERVER_PORT'] != 80 && $_SERVER['SERVER_PORT'] != 443)
					$this->hostname .= ':' . $_SERVER['SERVER_PORT'];
				/**
				 * Figure out if we are running at the top of a domain
				 * or in a sub-directory and adjust accordingly
				 */

				$path = trim(dirname($_SERVER['SCRIPT_NAME']),'/\\');
				if(isset($path) && strlen($path) && ($path != $this->path))
					$this->path = $path;
			}

			if ($hostname != "")
				$this->hostname = $hostname;

			if (is_array($argv) && $argc>1 && substr(end($argv), 0, 4)=="http" ) {
				$this->set_baseurl(array_pop($argv) );
				$argc --;
			}

			set_include_path("include/$this->hostname" . PATH_SEPARATOR . get_include_path());

			if((x($_SERVER,'QUERY_STRING')) && substr($_SERVER['QUERY_STRING'],0,2) === "q=") {
				$this->query_string = substr($_SERVER['QUERY_STRING'],2);
				// removing trailing / - maybe a nginx problem
				if (substr($this->query_string, 0, 1) == "/")
					$this->query_string = substr($this->query_string, 1);
			}
			if(x($_GET,'q'))
				$this->cmd = trim($_GET['q'],'/\\');

			// unix style "homedir"

			if(substr($this->cmd,0,1) === '~')
				$this->cmd = 'profile/' . substr($this->cmd,1);

			// Diaspora style profile url

			if(substr($this->cmd,0,2) === 'u/')
				$this->cmd = 'profile/' . substr($this->cmd,2);

			/**
			 *
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
			 *
			 */

			$this->argv = explode('/',$this->cmd);
			$this->argc = count($this->argv);
			if((array_key_exists('0',$this->argv)) && strlen($this->argv[0])) {
				$this->module = str_replace(".", "_", $this->argv[0]);
				$this->module = str_replace("-", "_", $this->module);
			}
			else {
				$this->argc = 1;
				$this->argv = array('home');
				$this->module = 'home';
			}

			/**
			 * See if there is any page number information, and initialise
			 * pagination
			 */

			$this->pager['page'] = ((x($_GET,'page') && intval($_GET['page']) > 0) ? intval($_GET['page']) : 1);
			$this->pager['itemspage'] = 50;
			$this->pager['start'] = ($this->pager['page'] * $this->pager['itemspage']) - $this->pager['itemspage'];
			if($this->pager['start'] < 0)
				$this->pager['start'] = 0;
			$this->pager['total'] = 0;

			/**
			 * Detect mobile devices
			 */

			$mobile_detect = new Mobile_Detect();
			$this->is_mobile = $mobile_detect->isMobile();
			$this->is_tablet = $mobile_detect->isTablet();

			/**
			 * register template engines
			 */
			$dc = get_declared_classes();
			foreach ($dc as $k) {
				if (in_array("ITemplateEngine", class_implements($k))){
					$this->register_template_engine($k);
				}
			}

		}

		function get_basepath() {

			$basepath = get_config("system", "basepath");

			if ($basepath == "")
				$basepath = $_SERVER["DOCUMENT_ROOT"];

			if ($basepath == "")
				$basepath = $_SERVER["PWD"];

			return($basepath);
		}

		function get_baseurl($ssl = false) {

			$scheme = $this->scheme;

			if((x($this->config,'system')) && (x($this->config['system'],'ssl_policy'))) {
				if(intval($this->config['system']['ssl_policy']) === intval(SSL_POLICY_FULL))
					$scheme = 'https';

				//	Basically, we have $ssl = true on any links which can only be seen by a logged in user
				//	(and also the login link). Anything seen by an outsider will have it turned off.

				if($this->config['system']['ssl_policy'] == SSL_POLICY_SELFSIGN) {
					if($ssl)
						$scheme = 'https';
					else
						$scheme = 'http';
				}
			}

			$this->baseurl = $scheme . "://" . $this->hostname . ((isset($this->path) && strlen($this->path)) ? '/' . $this->path : '' );
			return $this->baseurl;
		}

		function set_baseurl($url) {
			$parsed = @parse_url($url);

			$this->baseurl = $url;

			if($parsed) {
				$this->scheme = $parsed['scheme'];

				$hostname = $parsed['host'];
				if(x($parsed,'port'))
					$hostname .= ':' . $parsed['port'];
				if(x($parsed,'path'))
					$this->path = trim($parsed['path'],'\\/');

				if (file_exists(".htpreconfig.php"))
					@include(".htpreconfig.php");

				$this->hostname = $hostname;
			}

		}

		function get_hostname() {
			return $this->hostname;
		}

		function set_hostname($h) {
			$this->hostname = $h;
		}

		function set_path($p) {
			$this->path = trim(trim($p),'/');
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
			$interval = ((local_user()) ? get_pconfig(local_user(),'system','update_interval') : 40000);
			if($interval < 10000)
				$interval = 40000;

			$this->page['title'] = $this->config['sitename'];

			/* put the head template at the beginning of page['htmlhead']
			 * since the code added by the modules frequently depends on it
			 * being first
			 */
			if(!isset($this->page['htmlhead']))
				$this->page['htmlhead'] = '';

			// If we're using Smarty, then doing replace_macros() will replace
			// any unrecognized variables with a blank string. Since we delay
			// replacing $stylesheet until later, we need to replace it now
			// with another variable name
			if($this->theme['template_engine'] === 'smarty3')
				$stylesheet = $this->get_template_ldelim('smarty3') . '$stylesheet' . $this->get_template_rdelim('smarty3');
			else
				$stylesheet = '$stylesheet';

			$shortcut_icon = get_config("system", "shortcut_icon");
			if ($shortcut_icon == "")
				$shortcut_icon = $this->get_baseurl()."/images/friendica-32.png";

			$touch_icon = get_config("system", "touch_icon");
			if ($touch_icon == "")
				$touch_icon = $this->get_baseurl()."/images/friendica-128.png";

			$tpl = get_markup_template('head.tpl');
			$this->page['htmlhead'] = replace_macros($tpl,array(
				'$baseurl' => $this->get_baseurl(), // FIXME for z_path!!!!
				'$local_user' => local_user(),
				'$generator' => 'Friendica' . ' ' . FRIENDICA_VERSION,
				'$delitem' => t('Delete this item?'),
				'$comment' => t('Comment'),
				'$showmore' => t('show more'),
				'$showfewer' => t('show fewer'),
				'$update_interval' => $interval,
				'$shortcut_icon' => $shortcut_icon,
				'$touch_icon' => $touch_icon,
				'$stylesheet' => $stylesheet
			)) . $this->page['htmlhead'];
		}

		function init_page_end() {
			if(!isset($this->page['end']))
				$this->page['end'] = '';
			$tpl = get_markup_template('end.tpl');
			$this->page['end'] = replace_macros($tpl,array(
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

		function get_cached_avatar_image($avatar_image){
			if($this->cached_profile_image[$avatar_image])
				return $this->cached_profile_image[$avatar_image];

			$path_parts = explode("/",$avatar_image);
			$common_filename = $path_parts[count($path_parts)-1];

			if($this->cached_profile_picdate[$common_filename]){
				$this->cached_profile_image[$avatar_image] = $avatar_image . $this->cached_profile_picdate[$common_filename];
			} else {
				$r = q("SELECT `contact`.`avatar-date` AS picdate FROM `contact` WHERE `contact`.`thumb` like '%%/%s'",
					$common_filename);
				if(! count($r)){
					$this->cached_profile_image[$avatar_image] = $avatar_image;
				} else {
					$this->cached_profile_picdate[$common_filename] = "?rev=".urlencode($r[0]['picdate']);
					$this->cached_profile_image[$avatar_image] = $avatar_image.$this->cached_profile_picdate[$common_filename];
				}
			}
			return $this->cached_profile_image[$avatar_image];
		}


		/**
		 * register template engine class
		 * if $name is "", is used class static property $class::$name
		 * @param string $class
		 * @param string $name
		 */
		function register_template_engine($class, $name = '') {
			if ($name===""){
				$v = get_class_vars( $class );
				if(x($v,"name")) $name = $v['name'];
			}
	 		if ($name===""){
 				echo "template engine <tt>$class</tt> cannot be registered without a name.\n";
				killme(); 
 			}
			$this->template_engines[$name] = $class;
		}

		/**
		 * return template engine instance. If $name is not defined,
		 * return engine defined by theme, or default
		 * 
		 * @param strin $name Template engine name
		 * @return object Template Engine instance
		 */
		function template_engine($name = ''){
			if ($name!=="") {
				$template_engine = $name;
			} else {
				$template_engine = 'smarty3';
				if (x($this->theme, 'template_engine')) {
					$template_engine = $this->theme['template_engine'];
				}
			}

			if (isset($this->template_engines[$template_engine])){
				if(isset($this->template_engine_instance[$template_engine])){
					return $this->template_engine_instance[$template_engine];
				} else {
					$class = $this->template_engines[$template_engine];
					$obj = new $class;
					$this->template_engine_instance[$template_engine] = $obj;
					return $obj;
				}
			}

			echo "template engine <tt>$template_engine</tt> is not registered!\n"; killme();
		}

		function get_template_engine() {
			return $this->theme['template_engine'];
		}

		function set_template_engine($engine = 'smarty3') {
			$this->theme['template_engine'] = $engine;
			/*
			$this->theme['template_engine'] = 'smarty3';

			switch($engine) {
				case 'smarty3':
					if(is_writable('view/smarty3/'))
						$this->theme['template_engine'] = 'smarty3';
					break;
				default:
					break;
			}
			*/
		}

		function get_template_ldelim($engine = 'smarty3') {
			return $this->ldelim[$engine];
		}

		function get_template_rdelim($engine = 'smarty3') {
			return $this->rdelim[$engine];
		}

		function save_timestamp($stamp, $value) {
			$duration = (float)(microtime(true)-$stamp);

			$this->performance[$value] += (float)$duration;
			$this->performance["marktime"] += (float)$duration;
		}

		function mark_timestamp($mark) {
			//$this->performance["markstart"] -= microtime(true) - $this->performance["marktime"];
			$this->performance["markstart"] = microtime(true) - $this->performance["markstart"] - $this->performance["marktime"];
		}

	}
}
?>