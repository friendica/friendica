<?php
/**
 * @copyright Copyright (C) 2020, Friendica
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * Friendica implementation of statusnet/twitter API
 *
 * @file include/api.php
 * @todo Automatically detect if incoming data is HTML or BBCode
 */

use Friendica\App;
use Friendica\Content\ContactSelector;
use Friendica\Content\Text\BBCode;
use Friendica\Content\Text\HTML;
use Friendica\Core\Hook;
use Friendica\Core\Logger;
use Friendica\Core\Protocol;
use Friendica\Core\Session;
use Friendica\Core\System;
use Friendica\Core\Worker;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Group;
use Friendica\Model\Item;
use Friendica\Model\Mail;
use Friendica\Model\Notification;
use Friendica\Model\Photo;
use Friendica\Model\Post;
use Friendica\Model\User;
use Friendica\Model\UserItem;
use Friendica\Model\Verb;
use Friendica\Network\HTTPException;
use Friendica\Network\HTTPException\BadRequestException;
use Friendica\Network\HTTPException\ExpectationFailedException;
use Friendica\Network\HTTPException\ForbiddenException;
use Friendica\Network\HTTPException\InternalServerErrorException;
use Friendica\Network\HTTPException\MethodNotAllowedException;
use Friendica\Network\HTTPException\NotFoundException;
use Friendica\Network\HTTPException\TooManyRequestsException;
use Friendica\Network\HTTPException\UnauthorizedException;
use Friendica\Object\Image;
use Friendica\Protocol\Activity;
use Friendica\Protocol\Diaspora;
use Friendica\Security\FKOAuth1;
use Friendica\Security\OAuth1\OAuthRequest;
use Friendica\Security\OAuth1\OAuthUtil;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Images;
use Friendica\Util\Network;
use Friendica\Util\Proxy as ProxyUtils;
use Friendica\Util\Strings;
use Friendica\Util\XML;

require_once __DIR__ . '/../mod/item.php';
require_once __DIR__ . '/../mod/wall_upload.php';

define('API_METHOD_ANY', '*');
define('API_METHOD_GET', 'GET');
define('API_METHOD_POST', 'POST,PUT');
define('API_METHOD_DELETE', 'POST,DELETE');

define('API_LOG_PREFIX', 'API {action} - ');

$API = [];
$called_api = [];

/**
 * Auth API user
 *
 * It is not sufficient to use local_user() to check whether someone is allowed to use the API,
 * because this will open CSRF holes (just embed an image with src=friendicasite.com/api/statuses/update?status=CSRF
 * into a page, and visitors will post something without noticing it).
 */
function api_user()
{
	if (!empty($_SESSION['allow_api'])) {
		return local_user();
	}

	return false;
}

/**
 * Get source name from API client
 *
 * Clients can send 'source' parameter to be show in post metadata
 * as "sent via <source>".
 * Some clients doesn't send a source param, we support ones we know
 * (only Twidere, atm)
 *
 * @return string
 *        Client source name, default to "api" if unset/unknown
 * @throws Exception
 */
function api_source()
{
	if (requestdata('source')) {
		return requestdata('source');
	}

	// Support for known clients that doesn't send a source name
	if (!empty($_SERVER['HTTP_USER_AGENT'])) {
		if(strpos($_SERVER['HTTP_USER_AGENT'], "Twidere") !== false) {
			return "Twidere";
		}

		Logger::info(API_LOG_PREFIX . 'Unrecognized user-agent', ['module' => 'api', 'action' => 'source', 'http_user_agent' => $_SERVER['HTTP_USER_AGENT']]);
	} else {
		Logger::info(API_LOG_PREFIX . 'Empty user-agent', ['module' => 'api', 'action' => 'source']);
	}

	return "api";
}

/**
 * Format date for API
 *
 * @param string $str Source date, as UTC
 * @return string Date in UTC formatted as "D M d H:i:s +0000 Y"
 * @throws Exception
 */
function api_date($str)
{
	// Wed May 23 06:01:13 +0000 2007
	return DateTimeFormat::utc($str, "D M d H:i:s +0000 Y");
}

/**
 * Register a function to be the endpoint for defined API path.
 *
 * @param string $path   API URL path, relative to DI::baseUrl()
 * @param string $func   Function name to call on path request
 * @param bool   $auth   API need logged user
 * @param string $method HTTP method reqiured to call this endpoint.
 *                       One of API_METHOD_ANY, API_METHOD_GET, API_METHOD_POST.
 *                       Default to API_METHOD_ANY
 */
function api_register_func($path, $func, $auth = false, $method = API_METHOD_ANY)
{
	global $API;

	$API[$path] = [
		'func'   => $func,
		'auth'   => $auth,
		'method' => $method,
	];

	// Workaround for hotot
	$path = str_replace("api/", "api/1.1/", $path);

	$API[$path] = [
		'func'   => $func,
		'auth'   => $auth,
		'method' => $method,
	];
}

/**
 * Log in user via OAuth1 or Simple HTTP Auth.
 * Simple Auth allow username in form of <pre>user@server</pre>, ignoring server part
 *
 * @param App $a App
 * @throws ForbiddenException
 * @throws InternalServerErrorException
 * @throws UnauthorizedException
 * @hook  'authenticate'
 *               array $addon_auth
 *               'username' => username from login form
 *               'password' => password from login form
 *               'authenticated' => return status,
 *               'user_record' => return authenticated user record
 */
function api_login(App $a)
{
	// workaround for HTTP-auth in CGI mode
	if (!empty($_SERVER['REDIRECT_REMOTE_USER'])) {
		$userpass = base64_decode(substr($_SERVER["REDIRECT_REMOTE_USER"], 6));
		if (strlen($userpass)) {
			list($name, $password) = explode(':', $userpass);
			$_SERVER['PHP_AUTH_USER'] = $name;
			$_SERVER['PHP_AUTH_PW'] = $password;
		}
	}

	if (empty($_SERVER['PHP_AUTH_USER'])) {
		// Try OAuth when no user is provided
		$oauth1 = new FKOAuth1();
		// login with oauth
		try {
			$request = OAuthRequest::from_request();
			list($consumer, $token) = $oauth1->verify_request($request);
			if (!is_null($token)) {
				$oauth1->loginUser($token->uid);
				Session::set('allow_api', true);
				return;
			}
			echo __FILE__.__LINE__.__FUNCTION__ . "<pre>";
			var_dump($consumer, $token);
			die();
		} catch (Exception $e) {
			Logger::warning(API_LOG_PREFIX . 'OAuth error', ['module' => 'api', 'action' => 'login', 'exception' => $e->getMessage()]);
		}

		Logger::debug(API_LOG_PREFIX . 'failed', ['module' => 'api', 'action' => 'login', 'parameters' => $_SERVER]);
		header('WWW-Authenticate: Basic realm="Friendica"');
		throw new UnauthorizedException("This API requires login");
	}

	$user = $_SERVER['PHP_AUTH_USER'] ?? '';
	$password = $_SERVER['PHP_AUTH_PW'] ?? '';

	// allow "user@server" login (but ignore 'server' part)
	$at = strstr($user, "@", true);
	if ($at) {
		$user = $at;
	}

	// next code from mod/auth.php. needs better solution
	$record = null;

	$addon_auth = [
		'username' => trim($user),
		'password' => trim($password),
		'authenticated' => 0,
		'user_record' => null,
	];

	/*
	* An addon indicates successful login by setting 'authenticated' to non-zero value and returning a user record
	* Addons should never set 'authenticated' except to indicate success - as hooks may be chained
	* and later addons should not interfere with an earlier one that succeeded.
	*/
	Hook::callAll('authenticate', $addon_auth);

	if ($addon_auth['authenticated'] && count($addon_auth['user_record'])) {
		$record = $addon_auth['user_record'];
	} else {
		$user_id = User::authenticate(trim($user), trim($password), true);
		if ($user_id !== false) {
			$record = DBA::selectFirst('user', [], ['uid' => $user_id]);
		}
	}

	if (!DBA::isResult($record)) {
		Logger::debug(API_LOG_PREFIX . 'failed', ['module' => 'api', 'action' => 'login', 'parameters' => $_SERVER]);
		header('WWW-Authenticate: Basic realm="Friendica"');
		//header('HTTP/1.0 401 Unauthorized');
		//die('This api requires login');
		throw new UnauthorizedException("This API requires login");
	}

	// Don't refresh the login date more often than twice a day to spare database writes
	$login_refresh = strcmp(DateTimeFormat::utc('now - 12 hours'), $record['login_date']) > 0;

	DI::auth()->setForUser($a, $record, false, false, $login_refresh);

	$_SESSION["allow_api"] = true;

	Hook::callAll('logged_in', $a->user);
}

/**
 * Check HTTP method of called API
 *
 * API endpoints can define which HTTP method to accept when called.
 * This function check the current HTTP method agains endpoint
 * registered method.
 *
 * @param string $method Required methods, uppercase, separated by comma
 * @return bool
 */
function api_check_method($method)
{
	if ($method == "*") {
		return true;
	}
	return (stripos($method, $_SERVER['REQUEST_METHOD'] ?? 'GET') !== false);
}

/**
 * Main API entry point
 *
 * Authenticate user, call registered API function, set HTTP headers
 *
 * @param App $a App
 * @param App\Arguments $args The app arguments (optional, will retrieved by the DI-Container in case of missing)
 * @return string|array API call result
 * @throws Exception
 */
function api_call(App $a, App\Arguments $args = null)
{
	global $API, $called_api;

	if ($args == null) {
		$args = DI::args();
	}

	$type = "json";
	if (strpos($args->getCommand(), ".xml") > 0) {
		$type = "xml";
	}
	if (strpos($args->getCommand(), ".json") > 0) {
		$type = "json";
	}
	if (strpos($args->getCommand(), ".rss") > 0) {
		$type = "rss";
	}
	if (strpos($args->getCommand(), ".atom") > 0) {
		$type = "atom";
	}

	try {
		foreach ($API as $p => $info) {
			if (strpos($args->getCommand(), $p) === 0) {
				if (!api_check_method($info['method'])) {
					throw new MethodNotAllowedException();
				}

				$called_api = explode("/", $p);

				if (!empty($info['auth']) && api_user() === false) {
					api_login($a);
					Logger::info(API_LOG_PREFIX . 'username {username}', ['module' => 'api', 'action' => 'call', 'username' => $a->user['username']]);
				}

				Logger::debug(API_LOG_PREFIX . 'parameters', ['module' => 'api', 'action' => 'call', 'parameters' => $_REQUEST]);

				$stamp =  microtime(true);
				$return = call_user_func($info['func'], $type);
				$duration = floatval(microtime(true) - $stamp);

				Logger::info(API_LOG_PREFIX . 'duration {duration}', ['module' => 'api', 'action' => 'call', 'duration' => round($duration, 2)]);

				DI::profiler()->saveLog(DI::logger(), API_LOG_PREFIX . 'performance');

				if (false === $return) {
					/*
						* api function returned false withour throw an
						* exception. This should not happend, throw a 500
						*/
					throw new InternalServerErrorException();
				}

				switch ($type) {
					case "xml":
						header("Content-Type: text/xml");
						break;
					case "json":
						header("Content-Type: application/json");
						if (!empty($return)) {
							$json = json_encode(end($return));
							if (!empty($_GET['callback'])) {
								$json = $_GET['callback'] . "(" . $json . ")";
							}
							$return = $json;
						}
						break;
					case "rss":
						header("Content-Type: application/rss+xml");
						$return  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . $return;
						break;
					case "atom":
						header("Content-Type: application/atom+xml");
						$return = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . $return;
						break;
				}
				return $return;
			}
		}

		Logger::warning(API_LOG_PREFIX . 'not implemented', ['module' => 'api', 'action' => 'call', 'query' => DI::args()->getQueryString()]);
		throw new NotFoundException();
	} catch (HTTPException $e) {
		header("HTTP/1.1 {$e->getCode()} {$e->httpdesc}");
		return api_error($type, $e, $args);
	}
}

/**
 * Format API error string
 *
 * @param string $type Return type (xml, json, rss, as)
 * @param object $e    HTTPException Error object
 * @param App\Arguments $args The App arguments
 * @return string|array error message formatted as $type
 */
function api_error($type, $e, App\Arguments $args)
{
	$error = ($e->getMessage() !== "" ? $e->getMessage() : $e->httpdesc);
	/// @TODO:  https://dev.twitter.com/overview/api/response-codes

	$error = ["error" => $error,
			"code" => $e->getCode() . " " . $e->httpdesc,
			"request" => $args->getQueryString()];

	$return = api_format_data('status', $type, ['status' => $error]);

	switch ($type) {
		case "xml":
			header("Content-Type: text/xml");
			break;
		case "json":
			header("Content-Type: application/json");
			$return = json_encode($return);
			break;
		case "rss":
			header("Content-Type: application/rss+xml");
			break;
		case "atom":
			header("Content-Type: application/atom+xml");
			break;
	}

	return $return;
}

/**
 * Set values for RSS template
 *
 * @param App   $a
 * @param array $arr       Array to be passed to template
 * @param array $user_info User info
 * @return array
 * @throws BadRequestException
 * @throws ImagickException
 * @throws InternalServerErrorException
 * @throws UnauthorizedException
 * @todo  find proper type-hints
 */
function api_rss_extra(App $a, $arr, $user_info)
{
	if (is_null($user_info)) {
		$user_info = api_get_user($a);
	}

	$arr['$user'] = $user_info;
	$arr['$rss'] = [
		'alternate'    => $user_info['url'],
		'self'         => DI::baseUrl() . "/" . DI::args()->getQueryString(),
		'base'         => DI::baseUrl(),
		'updated'      => api_date(null),
		'atom_updated' => DateTimeFormat::utcNow(DateTimeFormat::ATOM),
		'language'     => $user_info['lang'],
		'logo'         => DI::baseUrl() . "/images/friendica-32.png",
	];

	return $arr;
}


/**
 * Unique contact to contact url.
 *
 * @param int $id Contact id
 * @return bool|string
 *                Contact url or False if contact id is unknown
 * @throws Exception
 */
function api_unique_id_to_nurl($id)
{
	$r = DBA::selectFirst('contact', ['nurl'], ['id' => $id]);

	if (DBA::isResult($r)) {
		return $r["nurl"];
	} else {
		return false;
	}
}

/**
 * Get user info array.
 *
 * @param App        $a          App
 * @param int|string $contact_id Contact ID or URL
 * @return array|bool
 * @throws BadRequestException
 * @throws ImagickException
 * @throws InternalServerErrorException
 * @throws UnauthorizedException
 */
function api_get_user(App $a, $contact_id = null)
{
	global $called_api;

	$user = null;
	$extra_query = "";
	$url = "";

	Logger::info(API_LOG_PREFIX . 'Fetching data for user {user}', ['module' => 'api', 'action' => 'get_user', 'user' => $contact_id]);

	// Searching for contact URL
	if (!is_null($contact_id) && (intval($contact_id) == 0)) {
		$user = DBA::escape(Strings::normaliseLink($contact_id));
		$url = $user;
		$extra_query = "AND `contact`.`nurl` = '%s' ";
		if (api_user() !== false) {
			$extra_query .= "AND `contact`.`uid`=" . intval(api_user());
		}
	}

	// Searching for contact id with uid = 0
	if (!is_null($contact_id) && (intval($contact_id) != 0)) {
		$user = DBA::escape(api_unique_id_to_nurl(intval($contact_id)));

		if ($user == "") {
			throw new BadRequestException("User ID ".$contact_id." not found.");
		}

		$url = $user;
		$extra_query = "AND `contact`.`nurl` = '%s' ";
		if (api_user() !== false) {
			$extra_query .= "AND `contact`.`uid`=" . intval(api_user());
		}
	}

	if (is_null($user) && !empty($_GET['user_id'])) {
		$user = DBA::escape(api_unique_id_to_nurl($_GET['user_id']));

		if ($user == "") {
			throw new BadRequestException("User ID ".$_GET['user_id']." not found.");
		}

		$url = $user;
		$extra_query = "AND `contact`.`nurl` = '%s' ";
		if (api_user() !== false) {
			$extra_query .= "AND `contact`.`uid`=" . intval(api_user());
		}
	}
	if (is_null($user) && !empty($_GET['screen_name'])) {
		$user = DBA::escape($_GET['screen_name']);
		$extra_query = "AND `contact`.`nick` = '%s' ";
		if (api_user() !== false) {
			$extra_query .= "AND `contact`.`uid`=".intval(api_user());
		}
	}

	if (is_null($user) && !empty($_GET['profileurl'])) {
		$user = DBA::escape(Strings::normaliseLink($_GET['profileurl']));
		$extra_query = "AND `contact`.`nurl` = '%s' ";
		if (api_user() !== false) {
			$extra_query .= "AND `contact`.`uid`=".intval(api_user());
		}
	}

	// $called_api is the API path exploded on / and is expected to have at least 2 elements
	if (is_null($user) && ($a->argc > (count($called_api) - 1)) && (count($called_api) > 0)) {
		$argid = count($called_api);
		if (!empty($a->argv[$argid])) {
			$data = explode(".", $a->argv[$argid]);
			if (count($data) > 1) {
				list($user, $null) = $data;
			}
		}
		if (is_numeric($user)) {
			$user = DBA::escape(api_unique_id_to_nurl(intval($user)));

			if ($user != "") {
				$url = $user;
				$extra_query = "AND `contact`.`nurl` = '%s' ";
				if (api_user() !== false) {
					$extra_query .= "AND `contact`.`uid`=" . intval(api_user());
				}
			}
		} else {
			$user = DBA::escape($user);
			$extra_query = "AND `contact`.`nick` = '%s' ";
			if (api_user() !== false) {
				$extra_query .= "AND `contact`.`uid`=" . intval(api_user());
			}
		}
	}

	Logger::info(API_LOG_PREFIX . 'getting user {user}', ['module' => 'api', 'action' => 'get_user', 'user' => $user]);

	if (!$user) {
		if (api_user() === false) {
			api_login($a);
			return false;
		} else {
			$user = $_SESSION['uid'];
			$extra_query = "AND `contact`.`uid` = %d AND `contact`.`self` ";
		}
	}

	Logger::info(API_LOG_PREFIX . 'found user {user}', ['module' => 'api', 'action' => 'get_user', 'user' => $user, 'extra_query' => $extra_query]);

	// user info
	$uinfo = q(
		"SELECT *, `contact`.`id` AS `cid` FROM `contact`
			WHERE 1
		$extra_query",
		$user
	);

	// Selecting the id by priority, friendica first
	if (is_array($uinfo)) {
		api_best_nickname($uinfo);
	}

	// if the contact wasn't found, fetch it from the contacts with uid = 0
	if (!DBA::isResult($uinfo)) {
		if ($url == "") {
			throw new BadRequestException("User not found.");
		}

		$contact = DBA::selectFirst('contact', [], ['uid' => 0, 'nurl' => Strings::normaliseLink($url)]);

		if (DBA::isResult($contact)) {
			$ret = [
				'id' => $contact["id"],
				'id_str' => (string) $contact["id"],
				'name' => $contact["name"],
				'screen_name' => (($contact['nick']) ? $contact['nick'] : $contact['name']),
				'location' => ($contact["location"] != "") ? $contact["location"] : ContactSelector::networkToName($contact['network'], $contact['url'], $contact['protocol']),
				'description' => BBCode::toPlaintext($contact["about"] ?? ''),
				'profile_image_url' => $contact["micro"],
				'profile_image_url_https' => $contact["micro"],
				'profile_image_url_profile_size' => $contact["thumb"],
				'profile_image_url_large' => $contact["photo"],
				'url' => $contact["url"],
				'protected' => false,
				'followers_count' => 0,
				'friends_count' => 0,
				'listed_count' => 0,
				'created_at' => api_date($contact["created"]),
				'favourites_count' => 0,
				'utc_offset' => 0,
				'time_zone' => 'UTC',
				'geo_enabled' => false,
				'verified' => false,
				'statuses_count' => 0,
				'lang' => '',
				'contributors_enabled' => false,
				'is_translator' => false,
				'is_translation_enabled' => false,
				'following' => false,
				'follow_request_sent' => false,
				'statusnet_blocking' => false,
				'notifications' => false,
				'statusnet_profile_url' => $contact["url"],
				'uid' => 0,
				'cid' => Contact::getIdForURL($contact["url"], api_user(), false),
				'pid' => Contact::getIdForURL($contact["url"], 0, false),
				'self' => 0,
				'network' => $contact["network"],
			];

			return $ret;
		} else {
			throw new BadRequestException("User ".$url." not found.");
		}
	}

	if ($uinfo[0]['self']) {
		if ($uinfo[0]['network'] == "") {
			$uinfo[0]['network'] = Protocol::DFRN;
		}

		$usr = DBA::selectFirst('user', ['default-location'], ['uid' => api_user()]);
		$profile = DBA::selectFirst('profile', ['about'], ['uid' => api_user(), 'is-default' => true]);
	}
	$countitems = 0;
	$countfriends = 0;
	$countfollowers = 0;
	$starred = 0;

	$pcontact_id  = Contact::getIdForURL($uinfo[0]['url'], 0, false);

	if (!empty($profile['about'])) {
		$description = $profile['about'];
	} else {
		$description = $uinfo[0]["about"];
	}

	if (!empty($usr['default-location'])) {
		$location = $usr['default-location'];
	} elseif (!empty($uinfo[0]["location"])) {
		$location = $uinfo[0]["location"];
	} else {
		$location = ContactSelector::networkToName($uinfo[0]['network'], $uinfo[0]['url'], $uinfo[0]['protocol']);
	}

	$ret = [
		'id' => intval($pcontact_id),
		'id_str' => (string) intval($pcontact_id),
		'name' => (($uinfo[0]['name']) ? $uinfo[0]['name'] : $uinfo[0]['nick']),
		'screen_name' => (($uinfo[0]['nick']) ? $uinfo[0]['nick'] : $uinfo[0]['name']),
		'location' => $location,
		'description' => BBCode::toPlaintext($description ?? ''),
		'profile_image_url' => $uinfo[0]['micro'],
		'profile_image_url_https' => $uinfo[0]['micro'],
		'profile_image_url_profile_size' => $uinfo[0]["thumb"],
		'profile_image_url_large' => $uinfo[0]["photo"],
		'url' => $uinfo[0]['url'],
		'protected' => false,
		'followers_count' => intval($countfollowers),
		'friends_count' => intval($countfriends),
		'listed_count' => 0,
		'created_at' => api_date($uinfo[0]['created']),
		'favourites_count' => intval($starred),
		'utc_offset' => "0",
		'time_zone' => 'UTC',
		'geo_enabled' => false,
		'verified' => true,
		'statuses_count' => intval($countitems),
		'lang' => '',
		'contributors_enabled' => false,
		'is_translator' => false,
		'is_translation_enabled' => false,
		'following' => (($uinfo[0]['rel'] == Contact::FOLLOWER) || ($uinfo[0]['rel'] == Contact::FRIEND)),
		'follow_request_sent' => false,
		'statusnet_blocking' => false,
		'notifications' => false,
		/// @TODO old way?
		//'statusnet_profile_url' => DI::baseUrl()."/contact/".$uinfo[0]['cid'],
		'statusnet_profile_url' => $uinfo[0]['url'],
		'uid' => intval($uinfo[0]['uid']),
		'cid' => intval($uinfo[0]['cid']),
		'pid' => Contact::getIdForURL($uinfo[0]["url"], 0, false),
		'self' => $uinfo[0]['self'],
		'network' => $uinfo[0]['network'],
	];

	// If this is a local user and it uses Frio, we can get its color preferences.
	if ($ret['self']) {
		$theme_info = DBA::selectFirst('user', ['theme'], ['uid' => $ret['uid']]);
		if ($theme_info['theme'] === 'frio') {
			$schema = DI::pConfig()->get($ret['uid'], 'frio', 'schema');

			if ($schema && ($schema != '---')) {
				if (file_exists('view/theme/frio/schema/'.$schema.'.php')) {
					$schemefile = 'view/theme/frio/schema/'.$schema.'.php';
					require_once $schemefile;
				}
			} else {
				$nav_bg = DI::pConfig()->get($ret['uid'], 'frio', 'nav_bg');
				$link_color = DI::pConfig()->get($ret['uid'], 'frio', 'link_color');
				$bgcolor = DI::pConfig()->get($ret['uid'], 'frio', 'background_color');
			}
			if (empty($nav_bg)) {
				$nav_bg = "#708fa0";
			}
			if (empty($link_color)) {
				$link_color = "#6fdbe8";
			}
			if (empty($bgcolor)) {
				$bgcolor = "#ededed";
			}

			$ret['profile_sidebar_fill_color'] = str_replace('#', '', $nav_bg);
			$ret['profile_link_color'] = str_replace('#', '', $link_color);
			$ret['profile_background_color'] = str_replace('#', '', $bgcolor);
		}
	}

	return $ret;
}

/**
 * return api-formatted array for item's author and owner
 *
 * @param App   $a    App
 * @param array $item item from db
 * @return array(array:author, array:owner)
 * @throws BadRequestException
 * @throws ImagickException
 * @throws InternalServerErrorException
 * @throws UnauthorizedException
 */
function api_item_get_user(App $a, $item)
{
	$status_user = api_get_user($a, $item['author-id'] ?? null);

	$author_user = $status_user;

	$status_user["protected"] = isset($item['private']) && ($item['private'] == Item::PRIVATE);

	if (($item['thr-parent'] ?? '') == ($item['uri'] ?? '')) {
		$owner_user = api_get_user($a, $item['owner-id'] ?? null);
	} else {
		$owner_user = $author_user;
	}

	return ([$status_user, $author_user, $owner_user]);
}

/**
 * walks recursively through an array with the possibility to change value and key
 *
 * @param array    $array    The array to walk through
 * @param callable $callback The callback function
 *
 * @return array the transformed array
 */
function api_walk_recursive(array &$array, callable $callback)
{
	$new_array = [];

	foreach ($array as $k => $v) {
		if (is_array($v)) {
			if ($callback($v, $k)) {
				$new_array[$k] = api_walk_recursive($v, $callback);
			}
		} else {
			if ($callback($v, $k)) {
				$new_array[$k] = $v;
			}
		}
	}
	$array = $new_array;

	return $array;
}

/**
 * Callback function to transform the array in an array that can be transformed in a XML file
 *
 * @param mixed  $item Array item value
 * @param string $key  Array key
 *
 * @return boolean Should the array item be deleted?
 */
function api_reformat_xml(&$item, &$key)
{
	if (is_bool($item)) {
		$item = ($item ? "true" : "false");
	}

	if (substr($key, 0, 10) == "statusnet_") {
		$key = "statusnet:".substr($key, 10);
	} elseif (substr($key, 0, 10) == "friendica_") {
		$key = "friendica:".substr($key, 10);
	}
	/// @TODO old-lost code?
	//else
	//	$key = "default:".$key;

	return true;
}

/**
 * Creates the XML from a JSON style array
 *
 * @param array  $data         JSON style array
 * @param string $root_element Name of the root element
 *
 * @return string The XML data
 */
function api_create_xml(array $data, $root_element)
{
	$childname = key($data);
	$data2 = array_pop($data);

	$namespaces = ["" => "http://api.twitter.com",
				"statusnet" => "http://status.net/schema/api/1/",
				"friendica" => "http://friendi.ca/schema/api/1/",
				"georss" => "http://www.georss.org/georss"];

	/// @todo Auto detection of needed namespaces
	if (in_array($root_element, ["ok", "hash", "config", "version", "ids", "notes", "photos"])) {
		$namespaces = [];
	}

	if (is_array($data2)) {
		$key = key($data2);
		api_walk_recursive($data2, "api_reformat_xml");

		if ($key == "0") {
			$data4 = [];
			$i = 1;

			foreach ($data2 as $item) {
				$data4[$i++ . ":" . $childname] = $item;
			}

			$data2 = $data4;
		}
	}

	$data3 = [$root_element => $data2];

	$ret = XML::fromArray($data3, $xml, false, $namespaces);
	return $ret;
}

/**
 * Formats the data according to the data type
 *
 * @param string $root_element Name of the root element
 * @param string $type         Return type (atom, rss, xml, json)
 * @param array  $data         JSON style array
 *
 * @return array|string (string|array) XML data or JSON data
 */
function api_format_data($root_element, $type, $data)
{
	switch ($type) {
		case "atom":
		case "rss":
		case "xml":
			$ret = api_create_xml($data, $root_element);
			break;
		case "json":
		default:
			$ret = $data;
			break;
	}
	return $ret;
}

/**
 * TWITTER API
 */

/**
 * Returns an HTTP 200 OK response code and a representation of the requesting user if authentication was successful;
 * returns a 401 status code and an error message if not.
 *
 * @see https://developer.twitter.com/en/docs/accounts-and-users/manage-account-settings/api-reference/get-account-verify_credentials
 *
 * @param string $type Return type (atom, rss, xml, json)
 * @return array|string
 * @throws BadRequestException
 * @throws ForbiddenException
 * @throws ImagickException
 * @throws InternalServerErrorException
 * @throws UnauthorizedException
 */
function api_account_verify_credentials($type)
{
	$a = DI::app();

	if (api_user() === false) {
		throw new ForbiddenException();
	}

	unset($_REQUEST["user_id"]);
	unset($_GET["user_id"]);

	unset($_REQUEST["screen_name"]);
	unset($_GET["screen_name"]);

	$skip_status = $_REQUEST['skip_status'] ?? false;

	$user_info = api_get_user($a);

	// "verified" isn't used here in the standard
	unset($user_info["verified"]);

	// - Adding last status
	if (!$skip_status) {
		$item = api_get_last_status($user_info['pid'], $user_info['uid']);
		if (!empty($item)) {
			$user_info['status'] = api_format_item($item, $type);
		}
	}

	// "uid" and "self" are only needed for some internal stuff, so remove it from here
	unset($user_info["uid"]);
	unset($user_info["self"]);

	return api_format_data("user", $type, ['user' => $user_info]);
}

/// @TODO move to top of file or somewhere better
api_register_func('api/account/verify_credentials', 'api_account_verify_credentials', true);

/**
 * Get data from $_POST or $_GET
 *
 * @param string $k
 * @return null
 */
function requestdata($k)
{
	if (!empty($_POST[$k])) {
		return $_POST[$k];
	}
	if (!empty($_GET[$k])) {
		return $_GET[$k];
	}
	return null;
}

/**
 * Deprecated function to upload media.
 *
 * @param string $type Return type (atom, rss, xml, json)
 *
 * @return array|string
 * @throws BadRequestException
 * @throws ForbiddenException
 * @throws ImagickException
 * @throws InternalServerErrorException
 * @throws UnauthorizedException
 */
function api_statuses_mediap($type)
{
	$a = DI::app();

	if (api_user() === false) {
		Logger::log('api_statuses_update: no user');
		throw new ForbiddenException();
	}
	$user_info = api_get_user($a);

	$_REQUEST['profile_uid'] = api_user();
	$_REQUEST['api_source'] = true;
	$txt = requestdata('status');
	/// @TODO old-lost code?
	//$txt = urldecode(requestdata('status'));

	if ((strpos($txt, '<') !== false) || (strpos($txt, '>') !== false)) {
		$txt = HTML::toBBCodeVideo($txt);
		$config = HTMLPurifier_Config::createDefault();
		$config->set('Cache.DefinitionImpl', null);
		$purifier = new HTMLPurifier($config);
		$txt = $purifier->purify($txt);
	}
	$txt = HTML::toBBCode($txt);

	$a->argv[1] = $user_info['screen_name']; //should be set to username?

	$picture = wall_upload_post($a, false);

	// now that we have the img url in bbcode we can add it to the status and insert the wall item.
	$_REQUEST['body'] = $txt . "\n\n" . '[url=' . $picture["albumpage"] . '][img]' . $picture["preview"] . "[/img][/url]";
	$item_id = item_post($a);

	// output the post that we just posted.
	return api_status_show($type, $item_id);
}

/// @TODO move this to top of file or somewhere better!
api_register_func('api/statuses/mediap', 'api_statuses_mediap', true, API_METHOD_POST);

/**
 * Updates the user’s current status.
 *
 * @param string $type Return type (atom, rss, xml, json)
 *
 * @return array|string
 * @throws BadRequestException
 * @throws ForbiddenException
 * @throws ImagickException
 * @throws InternalServerErrorException
 * @throws TooManyRequestsException
 * @throws UnauthorizedException
 * @see https://developer.twitter.com/en/docs/tweets/post-and-engage/api-reference/post-statuses-update
 */
function api_statuses_update($type)
{
	$a = DI::app();

	if (api_user() === false) {
		Logger::log('api_statuses_update: no user');
		throw new ForbiddenException();
	}

	api_get_user($a);

	// convert $_POST array items to the form we use for web posts.
	if (requestdata('htmlstatus')) {
		$txt = requestdata('htmlstatus');
		if ((strpos($txt, '<') !== false) || (strpos($txt, '>') !== false)) {
			$txt = HTML::toBBCodeVideo($txt);

			$config = HTMLPurifier_Config::createDefault();
			$config->set('Cache.DefinitionImpl', null);

			$purifier = new HTMLPurifier($config);
			$txt = $purifier->purify($txt);

			$_REQUEST['body'] = HTML::toBBCode($txt);
		}
	} else {
		$_REQUEST['body'] = requestdata('status');
	}

	$_REQUEST['title'] = requestdata('title');

	$parent = requestdata('in_reply_to_status_id');

	// Twidere sends "-1" if it is no reply ...
	if ($parent == -1) {
		$parent = "";
	}

	if (ctype_digit($parent)) {
		$_REQUEST['parent'] = $parent;
	} else {
		$_REQUEST['parent_uri'] = $parent;
	}

	if (requestdata('lat') && requestdata('long')) {
		$_REQUEST['coord'] = sprintf("%s %s", requestdata('lat'), requestdata('long'));
	}
	$_REQUEST['profile_uid'] = api_user();

	if (!$parent) {
		// Check for throttling (maximum posts per day, week and month)
		$throttle_day = DI::config()->get('system', 'throttle_limit_day');
		if ($throttle_day > 0) {
			$datefrom = date(DateTimeFormat::MYSQL, time() - 24*60*60);

			$condition = ["`uid` = ? AND `wall` AND `received` > ?", api_user(), $datefrom];
			$posts_day = DBA::count('thread', $condition);

			if ($posts_day > $throttle_day) {
				Logger::log('Daily posting limit reached for user '.api_user(), Logger::DEBUG);
				// die(api_error($type, DI::l10n()->t("Daily posting limit of %d posts reached. The post was rejected.", $throttle_day));
				throw new TooManyRequestsException(DI::l10n()->tt("Daily posting limit of %d post reached. The post was rejected.", "Daily posting limit of %d posts reached. The post was rejected.", $throttle_day));
			}
		}

		$throttle_week = DI::config()->get('system', 'throttle_limit_week');
		if ($throttle_week > 0) {
			$datefrom = date(DateTimeFormat::MYSQL, time() - 24*60*60*7);

			$condition = ["`uid` = ? AND `wall` AND `received` > ?", api_user(), $datefrom];
			$posts_week = DBA::count('thread', $condition);

			if ($posts_week > $throttle_week) {
				Logger::log('Weekly posting limit reached for user '.api_user(), Logger::DEBUG);
				// die(api_error($type, DI::l10n()->t("Weekly posting limit of %d posts reached. The post was rejected.", $throttle_week)));
				throw new TooManyRequestsException(DI::l10n()->tt("Weekly posting limit of %d post reached. The post was rejected.", "Weekly posting limit of %d posts reached. The post was rejected.", $throttle_week));
			}
		}

		$throttle_month = DI::config()->get('system', 'throttle_limit_month');
		if ($throttle_month > 0) {
			$datefrom = date(DateTimeFormat::MYSQL, time() - 24*60*60*30);

			$condition = ["`uid` = ? AND `wall` AND `received` > ?", api_user(), $datefrom];
			$posts_month = DBA::count('thread', $condition);

			if ($posts_month > $throttle_month) {
				Logger::log('Monthly posting limit reached for user '.api_user(), Logger::DEBUG);
				// die(api_error($type, DI::l10n()->t("Monthly posting limit of %d posts reached. The post was rejected.", $throttle_month));
				throw new TooManyRequestsException(DI::l10n()->t("Monthly posting limit of %d post reached. The post was rejected.", "Monthly posting limit of %d posts reached. The post was rejected.", $throttle_month));
			}
		}
	}

	if (!empty($_FILES['media'])) {
		// upload the image if we have one
		$picture = wall_upload_post($a, false);
		if (is_array($picture)) {
			$_REQUEST['body'] .= "\n\n" . '[url=' . $picture["albumpage"] . '][img]' . $picture["preview"] . "[/img][/url]";
		}
	}

	if (requestdata('media_ids')) {
		$ids = explode(',', requestdata('media_ids'));
		foreach ($ids as $id) {
			$r = q(
				"SELECT `resource-id`, `scale`, `nickname`, `type`, `desc` FROM `photo` INNER JOIN `user` ON `user`.`uid` = `photo`.`uid` WHERE `resource-id` IN (SELECT `resource-id` FROM `photo` WHERE `id` = %d) AND `scale` > 0 AND `photo`.`uid` = %d ORDER BY `photo`.`width` DESC LIMIT 1",
				intval($id),
				api_user()
			);
			if (DBA::isResult($r)) {
				$phototypes = Images::supportedTypes();
				$ext = $phototypes[$r[0]['type']];
				$description = $r[0]['desc'] ?? '';
				$_REQUEST['body'] .= "\n\n" . '[url=' . DI::baseUrl() . '/photos/' . $r[0]['nickname'] . '/image/' . $r[0]['resource-id'] . ']';
				$_REQUEST['body'] .= '[img=' . DI::baseUrl() . '/photo/' . $r[0]['resource-id'] . '-' . $r[0]['scale'] . '.' . $ext . ']' . $description . '[/img][/url]';
			}
		}
	}

	// set this so that the item_post() function is quiet and doesn't redirect or emit json

	$_REQUEST['api_source'] = true;

	if (empty($_REQUEST['source'])) {
		$_REQUEST["source"] = api_source();
	}

	// call out normal post function
	$item_id = item_post($a);

	// output the post that we just posted.
	return api_status_show($type, $item_id);
}

/// @TODO move to top of file or somewhere better
api_register_func('api/statuses/update', 'api_statuses_update', true, API_METHOD_POST);
api_register_func('api/statuses/update_with_media', 'api_statuses_update', true, API_METHOD_POST);

/**
 * Uploads an image to Friendica.
 *
 * @return array
 * @throws BadRequestException
 * @throws ForbiddenException
 * @throws ImagickException
 * @throws InternalServerErrorException
 * @throws UnauthorizedException
 * @see https://developer.twitter.com/en/docs/media/upload-media/api-reference/post-media-upload
 */
function api_media_upload()
{
	$a = DI::app();

	if (api_user() === false) {
		Logger::log('no user');
		throw new ForbiddenException();
	}

	api_get_user($a);

	if (empty($_FILES['media'])) {
		// Output error
		throw new BadRequestException("No media.");
	}

	$media = wall_upload_post($a, false);
	if (!$media) {
		// Output error
		throw new InternalServerErrorException();
	}

	$returndata = [];
	$returndata["media_id"] = $media["id"];
	$returndata["media_id_string"] = (string)$media["id"];
	$returndata["size"] = $media["size"];
	$returndata["image"] = ["w" => $media["width"],
				"h" => $media["height"],
				"image_type" => $media["type"],
				"friendica_preview_url" => $media["preview"]];

	Logger::info('Media uploaded', ['return' => $returndata]);

	return ["media" => $returndata];
}

/// @TODO move to top of file or somewhere better
api_register_func('api/media/upload', 'api_media_upload', true, API_METHOD_POST);

/**
 * Updates media meta data (picture descriptions)
 *
 * @param string $type Return type (atom, rss, xml, json)
 *
 * @return array|string
 * @throws BadRequestException
 * @throws ForbiddenException
 * @throws ImagickException
 * @throws InternalServerErrorException
 * @throws TooManyRequestsException
 * @throws UnauthorizedException
 * @see https://developer.twitter.com/en/docs/tweets/post-and-engage/api-reference/post-statuses-update
 *
 * @todo Compare the corresponding Twitter function for correct return values
 */
function api_media_metadata_create($type)
{
	$a = DI::app();

	if (api_user() === false) {
		Logger::info('no user');
		throw new ForbiddenException();
	}

	api_get_user($a);

	$postdata = Network::postdata();

	if (empty($postdata)) {
		throw new BadRequestException("No post data");
	}

	$data = json_decode($postdata, true);
	if (empty($data)) {
		throw new BadRequestException("Invalid post data");
	}

	if (empty($data['media_id']) || empty($data['alt_text'])) {
		throw new BadRequestException("Missing post data values");
	}

	if (empty($data['alt_text']['text'])) {
		throw new BadRequestException("No alt text.");
	}

	Logger::info('Updating metadata', ['media_id' => $data['media_id']]);

	$condition =  ['id' => $data['media_id'], 'uid' => api_user()];
	$photo = DBA::selectFirst('photo', ['resource-id'], $condition);
	if (!DBA::isResult($photo)) {
		throw new BadRequestException("Metadata not found.");
	}

	DBA::update('photo', ['desc' => $data['alt_text']['text']], ['resource-id' => $photo['resource-id']]);
}

api_register_func('api/media/metadata/create', 'api_media_metadata_create', true, API_METHOD_POST);

/**
 * @param string $type    Return format (atom, rss, xml, json)
 * @param int    $item_id
 * @return array|string
 * @throws Exception
 */
function api_status_show($type, $item_id)
{
	Logger::info(API_LOG_PREFIX . 'Start', ['action' => 'status_show', 'type' => $type, 'item_id' => $item_id]);

	$status_info = [];

	$item = api_get_item(['id' => $item_id]);
	if (!empty($item)) {
		$status_info = api_format_item($item, $type);
	}

	Logger::info(API_LOG_PREFIX . 'End', ['action' => 'get_status', 'status_info' => $status_info]);

	return api_format_data('statuses', $type, ['status' => $status_info]);
}

/**
 * Retrieves the last public status of the provided user info
 *
 * @param int    $ownerId Public contact Id
 * @param int    $uid     User Id
 * @return array
 * @throws Exception
 */
function api_get_last_status($ownerId, $uid)
{
	$condition = [
		'author-id'=> $ownerId,
		'uid'      => $uid,
		'gravity'  => [GRAVITY_PARENT, GRAVITY_COMMENT],
		'private'  => [Item::PUBLIC, Item::UNLISTED]
	];

	$item = api_get_item($condition);

	return $item;
}

/**
 * Retrieves a single item record based on the provided condition and converts it for API use.
 *
 * @param array $condition Item table condition array
 * @return array
 * @throws Exception
 */
function api_get_item(array $condition)
{
	$item = Post::selectFirst(Item::DISPLAY_FIELDLIST, $condition, ['order' => ['id' => true]]);

	return $item;
}

/**
 * Returns extended information of a given user, specified by ID or screen name as per the required id parameter.
 * The author's most recent status will be returned inline.
 *
 * @param string $type Return type (atom, rss, xml, json)
 * @return array|string
 * @throws BadRequestException
 * @throws ImagickException
 * @throws InternalServerErrorException
 * @throws UnauthorizedException
 * @see https://developer.twitter.com/en/docs/accounts-and-users/follow-search-get-users/api-reference/get-users-show
 */
function api_users_show($type)
{
	$a = Friendica\DI::app();

	$user_info = api_get_user($a);

	$item = api_get_last_status($user_info['pid'], $user_info['uid']);
	if (!empty($item)) {
		$user_info['status'] = api_format_item($item, $type);
	}

	// "uid" and "self" are only needed for some internal stuff, so remove it from here
	unset($user_info['uid']);
	unset($user_info['self']);

	return api_format_data('user', $type, ['user' => $user_info]);
}

/// @TODO move to top of file or somewhere better
api_register_func('api/users/show', 'api_users_show');
api_register_func('api/externalprofile/show', 'api_users_show');

/**
 * Search a public user account.
 *
 * @param string $type Return type (atom, rss, xml, json)
 *
 * @return array|string
 * @throws BadRequestException
 * @throws ImagickException
 * @throws InternalServerErrorException
 * @throws UnauthorizedException
 * @see https://developer.twitter.com/en/docs/accounts-and-users/follow-search-get-users/api-reference/get-users-search
 */
function api_users_search($type)
{
	$a = DI::app();

	$userlist = [];

	if (!empty($_GET['q'])) {
		$contacts = Contact::selectToArray(
			['id'],
			[
				'`uid` = 0 AND (`name` = ? OR `nick` = ? OR `url` = ? OR `addr` = ?)',
				$_GET['q'],
				$_GET['q'],
				$_GET['q'],
				$_GET['q'],
			]
		);

		if (DBA::isResult($contacts)) {
			$k = 0;
			foreach ($contacts as $contact) {
				$user_info = api_get_user($a, $contact['id']);

				if ($type == 'xml') {
					$userlist[$k++ . ':user'] = $user_info;
				} else {
					$userlist[] = $user_info;
				}
			}
			$userlist = ['users' => $userlist];
		} else {
			throw new NotFoundException('User ' . $_GET['q'] . ' not found.');
		}
	} else {
		throw new BadRequestException('No search term specified.');
	}

	return api_format_data('users', $type, $userlist);
}

/// @TODO move to top of file or somewhere better
api_register_func('api/users/search', 'api_users_search');

/**
 * Return user objects
 *
 * @see https://developer.twitter.com/en/docs/accounts-and-users/follow-search-get-users/api-reference/get-users-lookup
 *
 * @param string $type Return format: json or xml
 *
 * @return array|string
 * @throws BadRequestException
 * @throws ImagickException
 * @throws InternalServerErrorException
 * @throws NotFoundException if the results are empty.
 * @throws UnauthorizedException
 */
function api_users_lookup($type)
{
	$users = [];

	if (!empty($_REQUEST['user_id'])) {
		foreach (explode(',', $_REQUEST['user_id']) as $id) {
			if (!empty($id)) {
				$users[] = api_get_user(DI::app(), $id);
			}
		}
	}

	if (empty($users)) {
		throw new NotFoundException;
	}

	return api_format_data("users", $type, ['users' => $users]);
}

/// @TODO move to top of file or somewhere better
api_register_func('api/users/lookup', 'api_users_lookup', true);

/**
 * Returns statuses that match a specified query.
 *
 * @see https://developer.twitter.com/en/docs/tweets/search/api-reference/get-search-tweets
 *
 * @param string $type Return format: json, xml, atom, rss
 *
 * @return array|string
 * @throws BadRequestException if the "q" parameter is missing.
 * @throws ForbiddenException
 * @throws ImagickException
 * @throws InternalServerErrorException
 * @throws UnauthorizedException
 */
function api_search($type)
{
	$a = DI::app();
	$user_info = api_get_user($a);

	if (api_user() === false || $user_info === false) {
		throw new ForbiddenException();
	}

	if (empty($_REQUEST['q'])) {
		throw new BadRequestException('q parameter is required.');
	}

	$searchTerm = trim(rawurldecode($_REQUEST['q']));

	$data = [];
	$data['status'] = [];
	$count = 15;
	$exclude_replies = !empty($_REQUEST['exclude_replies']);
	if (!empty($_REQUEST['rpp'])) {
		$count = $_REQUEST['rpp'];
	} elseif (!empty($_REQUEST['count'])) {
		$count = $_REQUEST['count'];
	}

	$since_id = $_REQUEST['since_id'] ?? 0;
	$max_id = $_REQUEST['max_id'] ?? 0;
	$page = $_REQUEST['page'] ?? 1;

	$start = max(0, ($page - 1) * $count);

	$params = ['order' => ['id' => true], 'limit' => [$start, $count]];
	if (preg_match('/^#(\w+)$/', $searchTerm, $matches) === 1 && isset($matches[1])) {
		$searchTerm = $matches[1];
		$condition = ["`iid` > ? AND `name` = ? AND (NOT `private` OR (`private` AND `uid` = ?))", $since_id, $searchTerm, local_user()];
		$tags = DBA::select('tag-search-view', ['uri-id'], $condition);
		$uriids = [];
		while ($tag = DBA::fetch($tags)) {
			$uriids[] = $tag['uri-id'];
		}
		DBA::close($tags);

		if (empty($uriids)) {
			return api_format_data('statuses', $type, $data);
		}

		$condition = ['uri-id' => $uriids];
		if ($exclude_replies) {
			$condition['gravity'] = GRAVITY_PARENT;
		}

		$params['group_by'] = ['uri-id'];
	} else {
		$condition = ["`id` > ?
			" . ($exclude_replies ? " AND `gravity` = " . GRAVITY_PARENT : ' ') . "
			AND (`uid` = 0 OR (`uid` = ? AND NOT `global`))
			AND `body` LIKE CONCAT('%',?,'%')",
			$since_id, api_user(), $_REQUEST['q']];
		if ($max_id > 0) {
			$condition[0] .= ' AND `id` <= ?';
			$condition[] = $max_id;
		}
	}

	$statuses = [];

	if (parse_url($searchTerm, PHP_URL_SCHEME) != '') {
		$id = Item::fetchByLink($searchTerm, api_user());
		if (!$id) {
			// Public post
			$id = Item::fetchByLink($searchTerm);
		}

		if (!empty($id)) {
			$statuses = Post::select([], ['id' => $id]);
		}
	}

	$statuses = $statuses ?: Post::selectForUser(api_user(), [], $condition, $params);

	$data['status'] = api_format_items(Post::toArray($statuses), $user_info);

	bindComments($data['status']);

	return api_format_data('statuses', $type, $data);
}

/// @TODO move to top of file or somewhere better
api_register_func('api/search/tweets', 'api_search', true);
api_register_func('api/search', 'api_search', true);

/**
 * Returns the most recent statuses posted by the user and the users they follow.
 *
 * @see  https://developer.twitter.com/en/docs/tweets/timelines/api-reference/get-statuses-home_timeline
 *
 * @param string $type Return type (atom, rss, xml, json)
 *
 * @return array|string
 * @throws BadRequestException
 * @throws ForbiddenException
 * @throws ImagickException
 * @throws InternalServerErrorException
 * @throws UnauthorizedException
 * @todo Optional parameters
 * @todo Add reply info
 */
function api_statuses_home_timeline($type)
{
	$a = DI::app();
	$user_info = api_get_user($a);

	if (api_user() === false || $user_info === false) {
		throw new ForbiddenException();
	}

	unset($_REQUEST["user_id"]);
	unset($_GET["user_id"]);

	unset($_REQUEST["screen_name"]);
	unset($_GET["screen_name"]);

	// get last network messages

	// params
	$count = $_REQUEST['count'] ?? 20;
	$page = $_REQUEST['page']?? 0;
	$since_id = $_REQUEST['since_id'] ?? 0;
	$max_id = $_REQUEST['max_id'] ?? 0;
	$exclude_replies = !empty($_REQUEST['exclude_replies']);
	$conversation_id = $_REQUEST['conversation_id'] ?? 0;

	$start = max(0, ($page - 1) * $count);

	$condition = ["`uid` = ? AND `gravity` IN (?, ?) AND `id` > ?",
		api_user(), GRAVITY_PARENT, GRAVITY_COMMENT, $since_id];

	if ($max_id > 0) {
		$condition[0] .= " AND `id` <= ?";
		$condition[] = $max_id;
	}
	if ($exclude_replies) {
		$condition[0] .= ' AND `gravity` = ?';
		$condition[] = GRAVITY_PARENT;
	}
	if ($conversation_id > 0) {
		$condition[0] .= " AND `parent` = ?";
		$condition[] = $conversation_id;
	}

	$params = ['order' => ['id' => true], 'limit' => [$start, $count]];
	$statuses = Post::selectForUser(api_user(), [], $condition, $params);

	$items = Post::toArray($statuses);

	$ret = api_format_items($items, $user_info, false, $type);

	// Set all posts from the query above to seen
	$idarray = [];
	foreach ($items as $item) {
		$idarray[] = intval($item["id"]);
	}

	if (!empty($idarray)) {
		$unseen = Post::exists(['unseen' => true, 'id' => $idarray]);
		if ($unseen) {
			Item::update(['unseen' => false], ['unseen' => true, 'id' => $idarray]);
		}
	}

	bindComments($ret);

	$data = ['status' => $ret];
	switch ($type) {
		case "atom":
			break;
		case "rss":
			$data = api_rss_extra($a, $data, $user_info);
			break;
	}

	return api_format_data("statuses", $type, $data);
}


/// @TODO move to top of file or somewhere better
api_register_func('api/statuses/home_timeline', 'api_statuses_home_timeline', true);
api_register_func('api/statuses/friends_timeline', 'api_statuses_home_timeline', true);

/**
 * Returns the most recent statuses from public users.
 *
 * @param string $type Return type (atom, rss, xml, json)
 *
 * @return array|string
 * @throws BadRequestException
 * @throws ForbiddenException
 * @throws ImagickException
 * @throws InternalServerErrorException
 * @throws UnauthorizedException
 */
function api_statuses_public_timeline($type)
{
	$a = DI::app();
	$user_info = api_get_user($a);

	if (api_user() === false || $user_info === false) {
		throw new ForbiddenException();
	}

	// get last network messages

	// params
	$count = $_REQUEST['count'] ?? 20;
	$page = $_REQUEST['page'] ?? 1;
	$since_id = $_REQUEST['since_id'] ?? 0;
	$max_id = $_REQUEST['max_id'] ?? 0;
	$exclude_replies = (!empty($_REQUEST['exclude_replies']) ? 1 : 0);
	$conversation_id = $_REQUEST['conversation_id'] ?? 0;

	$start = max(0, ($page - 1) * $count);

	if ($exclude_replies && !$conversation_id) {
		$condition = ["`gravity` IN (?, ?) AND `iid` > ? AND `private` = ? AND `wall` AND NOT `author-hidden`",
			GRAVITY_PARENT, GRAVITY_COMMENT, $since_id, Item::PUBLIC];

		if ($max_id > 0) {
			$condition[0] .= " AND `iid` <= ?";
			$condition[] = $max_id;
		}

		$params = ['order' => ['iid' => true], 'limit' => [$start, $count]];
		$statuses = Post::selectThreadForUser(api_user(), Item::DISPLAY_FIELDLIST, $condition, $params);

		$r = Post::toArray($statuses);
	} else {
		$condition = ["`gravity` IN (?, ?) AND `id` > ? AND `private` = ? AND `wall` AND `origin` AND NOT `author-hidden`",
			GRAVITY_PARENT, GRAVITY_COMMENT, $since_id, Item::PUBLIC];

		if ($max_id > 0) {
			$condition[0] .= " AND `id` <= ?";
			$condition[] = $max_id;
		}
		if ($conversation_id > 0) {
			$condition[0] .= " AND `parent` = ?";
			$condition[] = $conversation_id;
		}

		$params = ['order' => ['id' => true], 'limit' => [$start, $count]];
		$statuses = Post::selectForUser(api_user(), [], $condition, $params);

		$r = Post::toArray($statuses);
	}

	$ret = api_format_items($r, $user_info, false, $type);

	bindComments($ret);

	$data = ['status' => $ret];
	switch ($type) {
		case "atom":
			break;
		case "rss":
			$data = api_rss_extra($a, $data, $user_info);
			break;
	}

	return api_format_data("statuses", $type, $data);
}

/// @TODO move to top of file or somewhere better
api_register_func('api/statuses/public_timeline', 'api_statuses_public_timeline', true);

/**
 * Returns the most recent statuses posted by users this node knows about.
 *
 * @param string $type Return format: json, xml, atom, rss
 * @return array|string
 * @throws BadRequestException
 * @throws ForbiddenException
 * @throws ImagickException
 * @throws InternalServerErrorException
 * @throws UnauthorizedException
 */
function api_statuses_networkpublic_timeline($type)
{
	$a = DI::app();
	$user_info = api_get_user($a);

	if (api_user() === false || $user_info === false) {
		throw new ForbiddenException();
	}

	$since_id        = $_REQUEST['since_id'] ?? 0;
	$max_id          = $_REQUEST['max_id'] ?? 0;

	// pagination
	$count = $_REQUEST['count'] ?? 20;
	$page  = $_REQUEST['page'] ?? 1;

	$start = max(0, ($page - 1) * $count);

	$condition = ["`uid` = 0 AND `gravity` IN (?, ?) AND `iid` > ? AND `private` = ?",
		GRAVITY_PARENT, GRAVITY_COMMENT, $since_id, Item::PUBLIC];

	if ($max_id > 0) {
		$condition[0] .= " AND `iid` <= ?";
		$condition[] = $max_id;
	}

	$params = ['order' => ['iid' => true], 'limit' => [$start, $count]];
	$statuses = Post::selectThreadForUser(api_user(), Item::DISPLAY_FIELDLIST, $condition, $params);

	$ret = api_format_items(Post::toArray($statuses), $user_info, false, $type);

	bindComments($ret);

	$data = ['status' => $ret];
	switch ($type) {
		case "atom":
			break;
		case "rss":
			$data = api_rss_extra($a, $data, $user_info);
			break;
	}

	return api_format_data("statuses", $type, $data);
}

/// @TODO move to top of file or somewhere better
api_register_func('api/statuses/networkpublic_timeline', 'api_statuses_networkpublic_timeline', true);

/**
 * Returns a single status.
 *
 * @param string $type Return type (atom, rss, xml, json)
 *
 * @return array|string
 * @throws BadRequestException
 * @throws ForbiddenException
 * @throws ImagickException
 * @throws InternalServerErrorException
 * @throws UnauthorizedException
 * @see https://developer.twitter.com/en/docs/tweets/post-and-engage/api-reference/get-statuses-show-id
 */
function api_statuses_show($type)
{
	$a = DI::app();
	$user_info = api_get_user($a);

	if (api_user() === false || $user_info === false) {
		throw new ForbiddenException();
	}

	// params
	$id = intval($a->argv[3] ?? 0);

	if ($id == 0) {
		$id = intval($_REQUEST['id'] ?? 0);
	}

	// Hotot workaround
	if ($id == 0) {
		$id = intval($a->argv[4] ?? 0);
	}

	Logger::log('API: api_statuses_show: ' . $id);

	$conversation = !empty($_REQUEST['conversation']);

	// try to fetch the item for the local user - or the public item, if there is no local one
	$uri_item = Post::selectFirst(['uri-id'], ['id' => $id]);
	if (!DBA::isResult($uri_item)) {
		throw new BadRequestException(sprintf("There is no status with the id %d", $id));
	}

	$item = Post::selectFirst(['id'], ['uri-id' => $uri_item['uri-id'], 'uid' => [0, api_user()]], ['order' => ['uid' => true]]);
	if (!DBA::isResult($item)) {
		throw new BadRequestException(sprintf("There is no status with the uri-id %d for the given user.", $uri_item['uri-id']));
	}

	$id = $item['id'];

	if ($conversation) {
		$condition = ['parent' => $id, 'gravity' => [GRAVITY_PARENT, GRAVITY_COMMENT]];
		$params = ['order' => ['id' => true]];
	} else {
		$condition = ['id' => $id, 'gravity' => [GRAVITY_PARENT, GRAVITY_COMMENT]];
		$params = [];
	}

	$statuses = Post::selectForUser(api_user(), [], $condition, $params);

	/// @TODO How about copying this to above methods which don't check $r ?
	if (!DBA::isResult($statuses)) {
		throw new BadRequestException(sprintf("There is no status or conversation with the id %d.", $id));
	}

	$ret = api_format_items(Post::toArray($statuses), $user_info, false, $type);

	if ($conversation) {
		$data = ['status' => $ret];
		return api_format_data("statuses", $type, $data);
	} else {
		$data = ['status' => $ret[0]];
		return api_format_data("status", $type, $data);
	}
}

/// @TODO move to top of file or somewhere better
api_register_func('api/statuses/show', 'api_statuses_show', true);

/**
 *
 * @param string $type Return type (atom, rss, xml, json)
 *
 * @return array|string
 * @throws BadRequestException
 * @throws ForbiddenException
 * @throws ImagickException
 * @throws InternalServerErrorException
 * @throws UnauthorizedException
 * @todo nothing to say?
 */
function api_conversation_show($type)
{
	$a = DI::app();
	$user_info = api_get_user($a);

	if (api_user() === false || $user_info === false) {
		throw new ForbiddenException();
	}

	// params
	$id       = intval($a->argv[3]           ?? 0);
	$since_id = intval($_REQUEST['since_id'] ?? 0);
	$max_id   = intval($_REQUEST['max_id']   ?? 0);
	$count    = intval($_REQUEST['count']    ?? 20);
	$page     = intval($_REQUEST['page']     ?? 1);

	$start = max(0, ($page - 1) * $count);

	if ($id == 0) {
		$id = intval($_REQUEST['id'] ?? 0);
	}

	// Hotot workaround
	if ($id == 0) {
		$id = intval($a->argv[4] ?? 0);
	}

	Logger::info(API_LOG_PREFIX . '{subaction}', ['module' => 'api', 'action' => 'conversation', 'subaction' => 'show', 'id' => $id]);

	// try to fetch the item for the local user - or the public item, if there is no local one
	$item = Post::selectFirst(['parent-uri-id'], ['id' => $id]);
	if (!DBA::isResult($item)) {
		throw new BadRequestException("There is no status with this id.");
	}

	$parent = Post::selectFirst(['id'], ['uri-id' => $item['parent-uri-id'], 'uid' => [0, api_user()]], ['order' => ['uid' => true]]);
	if (!DBA::isResult($parent)) {
		throw new BadRequestException("There is no status with this id.");
	}

	$id = $parent['id'];

	$condition = ["`parent` = ? AND `uid` IN (0, ?) AND `gravity` IN (?, ?) AND `id` > ?",
		$id, api_user(), GRAVITY_PARENT, GRAVITY_COMMENT, $since_id];

	if ($max_id > 0) {
		$condition[0] .= " AND `id` <= ?";
		$condition[] = $max_id;
	}

	$params = ['order' => ['id' => true], 'limit' => [$start, $count]];
	$statuses = Post::selectForUser(api_user(), [], $condition, $params);

	if (!DBA::isResult($statuses)) {
		throw new BadRequestException("There is no status with id $id.");
	}

	$ret = api_format_items(Post::toArray($statuses), $user_info, false, $type);

	$data = ['status' => $ret];
	return api_format_data("statuses", $type, $data);
}

/// @TODO move to top of file or somewhere better
api_register_func('api/conversation/show', 'api_conversation_show', true);
api_register_func('api/statusnet/conversation', 'api_conversation_show', true);

/**
 * Repeats a status.
 *
 * @param string $type Return type (atom, rss, xml, json)
 *
 * @return array|string
 * @throws BadRequestException
 * @throws ForbiddenException
 * @throws ImagickException
 * @throws InternalServerErrorException
 * @throws UnauthorizedException
 * @see https://developer.twitter.com/en/docs/tweets/post-and-engage/api-reference/post-statuses-retweet-id
 */
function api_statuses_repeat($type)
{
	global $called_api;

	$a = DI::app();

	if (api_user() === false) {
		throw new ForbiddenException();
	}

	api_get_user($a);

	// params
	$id = intval($a->argv[3] ?? 0);

	if ($id == 0) {
		$id = intval($_REQUEST['id'] ?? 0);
	}

	// Hotot workaround
	if ($id == 0) {
		$id = intval($a->argv[4] ?? 0);
	}

	Logger::log('API: api_statuses_repeat: '.$id);

	$fields = ['uri-id', 'network', 'body', 'title', 'author-name', 'author-link', 'author-avatar', 'guid', 'created', 'plink'];
	$item = Post::selectFirst($fields, ['id' => $id, 'private' => [Item::PUBLIC, Item::UNLISTED]]);
 
	if (DBA::isResult($item) && !empty($item['body'])) {
		if (in_array($item['network'], [Protocol::ACTIVITYPUB, Protocol::DFRN, Protocol::TWITTER])) {
			if (!Item::performActivity($id, 'announce', local_user())) {
				throw new InternalServerErrorException();
			}
		
			$item_id = $id;
		} else {
			if (strpos($item['body'], "[/share]") !== false) {
				$pos = strpos($item['body'], "[share");
				$post = substr($item['body'], $pos);
			} else {
				$post = BBCode::getShareOpeningTag($item['author-name'], $item['author-link'], $item['author-avatar'], $item['plink'], $item['created'], $item['guid']);

				if (!empty($item['title'])) {
					$post .= '[h3]' . $item['title'] . "[/h3]\n";
				}

				$post .= $item['body'];
				$post .= "[/share]";
			}
			$_REQUEST['body'] = $post;
			$_REQUEST['profile_uid'] = api_user();
			$_REQUEST['api_source'] = true;

			if (empty($_REQUEST['source'])) {
				$_REQUEST["source"] = api_source();
			}

			$item_id = item_post($a);
		}
	} else {
		throw new ForbiddenException();
	}

	// output the post that we just posted.
	$called_api = [];
	return api_status_show($type, $item_id);
}

/// @TODO move to top of file or somewhere better
api_register_func('api/statuses/retweet', 'api_statuses_repeat', true, API_METHOD_POST);

/**
 * Destroys a specific status.
 *
 * @param string $type Return type (atom, rss, xml, json)
 *
 * @return array|string
 * @throws BadRequestException
 * @throws ForbiddenException
 * @throws ImagickException
 * @throws InternalServerErrorException
 * @throws UnauthorizedException
 * @see https://developer.twitter.com/en/docs/tweets/post-and-engage/api-reference/post-statuses-destroy-id
 */
function api_statuses_destroy($type)
{
	$a = DI::app();

	if (api_user() === false) {
		throw new ForbiddenException();
	}

	api_get_user($a);

	// params
	$id = intval($a->argv[3] ?? 0);

	if ($id == 0) {
		$id = intval($_REQUEST['id'] ?? 0);
	}

	// Hotot workaround
	if ($id == 0) {
		$id = intval($a->argv[4] ?? 0);
	}

	Logger::log('API: api_statuses_destroy: '.$id);

	$ret = api_statuses_show($type);

	Item::deleteForUser(['id' => $id], api_user());

	return $ret;
}

/// @TODO move to top of file or somewhere better
api_register_func('api/statuses/destroy', 'api_statuses_destroy', true, API_METHOD_DELETE);

/**
 * Returns the most recent mentions.
 *
 * @param string $type Return type (atom, rss, xml, json)
 *
 * @return array|string
 * @throws BadRequestException
 * @throws ForbiddenException
 * @throws ImagickException
 * @throws InternalServerErrorException
 * @throws UnauthorizedException
 * @see http://developer.twitter.com/doc/get/statuses/mentions
 */
function api_statuses_mentions($type)
{
	$a = DI::app();
	$user_info = api_get_user($a);

	if (api_user() === false || $user_info === false) {
		throw new ForbiddenException();
	}

	unset($_REQUEST["user_id"]);
	unset($_GET["user_id"]);

	unset($_REQUEST["screen_name"]);
	unset($_GET["screen_name"]);

	// get last network messages

	// params
	$since_id = intval($_REQUEST['since_id'] ?? 0);
	$max_id   = intval($_REQUEST['max_id']   ?? 0);
	$count    = intval($_REQUEST['count']    ?? 20);
	$page     = intval($_REQUEST['page']     ?? 1);

	$start = max(0, ($page - 1) * $count);

	$query = "`gravity` IN (?, ?) AND `id` IN (SELECT `iid` FROM `user-item`		
		WHERE (`hidden` IS NULL OR NOT `hidden`) AND
			`uid` = ? AND `notification-type` & ? != 0
			AND `iid` > ?";

	$condition = [GRAVITY_PARENT, GRAVITY_COMMENT, api_user(),
		UserItem::NOTIF_EXPLICIT_TAGGED | UserItem::NOTIF_IMPLICIT_TAGGED |
		UserItem::NOTIF_THREAD_COMMENT | UserItem::NOTIF_DIRECT_COMMENT |
		UserItem::NOTIF_DIRECT_THREAD_COMMENT,
		$since_id];

	if ($max_id > 0) {
		$query .= " AND `iid` <= ?";
		$condition[] = $max_id;
	}

	$query .= ")";

	array_unshift($condition, $query);

	$params = ['order' => ['id' => true], 'limit' => [$start, $count]];
	$statuses = Post::selectForUser(api_user(), [], $condition, $params);

	$ret = api_format_items(Post::toArray($statuses), $user_info, false, $type);

	$data = ['status' => $ret];
	switch ($type) {
		case "atom":
			break;
		case "rss":
			$data = api_rss_extra($a, $data, $user_info);
			break;
	}

	return api_format_data("statuses", $type, $data);
}

/// @TODO move to top of file or somewhere better
api_register_func('api/statuses/mentions', 'api_statuses_mentions', true);
api_register_func('api/statuses/replies', 'api_statuses_mentions', true);

/**
 * Returns the most recent statuses posted by the user.
 *
 * @param string $type Either "json" or "xml"
 * @return string|array
 * @throws BadRequestException
 * @throws ForbiddenException
 * @throws ImagickException
 * @throws InternalServerErrorException
 * @throws UnauthorizedException
 * @see   https://developer.twitter.com/en/docs/tweets/timelines/api-reference/get-statuses-user_timeline
 */
function api_statuses_user_timeline($type)
{
	$a = DI::app();
	$user_info = api_get_user($a);

	if (api_user() === false || $user_info === false) {
		throw new ForbiddenException();
	}

	Logger::info('api_statuses_user_timeline', ['api_user' => api_user(), 'user_info' => $user_info, '_REQUEST' => $_REQUEST]);

	$since_id        = $_REQUEST['since_id'] ?? 0;
	$max_id          = $_REQUEST['max_id'] ?? 0;
	$exclude_replies = !empty($_REQUEST['exclude_replies']);
	$conversation_id = $_REQUEST['conversation_id'] ?? 0;

	// pagination
	$count = $_REQUEST['count'] ?? 20;
	$page  = $_REQUEST['page'] ?? 1;

	$start = max(0, ($page - 1) * $count);

	$condition = ["`uid` = ? AND `gravity` IN (?, ?) AND `id` > ? AND `contact-id` = ?",
		api_user(), GRAVITY_PARENT, GRAVITY_COMMENT, $since_id, $user_info['cid']];

	if ($user_info['self'] == 1) {
		$condition[0] .= ' AND `wall` ';
	}

	if ($exclude_replies) {
		$condition[0] .= ' AND `gravity` = ?';
		$condition[] = GRAVITY_PARENT;
	}

	if ($conversation_id > 0) {
		$condition[0] .= " AND `parent` = ?";
		$condition[] = $conversation_id;
	}

	if ($max_id > 0) {
		$condition[0] .= " AND `id` <= ?";
		$condition[] = $max_id;
	}

	$params = ['order' => ['id' => true], 'limit' => [$start, $count]];
	$statuses = Post::selectForUser(api_user(), [], $condition, $params);

	$ret = api_format_items(Post::toArray($statuses), $user_info, true, $type);

	bindComments($ret);

	$data = ['status' => $ret];
	switch ($type) {
		case "atom":
			break;
		case "rss":
			$data = api_rss_extra($a, $data, $user_info);
			break;
	}

	return api_format_data("statuses", $type, $data);
}

/// @TODO move to top of file or somewhere better
api_register_func('api/statuses/user_timeline', 'api_statuses_user_timeline', true);

/**
 * Star/unstar an item.
 * param: id : id of the item
 *
 * @param string $type Return type (atom, rss, xml, json)
 *
 * @return array|string
 * @throws BadRequestException
 * @throws ForbiddenException
 * @throws ImagickException
 * @throws InternalServerErrorException
 * @throws UnauthorizedException
 * @see https://web.archive.org/web/20131019055350/https://dev.twitter.com/docs/api/1/post/favorites/create/%3Aid
 */
function api_favorites_create_destroy($type)
{
	$a = DI::app();

	if (api_user() === false) {
		throw new ForbiddenException();
	}

	// for versioned api.
	/// @TODO We need a better global soluton
	$action_argv_id = 2;
	if (count($a->argv) > 1 && $a->argv[1] == "1.1") {
		$action_argv_id = 3;
	}

	if ($a->argc <= $action_argv_id) {
		throw new BadRequestException("Invalid request.");
	}
	$action = str_replace("." . $type, "", $a->argv[$action_argv_id]);
	if ($a->argc == $action_argv_id + 2) {
		$itemid = intval($a->argv[$action_argv_id + 1] ?? 0);
	} else {
		$itemid = intval($_REQUEST['id'] ?? 0);
	}

	$item = Post::selectFirstForUser(api_user(), [], ['id' => $itemid, 'uid' => api_user()]);

	if (!DBA::isResult($item)) {
		throw new BadRequestException("Invalid item.");
	}

	switch ($action) {
		case "create":
			$item['starred'] = 1;
			break;
		case "destroy":
			$item['starred'] = 0;
			break;
		default:
			throw new BadRequestException("Invalid action ".$action);
	}

	$r = Item::update(['starred' => $item['starred']], ['id' => $itemid]);

	if ($r === false) {
		throw new InternalServerErrorException("DB error");
	}


	$user_info = api_get_user($a);
	$rets = api_format_items([$item], $user_info, false, $type);
	$ret = $rets[0];

	$data = ['status' => $ret];
	switch ($type) {
		case "atom":
			break;
		case "rss":
			$data = api_rss_extra($a, $data, $user_info);
			break;
	}

	return api_format_data("status", $type, $data);
}

/// @TODO move to top of file or somewhere better
api_register_func('api/favorites/create', 'api_favorites_create_destroy', true, API_METHOD_POST);
api_register_func('api/favorites/destroy', 'api_favorites_create_destroy', true, API_METHOD_DELETE);

/**
 * Returns the most recent favorite statuses.
 *
 * @param string $type Return type (atom, rss, xml, json)
 *
 * @return string|array
 * @throws BadRequestException
 * @throws ForbiddenException
 * @throws ImagickException
 * @throws InternalServerErrorException
 * @throws UnauthorizedException
 */
function api_favorites($type)
{
	global $called_api;

	$a = DI::app();
	$user_info = api_get_user($a);

	if (api_user() === false || $user_info === false) {
		throw new ForbiddenException();
	}

	$called_api = [];

	// in friendica starred item are private
	// return favorites only for self
	Logger::info(API_LOG_PREFIX . 'for {self}', ['module' => 'api', 'action' => 'favorites', 'self' => $user_info['self']]);

	if ($user_info['self'] == 0) {
		$ret = [];
	} else {
		// params
		$since_id = $_REQUEST['since_id'] ?? 0;
		$max_id = $_REQUEST['max_id'] ?? 0;
		$count = $_GET['count'] ?? 20;
		$page = $_REQUEST['page'] ?? 1;

		$start = max(0, ($page - 1) * $count);

		$condition = ["`uid` = ? AND `gravity` IN (?, ?) AND `id` > ? AND `starred`",
			api_user(), GRAVITY_PARENT, GRAVITY_COMMENT, $since_id];

		$params = ['order' => ['id' => true], 'limit' => [$start, $count]];

		if ($max_id > 0) {
			$condition[0] .= " AND `id` <= ?";
			$condition[] = $max_id;
		}

		$statuses = Post::selectForUser(api_user(), [], $condition, $params);

		$ret = api_format_items(Post::toArray($statuses), $user_info, false, $type);
	}

	bindComments($ret);

	$data = ['status' => $ret];
	switch ($type) {
		case "atom":
			break;
		case "rss":
			$data = api_rss_extra($a, $data, $user_info);
			break;
	}

	return api_format_data("statuses", $type, $data);
}

/// @TODO move to top of file or somewhere better
api_register_func('api/favorites', 'api_favorites', true);

/**
 *
 * @param array $item
 * @param array $recipient
 * @param array $sender
 *
 * @return array
 * @throws InternalServerErrorException
 */
function api_format_messages($item, $recipient, $sender)
{
	// standard meta information
	$ret = [
		'id'                    => $item['id'],
		'sender_id'             => $sender['id'],
		'text'                  => "",
		'recipient_id'          => $recipient['id'],
		'created_at'            => api_date($item['created'] ?? DateTimeFormat::utcNow()),
		'sender_screen_name'    => $sender['screen_name'],
		'recipient_screen_name' => $recipient['screen_name'],
		'sender'                => $sender,
		'recipient'             => $recipient,
		'title'                 => "",
		'friendica_seen'        => $item['seen'] ?? 0,
		'friendica_parent_uri'  => $item['parent-uri'] ?? '',
	];

	// "uid" and "self" are only needed for some internal stuff, so remove it from here
	if (isset($ret['sender']['uid'])) {
		unset($ret['sender']['uid']);
	}
	if (isset($ret['sender']['self'])) {
		unset($ret['sender']['self']);
	}
	if (isset($ret['recipient']['uid'])) {
		unset($ret['recipient']['uid']);
	}
	if (isset($ret['recipient']['self'])) {
		unset($ret['recipient']['self']);
	}

	//don't send title to regular StatusNET requests to avoid confusing these apps
	if (!empty($_GET['getText'])) {
		$ret['title'] = $item['title'];
		if ($_GET['getText'] == 'html') {
			$ret['text'] = BBCode::convert($item['body'], false);
		} elseif ($_GET['getText'] == 'plain') {
			$ret['text'] = trim(HTML::toPlaintext(BBCode::convert(api_clean_plain_items($item['body']), false, BBCode::API, true), 0));
		}
	} else {
		$ret['text'] = $item['title'] . "\n" . HTML::toPlaintext(BBCode::convert(api_clean_plain_items($item['body']), false, BBCode::API, true), 0);
	}
	if (!empty($_GET['getUserObjects']) && $_GET['getUserObjects'] == 'false') {
		unset($ret['sender']);
		unset($ret['recipient']);
	}

	return $ret;
}

/**
 *
 * @param array $item
 *
 * @return array
 * @throws InternalServerErrorException
 */
function api_convert_item($item)
{
	$body = $item['body'];
	$entities = api_get_entitities($statustext, $body);

	// Add pictures to the attachment array and remove them from the body
	$attachments = api_get_attachments($body);

	// Workaround for ostatus messages where the title is identically to the body
	$html = BBCode::convert(api_clean_plain_items($body), false, BBCode::API, true);
	$statusbody = trim(HTML::toPlaintext($html, 0));

	// handle data: images
	$statusbody = api_format_items_embeded_images($item, $statusbody);

	$statustitle = trim($item['title']);

	if (($statustitle != '') && (strpos($statusbody, $statustitle) !== false)) {
		$statustext = trim($statusbody);
	} else {
		$statustext = trim($statustitle."\n\n".$statusbody);
	}

	if ((($item['network'] ?? Protocol::PHANTOM) == Protocol::FEED) && (mb_strlen($statustext)> 1000)) {
		$statustext = mb_substr($statustext, 0, 1000) . "... \n" . ($item['plink'] ?? '');
	}

	$statushtml = BBCode::convert(BBCode::removeAttachment($body), false);

	// Workaround for clients with limited HTML parser functionality
	$search = ["<br>", "<blockquote>", "</blockquote>",
			"<h1>", "</h1>", "<h2>", "</h2>",
			"<h3>", "</h3>", "<h4>", "</h4>",
			"<h5>", "</h5>", "<h6>", "</h6>"];
	$replace = ["<br>", "<br><blockquote>", "</blockquote><br>",
			"<br><h1>", "</h1><br>", "<br><h2>", "</h2><br>",
			"<br><h3>", "</h3><br>", "<br><h4>", "</h4><br>",
			"<br><h5>", "</h5><br>", "<br><h6>", "</h6><br>"];
	$statushtml = str_replace($search, $replace, $statushtml);

	if ($item['title'] != "") {
		$statushtml = "<br><h4>" . BBCode::convert($item['title']) . "</h4><br>" . $statushtml;
	}

	do {
		$oldtext = $statushtml;
		$statushtml = str_replace("<br><br>", "<br>", $statushtml);
	} while ($oldtext != $statushtml);

	if (substr($statushtml, 0, 4) == '<br>') {
		$statushtml = substr($statushtml, 4);
	}

	if (substr($statushtml, 0, -4) == '<br>') {
		$statushtml = substr($statushtml, -4);
	}

	// feeds without body should contain the link
	if ((($item['network'] ?? Protocol::PHANTOM) == Protocol::FEED) && (strlen($item['body']) == 0)) {
		$statushtml .= BBCode::convert($item['plink']);
	}

	return [
		"text" => $statustext,
		"html" => $statushtml,
		"attachments" => $attachments,
		"entities" => $entities
	];
}

/**
 *
 * @param string $body
 *
 * @return array
 * @throws InternalServerErrorException
 */
function api_get_attachments(&$body)
{
	$body = preg_replace("/\[img\=([0-9]*)x([0-9]*)\](.*?)\[\/img\]/ism", '[img]$3[/img]', $body);
	$body = preg_replace("/\[img\=(.*?)\](.*?)\[\/img\]/ism", '[img]$1[/img]', $body);

	$URLSearchString = "^\[\]";
	if (!preg_match_all("/\[img\]([$URLSearchString]*)\[\/img\]/ism", $body, $images)) {
		return [];
	}

	// Remove all embedded pictures, since they are added as attachments
	foreach ($images[0] as $orig) {
		$body = str_replace($orig, '', $body);
	}

	$attachments = [];

	foreach ($images[1] as $image) {
		$imagedata = Images::getInfoFromURLCached($image);

		if ($imagedata) {
			$attachments[] = ["url" => $image, "mimetype" => $imagedata["mime"], "size" => $imagedata["size"]];
		}
	}

	return $attachments;
}

/**
 *
 * @param string $text
 * @param string $bbcode
 *
 * @return array
 * @throws InternalServerErrorException
 * @todo Links at the first character of the post
 */
function api_get_entitities(&$text, $bbcode)
{
	$include_entities = strtolower($_REQUEST['include_entities'] ?? 'false');

	if ($include_entities != "true") {
		preg_match_all("/\[img](.*?)\[\/img\]/ism", $bbcode, $images);

		foreach ($images[1] as $image) {
			$replace = ProxyUtils::proxifyUrl($image);
			$text = str_replace($image, $replace, $text);
		}
		return [];
	}

	$bbcode = BBCode::cleanPictureLinks($bbcode);

	// Change pure links in text to bbcode uris
	$bbcode = preg_replace("/([^\]\='".'"'."]|^)(https?\:\/\/[a-zA-Z0-9\:\/\-\?\&\;\.\=\_\~\#\%\$\!\+\,]+)/ism", '$1[url=$2]$2[/url]', $bbcode);

	$entities = [];
	$entities["hashtags"] = [];
	$entities["symbols"] = [];
	$entities["urls"] = [];
	$entities["user_mentions"] = [];

	$URLSearchString = "^\[\]";

	$bbcode = preg_replace("/#\[url\=([$URLSearchString]*)\](.*?)\[\/url\]/ism", '#$2', $bbcode);

	$bbcode = preg_replace("/\[bookmark\=([$URLSearchString]*)\](.*?)\[\/bookmark\]/ism", '[url=$1]$2[/url]', $bbcode);
	$bbcode = preg_replace("/\[video\](.*?)\[\/video\]/ism", '[url=$1]$1[/url]', $bbcode);

	$bbcode = preg_replace(
		"/\[youtube\]([A-Za-z0-9\-_=]+)(.*?)\[\/youtube\]/ism",
		'[url=https://www.youtube.com/watch?v=$1]https://www.youtube.com/watch?v=$1[/url]',
		$bbcode
	);
	$bbcode = preg_replace("/\[youtube\](.*?)\[\/youtube\]/ism", '[url=$1]$1[/url]', $bbcode);

	$bbcode = preg_replace(
		"/\[vimeo\]([0-9]+)(.*?)\[\/vimeo\]/ism",
		'[url=https://vimeo.com/$1]https://vimeo.com/$1[/url]',
		$bbcode
	);
	$bbcode = preg_replace("/\[vimeo\](.*?)\[\/vimeo\]/ism", '[url=$1]$1[/url]', $bbcode);

	$bbcode = preg_replace("/\[img\=([0-9]*)x([0-9]*)\](.*?)\[\/img\]/ism", '[img]$3[/img]', $bbcode);

	preg_match_all("/\[url\=([$URLSearchString]*)\](.*?)\[\/url\]/ism", $bbcode, $urls);

	$ordered_urls = [];
	foreach ($urls[1] as $id => $url) {
		$start = iconv_strpos($text, $url, 0, "UTF-8");
		if (!($start === false)) {
			$ordered_urls[$start] = ["url" => $url, "title" => $urls[2][$id]];
		}
	}

	ksort($ordered_urls);

	$offset = 0;

	foreach ($ordered_urls as $url) {
		if ((substr($url["title"], 0, 7) != "http://") && (substr($url["title"], 0, 8) != "https://")
			&& !strpos($url["title"], "http://") && !strpos($url["title"], "https://")
		) {
			$display_url = $url["title"];
		} else {
			$display_url = str_replace(["http://www.", "https://www."], ["", ""], $url["url"]);
			$display_url = str_replace(["http://", "https://"], ["", ""], $display_url);

			if (strlen($display_url) > 26) {
				$display_url = substr($display_url, 0, 25)."…";
			}
		}

		$start = iconv_strpos($text, $url["url"], $offset, "UTF-8");
		if (!($start === false)) {
			$entities["urls"][] = ["url" => $url["url"],
							"expanded_url" => $url["url"],
							"display_url" => $display_url,
							"indices" => [$start, $start+strlen($url["url"])]];
			$offset = $start + 1;
		}
	}

	preg_match_all("/\[img\=(.*?)\](.*?)\[\/img\]/ism", $bbcode, $images, PREG_SET_ORDER);
	$ordered_images = [];
	foreach ($images as $image) {
		$start = iconv_strpos($text, $image[1], 0, "UTF-8");
		if (!($start === false)) {
			$ordered_images[$start] = ['url' => $image[1], 'alt' => $image[2]];
		}
	}

	preg_match_all("/\[img](.*?)\[\/img\]/ism", $bbcode, $images);
	foreach ($images[1] as $image) {
		$start = iconv_strpos($text, $image, 0, "UTF-8");
		if (!($start === false)) {
			$ordered_images[$start] = ['url' => $image, 'alt' => ''];
		}
	}

	$offset = 0;

	foreach ($ordered_images as $image) {
		$url = $image['url'];
		$ext_alt_text = $image['alt'];

		$display_url = str_replace(["http://www.", "https://www."], ["", ""], $url);
		$display_url = str_replace(["http://", "https://"], ["", ""], $display_url);

		if (strlen($display_url) > 26) {
			$display_url = substr($display_url, 0, 25)."…";
		}

		$start = iconv_strpos($text, $url, $offset, "UTF-8");
		if (!($start === false)) {
			$image = Images::getInfoFromURLCached($url);
			if ($image) {
				// If image cache is activated, then use the following sizes:
				// thumb  (150), small (340), medium (600) and large (1024)
				if (!DI::config()->get("system", "proxy_disabled")) {
					$media_url = ProxyUtils::proxifyUrl($url);

					$sizes = [];
					$scale = Images::getScalingDimensions($image[0], $image[1], 150);
					$sizes["thumb"] = ["w" => $scale["width"], "h" => $scale["height"], "resize" => "fit"];

					if (($image[0] > 150) || ($image[1] > 150)) {
						$scale = Images::getScalingDimensions($image[0], $image[1], 340);
						$sizes["small"] = ["w" => $scale["width"], "h" => $scale["height"], "resize" => "fit"];
					}

					$scale = Images::getScalingDimensions($image[0], $image[1], 600);
					$sizes["medium"] = ["w" => $scale["width"], "h" => $scale["height"], "resize" => "fit"];

					if (($image[0] > 600) || ($image[1] > 600)) {
						$scale = Images::getScalingDimensions($image[0], $image[1], 1024);
						$sizes["large"] = ["w" => $scale["width"], "h" => $scale["height"], "resize" => "fit"];
					}
				} else {
					$media_url = $url;
					$sizes["medium"] = ["w" => $image[0], "h" => $image[1], "resize" => "fit"];
				}

				$entities["media"][] = [
							"id" => $start+1,
							"id_str" => (string) ($start + 1),
							"indices" => [$start, $start+strlen($url)],
							"media_url" => Strings::normaliseLink($media_url),
							"media_url_https" => $media_url,
							"url" => $url,
							"display_url" => $display_url,
							"expanded_url" => $url,
							"ext_alt_text" => $ext_alt_text,
							"type" => "photo",
							"sizes" => $sizes];
			}
			$offset = $start + 1;
		}
	}

	return $entities;
}

/**
 *
 * @param array $item
 * @param string $text
 *
 * @return string
 */
function api_format_items_embeded_images($item, $text)
{
	$text = preg_replace_callback(
		'|data:image/([^;]+)[^=]+=*|m',
		function () use ($item) {
			return DI::baseUrl() . '/display/' . $item['guid'];
		},
		$text
	);
	return $text;
}

/**
 * return <a href='url'>name</a> as array
 *
 * @param string $txt text
 * @return array
 * 			'name' => 'name',
 * 			'url => 'url'
 */
function api_contactlink_to_array($txt)
{
	$match = [];
	$r = preg_match_all('|<a href="([^"]*)">([^<]*)</a>|', $txt, $match);
	if ($r && count($match)==3) {
		$res = [
			'name' => $match[2],
			'url' => $match[1]
		];
	} else {
		$res = [
			'name' => $txt,
			'url' => ""
		];
	}
	return $res;
}


/**
 * return likes, dislikes and attend status for item
 *
 * @param array  $item array
 * @param string $type Return type (atom, rss, xml, json)
 *
 * @return array
 *            likes => int count,
 *            dislikes => int count
 * @throws BadRequestException
 * @throws ImagickException
 * @throws InternalServerErrorException
 * @throws UnauthorizedException
 */
function api_format_items_activities($item, $type = "json")
{
	$a = DI::app();

	$activities = [
		'like' => [],
		'dislike' => [],
		'attendyes' => [],
		'attendno' => [],
		'attendmaybe' => [],
		'announce' => [],
	];

	$condition = ['uid' => $item['uid'], 'thr-parent' => $item['uri'], 'gravity' => GRAVITY_ACTIVITY];
	$ret = Post::selectForUser($item['uid'], ['author-id', 'verb'], $condition);

	while ($parent_item = Post::fetch($ret)) {
		// not used as result should be structured like other user data
		//builtin_activity_puller($i, $activities);

		// get user data and add it to the array of the activity
		$user = api_get_user($a, $parent_item['author-id']);
		switch ($parent_item['verb']) {
			case Activity::LIKE:
				$activities['like'][] = $user;
				break;
			case Activity::DISLIKE:
				$activities['dislike'][] = $user;
				break;
			case Activity::ATTEND:
				$activities['attendyes'][] = $user;
				break;
			case Activity::ATTENDNO:
				$activities['attendno'][] = $user;
				break;
			case Activity::ATTENDMAYBE:
				$activities['attendmaybe'][] = $user;
				break;
			case Activity::ANNOUNCE:
				$activities['announce'][] = $user;
				break;
			default:
				break;
		}
	}

	DBA::close($ret);

	if ($type == "xml") {
		$xml_activities = [];
		foreach ($activities as $k => $v) {
			// change xml element from "like" to "friendica:like"
			$xml_activities["friendica:".$k] = $v;
			// add user data into xml output
			$k_user = 0;
			foreach ($v as $user) {
				$xml_activities["friendica:".$k][$k_user++.":user"] = $user;
			}
		}
		$activities = $xml_activities;
	}

	return $activities;
}

/**
 * format items to be returned by api
 *
 * @param array  $items       array of items
 * @param array  $user_info
 * @param bool   $filter_user filter items by $user_info
 * @param string $type        Return type (atom, rss, xml, json)
 * @return array
 * @throws BadRequestException
 * @throws ImagickException
 * @throws InternalServerErrorException
 * @throws UnauthorizedException
 */
function api_format_items($items, $user_info, $filter_user = false, $type = "json")
{
	$a = Friendica\DI::app();

	$ret = [];

	foreach ((array)$items as $item) {
		list($status_user, $author_user, $owner_user) = api_item_get_user($a, $item);

		// Look if the posts are matching if they should be filtered by user id
		if ($filter_user && ($status_user["id"] != $user_info["id"])) {
			continue;
		}

		$status = api_format_item($item, $type, $status_user, $author_user, $owner_user);

		$ret[] = $status;
	}

	return $ret;
}

/**
 * @param array  $item       Item record
 * @param string $type       Return format (atom, rss, xml, json)
 * @param array $status_user User record of the item author, can be provided by api_item_get_user()
 * @param array $author_user User record of the item author, can be provided by api_item_get_user()
 * @param array $owner_user  User record of the item owner, can be provided by api_item_get_user()
 * @return array API-formatted status
 * @throws BadRequestException
 * @throws ImagickException
 * @throws InternalServerErrorException
 * @throws UnauthorizedException
 */
function api_format_item($item, $type = "json", $status_user = null, $author_user = null, $owner_user = null)
{
	$a = Friendica\DI::app();

	if (empty($status_user) || empty($author_user) || empty($owner_user)) {
		list($status_user, $author_user, $owner_user) = api_item_get_user($a, $item);
	}

	localize_item($item);

	$in_reply_to = api_in_reply_to($item);

	$converted = api_convert_item($item);

	if ($type == "xml") {
		$geo = "georss:point";
	} else {
		$geo = "geo";
	}

	$status = [
		'text'		=> $converted["text"],
		'truncated' => false,
		'created_at'=> api_date($item['created']),
		'in_reply_to_status_id' => $in_reply_to['status_id'],
		'in_reply_to_status_id_str' => $in_reply_to['status_id_str'],
		'source'    => (($item['app']) ? $item['app'] : 'web'),
		'id'		=> intval($item['id']),
		'id_str'	=> (string) intval($item['id']),
		'in_reply_to_user_id' => $in_reply_to['user_id'],
		'in_reply_to_user_id_str' => $in_reply_to['user_id_str'],
		'in_reply_to_screen_name' => $in_reply_to['screen_name'],
		$geo => null,
		'favorited' => $item['starred'] ? true : false,
		'user' =>  $status_user,
		'friendica_author' => $author_user,
		'friendica_owner' => $owner_user,
		'friendica_private' => $item['private'] == Item::PRIVATE,
		//'entities' => NULL,
		'statusnet_html' => $converted["html"],
		'statusnet_conversation_id' => $item['parent'],
		'external_url' => DI::baseUrl() . "/display/" . $item['guid'],
		'friendica_activities' => api_format_items_activities($item, $type),
		'friendica_title' => $item['title'],
		'friendica_html' => BBCode::convert($item['body'], false)
	];

	if (count($converted["attachments"]) > 0) {
		$status["attachments"] = $converted["attachments"];
	}

	if (count($converted["entities"]) > 0) {
		$status["entities"] = $converted["entities"];
	}

	if ($status["source"] == 'web') {
		$status["source"] = ContactSelector::networkToName($item['author-network'], $item['author-link'], $item['network']);
	} elseif (ContactSelector::networkToName($item['author-network'], $item['author-link'], $item['network']) != $status["source"]) {
		$status["source"] = trim($status["source"].' ('.ContactSelector::networkToName($item['author-network'], $item['author-link'], $item['network']).')');
	}

	$retweeted_item = [];
	$quoted_item = [];

	if ($item['gravity'] == GRAVITY_PARENT) {
		$body = $item['body'];
		$retweeted_item = api_share_as_retweet($item);
		if ($body != $item['body']) {
			$quoted_item = $retweeted_item;
			$retweeted_item = [];
		}
	}

	if (empty($retweeted_item) && ($item['owner-id'] == $item['author-id'])) {
		$announce = api_get_announce($item);
		if (!empty($announce)) {
			$retweeted_item = $item;
			$item = $announce;
			$status['friendica_owner'] = api_get_user($a, $announce['author-id']);
		}
	}

	if (!empty($quoted_item)) {
		if ($quoted_item['id'] != $item['id']) {
			$quoted_status = api_format_item($quoted_item);
			/// @todo Only remove the attachments that are also contained in the quotes status
			unset($status['attachments']);
			unset($status['entities']);
		} else {
			$conv_quoted = api_convert_item($quoted_item);
			$quoted_status = $status;
			unset($quoted_status['attachments']);
			unset($quoted_status['entities']);
			unset($quoted_status['statusnet_conversation_id']);
			$quoted_status['text'] = $conv_quoted['text'];
			$quoted_status['statusnet_html'] = $conv_quoted['html'];
			try {
				$quoted_status["user"] = api_get_user($a, $quoted_item["author-id"]);
			} catch (BadRequestException $e) {
				// user not found. should be found?
				/// @todo check if the user should be always found
				$quoted_status["user"] = [];
			}
		}
		unset($quoted_status['friendica_author']);
		unset($quoted_status['friendica_owner']);
		unset($quoted_status['friendica_activities']);
		unset($quoted_status['friendica_private']);
	}

	if (!empty($retweeted_item)) {
		$retweeted_status = $status;
		unset($retweeted_status['friendica_author']);
		unset($retweeted_status['friendica_owner']);
		unset($retweeted_status['friendica_activities']);
		unset($retweeted_status['friendica_private']);
		unset($retweeted_status['statusnet_conversation_id']);
		$status['user'] = $status['friendica_owner'];
		try {
			$retweeted_status["user"] = api_get_user($a, $retweeted_item["author-id"]);
		} catch (BadRequestException $e) {
			// user not found. should be found?
			/// @todo check if the user should be always found
			$retweeted_status["user"] = [];
		}

		$rt_converted = api_convert_item($retweeted_item);

		$retweeted_status['text'] = $rt_converted["text"];
		$retweeted_status['statusnet_html'] = $rt_converted["html"];
		$retweeted_status['created_at'] =  api_date($retweeted_item['created']);

		if (!empty($quoted_status)) {
			$retweeted_status['quoted_status'] = $quoted_status;
		}

		$status['friendica_author'] = $retweeted_status['user'];
		$status['retweeted_status'] = $retweeted_status;
	} elseif (!empty($quoted_status)) {
		$root_status = api_convert_item($item);

		$status['text'] = $root_status["text"];
		$status['statusnet_html'] = $root_status["html"];
		$status['quoted_status'] = $quoted_status;
	}

	// "uid" and "self" are only needed for some internal stuff, so remove it from here
	unset($status["user"]["uid"]);
	unset($status["user"]["self"]);

	if ($item["coord"] != "") {
		$coords = explode(' ', $item["coord"]);
		if (count($coords) == 2) {
			if ($type == "json") {
				$status["geo"] = ['type' => 'Point',
					'coordinates' => [(float) $coords[0],
						(float) $coords[1]]];
			} else {// Not sure if this is the official format - if someone founds a documentation we can check
				$status["georss:point"] = $item["coord"];
			}
		}
	}

	return $status;
}

/**
 * Returns the remaining number of API requests available to the user before the API limit is reached.
 *
 * @param string $type Return type (atom, rss, xml, json)
 *
 * @return array|string
 * @throws Exception
 */
function api_account_rate_limit_status($type)
{
	if ($type == "xml") {
		$hash = [
				'remaining-hits' => '150',
				'@attributes' => ["type" => "integer"],
				'hourly-limit' => '150',
				'@attributes2' => ["type" => "integer"],
				'reset-time' => DateTimeFormat::utc('now + 1 hour', DateTimeFormat::ATOM),
				'@attributes3' => ["type" => "datetime"],
				'reset_time_in_seconds' => strtotime('now + 1 hour'),
				'@attributes4' => ["type" => "integer"],
			];
	} else {
		$hash = [
				'reset_time_in_seconds' => strtotime('now + 1 hour'),
				'remaining_hits' => '150',
				'hourly_limit' => '150',
				'reset_time' => api_date(DateTimeFormat::utc('now + 1 hour', DateTimeFormat::ATOM)),
			];
	}

	return api_format_data('hash', $type, ['hash' => $hash]);
}

/// @TODO move to top of file or somewhere better
api_register_func('api/account/rate_limit_status', 'api_account_rate_limit_status', true);

/**
 * Returns the string "ok" in the requested format with a 200 OK HTTP status code.
 *
 * @param string $type Return type (atom, rss, xml, json)
 *
 * @return array|string
 */
function api_help_test($type)
{
	if ($type == 'xml') {
		$ok = "true";
	} else {
		$ok = "ok";
	}

	return api_format_data('ok', $type, ["ok" => $ok]);
}

/// @TODO move to top of file or somewhere better
api_register_func('api/help/test', 'api_help_test', false);

/**
 * Returns all lists the user subscribes to.
 *
 * @param string $type Return type (atom, rss, xml, json)
 *
 * @return array|string
 * @see https://developer.twitter.com/en/docs/accounts-and-users/create-manage-lists/api-reference/get-lists-list
 */
function api_lists_list($type)
{
	$ret = [];
	/// @TODO $ret is not filled here?
	return api_format_data('lists', $type, ["lists_list" => $ret]);
}

/// @TODO move to top of file or somewhere better
api_register_func('api/lists/list', 'api_lists_list', true);
api_register_func('api/lists/subscriptions', 'api_lists_list', true);

/**
 * Returns all groups the user owns.
 *
 * @param string $type Return type (atom, rss, xml, json)
 *
 * @return array|string
 * @throws BadRequestException
 * @throws ForbiddenException
 * @throws ImagickException
 * @throws InternalServerErrorException
 * @throws UnauthorizedException
 * @see https://developer.twitter.com/en/docs/accounts-and-users/create-manage-lists/api-reference/get-lists-ownerships
 */
function api_lists_ownerships($type)
{
	$a = DI::app();

	if (api_user() === false) {
		throw new ForbiddenException();
	}

	// params
	$user_info = api_get_user($a);
	$uid = $user_info['uid'];

	$groups = DBA::select('group', [], ['deleted' => 0, 'uid' => $uid]);

	// loop through all groups
	$lists = [];
	foreach ($groups as $group) {
		if ($group['visible']) {
			$mode = 'public';
		} else {
			$mode = 'private';
		}
		$lists[] = [
			'name' => $group['name'],
			'id' => intval($group['id']),
			'id_str' => (string) $group['id'],
			'user' => $user_info,
			'mode' => $mode
		];
	}
	return api_format_data("lists", $type, ['lists' => ['lists' => $lists]]);
}

/// @TODO move to top of file or somewhere better
api_register_func('api/lists/ownerships', 'api_lists_ownerships', true);

/**
 * Returns recent statuses from users in the specified group.
 *
 * @param string $type Return type (atom, rss, xml, json)
 *
 * @return array|string
 * @throws BadRequestException
 * @throws ForbiddenException
 * @throws ImagickException
 * @throws InternalServerErrorException
 * @throws UnauthorizedException
 * @see https://developer.twitter.com/en/docs/accounts-and-users/create-manage-lists/api-reference/get-lists-ownerships
 */
function api_lists_statuses($type)
{
	$a = DI::app();

	$user_info = api_get_user($a);
	if (api_user() === false || $user_info === false) {
		throw new ForbiddenException();
	}

	unset($_REQUEST["user_id"]);
	unset($_GET["user_id"]);

	unset($_REQUEST["screen_name"]);
	unset($_GET["screen_name"]);

	if (empty($_REQUEST['list_id'])) {
		throw new BadRequestException('list_id not specified');
	}

	// params
	$count = $_REQUEST['count'] ?? 20;
	$page = $_REQUEST['page'] ?? 1;
	$since_id = $_REQUEST['since_id'] ?? 0;
	$max_id = $_REQUEST['max_id'] ?? 0;
	$exclude_replies = (!empty($_REQUEST['exclude_replies']) ? 1 : 0);
	$conversation_id = $_REQUEST['conversation_id'] ?? 0;

	$start = max(0, ($page - 1) * $count);

	$groups = DBA::selectToArray('group_member', ['contact-id'], ['gid' => 1]);
	$gids = array_column($groups, 'contact-id');
	$condition = ['uid' => api_user(), 'gravity' => [GRAVITY_PARENT, GRAVITY_COMMENT], 'group-id' => $gids];
	$condition = DBA::mergeConditions($condition, ["`id` > ?", $since_id]);

	if ($max_id > 0) {
		$condition[0] .= " AND `id` <= ?";
		$condition[] = $max_id;
	}
	if ($exclude_replies > 0) {
		$condition[0] .= ' AND `gravity` = ?';
		$condition[] = GRAVITY_PARENT;
	}
	if ($conversation_id > 0) {
		$condition[0] .= " AND `parent` = ?";
		$condition[] = $conversation_id;
	}

	$params = ['order' => ['id' => true], 'limit' => [$start, $count]];
	$statuses = Post::selectForUser(api_user(), [], $condition, $params);

	$items = api_format_items(Post::toArray($statuses), $user_info, false, $type);

	$data = ['status' => $items];
	switch ($type) {
		case "atom":
			break;
		case "rss":
			$data = api_rss_extra($a, $data, $user_info);
			break;
	}

	return api_format_data("statuses", $type, $data);
}

/// @TODO move to top of file or somewhere better
api_register_func('api/lists/statuses', 'api_lists_statuses', true);

/**
 * Returns either the friends of the follower list
 *
 * Considers friends and followers lists to be private and won't return
 * anything if any user_id parameter is passed.
 *
 * @param string $qtype Either "friends" or "followers"
 * @return boolean|array
 * @throws BadRequestException
 * @throws ForbiddenException
 * @throws ImagickException
 * @throws InternalServerErrorException
 * @throws UnauthorizedException
 */
function api_statuses_f($qtype)
{
	$a = DI::app();

	if (api_user() === false) {
		throw new ForbiddenException();
	}

	// pagination
	$count = $_GET['count'] ?? 20;
	$page = $_GET['page'] ?? 1;

	$start = max(0, ($page - 1) * $count);

	$user_info = api_get_user($a);

	if (!empty($_GET['cursor']) && $_GET['cursor'] == 'undefined') {
		/* this is to stop Hotot to load friends multiple times
		*  I'm not sure if I'm missing return something or
		*  is a bug in hotot. Workaround, meantime
		*/

		/*$ret=Array();
		return array('$users' => $ret);*/
		return false;
	}

	$sql_extra = '';
	if ($qtype == 'friends') {
		$sql_extra = sprintf(" AND ( `rel` = %d OR `rel` = %d ) ", intval(Contact::SHARING), intval(Contact::FRIEND));
	} elseif ($qtype == 'followers') {
		$sql_extra = sprintf(" AND ( `rel` = %d OR `rel` = %d ) ", intval(Contact::FOLLOWER), intval(Contact::FRIEND));
	}

	// friends and followers only for self
	if ($user_info['self'] == 0) {
		$sql_extra = " AND false ";
	}

	if ($qtype == 'blocks') {
		$sql_filter = 'AND `blocked` AND NOT `pending`';
	} elseif ($qtype == 'incoming') {
		$sql_filter = 'AND `pending`';
	} else {
		$sql_filter = 'AND (NOT `blocked` OR `pending`)';
	}

	$r = q(
		"SELECT `nurl`
		FROM `contact`
		WHERE `uid` = %d
		AND NOT `self`
		$sql_filter
		$sql_extra
		ORDER BY `nick`
		LIMIT %d, %d",
		intval(api_user()),
		intval($start),
		intval($count)
	);

	$ret = [];
	foreach ($r as $cid) {
		$user = api_get_user($a, $cid['nurl']);
		// "uid" and "self" are only needed for some internal stuff, so remove it from here
		unset($user["uid"]);
		unset($user["self"]);

		if ($user) {
			$ret[] = $user;
		}
	}

	return ['user' => $ret];
}


/**
 * Returns the list of friends of the provided user
 *
 * @deprecated By Twitter API in favor of friends/list
 *
 * @param string $type Either "json" or "xml"
 * @return boolean|string|array
 * @throws BadRequestException
 * @throws ForbiddenException
 */
function api_statuses_friends($type)
{
	$data =  api_statuses_f("friends");
	if ($data === false) {
		return false;
	}
	return api_format_data("users", $type, $data);
}

/**
 * Returns the list of followers of the provided user
 *
 * @deprecated By Twitter API in favor of friends/list
 *
 * @param string $type Either "json" or "xml"
 * @return boolean|string|array
 * @throws BadRequestException
 * @throws ForbiddenException
 */
function api_statuses_followers($type)
{
	$data = api_statuses_f("followers");
	if ($data === false) {
		return false;
	}
	return api_format_data("users", $type, $data);
}

/// @TODO move to top of file or somewhere better
api_register_func('api/statuses/friends', 'api_statuses_friends', true);
api_register_func('api/statuses/followers', 'api_statuses_followers', true);

/**
 * Returns the list of blocked users
 *
 * @see https://developer.twitter.com/en/docs/accounts-and-users/mute-block-report-users/api-reference/get-blocks-list
 *
 * @param string $type Either "json" or "xml"
 *
 * @return boolean|string|array
 * @throws BadRequestException
 * @throws ForbiddenException
 */
function api_blocks_list($type)
{
	$data =  api_statuses_f('blocks');
	if ($data === false) {
		return false;
	}
	return api_format_data("users", $type, $data);
}

/// @TODO move to top of file or somewhere better
api_register_func('api/blocks/list', 'api_blocks_list', true);

/**
 * Returns the list of pending users IDs
 *
 * @see https://developer.twitter.com/en/docs/accounts-and-users/follow-search-get-users/api-reference/get-friendships-incoming
 *
 * @param string $type Either "json" or "xml"
 *
 * @return boolean|string|array
 * @throws BadRequestException
 * @throws ForbiddenException
 */
function api_friendships_incoming($type)
{
	$data =  api_statuses_f('incoming');
	if ($data === false) {
		return false;
	}

	$ids = [];
	foreach ($data['user'] as $user) {
		$ids[] = $user['id'];
	}

	return api_format_data("ids", $type, ['id' => $ids]);
}

/// @TODO move to top of file or somewhere better
api_register_func('api/friendships/incoming', 'api_friendships_incoming', true);

/**
 * Returns the instance's configuration information.
 *
 * @param string $type Return type (atom, rss, xml, json)
 *
 * @return array|string
 * @throws InternalServerErrorException
 */
function api_statusnet_config($type)
{
	$name      = DI::config()->get('config', 'sitename');
	$server    = DI::baseUrl()->getHostname();
	$logo      = DI::baseUrl() . '/images/friendica-64.png';
	$email     = DI::config()->get('config', 'admin_email');
	$closed    = intval(DI::config()->get('config', 'register_policy')) === \Friendica\Module\Register::CLOSED ? 'true' : 'false';
	$private   = DI::config()->get('system', 'block_public') ? 'true' : 'false';
	$textlimit = (string) DI::config()->get('config', 'api_import_size', DI::config()->get('config', 'max_import_size', 200000));
	$ssl       = DI::config()->get('system', 'have_ssl') ? 'true' : 'false';
	$sslserver = DI::config()->get('system', 'have_ssl') ? str_replace('http:', 'https:', DI::baseUrl()) : '';

	$config = [
		'site' => ['name' => $name,'server' => $server, 'theme' => 'default', 'path' => '',
			'logo' => $logo, 'fancy' => true, 'language' => 'en', 'email' => $email, 'broughtby' => '',
			'broughtbyurl' => '', 'timezone' => 'UTC', 'closed' => $closed, 'inviteonly' => false,
			'private' => $private, 'textlimit' => $textlimit, 'sslserver' => $sslserver, 'ssl' => $ssl,
			'shorturllength' => '30',
			'friendica' => [
					'FRIENDICA_PLATFORM' => FRIENDICA_PLATFORM,
					'FRIENDICA_VERSION' => FRIENDICA_VERSION,
					'DFRN_PROTOCOL_VERSION' => DFRN_PROTOCOL_VERSION,
					'DB_UPDATE_VERSION' => DB_UPDATE_VERSION
					]
		],
	];

	return api_format_data('config', $type, ['config' => $config]);
}

/// @TODO move to top of file or somewhere better
api_register_func('api/gnusocial/config', 'api_statusnet_config', false);
api_register_func('api/statusnet/config', 'api_statusnet_config', false);

/**
 *
 * @param string $type Return type (atom, rss, xml, json)
 *
 * @return array|string
 */
function api_statusnet_version($type)
{
	// liar
	$fake_statusnet_version = "0.9.7";

	return api_format_data('version', $type, ['version' => $fake_statusnet_version]);
}

/// @TODO move to top of file or somewhere better
api_register_func('api/gnusocial/version', 'api_statusnet_version', false);
api_register_func('api/statusnet/version', 'api_statusnet_version', false);

/**
 * Sends a new direct message.
 *
 * @param string $type Return type (atom, rss, xml, json)
 *
 * @return array|string
 * @throws BadRequestException
 * @throws ForbiddenException
 * @throws ImagickException
 * @throws InternalServerErrorException
 * @throws NotFoundException
 * @throws UnauthorizedException
 * @see https://developer.twitter.com/en/docs/direct-messages/sending-and-receiving/api-reference/new-message
 */
function api_direct_messages_new($type)
{
	$a = DI::app();

	if (api_user() === false) {
		throw new ForbiddenException();
	}

	if (empty($_POST["text"]) || empty($_POST["screen_name"]) && empty($_POST["user_id"])) {
		return;
	}

	$sender = api_get_user($a);

	$recipient = null;
	if (!empty($_POST['screen_name'])) {
		$r = q(
			"SELECT `id`, `nurl`, `network` FROM `contact` WHERE `uid`=%d AND `nick`='%s'",
			intval(api_user()),
			DBA::escape($_POST['screen_name'])
		);

		if (DBA::isResult($r)) {
			// Selecting the id by priority, friendica first
			api_best_nickname($r);

			$recipient = api_get_user($a, $r[0]['nurl']);
		}
	} else {
		$recipient = api_get_user($a, $_POST['user_id']);
	}

	if (empty($recipient)) {
		throw new NotFoundException('Recipient not found');
	}

	$replyto = '';
	if (!empty($_REQUEST['replyto'])) {
		$r = q(
			'SELECT `parent-uri`, `title` FROM `mail` WHERE `uid`=%d AND `id`=%d',
			intval(api_user()),
			intval($_REQUEST['replyto'])
		);
		$replyto = $r[0]['parent-uri'];
		$sub     = $r[0]['title'];
	} else {
		if (!empty($_REQUEST['title'])) {
			$sub = $_REQUEST['title'];
		} else {
			$sub = ((strlen($_POST['text'])>10) ? substr($_POST['text'], 0, 10)."...":$_POST['text']);
		}
	}

	$id = Mail::send($recipient['cid'], $_POST['text'], $sub, $replyto);

	if ($id > -1) {
		$r = q("SELECT * FROM `mail` WHERE id=%d", intval($id));
		$ret = api_format_messages($r[0], $recipient, $sender);
	} else {
		$ret = ["error"=>$id];
	}

	$data = ['direct_message'=>$ret];

	switch ($type) {
		case "atom":
			break;
		case "rss":
			$data = api_rss_extra($a, $data, $sender);
			break;
	}

	return api_format_data("direct-messages", $type, $data);
}

/// @TODO move to top of file or somewhere better
api_register_func('api/direct_messages/new', 'api_direct_messages_new', true, API_METHOD_POST);

/**
 * delete a direct_message from mail table through api
 *
 * @param string $type Known types are 'atom', 'rss', 'xml' and 'json'
 * @return string|array
 * @throws BadRequestException
 * @throws ForbiddenException
 * @throws ImagickException
 * @throws InternalServerErrorException
 * @throws UnauthorizedException
 * @see   https://developer.twitter.com/en/docs/direct-messages/sending-and-receiving/api-reference/delete-message
 */
function api_direct_messages_destroy($type)
{
	$a = DI::app();

	if (api_user() === false) {
		throw new ForbiddenException();
	}

	// params
	$user_info = api_get_user($a);
	//required
	$id = $_REQUEST['id'] ?? 0;
	// optional
	$parenturi = $_REQUEST['friendica_parenturi'] ?? '';
	$verbose = (!empty($_GET['friendica_verbose']) ? strtolower($_GET['friendica_verbose']) : "false");
	/// @todo optional parameter 'include_entities' from Twitter API not yet implemented

	$uid = $user_info['uid'];
	// error if no id or parenturi specified (for clients posting parent-uri as well)
	if ($verbose == "true" && ($id == 0 || $parenturi == "")) {
		$answer = ['result' => 'error', 'message' => 'message id or parenturi not specified'];
		return api_format_data("direct_messages_delete", $type, ['$result' => $answer]);
	}

	// BadRequestException if no id specified (for clients using Twitter API)
	if ($id == 0) {
		throw new BadRequestException('Message id not specified');
	}

	// add parent-uri to sql command if specified by calling app
	$sql_extra = ($parenturi != "" ? " AND `parent-uri` = '" . DBA::escape($parenturi) . "'" : "");

	// get data of the specified message id
	$r = q(
		"SELECT `id` FROM `mail` WHERE `uid` = %d AND `id` = %d" . $sql_extra,
		intval($uid),
		intval($id)
	);

	// error message if specified id is not in database
	if (!DBA::isResult($r)) {
		if ($verbose == "true") {
			$answer = ['result' => 'error', 'message' => 'message id not in database'];
			return api_format_data("direct_messages_delete", $type, ['$result' => $answer]);
		}
		/// @todo BadRequestException ok for Twitter API clients?
		throw new BadRequestException('message id not in database');
	}

	// delete message
	$result = q(
		"DELETE FROM `mail` WHERE `uid` = %d AND `id` = %d" . $sql_extra,
		intval($uid),
		intval($id)
	);

	if ($verbose == "true") {
		if ($result) {
			// return success
			$answer = ['result' => 'ok', 'message' => 'message deleted'];
			return api_format_data("direct_message_delete", $type, ['$result' => $answer]);
		} else {
			$answer = ['result' => 'error', 'message' => 'unknown error'];
			return api_format_data("direct_messages_delete", $type, ['$result' => $answer]);
		}
	}
	/// @todo return JSON data like Twitter API not yet implemented
}

/// @TODO move to top of file or somewhere better
api_register_func('api/direct_messages/destroy', 'api_direct_messages_destroy', true, API_METHOD_DELETE);

/**
 * Unfollow Contact
 *
 * @param string $type Known types are 'atom', 'rss', 'xml' and 'json'
 * @return string|array
 * @throws BadRequestException
 * @throws ForbiddenException
 * @throws ImagickException
 * @throws InternalServerErrorException
 * @throws NotFoundException
 * @see   https://developer.twitter.com/en/docs/accounts-and-users/follow-search-get-users/api-reference/post-friendships-destroy.html
 */
function api_friendships_destroy($type)
{
	$uid = api_user();

	if ($uid === false) {
		throw new ForbiddenException();
	}

	$contact_id = $_REQUEST['user_id'] ?? 0;

	if (empty($contact_id)) {
		Logger::notice(API_LOG_PREFIX . 'No user_id specified', ['module' => 'api', 'action' => 'friendships_destroy']);
		throw new BadRequestException("no user_id specified");
	}

	// Get Contact by given id
	$contact = DBA::selectFirst('contact', ['url'], ['id' => $contact_id, 'uid' => 0, 'self' => false]);

	if(!DBA::isResult($contact)) {
		Logger::notice(API_LOG_PREFIX . 'No contact found for ID {contact}', ['module' => 'api', 'action' => 'friendships_destroy', 'contact' => $contact_id]);
		throw new NotFoundException("no contact found to given ID");
	}

	$url = $contact["url"];

	$condition = ["`uid` = ? AND (`rel` = ? OR `rel` = ?) AND (`nurl` = ? OR `alias` = ? OR `alias` = ?)",
			$uid, Contact::SHARING, Contact::FRIEND, Strings::normaliseLink($url),
			Strings::normaliseLink($url), $url];
	$contact = DBA::selectFirst('contact', [], $condition);

	if (!DBA::isResult($contact)) {
		Logger::notice(API_LOG_PREFIX . 'Not following contact', ['module' => 'api', 'action' => 'friendships_destroy']);
		throw new NotFoundException("Not following Contact");
	}

	if (!in_array($contact['network'], Protocol::NATIVE_SUPPORT)) {
		Logger::notice(API_LOG_PREFIX . 'Not supported for {network}', ['module' => 'api', 'action' => 'friendships_destroy', 'network' => $contact['network']]);
		throw new ExpectationFailedException("Not supported");
	}

	$dissolve = ($contact['rel'] == Contact::SHARING);

	$owner = User::getOwnerDataById($uid);
	if ($owner) {
		Contact::terminateFriendship($owner, $contact, $dissolve);
	}
	else {
		Logger::notice(API_LOG_PREFIX . 'No owner {uid} found', ['module' => 'api', 'action' => 'friendships_destroy', 'uid' => $uid]);
		throw new NotFoundException("Error Processing Request");
	}

	// Sharing-only contacts get deleted as there no relationship any more
	if ($dissolve) {
		Contact::remove($contact['id']);
	} else {
		DBA::update('contact', ['rel' => Contact::FOLLOWER], ['id' => $contact['id']]);
	}

	// "uid" and "self" are only needed for some internal stuff, so remove it from here
	unset($contact["uid"]);
	unset($contact["self"]);

	// Set screen_name since Twidere requests it
	$contact["screen_name"] = $contact["nick"];

	return api_format_data("friendships-destroy", $type, ['user' => $contact]);
}
api_register_func('api/friendships/destroy', 'api_friendships_destroy', true, API_METHOD_POST);

/**
 *
 * @param string $type Return type (atom, rss, xml, json)
 * @param string $box
 * @param string $verbose
 *
 * @return array|string
 * @throws BadRequestException
 * @throws ForbiddenException
 * @throws ImagickException
 * @throws InternalServerErrorException
 * @throws UnauthorizedException
 */
function api_direct_messages_box($type, $box, $verbose)
{
	$a = DI::app();
	if (api_user() === false) {
		throw new ForbiddenException();
	}
	// params
	$count = $_GET['count'] ?? 20;
	$page = $_REQUEST['page'] ?? 1;

	$since_id = $_REQUEST['since_id'] ?? 0;
	$max_id = $_REQUEST['max_id'] ?? 0;

	$user_id = $_REQUEST['user_id'] ?? '';
	$screen_name = $_REQUEST['screen_name'] ?? '';

	//  caller user info
	unset($_REQUEST["user_id"]);
	unset($_GET["user_id"]);

	unset($_REQUEST["screen_name"]);
	unset($_GET["screen_name"]);

	$user_info = api_get_user($a);
	if ($user_info === false) {
		throw new ForbiddenException();
	}
	$profile_url = $user_info["url"];

	// pagination
	$start = max(0, ($page - 1) * $count);

	$sql_extra = "";

	// filters
	if ($box=="sentbox") {
		$sql_extra = "`mail`.`from-url`='" . DBA::escape($profile_url) . "'";
	} elseif ($box == "conversation") {
		$sql_extra = "`mail`.`parent-uri`='" . DBA::escape($_GET['uri'] ?? '')  . "'";
	} elseif ($box == "all") {
		$sql_extra = "true";
	} elseif ($box == "inbox") {
		$sql_extra = "`mail`.`from-url`!='" . DBA::escape($profile_url) . "'";
	}

	if ($max_id > 0) {
		$sql_extra .= ' AND `mail`.`id` <= ' . intval($max_id);
	}

	if ($user_id != "") {
		$sql_extra .= ' AND `mail`.`contact-id` = ' . intval($user_id);
	} elseif ($screen_name !="") {
		$sql_extra .= " AND `contact`.`nick` = '" . DBA::escape($screen_name). "'";
	}

	$r = q(
		"SELECT `mail`.*, `contact`.`nurl` AS `contact-url` FROM `mail`,`contact` WHERE `mail`.`contact-id` = `contact`.`id` AND `mail`.`uid`=%d AND $sql_extra AND `mail`.`id` > %d ORDER BY `mail`.`id` DESC LIMIT %d,%d",
		intval(api_user()),
		intval($since_id),
		intval($start),
		intval($count)
	);
	if ($verbose == "true" && !DBA::isResult($r)) {
		$answer = ['result' => 'error', 'message' => 'no mails available'];
		return api_format_data("direct_messages_all", $type, ['$result' => $answer]);
	}

	$ret = [];
	foreach ($r as $item) {
		if ($box == "inbox" || $item['from-url'] != $profile_url) {
			$recipient = $user_info;
			$sender = api_get_user($a, Strings::normaliseLink($item['contact-url']));
		} elseif ($box == "sentbox" || $item['from-url'] == $profile_url) {
			$recipient = api_get_user($a, Strings::normaliseLink($item['contact-url']));
			$sender = $user_info;
		}

		if (isset($recipient) && isset($sender)) {
			$ret[] = api_format_messages($item, $recipient, $sender);
		}
	}


	$data = ['direct_message' => $ret];
	switch ($type) {
		case "atom":
			break;
		case "rss":
			$data = api_rss_extra($a, $data, $user_info);
			break;
	}

	return api_format_data("direct-messages", $type, $data);
}

/**
 * Returns the most recent direct messages sent by the user.
 *
 * @param string $type Return type (atom, rss, xml, json)
 *
 * @return array|string
 * @throws BadRequestException
 * @throws ForbiddenException
 * @see https://developer.twitter.com/en/docs/direct-messages/sending-and-receiving/api-reference/get-sent-message
 */
function api_direct_messages_sentbox($type)
{
	$verbose = !empty($_GET['friendica_verbose']) ? strtolower($_GET['friendica_verbose']) : "false";
	return api_direct_messages_box($type, "sentbox", $verbose);
}

/**
 * Returns the most recent direct messages sent to the user.
 *
 * @param string $type Return type (atom, rss, xml, json)
 *
 * @return array|string
 * @throws BadRequestException
 * @throws ForbiddenException
 * @see https://developer.twitter.com/en/docs/direct-messages/sending-and-receiving/api-reference/get-messages
 */
function api_direct_messages_inbox($type)
{
	$verbose = !empty($_GET['friendica_verbose']) ? strtolower($_GET['friendica_verbose']) : "false";
	return api_direct_messages_box($type, "inbox", $verbose);
}

/**
 *
 * @param string $type Return type (atom, rss, xml, json)
 *
 * @return array|string
 * @throws BadRequestException
 * @throws ForbiddenException
 */
function api_direct_messages_all($type)
{
	$verbose = !empty($_GET['friendica_verbose']) ? strtolower($_GET['friendica_verbose']) : "false";
	return api_direct_messages_box($type, "all", $verbose);
}

/**
 *
 * @param string $type Return type (atom, rss, xml, json)
 *
 * @return array|string
 * @throws BadRequestException
 * @throws ForbiddenException
 */
function api_direct_messages_conversation($type)
{
	$verbose = !empty($_GET['friendica_verbose']) ? strtolower($_GET['friendica_verbose']) : "false";
	return api_direct_messages_box($type, "conversation", $verbose);
}

/// @TODO move to top of file or somewhere better
api_register_func('api/direct_messages/conversation', 'api_direct_messages_conversation', true);
api_register_func('api/direct_messages/all', 'api_direct_messages_all', true);
api_register_func('api/direct_messages/sent', 'api_direct_messages_sentbox', true);
api_register_func('api/direct_messages', 'api_direct_messages_inbox', true);

/**
 * Returns an OAuth Request Token.
 *
 * @see https://oauth.net/core/1.0/#auth_step1
 */
function api_oauth_request_token()
{
	$oauth1 = new FKOAuth1();
	try {
		$r = $oauth1->fetch_request_token(OAuthRequest::from_request());
	} catch (Exception $e) {
		echo "error=" . OAuthUtil::urlencode_rfc3986($e->getMessage());
		exit();
	}
	echo $r;
	exit();
}

/**
 * Returns an OAuth Access Token.
 *
 * @return array|string
 * @see https://oauth.net/core/1.0/#auth_step3
 */
function api_oauth_access_token()
{
	$oauth1 = new FKOAuth1();
	try {
		$r = $oauth1->fetch_access_token(OAuthRequest::from_request());
	} catch (Exception $e) {
		echo "error=". OAuthUtil::urlencode_rfc3986($e->getMessage());
		exit();
	}
	echo $r;
	exit();
}

/// @TODO move to top of file or somewhere better
api_register_func('api/oauth/request_token', 'api_oauth_request_token', false);
api_register_func('api/oauth/access_token', 'api_oauth_access_token', false);


/**
 * delete a complete photoalbum with all containing photos from database through api
 *
 * @param string $type Known types are 'atom', 'rss', 'xml' and 'json'
 * @return string|array
 * @throws BadRequestException
 * @throws ForbiddenException
 * @throws InternalServerErrorException
 */
function api_fr_photoalbum_delete($type)
{
	if (api_user() === false) {
		throw new ForbiddenException();
	}
	// input params
	$album = $_REQUEST['album'] ?? '';

	// we do not allow calls without album string
	if ($album == "") {
		throw new BadRequestException("no albumname specified");
	}
	// check if album is existing

	$photos = DBA::selectToArray('photo', ['resource-id'], ['uid' => api_user(), 'album' => $album], ['group_by' => ['resource-id']]);
	if (!DBA::isResult($photos)) {
		throw new BadRequestException("album not available");
	}

	$resourceIds = array_column($photos, 'resource-id');

	// function for setting the items to "deleted = 1" which ensures that comments, likes etc. are not shown anymore
	// to the user and the contacts of the users (drop_items() performs the federation of the deletion to other networks
	$condition = ['uid' => api_user(), 'resource-id' => $resourceIds, 'type' => 'photo'];
	Item::deleteForUser($condition, api_user());

	// now let's delete all photos from the album
	$result = Photo::delete(['uid' => api_user(), 'album' => $album]);

	// return success of deletion or error message
	if ($result) {
		$answer = ['result' => 'deleted', 'message' => 'album `' . $album . '` with all containing photos has been deleted.'];
		return api_format_data("photoalbum_delete", $type, ['$result' => $answer]);
	} else {
		throw new InternalServerErrorException("unknown error - deleting from database failed");
	}
}

/**
 * update the name of the album for all photos of an album
 *
 * @param string $type Known types are 'atom', 'rss', 'xml' and 'json'
 * @return string|array
 * @throws BadRequestException
 * @throws ForbiddenException
 * @throws InternalServerErrorException
 */
function api_fr_photoalbum_update($type)
{
	if (api_user() === false) {
		throw new ForbiddenException();
	}
	// input params
	$album = $_REQUEST['album'] ?? '';
	$album_new = $_REQUEST['album_new'] ?? '';

	// we do not allow calls without album string
	if ($album == "") {
		throw new BadRequestException("no albumname specified");
	}
	if ($album_new == "") {
		throw new BadRequestException("no new albumname specified");
	}
	// check if album is existing
	if (!Photo::exists(['uid' => api_user(), 'album' => $album])) {
		throw new BadRequestException("album not available");
	}
	// now let's update all photos to the albumname
	$result = Photo::update(['album' => $album_new], ['uid' => api_user(), 'album' => $album]);

	// return success of updating or error message
	if ($result) {
		$answer = ['result' => 'updated', 'message' => 'album `' . $album . '` with all containing photos has been renamed to `' . $album_new . '`.'];
		return api_format_data("photoalbum_update", $type, ['$result' => $answer]);
	} else {
		throw new InternalServerErrorException("unknown error - updating in database failed");
	}
}


/**
 * list all photos of the authenticated user
 *
 * @param string $type Known types are 'atom', 'rss', 'xml' and 'json'
 * @return string|array
 * @throws ForbiddenException
 * @throws InternalServerErrorException
 */
function api_fr_photos_list($type)
{
	if (api_user() === false) {
		throw new ForbiddenException();
	}
	$r = q(
		"SELECT `resource-id`, MAX(scale) AS `scale`, `album`, `filename`, `type`, MAX(`created`) AS `created`,
		MAX(`edited`) AS `edited`, MAX(`desc`) AS `desc` FROM `photo`
		WHERE `uid` = %d AND `album` != 'Contact Photos' GROUP BY `resource-id`, `album`, `filename`, `type`",
		intval(local_user())
	);
	$typetoext = [
		'image/jpeg' => 'jpg',
		'image/png' => 'png',
		'image/gif' => 'gif'
	];
	$data = ['photo'=>[]];
	if (DBA::isResult($r)) {
		foreach ($r as $rr) {
			$photo = [];
			$photo['id'] = $rr['resource-id'];
			$photo['album'] = $rr['album'];
			$photo['filename'] = $rr['filename'];
			$photo['type'] = $rr['type'];
			$thumb = DI::baseUrl() . "/photo/" . $rr['resource-id'] . "-" . $rr['scale'] . "." . $typetoext[$rr['type']];
			$photo['created'] = $rr['created'];
			$photo['edited'] = $rr['edited'];
			$photo['desc'] = $rr['desc'];

			if ($type == "xml") {
				$data['photo'][] = ["@attributes" => $photo, "1" => $thumb];
			} else {
				$photo['thumb'] = $thumb;
				$data['photo'][] = $photo;
			}
		}
	}
	return api_format_data("photos", $type, $data);
}

/**
 * upload a new photo or change an existing photo
 *
 * @param string $type Known types are 'atom', 'rss', 'xml' and 'json'
 * @return string|array
 * @throws BadRequestException
 * @throws ForbiddenException
 * @throws ImagickException
 * @throws InternalServerErrorException
 * @throws NotFoundException
 */
function api_fr_photo_create_update($type)
{
	if (api_user() === false) {
		throw new ForbiddenException();
	}
	// input params
	$photo_id  = $_REQUEST['photo_id']  ?? null;
	$desc      = $_REQUEST['desc']      ?? null;
	$album     = $_REQUEST['album']     ?? null;
	$album_new = $_REQUEST['album_new'] ?? null;
	$allow_cid = $_REQUEST['allow_cid'] ?? null;
	$deny_cid  = $_REQUEST['deny_cid' ] ?? null;
	$allow_gid = $_REQUEST['allow_gid'] ?? null;
	$deny_gid  = $_REQUEST['deny_gid' ] ?? null;
	$visibility = !$allow_cid && !$deny_cid && !$allow_gid && !$deny_gid;

	// do several checks on input parameters
	// we do not allow calls without album string
	if ($album == null) {
		throw new BadRequestException("no albumname specified");
	}
	// if photo_id == null --> we are uploading a new photo
	if ($photo_id == null) {
		$mode = "create";

		// error if no media posted in create-mode
		if (empty($_FILES['media'])) {
			// Output error
			throw new BadRequestException("no media data submitted");
		}

		// album_new will be ignored in create-mode
		$album_new = "";
	} else {
		$mode = "update";

		// check if photo is existing in databasei
		if (!Photo::exists(['resource-id' => $photo_id, 'uid' => api_user(), 'album' => $album])) {
			throw new BadRequestException("photo not available");
		}
	}

	// checks on acl strings provided by clients
	$acl_input_error = false;
	$acl_input_error |= check_acl_input($allow_cid);
	$acl_input_error |= check_acl_input($deny_cid);
	$acl_input_error |= check_acl_input($allow_gid);
	$acl_input_error |= check_acl_input($deny_gid);
	if ($acl_input_error) {
		throw new BadRequestException("acl data invalid");
	}
	// now let's upload the new media in create-mode
	if ($mode == "create") {
		$media = $_FILES['media'];
		$data = save_media_to_database("photo", $media, $type, $album, trim($allow_cid), trim($deny_cid), trim($allow_gid), trim($deny_gid), $desc, $visibility);

		// return success of updating or error message
		if (!is_null($data)) {
			return api_format_data("photo_create", $type, $data);
		} else {
			throw new InternalServerErrorException("unknown error - uploading photo failed, see Friendica log for more information");
		}
	}

	// now let's do the changes in update-mode
	if ($mode == "update") {
		$updated_fields = [];

		if (!is_null($desc)) {
			$updated_fields['desc'] = $desc;
		}

		if (!is_null($album_new)) {
			$updated_fields['album'] = $album_new;
		}

		if (!is_null($allow_cid)) {
			$allow_cid = trim($allow_cid);
			$updated_fields['allow_cid'] = $allow_cid;
		}

		if (!is_null($deny_cid)) {
			$deny_cid = trim($deny_cid);
			$updated_fields['deny_cid'] = $deny_cid;
		}

		if (!is_null($allow_gid)) {
			$allow_gid = trim($allow_gid);
			$updated_fields['allow_gid'] = $allow_gid;
		}

		if (!is_null($deny_gid)) {
			$deny_gid = trim($deny_gid);
			$updated_fields['deny_gid'] = $deny_gid;
		}

		$result = false;
		if (count($updated_fields) > 0) {
			$nothingtodo = false;
			$result = Photo::update($updated_fields, ['uid' => api_user(), 'resource-id' => $photo_id, 'album' => $album]);
		} else {
			$nothingtodo = true;
		}

		if (!empty($_FILES['media'])) {
			$nothingtodo = false;
			$media = $_FILES['media'];
			$data = save_media_to_database("photo", $media, $type, $album, $allow_cid, $deny_cid, $allow_gid, $deny_gid, $desc, 0, $visibility, $photo_id);
			if (!is_null($data)) {
				return api_format_data("photo_update", $type, $data);
			}
		}

		// return success of updating or error message
		if ($result) {
			$answer = ['result' => 'updated', 'message' => 'Image id `' . $photo_id . '` has been updated.'];
			return api_format_data("photo_update", $type, ['$result' => $answer]);
		} else {
			if ($nothingtodo) {
				$answer = ['result' => 'cancelled', 'message' => 'Nothing to update for image id `' . $photo_id . '`.'];
				return api_format_data("photo_update", $type, ['$result' => $answer]);
			}
			throw new InternalServerErrorException("unknown error - update photo entry in database failed");
		}
	}
	throw new InternalServerErrorException("unknown error - this error on uploading or updating a photo should never happen");
}

/**
 * delete a single photo from the database through api
 *
 * @param string $type Known types are 'atom', 'rss', 'xml' and 'json'
 * @return string|array
 * @throws BadRequestException
 * @throws ForbiddenException
 * @throws InternalServerErrorException
 */
function api_fr_photo_delete($type)
{
	if (api_user() === false) {
		throw new ForbiddenException();
	}

	// input params
	$photo_id = $_REQUEST['photo_id'] ?? null;

	// do several checks on input parameters
	// we do not allow calls without photo id
	if ($photo_id == null) {
		throw new BadRequestException("no photo_id specified");
	}

	// check if photo is existing in database
	if (!Photo::exists(['resource-id' => $photo_id, 'uid' => api_user()])) {
		throw new BadRequestException("photo not available");
	}

	// now we can perform on the deletion of the photo
	$result = Photo::delete(['uid' => api_user(), 'resource-id' => $photo_id]);

	// return success of deletion or error message
	if ($result) {
		// function for setting the items to "deleted = 1" which ensures that comments, likes etc. are not shown anymore
		// to the user and the contacts of the users (drop_items() do all the necessary magic to avoid orphans in database and federate deletion)
		$condition = ['uid' => api_user(), 'resource-id' => $photo_id, 'type' => 'photo'];
		Item::deleteForUser($condition, api_user());

		$result = ['result' => 'deleted', 'message' => 'photo with id `' . $photo_id . '` has been deleted from server.'];
		return api_format_data("photo_delete", $type, ['$result' => $result]);
	} else {
		throw new InternalServerErrorException("unknown error on deleting photo from database table");
	}
}


/**
 * returns the details of a specified photo id, if scale is given, returns the photo data in base 64
 *
 * @param string $type Known types are 'atom', 'rss', 'xml' and 'json'
 * @return string|array
 * @throws BadRequestException
 * @throws ForbiddenException
 * @throws InternalServerErrorException
 * @throws NotFoundException
 */
function api_fr_photo_detail($type)
{
	if (api_user() === false) {
		throw new ForbiddenException();
	}
	if (empty($_REQUEST['photo_id'])) {
		throw new BadRequestException("No photo id.");
	}

	$scale = (!empty($_REQUEST['scale']) ? intval($_REQUEST['scale']) : false);
	$photo_id = $_REQUEST['photo_id'];

	// prepare json/xml output with data from database for the requested photo
	$data = prepare_photo_data($type, $scale, $photo_id);

	return api_format_data("photo_detail", $type, $data);
}


/**
 * updates the profile image for the user (either a specified profile or the default profile)
 *
 * @param string $type Known types are 'atom', 'rss', 'xml' and 'json'
 *
 * @return string|array
 * @throws BadRequestException
 * @throws ForbiddenException
 * @throws ImagickException
 * @throws InternalServerErrorException
 * @throws NotFoundException
 * @see   https://developer.twitter.com/en/docs/accounts-and-users/manage-account-settings/api-reference/post-account-update_profile_image
 */
function api_account_update_profile_image($type)
{
	if (api_user() === false) {
		throw new ForbiddenException();
	}
	// input params
	$profile_id = $_REQUEST['profile_id'] ?? 0;

	// error if image data is missing
	if (empty($_FILES['image'])) {
		throw new BadRequestException("no media data submitted");
	}

	// check if specified profile id is valid
	if ($profile_id != 0) {
		$profile = DBA::selectFirst('profile', ['is-default'], ['uid' => api_user(), 'id' => $profile_id]);
		// error message if specified profile id is not in database
		if (!DBA::isResult($profile)) {
			throw new BadRequestException("profile_id not available");
		}
		$is_default_profile = $profile['is-default'];
	} else {
		$is_default_profile = 1;
	}

	// get mediadata from image or media (Twitter call api/account/update_profile_image provides image)
	$media = null;
	if (!empty($_FILES['image'])) {
		$media = $_FILES['image'];
	} elseif (!empty($_FILES['media'])) {
		$media = $_FILES['media'];
	}
	// save new profile image
	$data = save_media_to_database("profileimage", $media, $type, DI::l10n()->t('Profile Photos'), "", "", "", "", "", $is_default_profile);

	// get filetype
	if (is_array($media['type'])) {
		$filetype = $media['type'][0];
	} else {
		$filetype = $media['type'];
	}
	if ($filetype == "image/jpeg") {
		$fileext = "jpg";
	} elseif ($filetype == "image/png") {
		$fileext = "png";
	} else {
		throw new InternalServerErrorException('Unsupported filetype');
	}

	// change specified profile or all profiles to the new resource-id
	if ($is_default_profile) {
		$condition = ["`profile` AND `resource-id` != ? AND `uid` = ?", $data['photo']['id'], api_user()];
		Photo::update(['profile' => false], $condition);
	} else {
		$fields = ['photo' => DI::baseUrl() . '/photo/' . $data['photo']['id'] . '-4.' . $fileext,
			'thumb' => DI::baseUrl() . '/photo/' . $data['photo']['id'] . '-5.' . $fileext];
		DBA::update('profile', $fields, ['id' => $_REQUEST['profile'], 'uid' => api_user()]);
	}

	Contact::updateSelfFromUserID(api_user(), true);

	// Update global directory in background
	$url = DI::baseUrl() . '/profile/' . DI::app()->user['nickname'];
	if ($url && strlen(DI::config()->get('system', 'directory'))) {
		Worker::add(PRIORITY_LOW, "Directory", $url);
	}

	Worker::add(PRIORITY_LOW, 'ProfileUpdate', api_user());

	// output for client
	if ($data) {
		return api_account_verify_credentials($type);
	} else {
		// SaveMediaToDatabase failed for some reason
		throw new InternalServerErrorException("image upload failed");
	}
}

// place api-register for photoalbum calls before 'api/friendica/photo', otherwise this function is never reached
api_register_func('api/friendica/photoalbum/delete', 'api_fr_photoalbum_delete', true, API_METHOD_DELETE);
api_register_func('api/friendica/photoalbum/update', 'api_fr_photoalbum_update', true, API_METHOD_POST);
api_register_func('api/friendica/photos/list', 'api_fr_photos_list', true);
api_register_func('api/friendica/photo/create', 'api_fr_photo_create_update', true, API_METHOD_POST);
api_register_func('api/friendica/photo/update', 'api_fr_photo_create_update', true, API_METHOD_POST);
api_register_func('api/friendica/photo/delete', 'api_fr_photo_delete', true, API_METHOD_DELETE);
api_register_func('api/friendica/photo', 'api_fr_photo_detail', true);
api_register_func('api/account/update_profile_image', 'api_account_update_profile_image', true, API_METHOD_POST);

/**
 * Update user profile
 *
 * @param string $type Known types are 'atom', 'rss', 'xml' and 'json'
 *
 * @return array|string
 * @throws BadRequestException
 * @throws ForbiddenException
 * @throws ImagickException
 * @throws InternalServerErrorException
 * @throws UnauthorizedException
 */
function api_account_update_profile($type)
{
	$local_user = api_user();
	$api_user = api_get_user(DI::app());

	if (!empty($_POST['name'])) {
		DBA::update('profile', ['name' => $_POST['name']], ['uid' => $local_user]);
		DBA::update('user', ['username' => $_POST['name']], ['uid' => $local_user]);
		DBA::update('contact', ['name' => $_POST['name']], ['uid' => $local_user, 'self' => 1]);
		DBA::update('contact', ['name' => $_POST['name']], ['id' => $api_user['id']]);
	}

	if (isset($_POST['description'])) {
		DBA::update('profile', ['about' => $_POST['description']], ['uid' => $local_user]);
		DBA::update('contact', ['about' => $_POST['description']], ['uid' => $local_user, 'self' => 1]);
		DBA::update('contact', ['about' => $_POST['description']], ['id' => $api_user['id']]);
	}

	Worker::add(PRIORITY_LOW, 'ProfileUpdate', $local_user);
	// Update global directory in background
	if ($api_user['url'] && strlen(DI::config()->get('system', 'directory'))) {
		Worker::add(PRIORITY_LOW, "Directory", $api_user['url']);
	}

	return api_account_verify_credentials($type);
}

/// @TODO move to top of file or somewhere better
api_register_func('api/account/update_profile', 'api_account_update_profile', true, API_METHOD_POST);

/**
 *
 * @param string $acl_string
 * @return bool
 * @throws Exception
 */
function check_acl_input($acl_string)
{
	if (empty($acl_string)) {
		return false;
	}

	$contact_not_found = false;

	// split <x><y><z> into array of cid's
	preg_match_all("/<[A-Za-z0-9]+>/", $acl_string, $array);

	// check for each cid if it is available on server
	$cid_array = $array[0];
	foreach ($cid_array as $cid) {
		$cid = str_replace("<", "", $cid);
		$cid = str_replace(">", "", $cid);
		$condition = ['id' => $cid, 'uid' => api_user()];
		$contact_not_found |= !DBA::exists('contact', $condition);
	}
	return $contact_not_found;
}

/**
 * @param string  $mediatype
 * @param array   $media
 * @param string  $type
 * @param string  $album
 * @param string  $allow_cid
 * @param string  $deny_cid
 * @param string  $allow_gid
 * @param string  $deny_gid
 * @param string  $desc
 * @param integer $profile
 * @param boolean $visibility
 * @param string  $photo_id
 * @return array
 * @throws BadRequestException
 * @throws ForbiddenException
 * @throws ImagickException
 * @throws InternalServerErrorException
 * @throws NotFoundException
 * @throws UnauthorizedException
 */
function save_media_to_database($mediatype, $media, $type, $album, $allow_cid, $deny_cid, $allow_gid, $deny_gid, $desc, $profile = 0, $visibility = false, $photo_id = null)
{
	$visitor   = 0;
	$src = "";
	$filetype = "";
	$filename = "";
	$filesize = 0;

	if (is_array($media)) {
		if (is_array($media['tmp_name'])) {
			$src = $media['tmp_name'][0];
		} else {
			$src = $media['tmp_name'];
		}
		if (is_array($media['name'])) {
			$filename = basename($media['name'][0]);
		} else {
			$filename = basename($media['name']);
		}
		if (is_array($media['size'])) {
			$filesize = intval($media['size'][0]);
		} else {
			$filesize = intval($media['size']);
		}
		if (is_array($media['type'])) {
			$filetype = $media['type'][0];
		} else {
			$filetype = $media['type'];
		}
	}

	$filetype = Images::getMimeTypeBySource($src, $filename, $filetype);

	Logger::log(
		"File upload src: " . $src . " - filename: " . $filename .
		" - size: " . $filesize . " - type: " . $filetype,
		Logger::DEBUG
	);

	// check if there was a php upload error
	if ($filesize == 0 && $media['error'] == 1) {
		throw new InternalServerErrorException("image size exceeds PHP config settings, file was rejected by server");
	}
	// check against max upload size within Friendica instance
	$maximagesize = DI::config()->get('system', 'maximagesize');
	if ($maximagesize && ($filesize > $maximagesize)) {
		$formattedBytes = Strings::formatBytes($maximagesize);
		throw new InternalServerErrorException("image size exceeds Friendica config setting (uploaded size: $formattedBytes)");
	}

	// create Photo instance with the data of the image
	$imagedata = @file_get_contents($src);
	$Image = new Image($imagedata, $filetype);
	if (!$Image->isValid()) {
		throw new InternalServerErrorException("unable to process image data");
	}

	// check orientation of image
	$Image->orient($src);
	@unlink($src);

	// check max length of images on server
	$max_length = DI::config()->get('system', 'max_image_length');
	if (!$max_length) {
		$max_length = MAX_IMAGE_LENGTH;
	}
	if ($max_length > 0) {
		$Image->scaleDown($max_length);
		Logger::log("File upload: Scaling picture to new size " . $max_length, Logger::DEBUG);
	}
	$width = $Image->getWidth();
	$height = $Image->getHeight();

	// create a new resource-id if not already provided
	$resource_id = ($photo_id == null) ? Photo::newResource() : $photo_id;

	if ($mediatype == "photo") {
		// upload normal image (scales 0, 1, 2)
		Logger::log("photo upload: starting new photo upload", Logger::DEBUG);

		$r = Photo::store($Image, local_user(), $visitor, $resource_id, $filename, $album, 0, 0, $allow_cid, $allow_gid, $deny_cid, $deny_gid, $desc);
		if (!$r) {
			Logger::log("photo upload: image upload with scale 0 (original size) failed");
		}
		if ($width > 640 || $height > 640) {
			$Image->scaleDown(640);
			$r = Photo::store($Image, local_user(), $visitor, $resource_id, $filename, $album, 1, 0, $allow_cid, $allow_gid, $deny_cid, $deny_gid, $desc);
			if (!$r) {
				Logger::log("photo upload: image upload with scale 1 (640x640) failed");
			}
		}

		if ($width > 320 || $height > 320) {
			$Image->scaleDown(320);
			$r = Photo::store($Image, local_user(), $visitor, $resource_id, $filename, $album, 2, 0, $allow_cid, $allow_gid, $deny_cid, $deny_gid, $desc);
			if (!$r) {
				Logger::log("photo upload: image upload with scale 2 (320x320) failed");
			}
		}
		Logger::log("photo upload: new photo upload ended", Logger::DEBUG);
	} elseif ($mediatype == "profileimage") {
		// upload profile image (scales 4, 5, 6)
		Logger::log("photo upload: starting new profile image upload", Logger::DEBUG);

		if ($width > 300 || $height > 300) {
			$Image->scaleDown(300);
			$r = Photo::store($Image, local_user(), $visitor, $resource_id, $filename, $album, 4, $profile, $allow_cid, $allow_gid, $deny_cid, $deny_gid, $desc);
			if (!$r) {
				Logger::log("photo upload: profile image upload with scale 4 (300x300) failed");
			}
		}

		if ($width > 80 || $height > 80) {
			$Image->scaleDown(80);
			$r = Photo::store($Image, local_user(), $visitor, $resource_id, $filename, $album, 5, $profile, $allow_cid, $allow_gid, $deny_cid, $deny_gid, $desc);
			if (!$r) {
				Logger::log("photo upload: profile image upload with scale 5 (80x80) failed");
			}
		}

		if ($width > 48 || $height > 48) {
			$Image->scaleDown(48);
			$r = Photo::store($Image, local_user(), $visitor, $resource_id, $filename, $album, 6, $profile, $allow_cid, $allow_gid, $deny_cid, $deny_gid, $desc);
			if (!$r) {
				Logger::log("photo upload: profile image upload with scale 6 (48x48) failed");
			}
		}
		$Image->__destruct();
		Logger::log("photo upload: new profile image upload ended", Logger::DEBUG);
	}

	if (!empty($r)) {
		// create entry in 'item'-table on new uploads to enable users to comment/like/dislike the photo
		if ($photo_id == null && $mediatype == "photo") {
			post_photo_item($resource_id, $allow_cid, $deny_cid, $allow_gid, $deny_gid, $filetype, $visibility);
		}
		// on success return image data in json/xml format (like /api/friendica/photo does when no scale is given)
		return prepare_photo_data($type, false, $resource_id);
	} else {
		throw new InternalServerErrorException("image upload failed");
	}
}

/**
 *
 * @param string  $hash
 * @param string  $allow_cid
 * @param string  $deny_cid
 * @param string  $allow_gid
 * @param string  $deny_gid
 * @param string  $filetype
 * @param boolean $visibility
 * @throws InternalServerErrorException
 */
function post_photo_item($hash, $allow_cid, $deny_cid, $allow_gid, $deny_gid, $filetype, $visibility = false)
{
	// get data about the api authenticated user
	$uri = Item::newURI(intval(api_user()));
	$owner_record = DBA::selectFirst('contact', [], ['uid' => api_user(), 'self' => true]);

	$arr = [];
	$arr['guid']          = System::createUUID();
	$arr['uid']           = intval(api_user());
	$arr['uri']           = $uri;
	$arr['type']          = 'photo';
	$arr['wall']          = 1;
	$arr['resource-id']   = $hash;
	$arr['contact-id']    = $owner_record['id'];
	$arr['owner-name']    = $owner_record['name'];
	$arr['owner-link']    = $owner_record['url'];
	$arr['owner-avatar']  = $owner_record['thumb'];
	$arr['author-name']   = $owner_record['name'];
	$arr['author-link']   = $owner_record['url'];
	$arr['author-avatar'] = $owner_record['thumb'];
	$arr['title']         = "";
	$arr['allow_cid']     = $allow_cid;
	$arr['allow_gid']     = $allow_gid;
	$arr['deny_cid']      = $deny_cid;
	$arr['deny_gid']      = $deny_gid;
	$arr['visible']       = $visibility;
	$arr['origin']        = 1;

	$typetoext = [
			'image/jpeg' => 'jpg',
			'image/png' => 'png',
			'image/gif' => 'gif'
			];

	// adds link to the thumbnail scale photo
	$arr['body'] = '[url=' . DI::baseUrl() . '/photos/' . $owner_record['nick'] . '/image/' . $hash . ']'
				. '[img]' . DI::baseUrl() . '/photo/' . $hash . '-' . "2" . '.'. $typetoext[$filetype] . '[/img]'
				. '[/url]';

	// do the magic for storing the item in the database and trigger the federation to other contacts
	Item::insert($arr);
}

/**
 *
 * @param string $type
 * @param int    $scale
 * @param string $photo_id
 *
 * @return array
 * @throws BadRequestException
 * @throws ForbiddenException
 * @throws ImagickException
 * @throws InternalServerErrorException
 * @throws NotFoundException
 * @throws UnauthorizedException
 */
function prepare_photo_data($type, $scale, $photo_id)
{
	$a = DI::app();
	$user_info = api_get_user($a);

	if ($user_info === false) {
		throw new ForbiddenException();
	}

	$scale_sql = ($scale === false ? "" : sprintf("AND scale=%d", intval($scale)));
	$data_sql = ($scale === false ? "" : "data, ");

	// added allow_cid, allow_gid, deny_cid, deny_gid to output as string like stored in database
	// clients needs to convert this in their way for further processing
	$r = q(
		"SELECT %s `resource-id`, `created`, `edited`, `title`, `desc`, `album`, `filename`,
					`type`, `height`, `width`, `datasize`, `profile`, `allow_cid`, `deny_cid`, `allow_gid`, `deny_gid`,
					MIN(`scale`) AS `minscale`, MAX(`scale`) AS `maxscale`
			FROM `photo` WHERE `uid` = %d AND `resource-id` = '%s' %s GROUP BY 
			       `resource-id`, `created`, `edited`, `title`, `desc`, `album`, `filename`,
			       `type`, `height`, `width`, `datasize`, `profile`, `allow_cid`, `deny_cid`, `allow_gid`, `deny_gid`",
		$data_sql,
		intval(local_user()),
		DBA::escape($photo_id),
		$scale_sql
	);

	$typetoext = [
		'image/jpeg' => 'jpg',
		'image/png' => 'png',
		'image/gif' => 'gif'
	];

	// prepare output data for photo
	if (DBA::isResult($r)) {
		$data = ['photo' => $r[0]];
		$data['photo']['id'] = $data['photo']['resource-id'];
		if ($scale !== false) {
			$data['photo']['data'] = base64_encode($data['photo']['data']);
		} else {
			unset($data['photo']['datasize']); //needed only with scale param
		}
		if ($type == "xml") {
			$data['photo']['links'] = [];
			for ($k = intval($data['photo']['minscale']); $k <= intval($data['photo']['maxscale']); $k++) {
				$data['photo']['links'][$k . ":link"]["@attributes"] = ["type" => $data['photo']['type'],
										"scale" => $k,
										"href" => DI::baseUrl() . "/photo/" . $data['photo']['resource-id'] . "-" . $k . "." . $typetoext[$data['photo']['type']]];
			}
		} else {
			$data['photo']['link'] = [];
			// when we have profile images we could have only scales from 4 to 6, but index of array always needs to start with 0
			$i = 0;
			for ($k = intval($data['photo']['minscale']); $k <= intval($data['photo']['maxscale']); $k++) {
				$data['photo']['link'][$i] = DI::baseUrl() . "/photo/" . $data['photo']['resource-id'] . "-" . $k . "." . $typetoext[$data['photo']['type']];
				$i++;
			}
		}
		unset($data['photo']['resource-id']);
		unset($data['photo']['minscale']);
		unset($data['photo']['maxscale']);
	} else {
		throw new NotFoundException();
	}

	// retrieve item element for getting activities (like, dislike etc.) related to photo
	$condition = ['uid' => api_user(), 'resource-id' => $photo_id, 'type' => 'photo'];
	$item = Post::selectFirst(['id', 'uid', 'uri', 'parent', 'allow_cid', 'deny_cid', 'allow_gid', 'deny_gid'], $condition);
	if (!DBA::isResult($item)) {
		throw new NotFoundException('Photo-related item not found.');
	}

	$data['photo']['friendica_activities'] = api_format_items_activities($item, $type);

	// retrieve comments on photo
	$condition = ["`parent` = ? AND `uid` = ? AND (`gravity` IN (?, ?) OR `type`='photo')",
		$item['parent'], api_user(), GRAVITY_PARENT, GRAVITY_COMMENT];

	$statuses = Post::selectForUser(api_user(), [], $condition);

	// prepare output of comments
	$commentData = api_format_items(Post::toArray($statuses), $user_info, false, $type);
	$comments = [];
	if ($type == "xml") {
		$k = 0;
		foreach ($commentData as $comment) {
			$comments[$k++ . ":comment"] = $comment;
		}
	} else {
		foreach ($commentData as $comment) {
			$comments[] = $comment;
		}
	}
	$data['photo']['friendica_comments'] = $comments;

	// include info if rights on photo and rights on item are mismatching
	$rights_mismatch = $data['photo']['allow_cid'] != $item['allow_cid'] ||
		$data['photo']['deny_cid'] != $item['deny_cid'] ||
		$data['photo']['allow_gid'] != $item['allow_gid'] ||
		$data['photo']['deny_gid'] != $item['deny_gid'];
	$data['photo']['rights_mismatch'] = $rights_mismatch;

	return $data;
}


/**
 * Similar as /mod/redir.php
 * redirect to 'url' after dfrn auth
 *
 * Why this when there is mod/redir.php already?
 * This use api_user() and api_login()
 *
 * params
 * 		c_url: url of remote contact to auth to
 * 		url: string, url to redirect after auth
 */
function api_friendica_remoteauth()
{
	$url = $_GET['url'] ?? '';
	$c_url = $_GET['c_url'] ?? '';

	if ($url === '' || $c_url === '') {
		throw new BadRequestException("Wrong parameters.");
	}

	$c_url = Strings::normaliseLink($c_url);

	// traditional DFRN

	$contact = DBA::selectFirst('contact', [], ['uid' => api_user(), 'nurl' => $c_url]);
	if (!DBA::isResult($contact)) {
		throw new BadRequestException("Unknown contact");
	}

	$cid = $contact['id'];

	$dfrn_id = $contact['issued-id'] ?: $contact['dfrn-id'];

	if (($contact['network'] !== Protocol::DFRN) || empty($dfrn_id)) {
		System::externalRedirect($url ?: $c_url);
	}

	if ($contact['duplex'] && $contact['issued-id']) {
		$orig_id = $contact['issued-id'];
		$dfrn_id = '1:' . $orig_id;
	}
	if ($contact['duplex'] && $contact['dfrn-id']) {
		$orig_id = $contact['dfrn-id'];
		$dfrn_id = '0:' . $orig_id;
	}

	$sec = Strings::getRandomHex();

	$fields = ['uid' => api_user(), 'cid' => $cid, 'dfrn_id' => $dfrn_id,
		'sec' => $sec, 'expire' => time() + 45];
	DBA::insert('profile_check', $fields);

	Logger::info(API_LOG_PREFIX . 'for contact {contact}', ['module' => 'api', 'action' => 'friendica_remoteauth', 'contact' => $contact['name'], 'hey' => $sec]);
	$dest = ($url ? '&destination_url=' . $url : '');

	System::externalRedirect(
		$contact['poll'] . '?dfrn_id=' . $dfrn_id
		. '&dfrn_version=' . DFRN_PROTOCOL_VERSION
		. '&type=profile&sec=' . $sec . $dest
	);
}
api_register_func('api/friendica/remoteauth', 'api_friendica_remoteauth', true);

/**
 * Return an item with announcer data if it had been announced
 *
 * @param array $item Item array
 * @return array Item array with announce data
 */
function api_get_announce($item)
{
	// Quit if the item already has got a different owner and author
	if ($item['owner-id'] != $item['author-id']) {
		return [];
	}

	// Don't change original or Diaspora posts
	if ($item['origin'] || in_array($item['network'], [Protocol::DIASPORA])) {
		return [];
	}

	// Quit if we do now the original author and it had been a post from a native network
	if (!empty($item['contact-uid']) && in_array($item['network'], Protocol::NATIVE_SUPPORT)) {
		return [];
	}

	$fields = ['author-id', 'author-name', 'author-link', 'author-avatar'];
	$condition = ['parent-uri' => $item['uri'], 'gravity' => GRAVITY_ACTIVITY, 'uid' => [0, $item['uid']], 'vid' => Verb::getID(Activity::ANNOUNCE)];
	$announce = Post::selectFirstForUser($item['uid'], $fields, $condition, ['order' => ['received' => true]]);
	if (!DBA::isResult($announce)) {
		return [];
	}

	return array_merge($item, $announce);
}

/**
 * Return the item shared, if the item contains only the [share] tag
 *
 * @param array $item Sharer item
 * @return array|false Shared item or false if not a reshare
 * @throws ImagickException
 * @throws InternalServerErrorException
 */
function api_share_as_retweet(&$item)
{
	$body = trim($item["body"]);

	if (Diaspora::isReshare($body, false) === false) {
		if ($item['author-id'] == $item['owner-id']) {
			return false;
		} else {
			// Reshares from OStatus, ActivityPub and Twitter
			$reshared_item = $item;
			$reshared_item['owner-id'] = $reshared_item['author-id'];
			$reshared_item['owner-link'] = $reshared_item['author-link'];
			$reshared_item['owner-name'] = $reshared_item['author-name'];
			$reshared_item['owner-avatar'] = $reshared_item['author-avatar'];
			return $reshared_item;
		}
	}

	$reshared = Item::getShareArray($item);
	if (empty($reshared)) {
		return false;
	}

	$reshared_item = $item;

	if (empty($reshared['shared']) || empty($reshared['profile']) || empty($reshared['author']) || empty($reshared['avatar']) || empty($reshared['posted'])) {
		return false;
	}

	if (!empty($reshared['comment'])) {
		$item['body'] = $reshared['comment'];
	}

	$reshared_item["share-pre-body"] = $reshared['comment'];
	$reshared_item["body"] = $reshared['shared'];
	$reshared_item["author-id"] = Contact::getIdForURL($reshared['profile'], 0, false);
	$reshared_item["author-name"] = $reshared['author'];
	$reshared_item["author-link"] = $reshared['profile'];
	$reshared_item["author-avatar"] = $reshared['avatar'];
	$reshared_item["plink"] = $reshared['link'] ?? '';
	$reshared_item["created"] = $reshared['posted'];
	$reshared_item["edited"] = $reshared['posted'];

	// Try to fetch the original item
	if (!empty($reshared['guid'])) {
		$condition = ['guid' => $reshared['guid'], 'uid' => [0, $item['uid']]];
	} elseif (!empty($reshared_item['plink']) && ($original_id = Item::searchByLink($reshared_item['plink']))) {
		$condition = ['id' => $original_id];
	} else {
		$condition = [];
	}

	if (!empty($condition)) {
		$original_item = Post::selectFirst([], $condition);
		if (DBA::isResult($original_item)) {
			$reshared_item = array_merge($reshared_item, $original_item);
		}
	}

	return $reshared_item;
}

/**
 *
 * @param array $item
 *
 * @return array
 * @throws Exception
 */
function api_in_reply_to($item)
{
	$in_reply_to = [];

	$in_reply_to['status_id'] = null;
	$in_reply_to['user_id'] = null;
	$in_reply_to['status_id_str'] = null;
	$in_reply_to['user_id_str'] = null;
	$in_reply_to['screen_name'] = null;

	if (($item['thr-parent'] != $item['uri']) && ($item['gravity'] != GRAVITY_PARENT)) {
		$parent = Post::selectFirst(['id'], ['uid' => $item['uid'], 'uri' => $item['thr-parent']]);
		if (DBA::isResult($parent)) {
			$in_reply_to['status_id'] = intval($parent['id']);
		} else {
			$in_reply_to['status_id'] = intval($item['parent']);
		}

		$in_reply_to['status_id_str'] = (string) intval($in_reply_to['status_id']);

		$fields = ['author-nick', 'author-name', 'author-id', 'author-link'];
		$parent = Post::selectFirst($fields, ['id' => $in_reply_to['status_id']]);

		if (DBA::isResult($parent)) {
			$in_reply_to['screen_name'] = (($parent['author-nick']) ? $parent['author-nick'] : $parent['author-name']);
			$in_reply_to['user_id'] = intval($parent['author-id']);
			$in_reply_to['user_id_str'] = (string) intval($parent['author-id']);
		}

		// There seems to be situation, where both fields are identical:
		// https://github.com/friendica/friendica/issues/1010
		// This is a bugfix for that.
		if (intval($in_reply_to['status_id']) == intval($item['id'])) {
			Logger::warning(API_LOG_PREFIX . 'ID {id} is similar to reply-to {reply-to}', ['module' => 'api', 'action' => 'in_reply_to', 'id' => $item['id'], 'reply-to' => $in_reply_to['status_id']]);
			$in_reply_to['status_id'] = null;
			$in_reply_to['user_id'] = null;
			$in_reply_to['status_id_str'] = null;
			$in_reply_to['user_id_str'] = null;
			$in_reply_to['screen_name'] = null;
		}
	}

	return $in_reply_to;
}

/**
 *
 * @param string $text
 *
 * @return string
 * @throws InternalServerErrorException
 */
function api_clean_plain_items($text)
{
	$include_entities = strtolower($_REQUEST['include_entities'] ?? 'false');

	$text = BBCode::cleanPictureLinks($text);
	$URLSearchString = "^\[\]";

	$text = preg_replace("/([!#@])\[url\=([$URLSearchString]*)\](.*?)\[\/url\]/ism", '$1$3', $text);

	if ($include_entities == "true") {
		$text = preg_replace("/\[url\=([$URLSearchString]*)\](.*?)\[\/url\]/ism", '[url=$1]$1[/url]', $text);
	}

	// Simplify "attachment" element
	$text = BBCode::removeAttachment($text);

	return $text;
}

/**
 *
 * @param array $contacts
 *
 * @return void
 */
function api_best_nickname(&$contacts)
{
	$best_contact = [];

	if (count($contacts) == 0) {
		return;
	}

	foreach ($contacts as $contact) {
		if ($contact["network"] == "") {
			$contact["network"] = "dfrn";
			$best_contact = [$contact];
		}
	}

	if (sizeof($best_contact) == 0) {
		foreach ($contacts as $contact) {
			if ($contact["network"] == "dfrn") {
				$best_contact = [$contact];
			}
		}
	}

	if (sizeof($best_contact) == 0) {
		foreach ($contacts as $contact) {
			if ($contact["network"] == "dspr") {
				$best_contact = [$contact];
			}
		}
	}

	if (sizeof($best_contact) == 0) {
		foreach ($contacts as $contact) {
			if ($contact["network"] == "stat") {
				$best_contact = [$contact];
			}
		}
	}

	if (sizeof($best_contact) == 0) {
		foreach ($contacts as $contact) {
			if ($contact["network"] == "pump") {
				$best_contact = [$contact];
			}
		}
	}

	if (sizeof($best_contact) == 0) {
		foreach ($contacts as $contact) {
			if ($contact["network"] == "twit") {
				$best_contact = [$contact];
			}
		}
	}

	if (sizeof($best_contact) == 1) {
		$contacts = $best_contact;
	} else {
		$contacts = [$contacts[0]];
	}
}

/**
 * Return all or a specified group of the user with the containing contacts.
 *
 * @param string $type Return type (atom, rss, xml, json)
 *
 * @return array|string
 * @throws BadRequestException
 * @throws ForbiddenException
 * @throws ImagickException
 * @throws InternalServerErrorException
 * @throws UnauthorizedException
 */
function api_friendica_group_show($type)
{
	$a = DI::app();

	if (api_user() === false) {
		throw new ForbiddenException();
	}

	// params
	$user_info = api_get_user($a);
	$gid = $_REQUEST['gid'] ?? 0;
	$uid = $user_info['uid'];

	// get data of the specified group id or all groups if not specified
	if ($gid != 0) {
		$r = q(
			"SELECT * FROM `group` WHERE `deleted` = 0 AND `uid` = %d AND `id` = %d",
			intval($uid),
			intval($gid)
		);
		// error message if specified gid is not in database
		if (!DBA::isResult($r)) {
			throw new BadRequestException("gid not available");
		}
	} else {
		$r = q(
			"SELECT * FROM `group` WHERE `deleted` = 0 AND `uid` = %d",
			intval($uid)
		);
	}

	// loop through all groups and retrieve all members for adding data in the user array
	$grps = [];
	foreach ($r as $rr) {
		$members = Contact\Group::getById($rr['id']);
		$users = [];

		if ($type == "xml") {
			$user_element = "users";
			$k = 0;
			foreach ($members as $member) {
				$user = api_get_user($a, $member['nurl']);
				$users[$k++.":user"] = $user;
			}
		} else {
			$user_element = "user";
			foreach ($members as $member) {
				$user = api_get_user($a, $member['nurl']);
				$users[] = $user;
			}
		}
		$grps[] = ['name' => $rr['name'], 'gid' => $rr['id'], $user_element => $users];
	}
	return api_format_data("groups", $type, ['group' => $grps]);
}
api_register_func('api/friendica/group_show', 'api_friendica_group_show', true);


/**
 * Delete the specified group of the user.
 *
 * @param string $type Return type (atom, rss, xml, json)
 *
 * @return array|string
 * @throws BadRequestException
 * @throws ForbiddenException
 * @throws ImagickException
 * @throws InternalServerErrorException
 * @throws UnauthorizedException
 */
function api_friendica_group_delete($type)
{
	$a = DI::app();

	if (api_user() === false) {
		throw new ForbiddenException();
	}

	// params
	$user_info = api_get_user($a);
	$gid = $_REQUEST['gid'] ?? 0;
	$name = $_REQUEST['name'] ?? '';
	$uid = $user_info['uid'];

	// error if no gid specified
	if ($gid == 0 || $name == "") {
		throw new BadRequestException('gid or name not specified');
	}

	// get data of the specified group id
	$r = q(
		"SELECT * FROM `group` WHERE `uid` = %d AND `id` = %d",
		intval($uid),
		intval($gid)
	);
	// error message if specified gid is not in database
	if (!DBA::isResult($r)) {
		throw new BadRequestException('gid not available');
	}

	// get data of the specified group id and group name
	$rname = q(
		"SELECT * FROM `group` WHERE `uid` = %d AND `id` = %d AND `name` = '%s'",
		intval($uid),
		intval($gid),
		DBA::escape($name)
	);
	// error message if specified gid is not in database
	if (!DBA::isResult($rname)) {
		throw new BadRequestException('wrong group name');
	}

	// delete group
	$ret = Group::removeByName($uid, $name);
	if ($ret) {
		// return success
		$success = ['success' => $ret, 'gid' => $gid, 'name' => $name, 'status' => 'deleted', 'wrong users' => []];
		return api_format_data("group_delete", $type, ['result' => $success]);
	} else {
		throw new BadRequestException('other API error');
	}
}
api_register_func('api/friendica/group_delete', 'api_friendica_group_delete', true, API_METHOD_DELETE);

/**
 * Delete a group.
 *
 * @param string $type Return type (atom, rss, xml, json)
 *
 * @return array|string
 * @throws BadRequestException
 * @throws ForbiddenException
 * @throws ImagickException
 * @throws InternalServerErrorException
 * @throws UnauthorizedException
 * @see https://developer.twitter.com/en/docs/accounts-and-users/create-manage-lists/api-reference/post-lists-destroy
 */
function api_lists_destroy($type)
{
	$a = DI::app();

	if (api_user() === false) {
		throw new ForbiddenException();
	}

	// params
	$user_info = api_get_user($a);
	$gid = $_REQUEST['list_id'] ?? 0;
	$uid = $user_info['uid'];

	// error if no gid specified
	if ($gid == 0) {
		throw new BadRequestException('gid not specified');
	}

	// get data of the specified group id
	$group = DBA::selectFirst('group', [], ['uid' => $uid, 'id' => $gid]);
	// error message if specified gid is not in database
	if (!$group) {
		throw new BadRequestException('gid not available');
	}

	if (Group::remove($gid)) {
		$list = [
			'name' => $group['name'],
			'id' => intval($gid),
			'id_str' => (string) $gid,
			'user' => $user_info
		];

		return api_format_data("lists", $type, ['lists' => $list]);
	}
}
api_register_func('api/lists/destroy', 'api_lists_destroy', true, API_METHOD_DELETE);

/**
 * Add a new group to the database.
 *
 * @param  string $name  Group name
 * @param  int    $uid   User ID
 * @param  array  $users List of users to add to the group
 *
 * @return array
 * @throws BadRequestException
 */
function group_create($name, $uid, $users = [])
{
	// error if no name specified
	if ($name == "") {
		throw new BadRequestException('group name not specified');
	}

	// get data of the specified group name
	$rname = q(
		"SELECT * FROM `group` WHERE `uid` = %d AND `name` = '%s' AND `deleted` = 0",
		intval($uid),
		DBA::escape($name)
	);
	// error message if specified group name already exists
	if (DBA::isResult($rname)) {
		throw new BadRequestException('group name already exists');
	}

	// check if specified group name is a deleted group
	$rname = q(
		"SELECT * FROM `group` WHERE `uid` = %d AND `name` = '%s' AND `deleted` = 1",
		intval($uid),
		DBA::escape($name)
	);
	// error message if specified group name already exists
	if (DBA::isResult($rname)) {
		$reactivate_group = true;
	}

	// create group
	$ret = Group::create($uid, $name);
	if ($ret) {
		$gid = Group::getIdByName($uid, $name);
	} else {
		throw new BadRequestException('other API error');
	}

	// add members
	$erroraddinguser = false;
	$errorusers = [];
	foreach ($users as $user) {
		$cid = $user['cid'];
		// check if user really exists as contact
		$contact = q(
			"SELECT * FROM `contact` WHERE `id` = %d AND `uid` = %d",
			intval($cid),
			intval($uid)
		);
		if (count($contact)) {
			Group::addMember($gid, $cid);
		} else {
			$erroraddinguser = true;
			$errorusers[] = $cid;
		}
	}

	// return success message incl. missing users in array
	$status = ($erroraddinguser ? "missing user" : ((isset($reactivate_group) && $reactivate_group) ? "reactivated" : "ok"));

	return ['success' => true, 'gid' => $gid, 'name' => $name, 'status' => $status, 'wrong users' => $errorusers];
}

/**
 * Create the specified group with the posted array of contacts.
 *
 * @param string $type Return type (atom, rss, xml, json)
 *
 * @return array|string
 * @throws BadRequestException
 * @throws ForbiddenException
 * @throws ImagickException
 * @throws InternalServerErrorException
 * @throws UnauthorizedException
 */
function api_friendica_group_create($type)
{
	$a = DI::app();

	if (api_user() === false) {
		throw new ForbiddenException();
	}

	// params
	$user_info = api_get_user($a);
	$name = $_REQUEST['name'] ?? '';
	$uid = $user_info['uid'];
	$json = json_decode($_POST['json'], true);
	$users = $json['user'];

	$success = group_create($name, $uid, $users);

	return api_format_data("group_create", $type, ['result' => $success]);
}
api_register_func('api/friendica/group_create', 'api_friendica_group_create', true, API_METHOD_POST);

/**
 * Create a new group.
 *
 * @param string $type Return type (atom, rss, xml, json)
 *
 * @return array|string
 * @throws BadRequestException
 * @throws ForbiddenException
 * @throws ImagickException
 * @throws InternalServerErrorException
 * @throws UnauthorizedException
 * @see https://developer.twitter.com/en/docs/accounts-and-users/create-manage-lists/api-reference/post-lists-create
 */
function api_lists_create($type)
{
	$a = DI::app();

	if (api_user() === false) {
		throw new ForbiddenException();
	}

	// params
	$user_info = api_get_user($a);
	$name = $_REQUEST['name'] ?? '';
	$uid = $user_info['uid'];

	$success = group_create($name, $uid);
	if ($success['success']) {
		$grp = [
			'name' => $success['name'],
			'id' => intval($success['gid']),
			'id_str' => (string) $success['gid'],
			'user' => $user_info
		];

		return api_format_data("lists", $type, ['lists'=>$grp]);
	}
}
api_register_func('api/lists/create', 'api_lists_create', true, API_METHOD_POST);

/**
 * Update the specified group with the posted array of contacts.
 *
 * @param string $type Return type (atom, rss, xml, json)
 *
 * @return array|string
 * @throws BadRequestException
 * @throws ForbiddenException
 * @throws ImagickException
 * @throws InternalServerErrorException
 * @throws UnauthorizedException
 */
function api_friendica_group_update($type)
{
	$a = DI::app();

	if (api_user() === false) {
		throw new ForbiddenException();
	}

	// params
	$user_info = api_get_user($a);
	$uid = $user_info['uid'];
	$gid = $_REQUEST['gid'] ?? 0;
	$name = $_REQUEST['name'] ?? '';
	$json = json_decode($_POST['json'], true);
	$users = $json['user'];

	// error if no name specified
	if ($name == "") {
		throw new BadRequestException('group name not specified');
	}

	// error if no gid specified
	if ($gid == "") {
		throw new BadRequestException('gid not specified');
	}

	// remove members
	$members = Contact\Group::getById($gid);
	foreach ($members as $member) {
		$cid = $member['id'];
		foreach ($users as $user) {
			$found = ($user['cid'] == $cid ? true : false);
		}
		if (!isset($found) || !$found) {
			Group::removeMemberByName($uid, $name, $cid);
		}
	}

	// add members
	$erroraddinguser = false;
	$errorusers = [];
	foreach ($users as $user) {
		$cid = $user['cid'];
		// check if user really exists as contact
		$contact = q(
			"SELECT * FROM `contact` WHERE `id` = %d AND `uid` = %d",
			intval($cid),
			intval($uid)
		);

		if (count($contact)) {
			Group::addMember($gid, $cid);
		} else {
			$erroraddinguser = true;
			$errorusers[] = $cid;
		}
	}

	// return success message incl. missing users in array
	$status = ($erroraddinguser ? "missing user" : "ok");
	$success = ['success' => true, 'gid' => $gid, 'name' => $name, 'status' => $status, 'wrong users' => $errorusers];
	return api_format_data("group_update", $type, ['result' => $success]);
}

api_register_func('api/friendica/group_update', 'api_friendica_group_update', true, API_METHOD_POST);

/**
 * Update information about a group.
 *
 * @param string $type Return type (atom, rss, xml, json)
 *
 * @return array|string
 * @throws BadRequestException
 * @throws ForbiddenException
 * @throws ImagickException
 * @throws InternalServerErrorException
 * @throws UnauthorizedException
 * @see https://developer.twitter.com/en/docs/accounts-and-users/create-manage-lists/api-reference/post-lists-update
 */
function api_lists_update($type)
{
	$a = DI::app();

	if (api_user() === false) {
		throw new ForbiddenException();
	}

	// params
	$user_info = api_get_user($a);
	$gid = $_REQUEST['list_id'] ?? 0;
	$name = $_REQUEST['name'] ?? '';
	$uid = $user_info['uid'];

	// error if no gid specified
	if ($gid == 0) {
		throw new BadRequestException('gid not specified');
	}

	// get data of the specified group id
	$group = DBA::selectFirst('group', [], ['uid' => $uid, 'id' => $gid]);
	// error message if specified gid is not in database
	if (!$group) {
		throw new BadRequestException('gid not available');
	}

	if (Group::update($gid, $name)) {
		$list = [
			'name' => $name,
			'id' => intval($gid),
			'id_str' => (string) $gid,
			'user' => $user_info
		];

		return api_format_data("lists", $type, ['lists' => $list]);
	}
}

api_register_func('api/lists/update', 'api_lists_update', true, API_METHOD_POST);

/**
 *
 * @param string $type Return type (atom, rss, xml, json)
 *
 * @return array|string
 * @throws BadRequestException
 * @throws ForbiddenException
 * @throws ImagickException
 * @throws InternalServerErrorException
 */
function api_friendica_activity($type)
{
	$a = DI::app();

	if (api_user() === false) {
		throw new ForbiddenException();
	}
	$verb = strtolower($a->argv[3]);
	$verb = preg_replace("|\..*$|", "", $verb);

	$id = $_REQUEST['id'] ?? 0;

	$res = Item::performActivity($id, $verb, api_user());

	if ($res) {
		if ($type == "xml") {
			$ok = "true";
		} else {
			$ok = "ok";
		}
		return api_format_data('ok', $type, ['ok' => $ok]);
	} else {
		throw new BadRequestException('Error adding activity');
	}
}

/// @TODO move to top of file or somewhere better
api_register_func('api/friendica/activity/like', 'api_friendica_activity', true, API_METHOD_POST);
api_register_func('api/friendica/activity/dislike', 'api_friendica_activity', true, API_METHOD_POST);
api_register_func('api/friendica/activity/attendyes', 'api_friendica_activity', true, API_METHOD_POST);
api_register_func('api/friendica/activity/attendno', 'api_friendica_activity', true, API_METHOD_POST);
api_register_func('api/friendica/activity/attendmaybe', 'api_friendica_activity', true, API_METHOD_POST);
api_register_func('api/friendica/activity/unlike', 'api_friendica_activity', true, API_METHOD_POST);
api_register_func('api/friendica/activity/undislike', 'api_friendica_activity', true, API_METHOD_POST);
api_register_func('api/friendica/activity/unattendyes', 'api_friendica_activity', true, API_METHOD_POST);
api_register_func('api/friendica/activity/unattendno', 'api_friendica_activity', true, API_METHOD_POST);
api_register_func('api/friendica/activity/unattendmaybe', 'api_friendica_activity', true, API_METHOD_POST);

/**
 * Returns notifications
 *
 * @param string $type Known types are 'atom', 'rss', 'xml' and 'json'
 *
 * @return string|array
 * @throws ForbiddenException
 * @throws BadRequestException
 * @throws Exception
 */
function api_friendica_notification($type)
{
	$a = DI::app();

	if (api_user() === false) {
		throw new ForbiddenException();
	}
	if ($a->argc!==3) {
		throw new BadRequestException("Invalid argument count");
	}

	$notifications = DI::notification()->getApiList(local_user());

	if ($type == "xml") {
		$xmlnotes = false;
		if (!empty($notifications)) {
			foreach ($notifications as $notification) {
				$xmlnotes[] = ["@attributes" => $notification->toArray()];
			}
		}

		$result = $xmlnotes;
	} elseif (count($notifications) > 0) {
		$result = $notifications->getArrayCopy();
	} else {
		$result = false;
	}

	return api_format_data("notes", $type, ['note' => $result]);
}

/**
 * Set notification as seen and returns associated item (if possible)
 *
 * POST request with 'id' param as notification id
 *
 * @param string $type Known types are 'atom', 'rss', 'xml' and 'json'
 * @return string|array
 * @throws BadRequestException
 * @throws ForbiddenException
 * @throws ImagickException
 * @throws InternalServerErrorException
 * @throws UnauthorizedException
 */
function api_friendica_notification_seen($type)
{
	$a         = DI::app();
	$user_info = api_get_user($a);

	if (api_user() === false || $user_info === false) {
		throw new ForbiddenException();
	}
	if ($a->argc !== 4) {
		throw new BadRequestException("Invalid argument count");
	}

	$id = (!empty($_REQUEST['id']) ? intval($_REQUEST['id']) : 0);

	try {
		$notify = DI::notify()->getByID($id, api_user());
		DI::notify()->setSeen(true, $notify);

		if ($notify->otype === Notification\ObjectType::ITEM) {
			$item = Post::selectFirstForUser(api_user(), [], ['id' => $notify->iid, 'uid' => api_user()]);
			if (DBA::isResult($item)) {
				// we found the item, return it to the user
				$ret  = api_format_items([$item], $user_info, false, $type);
				$data = ['status' => $ret];
				return api_format_data("status", $type, $data);
			}
			// the item can't be found, but we set the notification as seen, so we count this as a success
		}
		return api_format_data('result', $type, ['result' => "success"]);
	} catch (NotFoundException $e) {
		throw new BadRequestException('Invalid argument', $e);
	} catch (Exception $e) {
		throw new InternalServerErrorException('Internal Server exception', $e);
	}
}

/// @TODO move to top of file or somewhere better
api_register_func('api/friendica/notification/seen', 'api_friendica_notification_seen', true, API_METHOD_POST);
api_register_func('api/friendica/notification', 'api_friendica_notification', true, API_METHOD_GET);

/**
 * update a direct_message to seen state
 *
 * @param string $type Known types are 'atom', 'rss', 'xml' and 'json'
 * @return string|array (success result=ok, error result=error with error message)
 * @throws BadRequestException
 * @throws ForbiddenException
 * @throws ImagickException
 * @throws InternalServerErrorException
 * @throws UnauthorizedException
 */
function api_friendica_direct_messages_setseen($type)
{
	$a = DI::app();
	if (api_user() === false) {
		throw new ForbiddenException();
	}

	// params
	$user_info = api_get_user($a);
	$uid = $user_info['uid'];
	$id = $_REQUEST['id'] ?? 0;

	// return error if id is zero
	if ($id == "") {
		$answer = ['result' => 'error', 'message' => 'message id not specified'];
		return api_format_data("direct_messages_setseen", $type, ['$result' => $answer]);
	}

	// error message if specified id is not in database
	if (!DBA::exists('mail', ['id' => $id, 'uid' => $uid])) {
		$answer = ['result' => 'error', 'message' => 'message id not in database'];
		return api_format_data("direct_messages_setseen", $type, ['$result' => $answer]);
	}

	// update seen indicator
	$result = DBA::update('mail', ['seen' => true], ['id' => $id]);

	if ($result) {
		// return success
		$answer = ['result' => 'ok', 'message' => 'message set to seen'];
		return api_format_data("direct_message_setseen", $type, ['$result' => $answer]);
	} else {
		$answer = ['result' => 'error', 'message' => 'unknown error'];
		return api_format_data("direct_messages_setseen", $type, ['$result' => $answer]);
	}
}

/// @TODO move to top of file or somewhere better
api_register_func('api/friendica/direct_messages_setseen', 'api_friendica_direct_messages_setseen', true);

/**
 * search for direct_messages containing a searchstring through api
 *
 * @param string $type      Known types are 'atom', 'rss', 'xml' and 'json'
 * @param string $box
 * @return string|array (success: success=true if found and search_result contains found messages,
 *                          success=false if nothing was found, search_result='nothing found',
 *                          error: result=error with error message)
 * @throws BadRequestException
 * @throws ForbiddenException
 * @throws ImagickException
 * @throws InternalServerErrorException
 * @throws UnauthorizedException
 */
function api_friendica_direct_messages_search($type, $box = "")
{
	$a = DI::app();

	if (api_user() === false) {
		throw new ForbiddenException();
	}

	// params
	$user_info = api_get_user($a);
	$searchstring = $_REQUEST['searchstring'] ?? '';
	$uid = $user_info['uid'];

	// error if no searchstring specified
	if ($searchstring == "") {
		$answer = ['result' => 'error', 'message' => 'searchstring not specified'];
		return api_format_data("direct_messages_search", $type, ['$result' => $answer]);
	}

	// get data for the specified searchstring
	$r = q(
		"SELECT `mail`.*, `contact`.`nurl` AS `contact-url` FROM `mail`,`contact` WHERE `mail`.`contact-id` = `contact`.`id` AND `mail`.`uid`=%d AND `body` LIKE '%s' ORDER BY `mail`.`id` DESC",
		intval($uid),
		DBA::escape('%'.$searchstring.'%')
	);

	$profile_url = $user_info["url"];

	// message if nothing was found
	if (!DBA::isResult($r)) {
		$success = ['success' => false, 'search_results' => 'problem with query'];
	} elseif (count($r) == 0) {
		$success = ['success' => false, 'search_results' => 'nothing found'];
	} else {
		$ret = [];
		foreach ($r as $item) {
			if ($box == "inbox" || $item['from-url'] != $profile_url) {
				$recipient = $user_info;
				$sender = api_get_user($a, Strings::normaliseLink($item['contact-url']));
			} elseif ($box == "sentbox" || $item['from-url'] == $profile_url) {
				$recipient = api_get_user($a, Strings::normaliseLink($item['contact-url']));
				$sender = $user_info;
			}

			if (isset($recipient) && isset($sender)) {
				$ret[] = api_format_messages($item, $recipient, $sender);
			}
		}
		$success = ['success' => true, 'search_results' => $ret];
	}

	return api_format_data("direct_message_search", $type, ['$result' => $success]);
}

/// @TODO move to top of file or somewhere better
api_register_func('api/friendica/direct_messages_search', 'api_friendica_direct_messages_search', true);

/**
 * Returns a list of saved searches.
 *
 * @see https://developer.twitter.com/en/docs/accounts-and-users/manage-account-settings/api-reference/get-saved_searches-list
 *
 * @param  string $type Return format: json or xml
 *
 * @return string|array
 * @throws Exception
 */
function api_saved_searches_list($type)
{
	$terms = DBA::select('search', ['id', 'term'], ['uid' => local_user()]);

	$result = [];
	while ($term = DBA::fetch($terms)) {
		$result[] = [
			'created_at' => api_date(time()),
			'id' => intval($term['id']),
			'id_str' => $term['id'],
			'name' => $term['term'],
			'position' => null,
			'query' => $term['term']
		];
	}

	DBA::close($terms);

	return api_format_data("terms", $type, ['terms' => $result]);
}

/// @TODO move to top of file or somewhere better
api_register_func('api/saved_searches/list', 'api_saved_searches_list', true);

/*
 * Number of comments
 *
 * Bind comment numbers(friendica_comments: Int) on each statuses page of *_timeline / favorites / search
 *
 * @param object $data [Status, Status]
 *
 * @return void
 */
function bindComments(&$data) 
{
	if (count($data) == 0) {
		return;
	}
	
	$ids = [];
	$comments = [];
	foreach ($data as $item) {
		$ids[] = $item['id'];
	}

	$idStr = DBA::escape(implode(', ', $ids));
	$sql = "SELECT `parent`, COUNT(*) as comments FROM `post-view` WHERE `parent` IN ($idStr) AND `deleted` = ? AND `gravity`= ? GROUP BY `parent`";
	$items = DBA::p($sql, 0, GRAVITY_COMMENT);
	$itemsData = DBA::toArray($items);

	foreach ($itemsData as $item) {
		$comments[$item['parent']] = $item['comments'];
	}

	foreach ($data as $idx => $item) {
		$id = $item['id'];
		$data[$idx]['friendica_comments'] = isset($comments[$id]) ? $comments[$id] : 0;
	}
}

/*
@TODO Maybe open to implement?
To.Do:
	[pagename] => api/1.1/statuses/lookup.json
	[id] => 605138389168451584
	[include_cards] => true
	[cards_platform] => Android-12
	[include_entities] => true
	[include_my_retweet] => 1
	[include_rts] => 1
	[include_reply_count] => true
	[include_descendent_reply_count] => true
(?)


Not implemented by now:
statuses/retweets_of_me
friendships/create
friendships/destroy
friendships/exists
friendships/show
account/update_location
account/update_profile_background_image
blocks/create
blocks/destroy
friendica/profile/update
friendica/profile/create
friendica/profile/delete

Not implemented in status.net:
statuses/retweeted_to_me
statuses/retweeted_by_me
direct_messages/destroy
account/end_session
account/update_delivery_device
notifications/follow
notifications/leave
blocks/exists
blocks/blocking
lists
*/
