<?php

/** @file boot.php
 *
 * This file defines some global constants and includes the central App class.
 */

/**
 * Friendica
 *
 * Friendica is a communications platform for integrated social communications
 * utilising decentralised communications and linkage to several indie social
 * projects - as well as popular mainstream providers.
 *
 * Our mission is to free our friends and families from the clutches of
 * data-harvesting corporations, and pave the way to a future where social
 * communications are free and open and flow between alternate providers as
 * easily as email does today.
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

use Friendica\App;
use Friendica\Core\Addon;
use Friendica\Core\Cache;
use Friendica\Core\Config;
use Friendica\Core\PConfig;
use Friendica\Core\System;
use Friendica\Core\Worker;
use Friendica\Database\DBM;
use Friendica\Model\Contact;
use Friendica\Database\DBStructure;
use Friendica\Module\Login;

require_once 'include/network.php';
require_once 'include/text.php';
require_once 'include/datetime.php';
require_once 'include/pgettext.php';

define('FRIENDICA_PLATFORM',     'Friendica');
define('FRIENDICA_CODENAME',     'Asparagus');
define('FRIENDICA_VERSION',      '3.6-dev');
define('DFRN_PROTOCOL_VERSION',  '2.23');
define('DB_UPDATE_VERSION',      1242);
define('NEW_UPDATE_ROUTINE_VERSION', 1170);

/**
 * @brief Constants for the database update check
 */
const DB_UPDATE_NOT_CHECKED = 0; // Database check wasn't executed before
const DB_UPDATE_SUCCESSFUL = 1;  // Database check was successful
const DB_UPDATE_FAILED = 2;      // Database check failed

/**
 * @brief Constant with a HTML line break.
 *
 * Contains a HTML line break (br) element and a real carriage return with line
 * feed for the source.
 * This can be used in HTML and JavaScript where needed a line break.
 */
define('EOL',                    "<br />\r\n");
define('ATOM_TIME',              'Y-m-d\TH:i:s\Z');

/**
 * @brief Image storage quality.
 *
 * Lower numbers save space at cost of image detail.
 * For ease of upgrade, please do not change here. Change jpeg quality with
 * $a->config['system']['jpeg_quality'] = n;
 * in .htconfig.php, where n is netween 1 and 100, and with very poor results
 * below about 50
 */
define('JPEG_QUALITY',            100);

/**
 * $a->config['system']['png_quality'] from 0 (uncompressed) to 9
 */
define('PNG_QUALITY',             8);

/**
 * An alternate way of limiting picture upload sizes. Specify the maximum pixel
 * length that pictures are allowed to be (for non-square pictures, it will apply
 * to the longest side). Pictures longer than this length will be resized to be
 * this length (on the longest side, the other side will be scaled appropriately).
 * Modify this value using
 *
 *    $a->config['system']['max_image_length'] = n;
 *
 * in .htconfig.php
 *
 * If you don't want to set a maximum length, set to -1. The default value is
 * defined by 'MAX_IMAGE_LENGTH' below.
 */
define('MAX_IMAGE_LENGTH',        -1);

/**
 * Not yet used
 */
define('DEFAULT_DB_ENGINE',  'InnoDB');

/**
 * @name SSL Policy
 *
 * SSL redirection policies
 * @{
 */
define('SSL_POLICY_NONE',         0);
define('SSL_POLICY_FULL',         1);
define('SSL_POLICY_SELFSIGN',     2);
/* @}*/

/**
 * @name Logger
 *
 * log levels
 * @{
 */
define('LOGGER_NORMAL',          0);
define('LOGGER_TRACE',           1);
define('LOGGER_DEBUG',           2);
define('LOGGER_DATA',            3);
define('LOGGER_ALL',             4);
/* @}*/

/**
 * @name Cache
 *
 * Cache levels
 * @{
 */
define('CACHE_MONTH',            0);
define('CACHE_WEEK',             1);
define('CACHE_DAY',              2);
define('CACHE_HOUR',             3);
define('CACHE_HALF_HOUR',        4);
define('CACHE_QUARTER_HOUR',     5);
define('CACHE_FIVE_MINUTES',     6);
define('CACHE_MINUTE',           7);
/* @}*/

/**
 * @name Register
 *
 * Registration policies
 * @{
 */
define('REGISTER_CLOSED',        0);
define('REGISTER_APPROVE',       1);
define('REGISTER_OPEN',          2);
/**
 * @}
*/

/**
 * @name Contact_is
 *
 * Relationship types
 * @{
 */
define('CONTACT_IS_FOLLOWER', 1);
define('CONTACT_IS_SHARING',  2);
define('CONTACT_IS_FRIEND',   3);
/**
 *  @}
 */

/**
 * @name Update
 *
 * DB update return values
 * @{
 */
define('UPDATE_SUCCESS', 0);
define('UPDATE_FAILED',  1);
/**
 * @}
 */

/**
 * @name page/profile types
 *
 * PAGE_NORMAL is a typical personal profile account
 * PAGE_SOAPBOX automatically approves all friend requests as CONTACT_IS_SHARING, (readonly)
 * PAGE_COMMUNITY automatically approves all friend requests as CONTACT_IS_SHARING, but with
 *      write access to wall and comments (no email and not included in page owner's ACL lists)
 * PAGE_FREELOVE automatically approves all friend requests as full friends (CONTACT_IS_FRIEND).
 *
 * @{
 */
define('PAGE_NORMAL',            0);
define('PAGE_SOAPBOX',           1);
define('PAGE_COMMUNITY',         2);
define('PAGE_FREELOVE',          3);
define('PAGE_BLOG',              4);
define('PAGE_PRVGROUP',          5);
/**
 * @}
 */

/**
 * @name account types
 *
 * ACCOUNT_TYPE_PERSON - the account belongs to a person
 *	Associated page types: PAGE_NORMAL, PAGE_SOAPBOX, PAGE_FREELOVE
 *
 * ACCOUNT_TYPE_ORGANISATION - the account belongs to an organisation
 *	Associated page type: PAGE_SOAPBOX
 *
 * ACCOUNT_TYPE_NEWS - the account is a news reflector
 *	Associated page type: PAGE_SOAPBOX
 *
 * ACCOUNT_TYPE_COMMUNITY - the account is community forum
 *	Associated page types: PAGE_COMMUNITY, PAGE_PRVGROUP
 * @{
 */
define('ACCOUNT_TYPE_PERSON',      0);
define('ACCOUNT_TYPE_ORGANISATION', 1);
define('ACCOUNT_TYPE_NEWS',        2);
define('ACCOUNT_TYPE_COMMUNITY',   3);
/**
 * @}
 */

/**
 * @name CP
 *
 * Type of the community page
 * @{
 */
define('CP_NO_COMMUNITY_PAGE',  -1);
define('CP_USERS_ON_SERVER',     0);
define('CP_GLOBAL_COMMUNITY',    1);
define('CP_USERS_AND_GLOBAL',    2);
/**
 * @}
 */

/**
 * @name Protocols
 *
 * Different protocols that we are storing
 * @{
 */
define('PROTOCOL_UNKNOWN',         0);
define('PROTOCOL_DFRN',            1);
define('PROTOCOL_DIASPORA',        2);
define('PROTOCOL_OSTATUS_SALMON',  3);
define('PROTOCOL_OSTATUS_FEED',    4); // Deprecated
define('PROTOCOL_GS_CONVERSATION', 5); // Deprecated
define('PROTOCOL_SPLITTED_CONV',   6);
/**
 * @}
 */

/**
 * @name Network
 *
 * Network and protocol family types
 * @{
 */
define('NETWORK_DFRN',             'dfrn');    // Friendica, Mistpark, other DFRN implementations
define('NETWORK_ZOT',              'zot!');    // Zot! - Currently unsupported
define('NETWORK_OSTATUS',          'stat');    // GNU-social, Pleroma, Mastodon, other OStatus implementations
define('NETWORK_FEED',             'feed');    // RSS/Atom feeds with no known "post/notify" protocol
define('NETWORK_DIASPORA',         'dspr');    // Diaspora
define('NETWORK_MAIL',             'mail');    // IMAP/POP
define('NETWORK_FACEBOOK',         'face');    // Facebook API
define('NETWORK_LINKEDIN',         'lnkd');    // LinkedIn
define('NETWORK_XMPP',             'xmpp');    // XMPP - Currently unsupported
define('NETWORK_MYSPACE',          'mysp');    // MySpace - Currently unsupported
define('NETWORK_GPLUS',            'goog');    // Google+
define('NETWORK_PUMPIO',           'pump');    // pump.io
define('NETWORK_TWITTER',          'twit');    // Twitter
define('NETWORK_DIASPORA2',        'dspc');    // Diaspora connector
define('NETWORK_STATUSNET',        'stac');    // Statusnet connector
define('NETWORK_APPNET',           'apdn');    // app.net - Dead protocol
define('NETWORK_NEWS',             'nntp');    // Network News Transfer Protocol - Currently unsupported
define('NETWORK_ICALENDAR',        'ical');    // iCalendar - Currently unsupported
define('NETWORK_PNUT',             'pnut');    // pnut.io - Currently unsupported
define('NETWORK_PHANTOM',          'unkn');    // Place holder
/**
 * @}
 */

/**
 * These numbers are used in stored permissions
 * and existing allocations MUST NEVER BE CHANGED
 * OR RE-ASSIGNED! You may only add to them.
 */
$netgroup_ids = [
	NETWORK_DFRN     => (-1),
	NETWORK_ZOT      => (-2),
	NETWORK_OSTATUS  => (-3),
	NETWORK_FEED     => (-4),
	NETWORK_DIASPORA => (-5),
	NETWORK_MAIL     => (-6),
	NETWORK_FACEBOOK => (-8),
	NETWORK_LINKEDIN => (-9),
	NETWORK_XMPP     => (-10),
	NETWORK_MYSPACE  => (-11),
	NETWORK_GPLUS    => (-12),
	NETWORK_PUMPIO   => (-13),
	NETWORK_TWITTER  => (-14),
	NETWORK_DIASPORA2 => (-15),
	NETWORK_STATUSNET => (-16),
	NETWORK_APPNET    => (-17),
	NETWORK_NEWS      => (-18),
	NETWORK_ICALENDAR => (-19),
	NETWORK_PNUT      => (-20),

	NETWORK_PHANTOM  => (-127),
];

/**
 * Maximum number of "people who like (or don't like) this"  that we will list by name
 */
define('MAX_LIKERS',    75);

/**
 * Communication timeout
 */
define('ZCURL_TIMEOUT', (-1));

/**
 * @name Notify
 *
 * Email notification options
 * @{
 */
define('NOTIFY_INTRO',    0x0001);
define('NOTIFY_CONFIRM',  0x0002);
define('NOTIFY_WALL',     0x0004);
define('NOTIFY_COMMENT',  0x0008);
define('NOTIFY_MAIL',     0x0010);
define('NOTIFY_SUGGEST',  0x0020);
define('NOTIFY_PROFILE',  0x0040);
define('NOTIFY_TAGSELF',  0x0080);
define('NOTIFY_TAGSHARE', 0x0100);
define('NOTIFY_POKE',     0x0200);
define('NOTIFY_SHARE',    0x0400);

define('SYSTEM_EMAIL',    0x4000);

define('NOTIFY_SYSTEM',   0x8000);
/* @}*/


/**
 * @name Term
 *
 * Tag/term types
 * @{
 */
define('TERM_UNKNOWN',   0);
define('TERM_HASHTAG',   1);
define('TERM_MENTION',   2);
define('TERM_CATEGORY',  3);
define('TERM_PCATEGORY', 4);
define('TERM_FILE',      5);
define('TERM_SAVEDSEARCH', 6);
define('TERM_CONVERSATION', 7);

define('TERM_OBJ_POST',  1);
define('TERM_OBJ_PHOTO', 2);

/**
 * @name Namespaces
 *
 * Various namespaces we may need to parse
 * @{
 */
define('NAMESPACE_ZOT',             'http://purl.org/zot');
define('NAMESPACE_DFRN',            'http://purl.org/macgirvin/dfrn/1.0');
define('NAMESPACE_THREAD',          'http://purl.org/syndication/thread/1.0');
define('NAMESPACE_TOMB',            'http://purl.org/atompub/tombstones/1.0');
define('NAMESPACE_ACTIVITY',        'http://activitystrea.ms/spec/1.0/');
define('NAMESPACE_ACTIVITY_SCHEMA', 'http://activitystrea.ms/schema/1.0/');
define('NAMESPACE_MEDIA',           'http://purl.org/syndication/atommedia');
define('NAMESPACE_SALMON_ME',       'http://salmon-protocol.org/ns/magic-env');
define('NAMESPACE_OSTATUSSUB',      'http://ostatus.org/schema/1.0/subscribe');
define('NAMESPACE_GEORSS',          'http://www.georss.org/georss');
define('NAMESPACE_POCO',            'http://portablecontacts.net/spec/1.0');
define('NAMESPACE_FEED',            'http://schemas.google.com/g/2010#updates-from');
define('NAMESPACE_OSTATUS',         'http://ostatus.org/schema/1.0');
define('NAMESPACE_STATUSNET',       'http://status.net/schema/api/1/');
define('NAMESPACE_ATOM1',           'http://www.w3.org/2005/Atom');
define('NAMESPACE_MASTODON',        'http://mastodon.social/schema/1.0');
/* @}*/

/**
 * @name Activity
 *
 * Activity stream defines
 * @{
 */
define('ACTIVITY_LIKE',        NAMESPACE_ACTIVITY_SCHEMA . 'like');
define('ACTIVITY_DISLIKE',     NAMESPACE_DFRN            . '/dislike');
define('ACTIVITY_ATTEND',      NAMESPACE_ZOT             . '/activity/attendyes');
define('ACTIVITY_ATTENDNO',    NAMESPACE_ZOT             . '/activity/attendno');
define('ACTIVITY_ATTENDMAYBE', NAMESPACE_ZOT             . '/activity/attendmaybe');

define('ACTIVITY_OBJ_HEART',   NAMESPACE_DFRN            . '/heart');

define('ACTIVITY_FRIEND',      NAMESPACE_ACTIVITY_SCHEMA . 'make-friend');
define('ACTIVITY_REQ_FRIEND',  NAMESPACE_ACTIVITY_SCHEMA . 'request-friend');
define('ACTIVITY_UNFRIEND',    NAMESPACE_ACTIVITY_SCHEMA . 'remove-friend');
define('ACTIVITY_FOLLOW',      NAMESPACE_ACTIVITY_SCHEMA . 'follow');
define('ACTIVITY_UNFOLLOW',    NAMESPACE_ACTIVITY_SCHEMA . 'stop-following');
define('ACTIVITY_JOIN',        NAMESPACE_ACTIVITY_SCHEMA . 'join');

define('ACTIVITY_POST',        NAMESPACE_ACTIVITY_SCHEMA . 'post');
define('ACTIVITY_UPDATE',      NAMESPACE_ACTIVITY_SCHEMA . 'update');
define('ACTIVITY_TAG',         NAMESPACE_ACTIVITY_SCHEMA . 'tag');
define('ACTIVITY_FAVORITE',    NAMESPACE_ACTIVITY_SCHEMA . 'favorite');
define('ACTIVITY_UNFAVORITE',  NAMESPACE_ACTIVITY_SCHEMA . 'unfavorite');
define('ACTIVITY_SHARE',       NAMESPACE_ACTIVITY_SCHEMA . 'share');
define('ACTIVITY_DELETE',      NAMESPACE_ACTIVITY_SCHEMA . 'delete');

define('ACTIVITY_POKE',        NAMESPACE_ZOT . '/activity/poke');

define('ACTIVITY_OBJ_BOOKMARK', NAMESPACE_ACTIVITY_SCHEMA . 'bookmark');
define('ACTIVITY_OBJ_COMMENT', NAMESPACE_ACTIVITY_SCHEMA . 'comment');
define('ACTIVITY_OBJ_NOTE',    NAMESPACE_ACTIVITY_SCHEMA . 'note');
define('ACTIVITY_OBJ_PERSON',  NAMESPACE_ACTIVITY_SCHEMA . 'person');
define('ACTIVITY_OBJ_IMAGE',   NAMESPACE_ACTIVITY_SCHEMA . 'image');
define('ACTIVITY_OBJ_PHOTO',   NAMESPACE_ACTIVITY_SCHEMA . 'photo');
define('ACTIVITY_OBJ_VIDEO',   NAMESPACE_ACTIVITY_SCHEMA . 'video');
define('ACTIVITY_OBJ_P_PHOTO', NAMESPACE_ACTIVITY_SCHEMA . 'profile-photo');
define('ACTIVITY_OBJ_ALBUM',   NAMESPACE_ACTIVITY_SCHEMA . 'photo-album');
define('ACTIVITY_OBJ_EVENT',   NAMESPACE_ACTIVITY_SCHEMA . 'event');
define('ACTIVITY_OBJ_GROUP',   NAMESPACE_ACTIVITY_SCHEMA . 'group');
define('ACTIVITY_OBJ_TAGTERM', NAMESPACE_DFRN            . '/tagterm');
define('ACTIVITY_OBJ_PROFILE', NAMESPACE_DFRN            . '/profile');
define('ACTIVITY_OBJ_QUESTION', 'http://activityschema.org/object/question');
/* @}*/

/**
 * @name Gravity
 *
 * Item weight for query ordering
 * @{
 */
define('GRAVITY_PARENT',       0);
define('GRAVITY_LIKE',         3);
define('GRAVITY_COMMENT',      6);
/* @}*/

/**
 * @name Priority
 *
 * Process priority for the worker
 * @{
 */
define('PRIORITY_UNDEFINED',   0);
define('PRIORITY_CRITICAL',   10);
define('PRIORITY_HIGH',       20);
define('PRIORITY_MEDIUM',     30);
define('PRIORITY_LOW',        40);
define('PRIORITY_NEGLIGIBLE', 50);
/* @}*/

/**
 * @name Social Relay settings
 *
 * See here: https://github.com/jaywink/social-relay
 * and here: https://wiki.diasporafoundation.org/Relay_servers_for_public_posts
 * @{
 */
define('SR_SCOPE_NONE', '');
define('SR_SCOPE_ALL',  'all');
define('SR_SCOPE_TAGS', 'tags');
/* @}*/

/**
 * Lowest possible date time value
 */
define('NULL_DATE', '0001-01-01 00:00:00');

// Normally this constant is defined - but not if "pcntl" isn't installed
if (!defined("SIGTERM")) {
	define("SIGTERM", 15);
}

/**
 * Depending on the PHP version this constant does exist - or not.
 * See here: http://php.net/manual/en/curl.constants.php#117928
 */
if (!defined('CURLE_OPERATION_TIMEDOUT')) {
	define('CURLE_OPERATION_TIMEDOUT', CURLE_OPERATION_TIMEOUTED);
}
/**
 * Reverse the effect of magic_quotes_gpc if it is enabled.
 * Please disable magic_quotes_gpc so we don't have to do this.
 * See http://php.net/manual/en/security.magicquotes.disabling.php
 */
function startup()
{
	error_reporting(E_ERROR | E_WARNING | E_PARSE);

	set_time_limit(0);

	// This has to be quite large to deal with embedded private photos
	ini_set('pcre.backtrack_limit', 500000);

	if (get_magic_quotes_gpc()) {
		$process = [&$_GET, &$_POST, &$_COOKIE, &$_REQUEST];
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
 * @brief Retrieve the App structure
 *
 * Useful in functions which require it but don't get it passed to them
 *
 * @return App
 */
function get_app()
{
	global $a;

	if (empty($a)) {
		$a = new App(dirname(__DIR__));
	}

	return $a;
}

/**
 * @brief Multi-purpose function to check variable state.
 *
 * Usage: x($var) or $x($array, 'key')
 *
 * returns false if variable/key is not set
 * if variable is set, returns 1 if has 'non-zero' value, otherwise returns 0.
 * e.g. x('') or x(0) returns 0;
 *
 * @param string|array $s variable to check
 * @param string       $k key inside the array to check
 *
 * @return bool|int
 */
function x($s, $k = null)
{
	if ($k != null) {
		if ((is_array($s)) && (array_key_exists($k, $s))) {
			if ($s[$k]) {
				return (int) 1;
			}
			return (int) 0;
		}
		return false;
	} else {
		if (isset($s)) {
			if ($s) {
				return (int) 1;
			}
			return (int) 0;
		}
		return false;
	}
}

/**
 * Return the provided variable value if it exists and is truthy or the provided
 * default value instead.
 *
 * Works with initialized variables and potentially uninitialized array keys
 *
 * Usages:
 * - defaults($var, $default)
 * - defaults($array, 'key', $default)
 *
 * @brief Returns a defaut value if the provided variable or array key is falsy
 * @see x()
 * @return mixed
 */
function defaults() {
	$args = func_get_args();

	if (count($args) < 2) {
		throw new BadFunctionCallException('defaults() requires at least 2 parameters');
	}
	if (count($args) > 3) {
		throw new BadFunctionCallException('defaults() cannot use more than 3 parameters');
	}
	if (count($args) === 3 && is_null($args[1])) {
		throw new BadFunctionCallException('defaults($arr, $key, $def) $key is null');
	}

	$default = array_pop($args);

	if (call_user_func_array('x', $args)) {
		if (count($args) === 1) {
			$return = $args[0];
		} else {
			$return = $args[0][$args[1]];
		}
	} else {
		$return = $default;
	}

	return $return;
}

/**
 * @brief Returns the baseurl.
 *
 * @see System::baseUrl()
 *
 * @return string
 * @TODO Function is deprecated and only used in some addons
 */
function z_root()
{
	return System::baseUrl();
}

/**
 * @brief Return absolut URL for given $path.
 *
 * @param string $path given path
 *
 * @return string
 */
function absurl($path)
{
	if (strpos($path, '/') === 0) {
		return z_path() . $path;
	}
	return $path;
}

/**
 * @brief Function to check if request was an AJAX (xmlhttprequest) request.
 *
 * @return boolean
 */
function is_ajax()
{
	return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');
}

/**
 * @brief Function to check if request was an AJAX (xmlhttprequest) request.
 *
 * @param boolean $via_worker boolean Is the check run via the worker?
 */
function check_db($via_worker)
{
	$build = Config::get('system', 'build');

	if (empty($build)) {
		Config::set('system', 'build', DB_UPDATE_VERSION - 1);
		$build = DB_UPDATE_VERSION;
	}

	// We don't support upgrading from very old versions anymore
	if ($build < NEW_UPDATE_ROUTINE_VERSION) {
		die('You try to update from a version prior to database version 1170. The direct upgrade path is not supported. Please update to version 3.5.4 before updating to this version.');
	}

	if ($build != DB_UPDATE_VERSION) {
		// When we cannot execute the database update via the worker, we will do it directly
		if (!Worker::add(PRIORITY_CRITICAL, 'DBUpdate') && $via_worker) {
			update_db();
		}
	}
}

/**
 * Sets the base url for use in cmdline programs which don't have
 * $_SERVER variables
 *
 * @param object $a App
 */
function check_url(App $a)
{
	$url = Config::get('system', 'url');

	// if the url isn't set or the stored url is radically different
	// than the currently visited url, store the current value accordingly.
	// "Radically different" ignores common variations such as http vs https
	// and www.example.com vs example.com.
	// We will only change the url to an ip address if there is no existing setting

	if (empty($url) || (!link_compare($url, System::baseUrl())) && (!preg_match("/^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})$/", $a->get_hostname))) {
		Config::set('system', 'url', System::baseUrl());
	}

	return;
}

/**
 * @brief Automatic database updates
 * @param object $a App
 */
function update_db()
{
	$build = Config::get('system', 'build');

	if (empty($build) || ($build > DB_UPDATE_VERSION)) {
		$build = DB_UPDATE_VERSION - 1;
		Config::set('system', 'build', $build);
	}

	if ($build != DB_UPDATE_VERSION) {
		require_once 'update.php';

		$stored = intval($build);
		$current = intval(DB_UPDATE_VERSION);
		if ($stored < $current) {
			Config::load('database');

			// Compare the current structure with the defined structure
			$t = Config::get('database', 'dbupdate_' . DB_UPDATE_VERSION);
			if (!is_null($t)) {
				return;
			}

			Config::set('database', 'dbupdate_' . DB_UPDATE_VERSION, time());

			// run update routine
			// it update the structure in one call
			$retval = DBStructure::update(false, true);
			if ($retval) {
				DBStructure::updateFail(
					DB_UPDATE_VERSION,
					$retval
				);
				return;
			} else {
				Config::set('database', 'dbupdate_' . DB_UPDATE_VERSION, 'success');
			}

			// run any left update_nnnn functions in update.php
			for ($x = $stored + 1; $x <= $current; $x++) {
				$r = run_update_function($x);
				if (!$r) {
					break;
				}
			}
		}
	}

	return;
}

function run_update_function($x)
{
	if (function_exists('update_' . $x)) {
		// There could be a lot of processes running or about to run.
		// We want exactly one process to run the update command.
		// So store the fact that we're taking responsibility
		// after first checking to see if somebody else already has.
		// If the update fails or times-out completely you may need to
		// delete the config entry to try again.

		$t = Config::get('database', 'update_' . $x);
		if (!is_null($t)) {
			return false;
		}
		Config::set('database', 'update_' . $x, time());

		// call the specific update

		$func = 'update_' . $x;
		$retval = $func();

		if ($retval) {
			//send the administrator an e-mail
			DBStructure::updateFail(
				$x,
				sprintf(t('Update %s failed. See error logs.'), $x)
			);
			return false;
		} else {
			Config::set('database', 'update_' . $x, 'success');
			Config::set('system', 'build', $x);
			return true;
		}
	} else {
		Config::set('database', 'update_' . $x, 'success');
		Config::set('system', 'build', $x);
		return true;
	}
}

/**
 * @brief Synchronise plugins:
 *
 * $a->config['system']['addon'] contains a comma-separated list of names
 * of plugins/addons which are used on this system.
 * Go through the database list of already installed addons, and if we have
 * an entry, but it isn't in the config list, call the uninstall procedure
 * and mark it uninstalled in the database (for now we'll remove it).
 * Then go through the config list and if we have a plugin that isn't installed,
 * call the install procedure and add it to the database.
 *
 * @param object $a App
 */
function check_plugins(App $a)
{
	$r = q("SELECT * FROM `addon` WHERE `installed` = 1");
	if (DBM::is_result($r)) {
		$installed = $r;
	} else {
		$installed = [];
	}

	$plugins = Config::get('system', 'addon');
	$plugins_arr = [];

	if ($plugins) {
		$plugins_arr = explode(',', str_replace(' ', '', $plugins));
	}

	$a->plugins = $plugins_arr;

	$installed_arr = [];

	if (count($installed)) {
		foreach ($installed as $i) {
			if (!in_array($i['name'], $plugins_arr)) {
				Addon::uninstallPlugin($i['name']);
			} else {
				$installed_arr[] = $i['name'];
			}
		}
	}

	if (count($plugins_arr)) {
		foreach ($plugins_arr as $p) {
			if (!in_array($p, $installed_arr)) {
				Addon::installPlugin($p);
			}
		}
	}

	Addon::loadHooks();

	return;
}

function get_guid($size = 16, $prefix = "")
{
	if ($prefix == "") {
		$a = get_app();
		$prefix = hash("crc32", $a->get_hostname());
	}

	while (strlen($prefix) < ($size - 13)) {
		$prefix .= mt_rand();
	}

	if ($size >= 24) {
		$prefix = substr($prefix, 0, $size - 22);
		return(str_replace(".", "", uniqid($prefix, true)));
	} else {
		$prefix = substr($prefix, 0, max($size - 13, 0));
		return(uniqid($prefix));
	}
}

/**
 * @brief Used to end the current process, after saving session state.
 */
function killme()
{
	if (!get_app()->is_backend()) {
		session_write_close();
	}

	exit();
}

/**
 * @brief Redirect to another URL and terminate this process.
 */
function goaway($path)
{
	if (strstr(normalise_link($path), 'http://')) {
		$url = $path;
	} else {
		$url = System::baseUrl() . '/' . ltrim($path, '/');
	}

	header("Location: $url");
	killme();
}

/**
 * @brief Returns the user id of locally logged in user or false.
 *
 * @return int|bool user id or false
 */
function local_user()
{
	if (x($_SESSION, 'authenticated') && x($_SESSION, 'uid')) {
		return intval($_SESSION['uid']);
	}
	return false;
}

/**
 * @brief Returns the public contact id of logged in user or false.
 *
 * @return int|bool public contact id or false
 */
function public_contact()
{
	static $public_contact_id = false;

	if (!$public_contact_id && x($_SESSION, 'authenticated')) {
		if (x($_SESSION, 'my_address')) {
			// Local user
			$public_contact_id = intval(Contact::getIdForURL($_SESSION['my_address'], 0));
		} elseif (x($_SESSION, 'visitor_home')) {
			// Remote user
			$public_contact_id = intval(Contact::getIdForURL($_SESSION['visitor_home'], 0));
		}
	} elseif (!x($_SESSION, 'authenticated')) {
		$public_contact_id = false;
	}

	return $public_contact_id;
}

/**
 * @brief Returns contact id of authenticated site visitor or false
 *
 * @return int|bool visitor_id or false
 */
function remote_user()
{
	// You cannot be both local and remote
	if (local_user()) {
		return false;
	}
	if (x($_SESSION, 'authenticated') && x($_SESSION, 'visitor_id')) {
		return intval($_SESSION['visitor_id']);
	}
	return false;
}

/**
 * @brief Show an error message to user.
 *
 * This function save text in session, to be shown to the user at next page load
 *
 * @param string $s - Text of notice
 */
function notice($s)
{
	$a = get_app();
	if (!x($_SESSION, 'sysmsg')) {
		$_SESSION['sysmsg'] = [];
	}
	if ($a->interactive) {
		$_SESSION['sysmsg'][] = $s;
	}
}

/**
 * @brief Show an info message to user.
 *
 * This function save text in session, to be shown to the user at next page load
 *
 * @param string $s - Text of notice
 */
function info($s)
{
	$a = get_app();

	if (local_user() && PConfig::get(local_user(), 'system', 'ignore_info')) {
		return;
	}

	if (!x($_SESSION, 'sysmsg_info')) {
		$_SESSION['sysmsg_info'] = [];
	}
	if ($a->interactive) {
		$_SESSION['sysmsg_info'][] = $s;
	}
}

/**
 * @brief Wrapper around config to limit the text length of an incoming message
 *
 * @return int
 */
function get_max_import_size()
{
	$a = get_app();
	return (x($a->config, 'max_import_size') ? $a->config['max_import_size'] : 0);
}


function current_theme()
{
	$app_base_themes = ['duepuntozero', 'dispy', 'quattro'];

	$a = get_app();

	$page_theme = null;

	// Find the theme that belongs to the user whose stuff we are looking at

	if ($a->profile_uid && ($a->profile_uid != local_user())) {
		$r = q(
			"select theme from user where uid = %d limit 1",
			intval($a->profile_uid)
		);
		if (DBM::is_result($r)) {
			$page_theme = $r[0]['theme'];
		}
	}

	// Allow folks to over-rule user themes and always use their own on their own site.
	// This works only if the user is on the same server

	if ($page_theme && local_user() && (local_user() != $a->profile_uid)) {
		if (PConfig::get(local_user(), 'system', 'always_my_theme')) {
			$page_theme = null;
		}
	}

//		$mobile_detect = new Mobile_Detect();
//		$is_mobile = $mobile_detect->isMobile() || $mobile_detect->isTablet();
	$is_mobile = $a->is_mobile || $a->is_tablet;

	$standard_system_theme = Config::get('system', 'theme', '');
	$standard_theme_name = ((isset($_SESSION) && x($_SESSION, 'theme')) ? $_SESSION['theme'] : $standard_system_theme);

	if ($is_mobile) {
		if (isset($_SESSION['show-mobile']) && !$_SESSION['show-mobile']) {
			$theme_name = $standard_theme_name;
		} else {
			$system_theme = Config::get('system', 'mobile-theme', '');
			if ($system_theme == '') {
				$system_theme = $standard_system_theme;
			}
			$theme_name = ((isset($_SESSION) && x($_SESSION, 'mobile-theme')) ? $_SESSION['mobile-theme'] : $system_theme);

			if ($theme_name === '---') {
				// user has selected to have the mobile theme be the same as the normal one
				$theme_name = $standard_theme_name;

				if ($page_theme) {
					$theme_name = $page_theme;
				}
			}
		}
	} else {
		$theme_name = $standard_theme_name;

		if ($page_theme) {
			$theme_name = $page_theme;
		}
	}

	if ($theme_name
		&& (file_exists('view/theme/' . $theme_name . '/style.css')
		|| file_exists('view/theme/' . $theme_name . '/style.php'))
	) {
		return($theme_name);
	}

	foreach ($app_base_themes as $t) {
		if (file_exists('view/theme/' . $t . '/style.css')
			|| file_exists('view/theme/' . $t . '/style.php')
		) {
			return($t);
		}
	}

	$fallback = array_merge(glob('view/theme/*/style.css'), glob('view/theme/*/style.php'));
	if (count($fallback)) {
		return (str_replace('view/theme/', '', substr($fallback[0], 0, -10)));
	}

	/// @TODO No final return statement?
}

/**
 * @brief Return full URL to theme which is currently in effect.
 *
 * Provide a sane default if nothing is chosen or the specified theme does not exist.
 *
 * @return string
 */
function current_theme_url()
{
	$a = get_app();

	$t = current_theme();

	$opts = (($a->profile_uid) ? '?f=&puid=' . $a->profile_uid : '');
	if (file_exists('view/theme/' . $t . '/style.php')) {
		return('view/theme/' . $t . '/style.pcss' . $opts);
	}

	return('view/theme/' . $t . '/style.css');
}

function feed_birthday($uid, $tz)
{
	/**
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
	 */
	$birthday = '';

	if (!strlen($tz)) {
		$tz = 'UTC';
	}

	$p = q(
		"SELECT `dob` FROM `profile` WHERE `is-default` = 1 AND `uid` = %d LIMIT 1",
		intval($uid)
	);

	if (DBM::is_result($p)) {
		$tmp_dob = substr($p[0]['dob'], 5);
		if (intval($tmp_dob)) {
			$y = datetime_convert($tz, $tz, 'now', 'Y');
			$bd = $y . '-' . $tmp_dob . ' 00:00';
			$t_dob = strtotime($bd);
			$now = strtotime(datetime_convert($tz, $tz, 'now'));
			if ($t_dob < $now) {
				$bd = $y + 1 . '-' . $tmp_dob . ' 00:00';
			}
			$birthday = datetime_convert($tz, 'UTC', $bd, ATOM_TIME);
		}
	}

	return $birthday;
}

/**
 * @brief Check if current user has admin role.
 *
 * @return bool true if user is an admin
 */
function is_site_admin()
{
	$a = get_app();

	$adminlist = explode(",", str_replace(" ", "", $a->config['admin_email']));

	//if(local_user() && x($a->user,'email') && x($a->config,'admin_email') && ($a->user['email'] === $a->config['admin_email']))
	if (local_user() && x($a->user, 'email') && x($a->config, 'admin_email') && in_array($a->user['email'], $adminlist)) {
		return true;
	}
	return false;
}

/**
 * @brief Returns querystring as string from a mapped array.
 *
 * @param array  $params mapped array with query parameters
 * @param string $name   of parameter, default null
 *
 * @return string
 */
function build_querystring($params, $name = null)
{
	$ret = "";
	foreach ($params as $key => $val) {
		if (is_array($val)) {
			/// @TODO maybe not compare against null, use is_null()
			if ($name == null) {
				$ret .= build_querystring($val, $key);
			} else {
				$ret .= build_querystring($val, $name . "[$key]");
			}
		} else {
			$val = urlencode($val);
			/// @TODO maybe not compare against null, use is_null()
			if ($name != null) {
				/// @TODO two string concated, can be merged to one
				$ret .= $name . "[$key]" . "=$val&";
			} else {
				$ret .= "$key=$val&";
			}
		}
	}
	return $ret;
}

function explode_querystring($query)
{
	$arg_st = strpos($query, '?');
	if ($arg_st !== false) {
		$base = substr($query, 0, $arg_st);
		$arg_st += 1;
	} else {
		$base = '';
		$arg_st = 0;
	}

	$args = explode('&', substr($query, $arg_st));
	foreach ($args as $k => $arg) {
		/// @TODO really compare type-safe here?
		if ($arg === '') {
			unset($args[$k]);
		}
	}
	$args = array_values($args);

	if (!$base) {
		$base = $args[0];
		unset($args[0]);
		$args = array_values($args);
	}

	return [
		'base' => $base,
		'args' => $args,
	];
}

/**
 * Returns the complete URL of the current page, e.g.: http(s)://something.com/network
 *
 * Taken from http://webcheatsheet.com/php/get_current_page_url.php
 */
function curPageURL()
{
	$pageURL = 'http';
	if ($_SERVER["HTTPS"] == "on") {
		$pageURL .= "s";
	}

	$pageURL .= "://";

	if ($_SERVER["SERVER_PORT"] != "80" && $_SERVER["SERVER_PORT"] != "443") {
		$pageURL .= $_SERVER["SERVER_NAME"] . ":" . $_SERVER["SERVER_PORT"] . $_SERVER["REQUEST_URI"];
	} else {
		$pageURL .= $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];
	}
	return $pageURL;
}

function random_digits($digits)
{
	$rn = '';
	for ($i = 0; $i < $digits; $i++) {
		/// @TODO rand() is different to mt_rand() and maybe lesser "random"
		$rn .= rand(0, 9);
	}
	return $rn;
}

function get_server()
{
	$server = Config::get("system", "directory");

	if ($server == "") {
		$server = "http://dir.friendica.social";
	}

	return($server);
}

function get_temppath()
{
	$a = get_app();

	$temppath = Config::get("system", "temppath");

	if (($temppath != "") && App::directory_usable($temppath)) {
		// We have a temp path and it is usable
		return App::realpath($temppath);
	}

	// We don't have a working preconfigured temp path, so we take the system path.
	$temppath = sys_get_temp_dir();

	// Check if it is usable
	if (($temppath != "") && App::directory_usable($temppath)) {
		// Always store the real path, not the path through symlinks
		$temppath = App::realpath($temppath);

		// To avoid any interferences with other systems we create our own directory
		$new_temppath = $temppath . "/" . $a->get_hostname();
		if (!is_dir($new_temppath)) {
			/// @TODO There is a mkdir()+chmod() upwards, maybe generalize this (+ configurable) into a function/method?
			mkdir($new_temppath);
		}

		if (App::directory_usable($new_temppath)) {
			// The new path is usable, we are happy
			Config::set("system", "temppath", $new_temppath);
			return $new_temppath;
		} else {
			// We can't create a subdirectory, strange.
			// But the directory seems to work, so we use it but don't store it.
			return $temppath;
		}
	}

	// Reaching this point means that the operating system is configured badly.
	return '';
}

function get_cachefile($file, $writemode = true)
{
	$cache = get_itemcachepath();

	if ((!$cache) || (!is_dir($cache))) {
		return("");
	}

	$subfolder = $cache . "/" . substr($file, 0, 2);

	$cachepath = $subfolder . "/" . $file;

	if ($writemode) {
		if (!is_dir($subfolder)) {
			mkdir($subfolder);
			chmod($subfolder, 0777);
		}
	}

	/// @TODO no need to put braces here
	return $cachepath;
}

function clear_cache($basepath = "", $path = "")
{
	if ($path == "") {
		$basepath = get_itemcachepath();
		$path = $basepath;
	}

	if (($path == "") || (!is_dir($path))) {
		return;
	}

	if (substr(realpath($path), 0, strlen($basepath)) != $basepath) {
		return;
	}

	$cachetime = (int) Config::get('system', 'itemcache_duration');
	if ($cachetime == 0) {
		$cachetime = 86400;
	}

	if (is_writable($path)) {
		if ($dh = opendir($path)) {
			while (($file = readdir($dh)) !== false) {
				$fullpath = $path . "/" . $file;
				if ((filetype($fullpath) == "dir") && ($file != ".") && ($file != "..")) {
					clear_cache($basepath, $fullpath);
				}
				if ((filetype($fullpath) == "file") && (filectime($fullpath) < (time() - $cachetime))) {
					unlink($fullpath);
				}
			}
			closedir($dh);
		}
	}
}

function get_itemcachepath()
{
	// Checking, if the cache is deactivated
	$cachetime = (int) Config::get('system', 'itemcache_duration');
	if ($cachetime < 0) {
		return "";
	}

	$itemcache = Config::get('system', 'itemcache');
	if (($itemcache != "") && App::directory_usable($itemcache)) {
		return App::realpath($itemcache);
	}

	$temppath = get_temppath();

	if ($temppath != "") {
		$itemcache = $temppath . "/itemcache";
		if (!file_exists($itemcache) && !is_dir($itemcache)) {
			mkdir($itemcache);
		}

		if (App::directory_usable($itemcache)) {
			Config::set("system", "itemcache", $itemcache);
			return $itemcache;
		}
	}
	return "";
}

/**
 * @brief Returns the path where spool files are stored
 *
 * @return string Spool path
 */
function get_spoolpath()
{
	$spoolpath = Config::get('system', 'spoolpath');
	if (($spoolpath != "") && App::directory_usable($spoolpath)) {
		// We have a spool path and it is usable
		return $spoolpath;
	}

	// We don't have a working preconfigured spool path, so we take the temp path.
	$temppath = get_temppath();

	if ($temppath != "") {
		// To avoid any interferences with other systems we create our own directory
		$spoolpath = $temppath . "/spool";
		if (!is_dir($spoolpath)) {
			mkdir($spoolpath);
		}

		if (App::directory_usable($spoolpath)) {
			// The new path is usable, we are happy
			Config::set("system", "spoolpath", $spoolpath);
			return $spoolpath;
		} else {
			// We can't create a subdirectory, strange.
			// But the directory seems to work, so we use it but don't store it.
			return $temppath;
		}
	}

	// Reaching this point means that the operating system is configured badly.
	return "";
}


if (!function_exists('exif_imagetype')) {
	function exif_imagetype($file)
	{
		$size = getimagesize($file);
		return $size[2];
	}
}

function validate_include(&$file)
{
	$orig_file = $file;

	$file = realpath($file);

	if (strpos($file, getcwd()) !== 0) {
		return false;
	}

	$file = str_replace(getcwd() . "/", "", $file, $count);
	if ($count != 1) {
		return false;
	}

	if ($orig_file !== $file) {
		return false;
	}

	$valid = false;
	if (strpos($file, "include/") === 0) {
		$valid = true;
	}

	if (strpos($file, "addon/") === 0) {
		$valid = true;
	}

	// Simply return flag
	return ($valid);
}

function current_load()
{
	if (!function_exists('sys_getloadavg')) {
		return false;
	}

	$load_arr = sys_getloadavg();

	if (!is_array($load_arr)) {
		return false;
	}

	return max($load_arr[0], $load_arr[1]);
}

/**
 * @brief get c-style args
 *
 * @return int
 */
function argc()
{
	return get_app()->argc;
}

/**
 * @brief Returns the value of a argv key
 *
 * @param int $x argv key
 * @return string Value of the argv key
 */
function argv($x)
{
	if (array_key_exists($x, get_app()->argv)) {
		return get_app()->argv[$x];
	}

	return '';
}

/**
 * @brief Get the data which is needed for infinite scroll
 *
 * For invinite scroll we need the page number of the actual page
 * and the the URI where the content of the next page comes from.
 * This data is needed for the js part in main.js.
 * Note: infinite scroll does only work for the network page (module)
 *
 * @param string $module The name of the module (e.g. "network")
 * @return array Of infinite scroll data
 * 	'pageno' => $pageno The number of the actual page
 * 	'reload_uri' => $reload_uri The URI of the content we have to load
 */
function infinite_scroll_data($module)
{
	if (PConfig::get(local_user(), 'system', 'infinite_scroll')
		&& $module == 'network'
		&& defaults($_GET, 'mode', '') != 'minimal'
	) {
		// get the page number
		$pageno = defaults($_GET, 'page', 1);

		$reload_uri = "";

		// try to get the uri from which we load the content
		foreach ($_GET as $param => $value) {
			if (($param != "page") && ($param != "q")) {
				$reload_uri .= "&" . $param . "=" . urlencode($value);
			}
		}

		$a = get_app();
		if ($a->page_offset != "" && !strstr($reload_uri, "&offset=")) {
			$reload_uri .= "&offset=" . urlencode($a->page_offset);
		}

		$arr = ["pageno" => $pageno, "reload_uri" => $reload_uri];

		return $arr;
	}
}
