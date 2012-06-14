<?php

require_once('include/config.php');
require_once('include/network.php');
require_once('include/plugin.php');
require_once('include/text.php');
require_once('include/datetime.php');
require_once('include/pgettext.php');
require_once('include/nav.php');
require_once('include/cache.php');

define ( 'FRIENDICA_PLATFORM',     'Friendica');
define ( 'FRIENDICA_VERSION',      '3.0.1373' );
define ( 'DFRN_PROTOCOL_VERSION',  '2.23'    );
define ( 'DB_UPDATE_VERSION',      1149      );

define ( 'EOL',                    "<br />\r\n"     );
define ( 'ATOM_TIME',              'Y-m-d\TH:i:s\Z' );


/**
 *
 * Image storage quality. Lower numbers save space at cost of image detail.
 * For ease of upgrade, please do not change here. Change jpeg quality with
 * $a->config['system']['jpeg_quality'] = n;
 * in .htconfig.php, where n is netween 1 and 100, and with very poor results
 * below about 50
 *
 */

define ( 'JPEG_QUALITY',            100  );
/**
 * $a->config['system']['png_quality'] from 0 (uncompressed) to 9
 */
define ( 'PNG_QUALITY',             8  );


/**
 * Not yet used
 */

define ( 'DEFAULT_DB_ENGINE',  'MyISAM'  );

/**
 * SSL redirection policies
 */

define ( 'SSL_POLICY_NONE',         0 );
define ( 'SSL_POLICY_FULL',         1 );
define ( 'SSL_POLICY_SELFSIGN',     2 );


/**
 * log levels
 */

define ( 'LOGGER_NORMAL',          0 );
define ( 'LOGGER_TRACE',           1 );
define ( 'LOGGER_DEBUG',           2 );
define ( 'LOGGER_DATA',            3 );
define ( 'LOGGER_ALL',             4 );

/**
 * registration policies
 */

define ( 'REGISTER_CLOSED',        0 );
define ( 'REGISTER_APPROVE',       1 );
define ( 'REGISTER_OPEN',          2 );

/**
 * relationship types
 */

define ( 'CONTACT_IS_FOLLOWER', 1);
define ( 'CONTACT_IS_SHARING',  2);
define ( 'CONTACT_IS_FRIEND',   3);


/**
 * Hook array order
 */

define ( 'HOOK_HOOK',      0);
define ( 'HOOK_FILE',      1);
define ( 'HOOK_FUNCTION',  2);

/**
 * DB update return values
 */

define ( 'UPDATE_SUCCESS', 0);
define ( 'UPDATE_FAILED',  1);


/**
 *
 * page/profile types
 *
 * PAGE_NORMAL is a typical personal profile account
 * PAGE_SOAPBOX automatically approves all friend requests as CONTACT_IS_SHARING, (readonly)
 * PAGE_COMMUNITY automatically approves all friend requests as CONTACT_IS_SHARING, but with
 *      write access to wall and comments (no email and not included in page owner's ACL lists)
 * PAGE_FREELOVE automatically approves all friend requests as full friends (CONTACT_IS_FRIEND).
 *
 */

define ( 'PAGE_NORMAL',            0 );
define ( 'PAGE_SOAPBOX',           1 );
define ( 'PAGE_COMMUNITY',         2 );
define ( 'PAGE_FREELOVE',          3 );
define ( 'PAGE_BLOG',              4 );
define ( 'PAGE_PRVGROUP',          5 );

/**
 * Network and protocol family types
 */

define ( 'NETWORK_DFRN',             'dfrn');    // Friendica, Mistpark, other DFRN implementations
define ( 'NETWORK_ZOT',              'zot!');    // Zot!
define ( 'NETWORK_OSTATUS',          'stat');    // status.net, identi.ca, GNU-social, other OStatus implementations
define ( 'NETWORK_FEED',             'feed');    // RSS/Atom feeds with no known "post/notify" protocol
define ( 'NETWORK_DIASPORA',         'dspr');    // Diaspora
define ( 'NETWORK_MAIL',             'mail');    // IMAP/POP
define ( 'NETWORK_MAIL2',            'mai2');    // extended IMAP/POP
define ( 'NETWORK_FACEBOOK',         'face');    // Facebook API
define ( 'NETWORK_LINKEDIN',         'lnkd');    // LinkedIn
define ( 'NETWORK_XMPP',             'xmpp');    // XMPP
define ( 'NETWORK_MYSPACE',          'mysp');    // MySpace
define ( 'NETWORK_GPLUS',            'goog');    // Google+

define ( 'NETWORK_PHANTOM',          'unkn');    // Place holder

/**
 * These numbers are used in stored permissions
 * and existing allocations MUST NEVER BE CHANGED
 * OR RE-ASSIGNED! You may only add to them.
 */

$netgroup_ids = array(
	NETWORK_DFRN     => (-1),
	NETWORK_ZOT      => (-2),
	NETWORK_OSTATUS  => (-3),
	NETWORK_FEED     => (-4),
	NETWORK_DIASPORA => (-5),
	NETWORK_MAIL     => (-6),
	NETWORK_MAIL2    => (-7),
	NETWORK_FACEBOOK => (-8),
	NETWORK_LINKEDIN => (-9),
	NETWORK_XMPP     => (-10),
	NETWORK_MYSPACE  => (-11),
	NETWORK_GPLUS    => (-12),

	NETWORK_PHANTOM  => (-127),
);


/**
 * Maximum number of "people who like (or don't like) this"  that we will list by name
 */

define ( 'MAX_LIKERS',    75);

/**
 * Communication timeout
 */

define ( 'ZCURL_TIMEOUT' , (-1));


/**
 * email notification options
 */

define ( 'NOTIFY_INTRO',    0x0001 );
define ( 'NOTIFY_CONFIRM',  0x0002 );
define ( 'NOTIFY_WALL',     0x0004 );
define ( 'NOTIFY_COMMENT',  0x0008 );
define ( 'NOTIFY_MAIL',     0x0010 );
define ( 'NOTIFY_SUGGEST',  0x0020 );
define ( 'NOTIFY_PROFILE',  0x0040 );
define ( 'NOTIFY_TAGSELF',  0x0080 );
define ( 'NOTIFY_TAGSHARE', 0x0100 );

define ( 'NOTIFY_SYSTEM',   0x8000 );


/**
 * various namespaces we may need to parse
 */

define ( 'NAMESPACE_ZOT',             'http://purl.org/macgirvin/zot' );
define ( 'NAMESPACE_DFRN' ,           'http://purl.org/macgirvin/dfrn/1.0' );
define ( 'NAMESPACE_THREAD' ,         'http://purl.org/syndication/thread/1.0' );
define ( 'NAMESPACE_TOMB' ,           'http://purl.org/atompub/tombstones/1.0' );
define ( 'NAMESPACE_ACTIVITY',        'http://activitystrea.ms/spec/1.0/' );
define ( 'NAMESPACE_ACTIVITY_SCHEMA', 'http://activitystrea.ms/schema/1.0/' );
define ( 'NAMESPACE_MEDIA',           'http://purl.org/syndication/atommedia' );
define ( 'NAMESPACE_SALMON_ME',       'http://salmon-protocol.org/ns/magic-env' );
define ( 'NAMESPACE_OSTATUSSUB',      'http://ostatus.org/schema/1.0/subscribe' );
define ( 'NAMESPACE_GEORSS',          'http://www.georss.org/georss' );
define ( 'NAMESPACE_POCO',            'http://portablecontacts.net/spec/1.0' );
define ( 'NAMESPACE_FEED',            'http://schemas.google.com/g/2010#updates-from' );
define ( 'NAMESPACE_OSTATUS',         'http://ostatus.org/schema/1.0' );
define ( 'NAMESPACE_STATUSNET',       'http://status.net/schema/api/1/' );
define ( 'NAMESPACE_ATOM1',           'http://www.w3.org/2005/Atom' );
/**
 * activity stream defines
 */

define ( 'ACTIVITY_LIKE',        NAMESPACE_ACTIVITY_SCHEMA . 'like' );
define ( 'ACTIVITY_DISLIKE',     NAMESPACE_DFRN            . '/dislike' );
define ( 'ACTIVITY_OBJ_HEART',   NAMESPACE_DFRN            . '/heart' );

define ( 'ACTIVITY_FRIEND',      NAMESPACE_ACTIVITY_SCHEMA . 'make-friend' );
define ( 'ACTIVITY_REQ_FRIEND',  NAMESPACE_ACTIVITY_SCHEMA . 'request-friend' );
define ( 'ACTIVITY_UNFRIEND',    NAMESPACE_ACTIVITY_SCHEMA . 'remove-friend' );
define ( 'ACTIVITY_FOLLOW',      NAMESPACE_ACTIVITY_SCHEMA . 'follow' );
define ( 'ACTIVITY_UNFOLLOW',    NAMESPACE_ACTIVITY_SCHEMA . 'stop-following' );
define ( 'ACTIVITY_JOIN',        NAMESPACE_ACTIVITY_SCHEMA . 'join' );

define ( 'ACTIVITY_POST',        NAMESPACE_ACTIVITY_SCHEMA . 'post' );
define ( 'ACTIVITY_UPDATE',      NAMESPACE_ACTIVITY_SCHEMA . 'update' );
define ( 'ACTIVITY_TAG',         NAMESPACE_ACTIVITY_SCHEMA . 'tag' );
define ( 'ACTIVITY_FAVORITE',    NAMESPACE_ACTIVITY_SCHEMA . 'favorite' );

define ( 'ACTIVITY_OBJ_COMMENT', NAMESPACE_ACTIVITY_SCHEMA . 'comment' );
define ( 'ACTIVITY_OBJ_NOTE',    NAMESPACE_ACTIVITY_SCHEMA . 'note' );
define ( 'ACTIVITY_OBJ_PERSON',  NAMESPACE_ACTIVITY_SCHEMA . 'person' );
define ( 'ACTIVITY_OBJ_PHOTO',   NAMESPACE_ACTIVITY_SCHEMA . 'photo' );
define ( 'ACTIVITY_OBJ_P_PHOTO', NAMESPACE_ACTIVITY_SCHEMA . 'profile-photo' );
define ( 'ACTIVITY_OBJ_ALBUM',   NAMESPACE_ACTIVITY_SCHEMA . 'photo-album' );
define ( 'ACTIVITY_OBJ_EVENT',   NAMESPACE_ACTIVITY_SCHEMA . 'event' );
define ( 'ACTIVITY_OBJ_GROUP',   NAMESPACE_ACTIVITY_SCHEMA . 'group' );
define ( 'ACTIVITY_OBJ_TAGTERM', NAMESPACE_DFRN            . '/tagterm' );
define ( 'ACTIVITY_OBJ_PROFILE', NAMESPACE_DFRN            . '/profile' );

/**
 * item weight for query ordering
 */

define ( 'GRAVITY_PARENT',       0);
define ( 'GRAVITY_LIKE',         3);
define ( 'GRAVITY_COMMENT',      6);

/**
 *
 * Reverse the effect of magic_quotes_gpc if it is enabled.
 * Please disable magic_quotes_gpc so we don't have to do this.
 * See http://php.net/manual/en/security.magicquotes.disabling.php
 *
 */

function startup() {
	error_reporting(E_ERROR | E_WARNING | E_PARSE);
	set_time_limit(0);

	// This has to be quite large to deal with embedded private photos
	ini_set('pcre.backtrack_limit', 500000);


	if (get_magic_quotes_gpc()) {
		$process = array(&$_GET, &$_POST, &$_COOKIE, &$_REQUEST);
		while (list($key, $val) = each($process)) {
			foreach ($val as $k => $v) {
				unset($process[$key][$k]);
				if (is_array($v)) {
					$process[$key][stripslashes($k)] = $v;
					$process[] = &$process[$key][stripslashes($k)];
				} else {
					$process[$key][stripslashes($k)] = stripslashes($v);
				}
			}
		}
		unset($process);
	}

}

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
	
		public $nav_sel;

		public $category;

		private $scheme;
		private $hostname;
		private $baseurl;
		private $db;

		private $curl_code;
		private $curl_headers;

		function __construct() {

			global $default_timezone;

			$this->timezone = ((x($default_timezone)) ? $default_timezone : 'UTC');

			date_default_timezone_set($this->timezone);

			$this->config = array();
			$this->page = array();
			$this->pager= array();

			$this->query_string = '';

			startup();

			$this->scheme = 'http';
			if(x($_SERVER,'HTTPS') && $_SERVER['HTTPS'])
				$this->scheme = 'https';
			elseif(x($_SERVER,'SERVER_PORT') && (intval($_SERVER['SERVER_PORT']) == 443))
			$this->scheme = 'https';


			if(x($_SERVER,'SERVER_NAME')) {
				$this->hostname = $_SERVER['SERVER_NAME'];
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

			set_include_path(
					"include/$this->hostname" . PATH_SEPARATOR
					. 'include' . PATH_SEPARATOR
					. 'library' . PATH_SEPARATOR
					. 'library/phpsec' . PATH_SEPARATOR
					. '.' );

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
			}
			else {
				$this->argc = 1;
				$this->argv = array('home');
				$this->module = 'home';
			}

			/**
			 * Special handling for the webfinger/lrdd host XRD file
			 */

			if($this->cmd === '.well-known/host-meta') {
				$this->argc = 1;
				$this->argv = array('hostxrd');
				$this->module = 'hostxrd';
			}

			/**
			 * See if there is any page number information, and initialise
			 * pagination
			 */

			$this->pager['page'] = ((x($_GET,'page') && intval($_GET['page']) > 0) ? intval($_GET['page']) : 1);
			$this->pager['itemspage'] = 50;
			$this->pager['start'] = ($this->pager['page'] * $this->pager['itemspage']) - $this->pager['itemspage'];
			$this->pager['total'] = 0;
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

				$this->hostname = $parsed['host'];
				if(x($parsed,'port'))
					$this->hostname .= ':' . $parsed['port'];
				if(x($parsed,'path'))
					$this->path = trim($parsed['path'],'\\/');
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

		function init_pagehead() {
			$interval = ((local_user()) ? get_pconfig(local_user(),'system','update_interval') : 40000);
			if($interval < 10000)
				$interval = 40000;

			$this->page['title'] = $this->config['sitename'];
			$tpl = file_get_contents('view/head.tpl');
			$this->page['htmlhead'] = replace_macros($tpl,array(
				'$baseurl' => $this->get_baseurl(), // FIXME for z_path!!!!
				'$local_user' => local_user(),
				'$generator' => 'Friendica' . ' ' . FRIENDICA_VERSION,
				'$delitem' => t('Delete this item?'),
				'$comment' => t('Comment'),
				'$showmore' => t('show more'),
				'$showfewer' => t('show fewer'),
				'$update_interval' => $interval
			));
		}

		function set_curl_code($code) {
			$this->curl_code = $code;
		}

		function get_curl_code() {
			return $this->curl_code;
		}

		function set_curl_headers($headers) {
			$this->curl_headers = $headers;
		}

		function get_curl_headers() {
			return $this->curl_headers;
		}


	}
}

// retrieve the App structure
// useful in functions which require it but don't get it passed to them

if(! function_exists('get_app')) {
	function get_app() {
		global $a;
		return $a;
	}
};


// Multi-purpose function to check variable state.
// Usage: x($var) or $x($array,'key')
// returns false if variable/key is not set
// if variable is set, returns 1 if has 'non-zero' value, otherwise returns 0.
// e.g. x('') or x(0) returns 0;

if(! function_exists('x')) {
	function x($s,$k = NULL) {
		if($k != NULL) {
			if((is_array($s)) && (array_key_exists($k,$s))) {
				if($s[$k])
					return (int) 1;
				return (int) 0;
		}
			return false;
		}
		else {
			if(isset($s)) {
				if($s) {
					return (int) 1;
				}
				return (int) 0;
			}
			return false;
		}
	}
}

// called from db initialisation if db is dead.

if(! function_exists('system_unavailable')) {
	function system_unavailable() {
		include('system_unavailable.php');
		system_down();
		killme();
	}
}



function clean_urls() {
	global $a;
	//	if($a->config['system']['clean_urls'])
	return true;
	//	return false;
}

function z_path() {
	global $a;
	$base = $a->get_baseurl();
	if(! clean_urls())
		$base .= '/?q=';
	return $base;
}

function z_root() {
	global $a;
	return $a->get_baseurl();
}

function absurl($path) {
	if(strpos($path,'/') === 0)
		return z_path() . $path;
	return $path;
}

function is_ajax() {
	return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');
}


// Primarily involved with database upgrade, but also sets the
// base url for use in cmdline programs which don't have
// $_SERVER variables, and synchronising the state of installed plugins.


if(! function_exists('check_config')) {
	function check_config(&$a) {

		$build = get_config('system','build');
		if(! x($build))
			$build = set_config('system','build',DB_UPDATE_VERSION);

		$url = get_config('system','url');

		// if the url isn't set or the stored url is radically different
		// than the currently visited url, store the current value accordingly.
		// "Radically different" ignores common variations such as http vs https
		// and www.example.com vs example.com.

		if((! x($url)) || (! link_compare($url,$a->get_baseurl())))
			$url = set_config('system','url',$a->get_baseurl());

		if($build != DB_UPDATE_VERSION) {
			$stored = intval($build);
			$current = intval(DB_UPDATE_VERSION);
			if(($stored < $current) && file_exists('update.php')) {

				load_config('database');

				// We're reporting a different version than what is currently installed.
				// Run any existing update scripts to bring the database up to current.

				require_once('update.php');

				// make sure that boot.php and update.php are the same release, we might be
				// updating right this very second and the correct version of the update.php
				// file may not be here yet. This can happen on a very busy site.

				if(DB_UPDATE_VERSION == UPDATE_VERSION) {

					for($x = $stored; $x < $current; $x ++) {
						if(function_exists('update_' . $x)) {

							// There could be a lot of processes running or about to run.
							// We want exactly one process to run the update command.
							// So store the fact that we're taking responsibility
							// after first checking to see if somebody else already has.

							// If the update fails or times-out completely you may need to
							// delete the config entry to try again.

							if(get_config('database','update_' . $x))
								break;
							set_config('database','update_' . $x, '1');

							// call the specific update

							$func = 'update_' . $x;
							$retval = $func();
							if($retval) {
								//send the administrator an e-mail
								$email_tpl = get_intltext_template("update_fail_eml.tpl");
								$email_msg = replace_macros($email_tpl, array(
									'$sitename' => $a->config['sitename'],
									'$siteurl' =>  $a->get_baseurl(),
									'$update' => $x,
									'$error' => sprintf( t('Update %s failed. See error logs.'), $x)
								));
								$subject=sprintf(t('Update Error at %s'), $a->get_baseurl());
									
								mail($a->config['admin_email'], $subject, $email_msg,
									'From: ' . t('Administrator') . '@' . $_SERVER['SERVER_NAME'] . "\n"
									. 'Content-type: text/plain; charset=UTF-8' . "\n"
									. 'Content-transfer-encoding: 8bit' );
								//try the logger
								logger('CRITICAL: Update Failed: '. $x);
							}
							else
								set_config('database','update_' . $x, 'success');
								
						}
					}
					set_config('system','build', DB_UPDATE_VERSION);
				}
			}
		}

		/**
		 *
		 * Synchronise plugins:
		 *
		 * $a->config['system']['addon'] contains a comma-separated list of names
		 * of plugins/addons which are used on this system.
		 * Go through the database list of already installed addons, and if we have
		 * an entry, but it isn't in the config list, call the uninstall procedure
		 * and mark it uninstalled in the database (for now we'll remove it).
		 * Then go through the config list and if we have a plugin that isn't installed,
		 * call the install procedure and add it to the database.
		 *
		 */

		$r = q("SELECT * FROM `addon` WHERE `installed` = 1");
		if(count($r))
			$installed = $r;
		else
			$installed = array();

		$plugins = get_config('system','addon');
		$plugins_arr = array();

		if($plugins)
			$plugins_arr = explode(',',str_replace(' ', '',$plugins));

		$a->plugins = $plugins_arr;

		$installed_arr = array();

		if(count($installed)) {
			foreach($installed as $i) {
				if(! in_array($i['name'],$plugins_arr)) {
					uninstall_plugin($i['name']);
				}
				else {
					$installed_arr[] = $i['name'];
				}
			}
		}

		if(count($plugins_arr)) {
			foreach($plugins_arr as $p) {
				if(! in_array($p,$installed_arr)) {
					install_plugin($p);
				}
			}
		}


		load_hooks();

		return;
	}
}


function get_guid($size=16) {
	$exists = true; // assume by default that we don't have a unique guid
	do {
		$s = random_string($size);
		$r = q("select id from guid where guid = '%s' limit 1", dbesc($s));
		if(! count($r))
			$exists = false;
	} while($exists);
	q("insert into guid ( guid ) values ( '%s' ) ", dbesc($s));
	return $s;
}


// wrapper for adding a login box. If $register == true provide a registration
// link. This will most always depend on the value of $a->config['register_policy'].
// returns the complete html for inserting into the page

if(! function_exists('login')) {
	function login($register = false, $hiddens=false) {
		$a = get_app();
		$o = "";
		$reg = false;
		if ($register) {
			$reg = array(
				'title' => t('Create a New Account'),
				'desc' => t('Register')
			);
		}

		$noid = get_config('system','no_openid');
	
		$dest_url = $a->get_baseurl(true) . '/' . $a->query_string;

		if(local_user()) {
			$tpl = get_markup_template("logout.tpl");
		}
		else {
			$tpl = get_markup_template("login.tpl");
			$_SESSION['return_url'] = $a->query_string;
		}


		$o .= replace_macros($tpl,array(

			'$dest_url'     => $dest_url,
			'$logout'       => t('Logout'),
			'$login'        => t('Login'),
	
			'$lname'	 	=> array('username', t('Nickname or Email address: ') , '', ''),
			'$lpassword' 	=> array('password', t('Password: '), '', ''),
	
			'$openid'		=> !$noid,
			'$lopenid'      => array('openid_url', t('Or login using OpenID: '),'',''),
	
			'$hiddens'      => $hiddens,
	
			'$register'     => $reg,
	
			'$lostpass'     => t('Forgot your password?'),
			'$lostlink'     => t('Password Reset'),
		));

		call_hooks('login_hook',$o);

		return $o;
	}
}

// Used to end the current process, after saving session state.

if(! function_exists('killme')) {
	function killme() {
		session_write_close();
		exit;
	}
}

// redirect to another URL and terminate this process.

if(! function_exists('goaway')) {
	function goaway($s) {
		header("Location: $s");
		killme();
	}
}


// Returns the uid of locally logged in user or false.

if(! function_exists('local_user')) {
	function local_user() {
		if((x($_SESSION,'authenticated')) && (x($_SESSION,'uid')))
			return intval($_SESSION['uid']);
		return false;
	}
}

// Returns contact id of authenticated site visitor or false

if(! function_exists('remote_user')) {
	function remote_user() {
		if((x($_SESSION,'authenticated')) && (x($_SESSION,'visitor_id')))
			return intval($_SESSION['visitor_id']);
		return false;
	}
}

// contents of $s are displayed prominently on the page the next time
// a page is loaded. Usually used for errors or alerts.

if(! function_exists('notice')) {
	function notice($s) {
		$a = get_app();
		if(! x($_SESSION,'sysmsg'))	$_SESSION['sysmsg'] = array();
		if($a->interactive)
			$_SESSION['sysmsg'][] = $s;
	}
}
if(! function_exists('info')) {
	function info($s) {
		$a = get_app();
		if(! x($_SESSION,'sysmsg_info')) $_SESSION['sysmsg_info'] = array();
		if($a->interactive)
			$_SESSION['sysmsg_info'][] = $s;
	}
}


// wrapper around config to limit the text length of an incoming message

if(! function_exists('get_max_import_size')) {
	function get_max_import_size() {
		global $a;
		return ((x($a->config,'max_import_size')) ? $a->config['max_import_size'] : 0 );
	}
}



/**
 *
 * Function : profile_load
 * @parameter App    $a
 * @parameter string $nickname
 * @parameter int    $profile
 *
 * Summary: Loads a profile into the page sidebar.
 * The function requires a writeable copy of the main App structure, and the nickname
 * of a registered local account.
 *
 * If the viewer is an authenticated remote viewer, the profile displayed is the
 * one that has been configured for his/her viewing in the Contact manager.
 * Passing a non-zero profile ID can also allow a preview of a selected profile
 * by the owner.
 *
 * Profile information is placed in the App structure for later retrieval.
 * Honours the owner's chosen theme for display.
 *
 */

if(! function_exists('profile_load')) {
	function profile_load(&$a, $nickname, $profile = 0) {
		if(remote_user()) {
			$r = q("SELECT `profile-id` FROM `contact` WHERE `id` = %d LIMIT 1",
					intval($_SESSION['visitor_id']));
			if(count($r))
				$profile = $r[0]['profile-id'];
		}

		$r = null;
                          
		if($profile) {
			$profile_int = intval($profile);
			$r = q("SELECT `profile`.`uid` AS `profile_uid`, `profile`.* , `contact`.`avatar-date` AS picdate, `user`.* FROM `profile`
					left join `contact` on `contact`.`uid` = `profile`.`uid` LEFT JOIN `user` ON `profile`.`uid` = `user`.`uid`
					WHERE `user`.`nickname` = '%s' AND `profile`.`id` = %d and `contact`.`self` = 1 LIMIT 1",
					dbesc($nickname),
					intval($profile_int)
			);
		}
		if((! $r) && (!  count($r))) {
			$r = q("SELECT `profile`.`uid` AS `profile_uid`, `profile`.* , `contact`.`avatar-date` AS picdate, `user`.* FROM `profile`
					left join `contact` on `contact`.`uid` = `profile`.`uid` LEFT JOIN `user` ON `profile`.`uid` = `user`.`uid`
					WHERE `user`.`nickname` = '%s' AND `profile`.`is-default` = 1 and `contact`.`self` = 1 LIMIT 1",
					dbesc($nickname)
			);
		}

		if(($r === false) || (! count($r))) {
			logger('profile error: ' . $a->query_string, LOGGER_DEBUG);
			notice( t('Requested profile is not available.') . EOL );
			$a->error = 404;
			return;
		}
	
		// fetch user tags if this isn't the default profile

		if(! $r[0]['is-default']) {
			$x = q("select `pub_keywords` from `profile` where uid = %d and `is-default` = 1 limit 1",
					intval($profile_uid)
			);
			if($x && count($x))
				$r[0]['pub_keywords'] = $x[0]['pub_keywords'];
		}

		$a->profile = $r[0];


		$a->page['title'] = $a->profile['name'] . " @ " . $a->config['sitename'];
		$_SESSION['theme'] = $a->profile['theme'];

		/**
		 * load/reload current theme info
		 */

		$theme_info_file = "view/theme/".current_theme()."/theme.php";
		if (file_exists($theme_info_file)){
			require_once($theme_info_file);
		}

		if(! (x($a->page,'aside')))
			$a->page['aside'] = '';

		if(local_user() && local_user() == $a->profile['uid']) {
			$a->page['aside'] .= replace_macros(get_markup_template('profile_edlink.tpl'),array(
				'$editprofile' => t('Edit profile'),
				'$profid' => $a->profile['id']
			));
		}

		$block = (((get_config('system','block_public')) && (! local_user()) && (! remote_user())) ? true : false);

		$a->page['aside'] .= profile_sidebar($a->profile, $block);

		/*if(! $block)
		 $a->page['aside'] .= contact_block();*/

		return;
	}
}


/**
 *
 * Function: profile_sidebar
 *
 * Formats a profile for display in the sidebar.
 * It is very difficult to templatise the HTML completely
 * because of all the conditional logic.
 *
 * @parameter: array $profile
 *
 * Returns HTML string stuitable for sidebar inclusion
 * Exceptions: Returns empty string if passed $profile is wrong type or not populated
 *
 */


if(! function_exists('profile_sidebar')) {
	function profile_sidebar($profile, $block = 0) {

		$a = get_app();

		$o = '';
		$location = false;
		$address = false;
		$pdesc = true;

		if((! is_array($profile)) && (! count($profile)))
			return $o;

		$profile['picdate'] = urlencode($profile['picdate']);

		call_hooks('profile_sidebar_enter', $profile);

	
		// don't show connect link to yourself
		$connect = (($profile['uid'] != local_user()) ? t('Connect')  : False);

		// don't show connect link to authenticated visitors either

		if((remote_user()) && ($_SESSION['visitor_visiting'] == $profile['uid']))
			$connect = False;

		if(get_my_url() && $profile['unkmail'])
			$wallmessage = t('Message');
		else
			$wallmessage = false;



		// show edit profile to yourself
		if ($profile['uid'] == local_user()) {
			$profile['edit'] = array($a->get_baseurl(). '/profiles', t('Profiles'),"", t('Manage/edit profiles'));
		
			$r = q("SELECT * FROM `profile` WHERE `uid` = %d",
					local_user());
		
			$profile['menu'] = array(
				'chg_photo' => t('Change profile photo'),
				'cr_new' => t('Create New Profile'),
				'entries' => array(),
			);

			if(count($r)) {

				foreach($r as $rr) {
					$profile['menu']['entries'][] = array(
						'photo' => $rr['thumb'],
						'id' => $rr['id'],
						'alt' => t('Profile Image'),
						'profile_name' => $rr['profile-name'],
						'isdefault' => $rr['is-default'],
						'visibile_to_everybody' =>  t('visible to everybody'),
						'edit_visibility' => t('Edit visibility'),

					);
				}


			}


		}



	
		if((x($profile,'address') == 1)
				|| (x($profile,'locality') == 1)
				|| (x($profile,'region') == 1)
				|| (x($profile,'postal-code') == 1)
				|| (x($profile,'country-name') == 1))
			$location = t('Location:');

		$gender = ((x($profile,'gender') == 1) ? t('Gender:') : False);


		$marital = ((x($profile,'marital') == 1) ?  t('Status:') : False);

		$homepage = ((x($profile,'homepage') == 1) ?  t('Homepage:') : False);

		if(($profile['hidewall'] || $block) && (! local_user()) && (! remote_user())) {
			$location = $pdesc = $gender = $marital = $homepage = False;
		}

		$firstname = ((strpos($profile['name'],' '))
				? trim(substr($profile['name'],0,strpos($profile['name'],' '))) : $profile['name']);
		$lastname = (($firstname === $profile['name']) ? '' : trim(substr($profile['name'],strlen($firstname))));

		$diaspora = array(
			'podloc' => $a->get_baseurl(),
			'searchable' => (($profile['publish'] && $profile['net-publish']) ? 'true' : 'false' ),
			'nickname' => $profile['nickname'],
			'fullname' => $profile['name'],
			'firstname' => $firstname,
			'lastname' => $lastname,
			'photo300' => $a->get_baseurl() . '/photo/custom/300/' . $profile['uid'] . '.jpg',
			'photo100' => $a->get_baseurl() . '/photo/custom/100/' . $profile['uid'] . '.jpg',
			'photo50' => $a->get_baseurl() . '/photo/custom/50/'  . $profile['uid'] . '.jpg',
		);

		if (!$block){
			$contact_block = contact_block();
		}


		$tpl = get_markup_template('profile_vcard.tpl');

		$o .= replace_macros($tpl, array(
			'$profile' => $profile,
			'$connect'  => $connect,
			'$wallmessage' => $wallmessage,
			'$location' => template_escape($location),
			'$gender'   => $gender,
			'$pdesc'	=> $pdesc,
			'$marital'  => $marital,
			'$homepage' => $homepage,
			'$diaspora' => $diaspora,
			'$contact_block' => $contact_block,
		));


		$arr = array('profile' => &$profile, 'entry' => &$o);

		call_hooks('profile_sidebar', $arr);

		return $o;
	}
}


if(! function_exists('get_birthdays')) {
	function get_birthdays() {

		$a = get_app();
		$o = '';

		if(! local_user())
			return $o;

		$bd_format = t('g A l F d') ; // 8 AM Friday January 18
		$bd_short = t('F d');

		$r = q("SELECT `event`.*, `event`.`id` AS `eid`, `contact`.* FROM `event`
				LEFT JOIN `contact` ON `contact`.`id` = `event`.`cid`
				WHERE `event`.`uid` = %d AND `type` = 'birthday' AND `start` < '%s' AND `finish` > '%s'
				ORDER BY `start` ASC ",
				intval(local_user()),
				dbesc(datetime_convert('UTC','UTC','now + 6 days')),
				dbesc(datetime_convert('UTC','UTC','now'))
		);

		if($r && count($r)) {
			$total = 0;
			$now = strtotime('now');
			$cids = array();

			$istoday = false;
			foreach($r as $rr) {
				if(strlen($rr['name']))
					$total ++;
				if((strtotime($rr['start'] . ' +00:00') < $now) && (strtotime($rr['finish'] . ' +00:00') > $now))
					$istoday = true;
			}
			$classtoday = $istoday ? ' birthday-today ' : '';
			if($total) {
				foreach($r as &$rr) {
					if(! strlen($rr['name']))
						continue;

					// avoid duplicates

					if(in_array($rr['cid'],$cids))
						continue;
					$cids[] = $rr['cid'];

					$today = (((strtotime($rr['start'] . ' +00:00') < $now) && (strtotime($rr['finish'] . ' +00:00') > $now)) ? true : false);
					$sparkle = '';
					$url = $rr['url'];
					if($rr['network'] === NETWORK_DFRN) {
						$sparkle = " sparkle";
						$url = $a->get_baseurl() . '/redir/'  . $rr['cid'];
					}
	
					$rr['link'] = $url;
					$rr['title'] = $rr['name'];
					$rr['date'] = day_translate(datetime_convert('UTC', $a->timezone, $rr['start'], $rr['adjust'] ? $bd_format : $bd_short)) . (($today) ?  ' ' . t('[today]') : '');
					$rr['startime'] = Null;
					$rr['today'] = $today;
	
				}
			}
		}
		$tpl = get_markup_template("birthdays_reminder.tpl");
		return replace_macros($tpl, array(
			'$baseurl' => $a->get_baseurl(),
			'$classtoday' => $classtoday,
			'$count' => $total,
			'$event_reminders' => t('Birthday Reminders'),
			'$event_title' => t('Birthdays this week:'),
			'$events' => $r,
		));
	}
}


if(! function_exists('get_events')) {
	function get_events() {

		require_once('include/bbcode.php');

		$a = get_app();

		if(! local_user())
			return $o;

		$bd_format = t('g A l F d') ; // 8 AM Friday January 18
		$bd_short = t('F d');

		$r = q("SELECT `event`.* FROM `event`
				WHERE `event`.`uid` = %d AND `type` != 'birthday' AND `start` < '%s' AND `start` > '%s'
				ORDER BY `start` ASC ",
				intval(local_user()),
				dbesc(datetime_convert('UTC','UTC','now + 6 days')),
				dbesc(datetime_convert('UTC','UTC','now - 1 days'))
		);

		if($r && count($r)) {
			$now = strtotime('now');
			$istoday = false;
			foreach($r as $rr) {
				if(strlen($rr['name']))
					$total ++;

				$strt = datetime_convert('UTC',$rr['convert'] ? $a->timezone : 'UTC',$rr['start'],'Y-m-d');
				if($strt === datetime_convert('UTC',$a->timezone,'now','Y-m-d'))
					$istoday = true;
			}
			$classtoday = (($istoday) ? 'event-today' : '');


			foreach($r as &$rr) {
				if($rr['adjust'])
					$md = datetime_convert('UTC',$a->timezone,$rr['start'],'Y/m');
				else
					$md = datetime_convert('UTC','UTC',$rr['start'],'Y/m');
				$md .= "/#link-".$rr['id'];

				$title = substr(strip_tags(bbcode($rr['desc'])),0,32) . '... ';
				if(! $title)
					$title = t('[No description]');

				$strt = datetime_convert('UTC',$rr['convert'] ? $a->timezone : 'UTC',$rr['start']);
				$today = ((substr($strt,0,10) === datetime_convert('UTC',$a->timezone,'now','Y-m-d')) ? true : false);
				
				$rr['link'] = $md;
				$rr['title'] = $title;
				$rr['date'] = day_translate(datetime_convert('UTC', $rr['adjust'] ? $a->timezone : 'UTC', $rr['start'], $bd_format)) . (($today) ?  ' ' . t('[today]') : '');
				$rr['startime'] = $strt;
				$rr['today'] = $today;
			}
		}

		$tpl = get_markup_template("events_reminder.tpl");
		return replace_macros($tpl, array(
			'$baseurl' => $a->get_baseurl(),
			'$classtoday' => $classtoday,
			'$count' => count($r),
			'$event_reminders' => t('Event Reminders'),
			'$event_title' => t('Events this week:'),
			'$events' => $r,
		));
	}
}


/**
 *
 * Wrap calls to proc_close(proc_open()) and call hook
 * so plugins can take part in process :)
 *
 * args:
 * $cmd program to run
 *  next args are passed as $cmd command line
 *
 * e.g.: proc_run("ls","-la","/tmp");
 *
 * $cmd and string args are surrounded with ""
 */

if(! function_exists('proc_run')) {
	function proc_run($cmd){

		$a = get_app();

		$args = func_get_args();

		$newargs = array();
		if(! count($args))
			return;

		// expand any arrays

		foreach($args as $arg) {
			if(is_array($arg)) {
				foreach($arg as $n) {
					$newargs[] = $n;
				}
			}
			else
				$newargs[] = $arg;
		}

		$args = $newargs;
		
		$arr = array('args' => $args, 'run_cmd' => true);

		call_hooks("proc_run", $arr);
		if(! $arr['run_cmd'])
			return;

		if(count($args) && $args[0] === 'php')
			$args[0] = ((x($a->config,'php_path')) && (strlen($a->config['php_path'])) ? $a->config['php_path'] : 'php');
		foreach ($args as $arg){
			$arg = escapeshellarg($arg);
		}
		$cmdline = implode($args," ");
		proc_close(proc_open($cmdline." &",array(),$foo));
	}
}

if(! function_exists('current_theme')) {
	function current_theme(){
		$app_base_themes = array('duepuntozero', 'loozah');
	
		$a = get_app();
	
		$system_theme = ((isset($a->config['system']['theme'])) ? $a->config['system']['theme'] : '');
		$theme_name = ((isset($_SESSION) && x($_SESSION,'theme')) ? $_SESSION['theme'] : $system_theme);
	
		if($theme_name &&
				(file_exists('view/theme/' . $theme_name . '/style.css') ||
						file_exists('view/theme/' . $theme_name . '/style.php')))
			return($theme_name);
	
		foreach($app_base_themes as $t) {
			if(file_exists('view/theme/' . $t . '/style.css')||
					file_exists('view/theme/' . $t . '/style.php'))
				return($t);
		}
	
		$fallback = glob('view/theme/*/style.[css|php]');
		if(count($fallback))
			return (str_replace('view/theme/','', substr($fallback[0],0,-10)));
	
	}
}

/*
 * Return full URL to theme which is currently in effect.
* Provide a sane default if nothing is chosen or the specified theme does not exist.
*/
if(! function_exists('current_theme_url')) {
	function current_theme_url() {
		global $a;
		$t = current_theme();
		if (file_exists('view/theme/' . $t . '/style.php'))
			return($a->get_baseurl() . '/view/theme/' . $t . '/style.pcss');
		return($a->get_baseurl() . '/view/theme/' . $t . '/style.css');
	}
}

if(! function_exists('feed_birthday')) {
	function feed_birthday($uid,$tz) {

		/**
		 *
		 * Determine the next birthday, but only if the birthday is published
		 * in the default profile. We _could_ also look for a private profile that the
		 * recipient can see, but somebody could get mad at us if they start getting
		 * public birthday greetings when they haven't made this info public.
		 *
		 * Assuming we are able to publish this info, we are then going to convert
		 * the start time from the owner's timezone to UTC.
		 *
		 * This will potentially solve the problem found with some social networks
		 * where birthdays are converted to the viewer's timezone and salutations from
		 * elsewhere in the world show up on the wrong day. We will convert it to the
		 * viewer's timezone also, but first we are going to convert it from the birthday
		 * person's timezone to GMT - so the viewer may find the birthday starting at
		 * 6:00PM the day before, but that will correspond to midnight to the birthday person.
		 *
		 */

	
		$birthday = '';

		if(! strlen($tz))
			$tz = 'UTC';

		$p = q("SELECT `dob` FROM `profile` WHERE `is-default` = 1 AND `uid` = %d LIMIT 1",
				intval($uid)
		);

		if($p && count($p)) {
			$tmp_dob = substr($p[0]['dob'],5);
			if(intval($tmp_dob)) {
				$y = datetime_convert($tz,$tz,'now','Y');
				$bd = $y . '-' . $tmp_dob . ' 00:00';
				$t_dob = strtotime($bd);
				$now = strtotime(datetime_convert($tz,$tz,'now'));
				if($t_dob < $now)
					$bd = $y + 1 . '-' . $tmp_dob . ' 00:00';
				$birthday = datetime_convert($tz,'UTC',$bd,ATOM_TIME);
			}
		}

		return $birthday;
	}
}

if(! function_exists('is_site_admin')) {
	function is_site_admin() {
		$a = get_app();
		if(local_user() && x($a->user,'email') && x($a->config,'admin_email') && ($a->user['email'] === $a->config['admin_email']))
			return true;
		return false;
	}
}


if(! function_exists('load_contact_links')) {
	function load_contact_links($uid) {

		$a = get_app();

		$ret = array();

		if(! $uid || x($a->contacts,'empty'))
			return;

		$r = q("SELECT `id`,`network`,`url`,`thumb` FROM `contact` WHERE `uid` = %d AND `self` = 0 AND `blocked` = 0 ",
				intval($uid)
		);
		if(count($r)) {
			foreach($r as $rr){
				$url = normalise_link($rr['url']);
				$ret[$url] = $rr;
			}
		}
		else
			$ret['empty'] = true;
		$a->contacts = $ret;
		return;
	}
}

if(! function_exists('profile_tabs')){
	function profile_tabs($a, $is_owner=False, $nickname=Null){
		//echo "<pre>"; var_dump($a->user); killme();
	
		if (is_null($nickname))
			$nickname  = $a->user['nickname'];
		
		if(x($_GET,'tab'))
			$tab = notags(trim($_GET['tab']));
	
		$url = $a->get_baseurl() . '/profile/' . $nickname;

		$tabs = array(
			array(
				'label'=>t('Status'),
				'url' => $url,
				'sel' => ((!isset($tab)&&$a->argv[0]=='profile')?'active':''),
				'title' => t('Status Messages and Posts'),
			),
			array(
				'label' => t('Profile'),
				'url' 	=> $url.'/?tab=profile',
				'sel'	=> ((isset($tab) && $tab=='profile')?'active':''),
				'title' => t('Profile Details'),
			),
			array(
				'label' => t('Photos'),
				'url'	=> $a->get_baseurl() . '/photos/' . $nickname,
				'sel'	=> ((!isset($tab)&&$a->argv[0]=='photos')?'active':''),
				'title' => t('Photo Albums'),
			),
		);
	
		if ($is_owner){
			$tabs[] = array(
				'label' => t('Events'),
				'url'	=> $a->get_baseurl() . '/events',
				'sel' 	=>((!isset($tab)&&$a->argv[0]=='events')?'active':''),
				'title' => t('Events and Calendar'),
			);
			$tabs[] = array(
				'label' => t('Personal Notes'),
				'url'	=> $a->get_baseurl() . '/notes',
				'sel' 	=>((!isset($tab)&&$a->argv[0]=='notes')?'active':''),
				'title' => t('Only You Can See This'),
			);
		}


		$arr = array('is_owner' => $is_owner, 'nickname' => $nickname, 'tab' => (($tab) ? $tab : false), 'tabs' => $tabs);
		call_hooks('profile_tabs', $arr);
	
		$tpl = get_markup_template('common_tabs.tpl');

		return replace_macros($tpl,array('$tabs' => $arr['tabs']));
	}
}

function get_my_url() {
	if(x($_SESSION,'my_url'))
		return $_SESSION['my_url'];
	return false;
}

function zrl_init(&$a) {
	$tmp_str = get_my_url();
	if(validate_url($tmp_str)) {
		proc_run('php','include/gprobe.php',bin2hex($tmp_str));
		$arr = array('zrl' => $tmp_str, 'url' => $a->cmd);
		call_hooks('zrl_init',$arr);
	}
}

function zrl($s,$force = false) {
	if(! strlen($s))
		return $s;
	if((! strpos($s,'/profile/')) && (! $force))
		return $s;
	if($force && substr($s,-1,1) !== '/')
		$s = $s . '/';
	$achar = strpos($s,'?') ? '&' : '?';
	$mine = get_my_url();
	if($mine and ! link_compare($mine,$s))
		return $s . $achar . 'zrl=' . urlencode($mine);
	return $s;
}

/**
* returns querystring as string from a mapped array
*
* @param params Array 
* @return string
*/
function build_querystring($params, $name=null) { 
    $ret = ""; 
    foreach($params as $key=>$val) {
        if(is_array($val)) { 
            if($name==null) {
                $ret .= build_querystring($val, $key); 
            } else {
                $ret .= build_querystring($val, $name."[$key]");    
            }
        } else {
            $val = urlencode($val);
            if($name!=null) {
                $ret.=$name."[$key]"."=$val&"; 
            } else {
                $ret.= "$key=$val&"; 
            }
        } 
    } 
    return $ret;    
}
