<?php
/**
 * Friendica implementation of statusnet/twitter API
 *
 * @file include/api.php
 * @todo Automatically detect if incoming data is HTML or BBCode
 */

use Friendica\App;
use Friendica\Content\ContactSelector;
use Friendica\Content\Feature;
use Friendica\Content\Text\BBCode;
use Friendica\Content\Text\HTML;
use Friendica\Core\Authentication;
use Friendica\Core\Config;
use Friendica\Core\L10n;
use Friendica\Core\Logger;
use Friendica\Core\NotificationsManager;
use Friendica\Core\PConfig;
use Friendica\Core\Protocol;
use Friendica\Core\System;
use Friendica\Core\Worker;
use Friendica\Database\DBA;
use Friendica\Model\Contact;
use Friendica\Model\Group;
use Friendica\Model\Item;
use Friendica\Model\Mail;
use Friendica\Model\Photo;
use Friendica\Model\User;
use Friendica\Network\FKOAuth1;
use Friendica\Network\HTTPException;
use Friendica\Network\HTTPException\BadRequestException;
use Friendica\Network\HTTPException\ExpectationFailedException;
use Friendica\Network\HTTPException\ForbiddenException;
use Friendica\Network\HTTPException\InternalServerErrorException;
use Friendica\Network\HTTPException\MethodNotAllowedException;
use Friendica\Network\HTTPException\NotFoundException;
use Friendica\Network\HTTPException\NotImplementedException;
use Friendica\Network\HTTPException\TooManyRequestsException;
use Friendica\Network\HTTPException\UnauthorizedException;
use Friendica\Object\Image;
use Friendica\Protocol\Diaspora;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Network;
use Friendica\Util\Proxy as ProxyUtils;
use Friendica\Util\Strings;
use Friendica\Util\XML;

require_once 'mod/share.php';
require_once 'mod/item.php';
require_once 'mod/wall_upload.php';

define('API_METHOD_ANY', '*');
define('API_METHOD_GET', 'GET');
define('API_METHOD_POST', 'POST,PUT');
define('API_METHOD_DELETE', 'POST,DELETE');

define('API_LOG_PREFIX', 'API {action} - ');

$API = [];
$called_api = [];

/**
 * It is not sufficient to use local_user() to check whether someone is allowed to use the API,
 * because this will open CSRF holes (just embed an image with src=friendicasite.com/api/statuses/update?status=CSRF
 * into a page, and visitors will post something without noticing it).
 *
 * @brief Auth API user
 */
function api_user()
{
	if (!empty($_SESSION['allow_api'])) {
		return local_user();
	}

	return false;
}

/**
 * Clients can send 'source' parameter to be show in post metadata
 * as "sent via <source>".
 * Some clients doesn't send a source param, we support ones we know
 * (only Twidere, atm)
 *
 * @brief Get source name from API client
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
 * @brief Format date for API
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
 * @brief Register API endpoint
 *
 * @param string $path   API URL path, relative to System::baseUrl()
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
 * @brief Login API user
 *
 * @param App $a App
 * @throws InternalServerErrorException
 * @throws UnauthorizedException
 * @hook  'authenticate'
 *               array $addon_auth
 *               'username' => username from login form
 *               'password' => password from login form
 *               'authenticated' => return status,
 *               'user_record' => return authenticated user record
 * @hook  'logged_in'
 *               array $user    logged user record
 */
function api_login(App $a)
{
	$oauth1 = new FKOAuth1();
	// login with oauth
	try {
		$request = OAuthRequest::from_request();
		list($consumer, $token) = $oauth1->verify_request($request);
		if (!is_null($token)) {
			$oauth1->loginUser($token->uid);
			Hook::callAll('logged_in', $a->user);
			return;
		}
		echo __FILE__.__LINE__.__FUNCTION__ . "<pre>";
		var_dump($consumer, $token);
		die();
	} catch (Exception $e) {
		Logger::warning(API_LOG_PREFIX . 'error', ['module' => 'api', 'action' => 'login', 'exception' => $e->getMessage()]);
	}

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
		Logger::debug(API_LOG_PREFIX . 'failed', ['module' => 'api', 'action' => 'login', 'parameters' => $_SERVER]);
		header('WWW-Authenticate: Basic realm="Friendica"');
		throw new UnauthorizedException("This API requires login");
	}

	$user = defaults($_SERVER, 'PHP_AUTH_USER', '');
	$password = defaults($_SERVER, 'PHP_AUTH_PW', '');

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
		$user_id = User::authenticate(trim($user), trim($password));
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

	Authentication::setAuthenticatedSessionForUser($record);

	$_SESSION["allow_api"] = true;

	Hook::callAll('logged_in', $a->user);
}

/**
 * API endpoints can define which HTTP method to accept when called.
 * This function check the current HTTP method agains endpoint
 * registered method.
 *
 * @brief Check HTTP method of called API
 *
 * @param string $method Required methods, uppercase, separated by comma
 * @return bool
 */
function api_check_method($method)
{
	if ($method == "*") {
		return true;
	}
	return (stripos($method, defaults($_SERVER, 'REQUEST_METHOD', 'GET')) !== false);
}

/**
 * Authenticate user, call registered API function, set HTTP headers
 *
 * @brief Main API entry point
 *
 * @param App $a App
 * @return string|array API call result
 * @throws Exception
 */
function api_call(App $a)
{
	global $API, $called_api;

	$type = "json";
	if (strpos($a->query_string, ".xml") > 0) {
		$type = "xml";
	}
	if (strpos($a->query_string, ".json") > 0) {
		$type = "json";
	}
	if (strpos($a->query_string, ".rss") > 0) {
		$type = "rss";
	}
	if (strpos($a->query_string, ".atom") > 0) {
		$type = "atom";
	}

	try {
		foreach ($API as $p => $info) {
			if (strpos($a->query_string, $p) === 0) {
				if (!api_check_method($info['method'])) {
					throw new MethodNotAllowedException();
				}

				$called_api = explode("/", $p);
				//unset($_SERVER['PHP_AUTH_USER']);

				/// @TODO should be "true ==[=] $info['auth']", if you miss only one = character, you assign a variable (only with ==). Let's make all this even.
				if (!empty($info['auth']) && api_user() === false) {
					api_login($a);
				}

				Logger::info(API_LOG_PREFIX . 'username {username}', ['module' => 'api', 'action' => 'call', 'username' => $a->user['username']]);
				Logger::debug(API_LOG_PREFIX . 'parameters', ['module' => 'api', 'action' => 'call', 'parameters' => $_REQUEST]);

				$stamp =  microtime(true);
				$return = call_user_func($info['func'], $type);
				$duration = (float) (microtime(true) - $stamp);

				Logger::info(API_LOG_PREFIX . 'username {username}', ['module' => 'api', 'action' => 'call', 'username' => $a->user['username'], 'duration' => round($duration, 2)]);

				if (Config::get("system", "profiler")) {
					$duration = microtime(true)-$a->performance["start"];

					/// @TODO round() really everywhere?
					Logger::debug(
						API_LOG_PREFIX . 'performance',
						[
							'module' => 'api',
							'action' => 'call',
							'database_read' => round($a->performance["database"] - $a->performance["database_write"], 3),
							'database_write' => round($a->performance["database_write"], 3),
							'cache_read' => round($a->performance["cache"], 3),
							'cache_write' => round($a->performance["cache_write"], 3),
							'network_io' => round($a->performance["network"], 2),
							'file_io' => round($a->performance["file"], 2),
							'other_io' => round($duration - ($a->performance["database"]
									+ $a->performance["cache"] + $a->performance["cache_write"]
									+ $a->performance["network"] + $a->performance["file"]), 2),
							'total' => round($duration, 2)
						]
					);

					if (Config::get("rendertime", "callstack")) {
						$o = "Database Read:\n";
						foreach ($a->callstack["database"] as $func => $time) {
							$time = round($time, 3);
							if ($time > 0) {
								$o .= $func . ": " . $time . "\n";
							}
						}
						$o .= "\nDatabase Write:\n";
						foreach ($a->callstack["database_write"] as $func => $time) {
							$time = round($time, 3);
							if ($time > 0) {
								$o .= $func . ": " . $time . "\n";
							}
						}

						$o = "Cache Read:\n";
						foreach ($a->callstack["cache"] as $func => $time) {
							$time = round($time, 3);
							if ($time > 0) {
								$o .= $func . ": " . $time . "\n";
							}
						}
						$o .= "\nCache Write:\n";
						foreach ($a->callstack["cache_write"] as $func => $time) {
							$time = round($time, 3);
							if ($time > 0) {
								$o .= $func . ": " . $time . "\n";
							}
						}

						$o .= "\nNetwork:\n";
						foreach ($a->callstack["network"] as $func => $time) {
							$time = round($time, 3);
							if ($time > 0) {
								$o .= $func . ": " . $time . "\n";
							}
						}
						Logger::debug(API_LOG_PREFIX . $o, ['module' => 'api', 'action' => 'call']);
					}
				}

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
						$json = json_encode(end($return));
						if (!empty($_GET['callback'])) {
							$json = $_GET['callback'] . "(" . $json . ")";
						}
						$return = $json;
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

		Logger::warning(API_LOG_PREFIX . 'not implemented', ['module' => 'api', 'action' => 'call']);
		throw new NotImplementedException();
	} catch (HTTPException $e) {
		header("HTTP/1.1 {$e->httpcode} {$e->httpdesc}");
		return api_error($type, $e);
	}
}

/**
 * @brief Format API error string
 *
 * @param string $type Return type (xml, json, rss, as)
 * @param object $e    HTTPException Error object
 * @return string|array error message formatted as $type
 */
function api_error($type, $e)
{
	$a = \get_app();

	$error = ($e->getMessage() !== "" ? $e->getMessage() : $e->httpdesc);
	/// @TODO:  https://dev.twitter.com/overview/api/response-codes

	$error = ["error" => $error,
			"code" => $e->httpcode . " " . $e->httpdesc,
			"request" => $a->query_string];

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
 * @brief Set values for RSS template
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
		'self'         => System::baseUrl() . "/" . $a->query_string,
		'base'         => System::baseUrl(),
		'updated'      => api_date(null),
		'atom_updated' => DateTimeFormat::utcNow(DateTimeFormat::ATOM),
		'language'     => $user_info['lang'],
		'logo'         => System::baseUrl() . "/images/friendica-32.png",
	];

	return $arr;
}


/**
 * @brief Unique contact to contact url.
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
 * @brief Get user info array.
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

	$stmt = @vsprintf("SELECT *, `contact`.`id` AS `cid` FROM `contact` WHERE 1 $extra_query", $user);

	// user info
	$uinfo = DBA::p($stmt);

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
			// If no nick where given, extract it from the address
			if (($contact['nick'] == "") || ($contact['name'] == $contact['nick'])) {
				$contact['nick'] = api_get_nick($contact["url"]);
			}

			$ret = [
				'id' => $contact["id"],
				'id_str' => (string) $contact["id"],
				'name' => $contact["name"],
				'screen_name' => (($contact['nick']) ? $contact['nick'] : $contact['name']),
				'location' => ($contact["location"] != "") ? $contact["location"] : ContactSelector::networkToName($contact['network'], $contact['url']),
				'description' => $contact["about"],
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
				'cid' => Contact::getIdForURL($contact["url"], api_user(), true),
				'pid' => Contact::getIdForURL($contact["url"], 0, true),
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

	// Add a nick if it isn't present there
	if (($uinfo[0]['nick'] == "") || ($uinfo[0]['name'] == $uinfo[0]['nick'])) {
		$uinfo[0]['nick'] = api_get_nick($uinfo[0]["url"]);
	}

	$pcontact_id  = Contact::getIdForURL($uinfo[0]['url'], 0, true);

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
		$location = ContactSelector::networkToName($uinfo[0]['network'], $uinfo[0]['url']);
	}

	$ret = [
		'id' => intval($pcontact_id),
		'id_str' => (string) intval($pcontact_id),
		'name' => (($uinfo[0]['name']) ? $uinfo[0]['name'] : $uinfo[0]['nick']),
		'screen_name' => (($uinfo[0]['nick']) ? $uinfo[0]['nick'] : $uinfo[0]['name']),
		'location' => $location,
		'description' => $description,
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
		//'statusnet_profile_url' => System::baseUrl()."/contact/".$uinfo[0]['cid'],
		'statusnet_profile_url' => $uinfo[0]['url'],
		'uid' => intval($uinfo[0]['uid']),
		'cid' => intval($uinfo[0]['cid']),
		'pid' => Contact::getIdForURL($uinfo[0]["url"], 0, true),
		'self' => $uinfo[0]['self'],
		'network' => $uinfo[0]['network'],
	];

	// If this is a local user and it uses Frio, we can get its color preferences.
	if ($ret['self']) {
		$theme_info = DBA::selectFirst('user', ['theme'], ['uid' => $ret['uid']]);
		if ($theme_info['theme'] === 'frio') {
			$schema = PConfig::get($ret['uid'], 'frio', 'schema');

			if ($schema && ($schema != '---')) {
				if (file_exists('view/theme/frio/schema/'.$schema.'.php')) {
					$schemefile = 'view/theme/frio/schema/'.$schema.'.php';
					require_once $schemefile;
				}
			} else {
				$nav_bg = PConfig::get($ret['uid'], 'frio', 'nav_bg');
				$link_color = PConfig::get($ret['uid'], 'frio', 'link_color');
				$bgcolor = PConfig::get($ret['uid'], 'frio', 'background_color');
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
 * @brief return api-formatted array for item's author and owner
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
	$status_user = api_get_user($a, defaults($item, 'author-id', null));

	$status_user["protected"] = defaults($item, 'private', 0);

	if (defaults($item, 'thr-parent', '') == defaults($item, 'uri', '')) {
		$owner_user = api_get_user($a, defaults($item, 'owner-id', null));
	} else {
		$owner_user = $status_user;
	}

	return ([$status_user, $owner_user]);
}

/**
 * @brief walks recursively through an array with the possibility to change value and key
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
 * @brief Callback function to transform the array in an array that can be transformed in a XML file
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
 * @brief Creates the XML from a JSON style array
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
 * @brief Formats the data according to the data type
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

	$a = \get_app();

	if (api_user() === false) {
		throw new ForbiddenException();
	}

	unset($_REQUEST["user_id"]);
	unset($_GET["user_id"]);

	unset($_REQUEST["screen_name"]);
	unset($_GET["screen_name"]);

	$skip_status = defaults($_REQUEST, 'skip_status', false);

	$user_info = api_get_user($a);

	// "verified" isn't used here in the standard
	unset($user_info["verified"]);

	// - Adding last status
	if (!$skip_status) {
		$user_info["status"] = api_status_show("raw");
		if (!count($user_info["status"])) {
			unset($user_info["status"]);
		} else {
			unset($user_info["status"]["user"]);
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
	$a = \get_app();

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
	$a = \get_app();

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
		$throttle_day = Config::get('system', 'throttle_limit_day');
		if ($throttle_day > 0) {
			$datefrom = date(DateTimeFormat::MYSQL, time() - 24*60*60);

			$condition = ["`uid` = ? AND `wall` AND `created` > ?", api_user(), $datefrom];
			$posts_day = DBA::count('thread', $condition);

			if ($posts_day > $throttle_day) {
				Logger::log('Daily posting limit reached for user '.api_user(), Logger::DEBUG);
				// die(api_error($type, L10n::t("Daily posting limit of %d posts reached. The post was rejected.", $throttle_day));
				throw new TooManyRequestsException(L10n::tt("Daily posting limit of %d post reached. The post was rejected.", "Daily posting limit of %d posts reached. The post was rejected.", $throttle_day));
			}
		}

		$throttle_week = Config::get('system', 'throttle_limit_week');
		if ($throttle_week > 0) {
			$datefrom = date(DateTimeFormat::MYSQL, time() - 24*60*60*7);

			$condition = ["`uid` = ? AND `wall` AND `created` > ?", api_user(), $datefrom];
			$posts_week = DBA::count('thread', $condition);

			if ($posts_week > $throttle_week) {
				Logger::log('Weekly posting limit reached for user '.api_user(), Logger::DEBUG);
				// die(api_error($type, L10n::t("Weekly posting limit of %d posts reached. The post was rejected.", $throttle_week)));
				throw new TooManyRequestsException(L10n::tt("Weekly posting limit of %d post reached. The post was rejected.", "Weekly posting limit of %d posts reached. The post was rejected.", $throttle_week));
			}
		}

		$throttle_month = Config::get('system', 'throttle_limit_month');
		if ($throttle_month > 0) {
			$datefrom = date(DateTimeFormat::MYSQL, time() - 24*60*60*30);

			$condition = ["`uid` = ? AND `wall` AND `created` > ?", api_user(), $datefrom];
			$posts_month = DBA::count('thread', $condition);

			if ($posts_month > $throttle_month) {
				Logger::log('Monthly posting limit reached for user '.api_user(), Logger::DEBUG);
				// die(api_error($type, L10n::t("Monthly posting limit of %d posts reached. The post was rejected.", $throttle_month));
				throw new TooManyRequestsException(L10n::t("Monthly posting limit of %d post reached. The post was rejected.", "Monthly posting limit of %d posts reached. The post was rejected.", $throttle_month));
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

	// To-Do: Multiple IDs
	if (requestdata('media_ids')) {
		$r = q(
			"SELECT `resource-id`, `scale`, `nickname`, `type` FROM `photo` INNER JOIN `user` ON `user`.`uid` = `photo`.`uid` WHERE `resource-id` IN (SELECT `resource-id` FROM `photo` WHERE `id` = %d) AND `scale` > 0 AND `photo`.`uid` = %d ORDER BY `photo`.`width` DESC LIMIT 1",
			intval(requestdata('media_ids')),
			api_user()
		);
		if (DBA::isResult($r)) {
			$phototypes = Image::supportedTypes();
			$ext = $phototypes[$r[0]['type']];
			$_REQUEST['body'] .= "\n\n" . '[url=' . System::baseUrl() . '/photos/' . $r[0]['nickname'] . '/image/' . $r[0]['resource-id'] . ']';
			$_REQUEST['body'] .= '[img]' . System::baseUrl() . '/photo/' . $r[0]['resource-id'] . '-' . $r[0]['scale'] . '.' . $ext . '[/img][/url]';
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
	$a = \get_app();

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

	Logger::log("Media uploaded: " . print_r($returndata, true), Logger::DEBUG);

	return ["media" => $returndata];
}

/// @TODO move to top of file or somewhere better
api_register_func('api/media/upload', 'api_media_upload', true, API_METHOD_POST);

/**
 *
 * @param string $type Return type (atom, rss, xml, json)
 *
 * @param int    $item_id
 * @return array|string
 * @throws BadRequestException
 * @throws ImagickException
 * @throws InternalServerErrorException
 * @throws UnauthorizedException
 */
function api_status_show($type, $item_id = 0)
{
	$a = \get_app();

	$user_info = api_get_user($a);

	Logger::log('api_status_show: user_info: '.print_r($user_info, true), Logger::DEBUG);

	if (!empty($item_id)) {
		// Get the item with the given id
		$condition = ['id' => $item_id];
	} else {
		// get last public wall message
		$condition = ['owner-id' => $user_info['pid'], 'uid' => api_user(),
			'gravity' => [GRAVITY_PARENT, GRAVITY_COMMENT]];
	}

	if ($type == "raw") {
		$condition['private'] = false;
	}

	$lastwall = Item::selectFirst(Item::ITEM_FIELDLIST, $condition, ['order' => ['id' => true]]);

	if (DBA::isResult($lastwall)) {
		$in_reply_to = api_in_reply_to($lastwall);

		$converted = api_convert_item($lastwall);

		if ($type == "xml") {
			$geo = "georss:point";
		} else {
			$geo = "geo";
		}

		$status_info = [
			'created_at' => api_date($lastwall['created']),
			'id' => intval($lastwall['id']),
			'id_str' => (string) $lastwall['id'],
			'text' => $converted["text"],
			'source' => (($lastwall['app']) ? $lastwall['app'] : 'web'),
			'truncated' => false,
			'in_reply_to_status_id' => $in_reply_to['status_id'],
			'in_reply_to_status_id_str' => $in_reply_to['status_id_str'],
			'in_reply_to_user_id' => $in_reply_to['user_id'],
			'in_reply_to_user_id_str' => $in_reply_to['user_id_str'],
			'in_reply_to_screen_name' => $in_reply_to['screen_name'],
			'user' => $user_info,
			$geo => null,
			'coordinates' => '',
			'place' => '',
			'contributors' => '',
			'is_quote_status' => false,
			'retweet_count' => 0,
			'favorite_count' => 0,
			'favorited' => $lastwall['starred'] ? true : false,
			'retweeted' => false,
			'possibly_sensitive' => false,
			'lang' => '',
			'statusnet_html' => $converted["html"],
			'statusnet_conversation_id' => $lastwall['parent'],
			'external_url' => System::baseUrl() . '/display/' . $lastwall['guid'],
		];

		if (count($converted["attachments"]) > 0) {
			$status_info["attachments"] = $converted["attachments"];
		}

		if (count($converted["entities"]) > 0) {
			$status_info["entities"] = $converted["entities"];
		}

		if ($status_info["source"] == 'web') {
			$status_info["source"] = ContactSelector::networkToName($lastwall['network'], $lastwall['author-link']);
		} elseif (ContactSelector::networkToName($lastwall['network'], $lastwall['author-link']) != $status_info["source"]) {
			$status_info["source"] = trim($status_info["source"].' ('.ContactSelector::networkToName($lastwall['network'], $lastwall['author-link']).')');
		}

		// "uid" and "self" are only needed for some internal stuff, so remove it from here
		unset($status_info["user"]["uid"]);
		unset($status_info["user"]["self"]);

		Logger::log('status_info: '.print_r($status_info, true), Logger::DEBUG);

		if ($type == "raw") {
			return $status_info;
		}

		return api_format_data("statuses", $type, ['status' => $status_info]);
	}
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
	$a = \get_app();

	$user_info = api_get_user($a);

	$condition = ['owner-id' => $user_info['pid'], 'uid' => api_user(),
		'gravity' => [GRAVITY_PARENT, GRAVITY_COMMENT], 'private' => false];
	$lastwall = Item::selectFirst(Item::ITEM_FIELDLIST, $condition, ['order' => ['id' => true]]);

	if (DBA::isResult($lastwall)) {
		$in_reply_to = api_in_reply_to($lastwall);

		$converted = api_convert_item($lastwall);

		if ($type == "xml") {
			$geo = "georss:point";
		} else {
			$geo = "geo";
		}

		$user_info['status'] = [
			'text' => $converted["text"],
			'truncated' => false,
			'created_at' => api_date($lastwall['created']),
			'in_reply_to_status_id' => $in_reply_to['status_id'],
			'in_reply_to_status_id_str' => $in_reply_to['status_id_str'],
			'source' => (($lastwall['app']) ? $lastwall['app'] : 'web'),
			'id' => intval($lastwall['contact-id']),
			'id_str' => (string) $lastwall['contact-id'],
			'in_reply_to_user_id' => $in_reply_to['user_id'],
			'in_reply_to_user_id_str' => $in_reply_to['user_id_str'],
			'in_reply_to_screen_name' => $in_reply_to['screen_name'],
			$geo => null,
			'favorited' => $lastwall['starred'] ? true : false,
			'statusnet_html' => $converted["html"],
			'statusnet_conversation_id' => $lastwall['parent'],
			'external_url' => System::baseUrl() . "/display/" . $lastwall['guid'],
		];

		if (count($converted["attachments"]) > 0) {
			$user_info["status"]["attachments"] = $converted["attachments"];
		}

		if (count($converted["entities"]) > 0) {
			$user_info["status"]["entities"] = $converted["entities"];
		}

		if ($user_info["status"]["source"] == 'web') {
			$user_info["status"]["source"] = ContactSelector::networkToName($lastwall['network'], $lastwall['author-link']);
		}

		if (ContactSelector::networkToName($lastwall['network'], $user_info['url']) != $user_info["status"]["source"]) {
			$user_info["status"]["source"] = trim($user_info["status"]["source"] . ' (' . ContactSelector::networkToName($lastwall['network'], $lastwall['author-link']) . ')');
		}
	}

	// "uid" and "self" are only needed for some internal stuff, so remove it from here
	unset($user_info["uid"]);
	unset($user_info["self"]);

	return api_format_data("user", $type, ['user' => $user_info]);
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
	$a = \get_app();

	$userlist = [];

	if (!empty($_GET['q'])) {
		$r = q("SELECT id FROM `contact` WHERE `uid` = 0 AND `name` = '%s'", DBA::escape($_GET["q"]));

		if (!DBA::isResult($r)) {
			$r = q("SELECT `id` FROM `contact` WHERE `uid` = 0 AND `nick` = '%s'", DBA::escape($_GET["q"]));
		}

		if (DBA::isResult($r)) {
			$k = 0;
			foreach ($r as $user) {
				$user_info = api_get_user($a, $user["id"]);

				if ($type == "xml") {
					$userlist[$k++.":user"] = $user_info;
				} else {
					$userlist[] = $user_info;
				}
			}
			$userlist = ["users" => $userlist];
		} else {
			throw new BadRequestException("User ".$_GET["q"]." not found.");
		}
	} else {
		throw new BadRequestException("No user specified.");
	}

	return api_format_data("users", $type, $userlist);
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
				$users[] = api_get_user(get_app(), $id);
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
	$a = \get_app();
	$user_info = api_get_user($a);

	if (api_user() === false || $user_info === false) { throw new ForbiddenException(); }

	if (empty($_REQUEST['q'])) { throw new BadRequestException('q parameter is required.'); }
	
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
	
	$since_id = defaults($_REQUEST, 'since_id', 0);
	$max_id = defaults($_REQUEST, 'max_id', 0);
	$page = (!empty($_REQUEST['page']) ? $_REQUEST['page'] - 1 : 0);
	$start = $page * $count;
	$params = ['order' => ['id' => true], 'limit' => [$start, $count]];
	if (preg_match('/^#(\w+)$/', $searchTerm, $matches) === 1 && isset($matches[1])) {
		$searchTerm = $matches[1];
		$condition = ["`oid` > ?
			AND (`uid` = 0 OR (`uid` = ? AND NOT `global`)) 
			AND `otype` = ? AND `type` = ? AND `term` = ?",
			$since_id, local_user(), TERM_OBJ_POST, TERM_HASHTAG, $searchTerm];
		if ($max_id > 0) {
			$condition[0] .= ' AND `oid` <= ?';
			$condition[] = $max_id;
		}
		$terms = DBA::select('term', ['oid'], $condition, []);
		$itemIds = [];
		while ($term = DBA::fetch($terms)) {
			$itemIds[] = $term['oid'];
		}
		DBA::close($terms);

		if (empty($itemIds)) {
			return api_format_data('statuses', $type, $data);
		}

		$preCondition = ['`id` IN (' . implode(', ', $itemIds) . ')'];
		if ($exclude_replies) {
			$preCondition[] = '`id` = `parent`';
		}

		$condition = [implode(' AND ', $preCondition)];
	} else {
		$condition = ["`id` > ? 
			" . ($exclude_replies ? " AND `id` = `parent` " : ' ') . "
			AND (`uid` = 0 OR (`uid` = ? AND NOT `global`))
			AND `body` LIKE CONCAT('%',?,'%')",
			$since_id, api_user(), $_REQUEST['q']];
		if ($max_id > 0) {
			$condition[0] .= ' AND `id` <= ?';
			$condition[] = $max_id;
		}
	}

	$statuses = Item::selectForUser(api_user(), [], $condition, $params);

	$data['status'] = api_format_items(Item::inArray($statuses), $user_info);

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
	$a = \get_app();
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
	$count = defaults($_REQUEST, 'count', 20);
	$page = (!empty($_REQUEST['page']) ? $_REQUEST['page'] - 1 : 0);
	if ($page < 0) {
		$page = 0;
	}
	$since_id = defaults($_REQUEST, 'since_id', 0);
	$max_id = defaults($_REQUEST, 'max_id', 0);
	$exclude_replies = !empty($_REQUEST['exclude_replies']);
	$conversation_id = defaults($_REQUEST, 'conversation_id', 0);

	$start = $page * $count;

	$condition = ["`uid` = ? AND `gravity` IN (?, ?) AND `item`.`id` > ?",
		api_user(), GRAVITY_PARENT, GRAVITY_COMMENT, $since_id];

	if ($max_id > 0) {
		$condition[0] .= " AND `item`.`id` <= ?";
		$condition[] = $max_id;
	}
	if ($exclude_replies) {
		$condition[0] .= ' AND `item`.`parent` = `item`.`id`';
	}
	if ($conversation_id > 0) {
		$condition[0] .= " AND `item`.`parent` = ?";
		$condition[] = $conversation_id;
	}

	$params = ['order' => ['id' => true], 'limit' => [$start, $count]];
	$statuses = Item::selectForUser(api_user(), [], $condition, $params);

	$items = Item::inArray($statuses);

	$ret = api_format_items($items, $user_info, false, $type);

	// Set all posts from the query above to seen
	$idarray = [];
	foreach ($items as $item) {
		$idarray[] = intval($item["id"]);
	}

	if (!empty($idarray)) {
		$unseen = Item::exists(['unseen' => true, 'id' => $idarray]);
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
	$a = \get_app();
	$user_info = api_get_user($a);

	if (api_user() === false || $user_info === false) {
		throw new ForbiddenException();
	}

	// get last network messages

	// params
	$count = defaults($_REQUEST, 'count', 20);
	$page = (!empty($_REQUEST['page']) ? $_REQUEST['page'] -1 : 0);
	if ($page < 0) {
		$page = 0;
	}
	$since_id = defaults($_REQUEST, 'since_id', 0);
	$max_id = defaults($_REQUEST, 'max_id', 0);
	$exclude_replies = (!empty($_REQUEST['exclude_replies']) ? 1 : 0);
	$conversation_id = defaults($_REQUEST, 'conversation_id', 0);

	$start = $page * $count;

	if ($exclude_replies && !$conversation_id) {
		$condition = ["`gravity` IN (?, ?) AND `iid` > ? AND NOT `private` AND `wall` AND NOT `user`.`hidewall`",
			GRAVITY_PARENT, GRAVITY_COMMENT, $since_id];

		if ($max_id > 0) {
			$condition[0] .= " AND `thread`.`iid` <= ?";
			$condition[] = $max_id;
		}

		$params = ['order' => ['iid' => true], 'limit' => [$start, $count]];
		$statuses = Item::selectThreadForUser(api_user(), Item::DISPLAY_FIELDLIST, $condition, $params);

		$r = Item::inArray($statuses);
	} else {
		$condition = ["`gravity` IN (?, ?) AND `id` > ? AND NOT `private` AND `wall` AND NOT `user`.`hidewall` AND `item`.`origin`",
			GRAVITY_PARENT, GRAVITY_COMMENT, $since_id];

		if ($max_id > 0) {
			$condition[0] .= " AND `item`.`id` <= ?";
			$condition[] = $max_id;
		}
		if ($conversation_id > 0) {
			$condition[0] .= " AND `item`.`parent` = ?";
			$condition[] = $conversation_id;
		}

		$params = ['order' => ['id' => true], 'limit' => [$start, $count]];
		$statuses = Item::selectForUser(api_user(), [], $condition, $params);

		$r = Item::inArray($statuses);
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
 * @brief Returns the list of public federated posts this node knows about
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
	$a = \get_app();
	$user_info = api_get_user($a);

	if (api_user() === false || $user_info === false) {
		throw new ForbiddenException();
	}

	$since_id        = defaults($_REQUEST, 'since_id', 0);
	$max_id          = defaults($_REQUEST, 'max_id', 0);

	// pagination
	$count = defaults($_REQUEST, 'count', 20);
	$page  = defaults($_REQUEST, 'page', 1);
	if ($page < 1) {
		$page = 1;
	}
	$start = ($page - 1) * $count;

	$condition = ["`uid` = 0 AND `gravity` IN (?, ?) AND `thread`.`iid` > ? AND NOT `private`",
		GRAVITY_PARENT, GRAVITY_COMMENT, $since_id];

	if ($max_id > 0) {
		$condition[0] .= " AND `thread`.`iid` <= ?";
		$condition[] = $max_id;
	}

	$params = ['order' => ['iid' => true], 'limit' => [$start, $count]];
	$statuses = Item::selectThreadForUser(api_user(), Item::DISPLAY_FIELDLIST, $condition, $params);

	$ret = api_format_items(Item::inArray($statuses), $user_info, false, $type);

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
	$a = \get_app();
	$user_info = api_get_user($a);

	if (api_user() === false || $user_info === false) {
		throw new ForbiddenException();
	}

	// params
	$id = intval(defaults($a->argv, 3, 0));

	if ($id == 0) {
		$id = intval(defaults($_REQUEST, 'id', 0));
	}

	// Hotot workaround
	if ($id == 0) {
		$id = intval(defaults($a->argv, 4, 0));
	}

	Logger::log('API: api_statuses_show: ' . $id);

	$conversation = !empty($_REQUEST['conversation']);

	// try to fetch the item for the local user - or the public item, if there is no local one
	$uri_item = Item::selectFirst(['uri'], ['id' => $id]);
	if (!DBA::isResult($uri_item)) {
		throw new BadRequestException("There is no status with this id.");
	}

	$item = Item::selectFirst(['id'], ['uri' => $uri_item['uri'], 'uid' => [0, api_user()]], ['order' => ['uid' => true]]);
	if (!DBA::isResult($item)) {
		throw new BadRequestException("There is no status with this id.");
	}

	$id = $item['id'];

	if ($conversation) {
		$condition = ['parent' => $id, 'gravity' => [GRAVITY_PARENT, GRAVITY_COMMENT]];
		$params = ['order' => ['id' => true]];
	} else {
		$condition = ['id' => $id, 'gravity' => [GRAVITY_PARENT, GRAVITY_COMMENT]];
		$params = [];
	}

	$statuses = Item::selectForUser(api_user(), [], $condition, $params);

	/// @TODO How about copying this to above methods which don't check $r ?
	if (!DBA::isResult($statuses)) {
		throw new BadRequestException("There is no status with this id.");
	}

	$ret = api_format_items(Item::inArray($statuses), $user_info, false, $type);

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
	$a = \get_app();
	$user_info = api_get_user($a);

	if (api_user() === false || $user_info === false) {
		throw new ForbiddenException();
	}

	// params
	$id       = intval(defaults($a->argv , 3         , 0));
	$since_id = intval(defaults($_REQUEST, 'since_id', 0));
	$max_id   = intval(defaults($_REQUEST, 'max_id'  , 0));
	$count    = intval(defaults($_REQUEST, 'count'   , 20));
	$page     = intval(defaults($_REQUEST, 'page'    , 1)) - 1;
	if ($page < 0) {
		$page = 0;
	}

	$start = $page * $count;

	if ($id == 0) {
		$id = intval(defaults($_REQUEST, 'id', 0));
	}

	// Hotot workaround
	if ($id == 0) {
		$id = intval(defaults($a->argv, 4, 0));
	}

	Logger::info(API_LOG_PREFIX . '{subaction}', ['module' => 'api', 'action' => 'conversation', 'subaction' => 'show', 'id' => $id]);

	// try to fetch the item for the local user - or the public item, if there is no local one
	$item = Item::selectFirst(['parent-uri'], ['id' => $id]);
	if (!DBA::isResult($item)) {
		throw new BadRequestException("There is no status with this id.");
	}

	$parent = Item::selectFirst(['id'], ['uri' => $item['parent-uri'], 'uid' => [0, api_user()]], ['order' => ['uid' => true]]);
	if (!DBA::isResult($parent)) {
		throw new BadRequestException("There is no status with this id.");
	}

	$id = $parent['id'];

	$condition = ["`parent` = ? AND `uid` IN (0, ?) AND `gravity` IN (?, ?) AND `item`.`id` > ?",
		$id, api_user(), GRAVITY_PARENT, GRAVITY_COMMENT, $since_id];

	if ($max_id > 0) {
		$condition[0] .= " AND `item`.`id` <= ?";
		$condition[] = $max_id;
	}

	$params = ['order' => ['id' => true], 'limit' => [$start, $count]];
	$statuses = Item::selectForUser(api_user(), [], $condition, $params);

	if (!DBA::isResult($statuses)) {
		throw new BadRequestException("There is no status with id $id.");
	}

	$ret = api_format_items(Item::inArray($statuses), $user_info, false, $type);

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

	$a = \get_app();

	if (api_user() === false) {
		throw new ForbiddenException();
	}

	api_get_user($a);

	// params
	$id = intval(defaults($a->argv, 3, 0));

	if ($id == 0) {
		$id = intval(defaults($_REQUEST, 'id', 0));
	}

	// Hotot workaround
	if ($id == 0) {
		$id = intval(defaults($a->argv, 4, 0));
	}

	Logger::log('API: api_statuses_repeat: '.$id);

	$fields = ['body', 'author-name', 'author-link', 'author-avatar', 'guid', 'created', 'plink'];
	$item = Item::selectFirst($fields, ['id' => $id, 'private' => false]);

	if (DBA::isResult($item) && $item['body'] != "") {
		if (strpos($item['body'], "[/share]") !== false) {
			$pos = strpos($item['body'], "[share");
			$post = substr($item['body'], $pos);
		} else {
			$post = share_header($item['author-name'], $item['author-link'], $item['author-avatar'], $item['guid'], $item['created'], $item['plink']);

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
	$a = \get_app();

	if (api_user() === false) {
		throw new ForbiddenException();
	}

	api_get_user($a);

	// params
	$id = intval(defaults($a->argv, 3, 0));

	if ($id == 0) {
		$id = intval(defaults($_REQUEST, 'id', 0));
	}

	// Hotot workaround
	if ($id == 0) {
		$id = intval(defaults($a->argv, 4, 0));
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
	$a = \get_app();
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
	$since_id = defaults($_REQUEST, 'since_id', 0);
	$max_id   = defaults($_REQUEST, 'max_id'  , 0);
	$count    = defaults($_REQUEST, 'count'   , 20);
	$page     = defaults($_REQUEST, 'page'    , 1);
	if ($page < 1) {
		$page = 1;
	}

	$start = ($page - 1) * $count;

	$condition = ["`uid` = ? AND `gravity` IN (?, ?) AND `item`.`id` > ? AND `author-id` != ?
		AND `item`.`parent` IN (SELECT `iid` FROM `thread` WHERE `thread`.`uid` = ? AND `thread`.`mention` AND NOT `thread`.`ignored`)",
		api_user(), GRAVITY_PARENT, GRAVITY_COMMENT, $since_id, $user_info['pid'], api_user()];

	if ($max_id > 0) {
		$condition[0] .= " AND `item`.`id` <= ?";
		$condition[] = $max_id;
	}

	$params = ['order' => ['id' => true], 'limit' => [$start, $count]];
	$statuses = Item::selectForUser(api_user(), [], $condition, $params);

	$ret = api_format_items(Item::inArray($statuses), $user_info, false, $type);

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
 * @brief Returns a user's public timeline
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
	$a = \get_app();
	$user_info = api_get_user($a);

	if (api_user() === false || $user_info === false) {
		throw new ForbiddenException();
	}

	Logger::log(
		"api_statuses_user_timeline: api_user: ". api_user() .
			"\nuser_info: ".print_r($user_info, true) .
			"\n_REQUEST:  ".print_r($_REQUEST, true),
		Logger::DEBUG
	);

	$since_id        = defaults($_REQUEST, 'since_id', 0);
	$max_id          = defaults($_REQUEST, 'max_id', 0);
	$exclude_replies = !empty($_REQUEST['exclude_replies']);
	$conversation_id = defaults($_REQUEST, 'conversation_id', 0);

	// pagination
	$count = defaults($_REQUEST, 'count', 20);
	$page  = defaults($_REQUEST, 'page', 1);
	if ($page < 1) {
		$page = 1;
	}
	$start = ($page - 1) * $count;

	$condition = ["`uid` = ? AND `gravity` IN (?, ?) AND `item`.`id` > ? AND `item`.`contact-id` = ?",
		api_user(), GRAVITY_PARENT, GRAVITY_COMMENT, $since_id, $user_info['cid']];

	if ($user_info['self'] == 1) {
		$condition[0] .= ' AND `item`.`wall` ';
	}

	if ($exclude_replies) {
		$condition[0] .= ' AND `item`.`parent` = `item`.`id`';
	}

	if ($conversation_id > 0) {
		$condition[0] .= " AND `item`.`parent` = ?";
		$condition[] = $conversation_id;
	}

	if ($max_id > 0) {
		$condition[0] .= " AND `item`.`id` <= ?";
		$condition[] = $max_id;
	}

	$params = ['order' => ['id' => true], 'limit' => [$start, $count]];
	$statuses = Item::selectForUser(api_user(), [], $condition, $params);

	$ret = api_format_items(Item::inArray($statuses), $user_info, true, $type);

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
	$a = \get_app();

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
		$itemid = intval(defaults($a->argv, $action_argv_id + 1, 0));
	} else {
		$itemid = intval(defaults($_REQUEST, 'id', 0));
	}

	$item = Item::selectFirstForUser(api_user(), [], ['id' => $itemid, 'uid' => api_user()]);

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

	$a = \get_app();
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
		$since_id = defaults($_REQUEST, 'since_id', 0);
		$max_id = defaults($_REQUEST, 'max_id', 0);
		$count = defaults($_GET, 'count', 20);
		$page = (!empty($_REQUEST['page']) ? $_REQUEST['page'] -1 : 0);
		if ($page < 0) {
			$page = 0;
		}

		$start = $page*$count;

		$condition = ["`uid` = ? AND `gravity` IN (?, ?) AND `id` > ? AND `starred`",
			api_user(), GRAVITY_PARENT, GRAVITY_COMMENT, $since_id];

		$params = ['order' => ['id' => true], 'limit' => [$start, $count]];

		if ($max_id > 0) {
			$condition[0] .= " AND `item`.`id` <= ?";
			$condition[] = $max_id;
		}

		$statuses = Item::selectForUser(api_user(), [], $condition, $params);

		$ret = api_format_items(Item::inArray($statuses), $user_info, false, $type);
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
		'created_at'            => api_date(defaults($item, 'created', DateTimeFormat::utcNow())),
		'sender_screen_name'    => $sender['screen_name'],
		'recipient_screen_name' => $recipient['screen_name'],
		'sender'                => $sender,
		'recipient'             => $recipient,
		'title'                 => "",
		'friendica_seen'        => defaults($item, 'seen', 0),
		'friendica_parent_uri'  => defaults($item, 'parent-uri', ''),
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
			$ret['text'] = trim(HTML::toPlaintext(BBCode::convert(api_clean_plain_items($item['body']), false, 2, true), 0));
		}
	} else {
		$ret['text'] = $item['title'] . "\n" . HTML::toPlaintext(BBCode::convert(api_clean_plain_items($item['body']), false, 2, true), 0);
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
	$attachments = api_get_attachments($body);

	// Workaround for ostatus messages where the title is identically to the body
	$html = BBCode::convert(api_clean_plain_items($body), false, 2, true);
	$statusbody = trim(HTML::toPlaintext($html, 0));

	// handle data: images
	$statusbody = api_format_items_embeded_images($item, $statusbody);

	$statustitle = trim($item['title']);

	if (($statustitle != '') && (strpos($statusbody, $statustitle) !== false)) {
		$statustext = trim($statusbody);
	} else {
		$statustext = trim($statustitle."\n\n".$statusbody);
	}

	if ((defaults($item, 'network', Protocol::PHANTOM) == Protocol::FEED) && (strlen($statustext)> 1000)) {
		$statustext = substr($statustext, 0, 1000) . "... \n" . defaults($item, 'plink', '');
	}

	$statushtml = BBCode::convert(api_clean_attachments($body), false);

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
	if ((defaults($item, 'network', Protocol::PHANTOM) == Protocol::FEED) && (strlen($item['body']) == 0)) {
		$statushtml .= BBCode::convert($item['plink']);
	}

	$entities = api_get_entitities($statustext, $body);

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
	$text = $body;
	$text = preg_replace("/\[img\=([0-9]*)x([0-9]*)\](.*?)\[\/img\]/ism", '[img]$3[/img]', $text);

	$URLSearchString = "^\[\]";
	$ret = preg_match_all("/\[img\]([$URLSearchString]*)\[\/img\]/ism", $text, $images);

	if (!$ret) {
		return [];
	}

	$attachments = [];

	foreach ($images[1] as $image) {
		$imagedata = Image::getInfoFromURL($image);

		if ($imagedata) {
			$attachments[] = ["url" => $image, "mimetype" => $imagedata["mime"], "size" => $imagedata["size"]];
		}
	}

	if (strstr(defaults($_SERVER, 'HTTP_USER_AGENT', ''), "AndStatus")) {
		foreach ($images[0] as $orig) {
			$body = str_replace($orig, "", $body);
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
	$include_entities = strtolower(defaults($_REQUEST, 'include_entities', "false"));

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
	//$bbcode = preg_replace("/\[url\](.*?)\[\/url\]/ism",'[url=$1]$1[/url]',$bbcode);
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

	//preg_match_all("/\[url\]([$URLSearchString]*)\[\/url\]/ism", $bbcode, $urls1);
	preg_match_all("/\[url\=([$URLSearchString]*)\](.*?)\[\/url\]/ism", $bbcode, $urls);

	$ordered_urls = [];
	foreach ($urls[1] as $id => $url) {
		//$start = strpos($text, $url, $offset);
		$start = iconv_strpos($text, $url, 0, "UTF-8");
		if (!($start === false)) {
			$ordered_urls[$start] = ["url" => $url, "title" => $urls[2][$id]];
		}
	}

	ksort($ordered_urls);

	$offset = 0;
	//foreach ($urls[1] AS $id=>$url) {
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

		//$start = strpos($text, $url, $offset);
		$start = iconv_strpos($text, $url["url"], $offset, "UTF-8");
		if (!($start === false)) {
			$entities["urls"][] = ["url" => $url["url"],
							"expanded_url" => $url["url"],
							"display_url" => $display_url,
							"indices" => [$start, $start+strlen($url["url"])]];
			$offset = $start + 1;
		}
	}

	preg_match_all("/\[img](.*?)\[\/img\]/ism", $bbcode, $images);
	$ordered_images = [];
	foreach ($images[1] as $image) {
		//$start = strpos($text, $url, $offset);
		$start = iconv_strpos($text, $image, 0, "UTF-8");
		if (!($start === false)) {
			$ordered_images[$start] = $image;
		}
	}
	//$entities["media"] = array();
	$offset = 0;

	foreach ($ordered_images as $url) {
		$display_url = str_replace(["http://www.", "https://www."], ["", ""], $url);
		$display_url = str_replace(["http://", "https://"], ["", ""], $display_url);

		if (strlen($display_url) > 26) {
			$display_url = substr($display_url, 0, 25)."…";
		}

		$start = iconv_strpos($text, $url, $offset, "UTF-8");
		if (!($start === false)) {
			$image = Image::getInfoFromURL($url);
			if ($image) {
				// If image cache is activated, then use the following sizes:
				// thumb  (150), small (340), medium (600) and large (1024)
				if (!Config::get("system", "proxy_disabled")) {
					$media_url = ProxyUtils::proxifyUrl($url);

					$sizes = [];
					$scale = Image::getScalingDimensions($image[0], $image[1], 150);
					$sizes["thumb"] = ["w" => $scale["width"], "h" => $scale["height"], "resize" => "fit"];

					if (($image[0] > 150) || ($image[1] > 150)) {
						$scale = Image::getScalingDimensions($image[0], $image[1], 340);
						$sizes["small"] = ["w" => $scale["width"], "h" => $scale["height"], "resize" => "fit"];
					}

					$scale = Image::getScalingDimensions($image[0], $image[1], 600);
					$sizes["medium"] = ["w" => $scale["width"], "h" => $scale["height"], "resize" => "fit"];

					if (($image[0] > 600) || ($image[1] > 600)) {
						$scale = Image::getScalingDimensions($image[0], $image[1], 1024);
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
			return System::baseUrl() . '/display/' . $item['guid'];
		},
		$text
	);
	return $text;
}

/**
 * @brief return <a href='url'>name</a> as array
 * @deprecated No usage found (nupplaphil 20190119)
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
 * @brief return likes, dislikes and attend status for item
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
	$a = \get_app();

	$activities = [
		'like' => [],
		'dislike' => [],
		'attendyes' => [],
		'attendno' => [],
		'attendmaybe' => [],
	];

	$condition = ['uid' => $item['uid'], 'thr-parent' => $item['uri']];
	$ret = Item::selectForUser($item['uid'], ['author-id', 'verb'], $condition);

	while ($parent_item = Item::fetch($ret)) {
		// not used as result should be structured like other user data
		//builtin_activity_puller($i, $activities);

		// get user data and add it to the array of the activity
		$user = api_get_user($a, $parent_item['author-id']);
		switch ($parent_item['verb']) {
			case ACTIVITY_LIKE:
				$activities['like'][] = $user;
				break;
			case ACTIVITY_DISLIKE:
				$activities['dislike'][] = $user;
				break;
			case ACTIVITY_ATTEND:
				$activities['attendyes'][] = $user;
				break;
			case ACTIVITY_ATTENDNO:
				$activities['attendno'][] = $user;
				break;
			case ACTIVITY_ATTENDMAYBE:
				$activities['attendmaybe'][] = $user;
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
 * @brief return data from profiles
 *
 * @param array $profile_row array containing data from db table 'profile'
 * @return array
 * @throws InternalServerErrorException
 */
function api_format_items_profiles($profile_row)
{
	$profile = [
		'profile_id'       => $profile_row['id'],
		'profile_name'     => $profile_row['profile-name'],
		'is_default'       => $profile_row['is-default'] ? true : false,
		'hide_friends'     => $profile_row['hide-friends'] ? true : false,
		'profile_photo'    => $profile_row['photo'],
		'profile_thumb'    => $profile_row['thumb'],
		'publish'          => $profile_row['publish'] ? true : false,
		'net_publish'      => $profile_row['net-publish'] ? true : false,
		'description'      => $profile_row['pdesc'],
		'date_of_birth'    => $profile_row['dob'],
		'address'          => $profile_row['address'],
		'city'             => $profile_row['locality'],
		'region'           => $profile_row['region'],
		'postal_code'      => $profile_row['postal-code'],
		'country'          => $profile_row['country-name'],
		'hometown'         => $profile_row['hometown'],
		'gender'           => $profile_row['gender'],
		'marital'          => $profile_row['marital'],
		'marital_with'     => $profile_row['with'],
		'marital_since'    => $profile_row['howlong'],
		'sexual'           => $profile_row['sexual'],
		'politic'          => $profile_row['politic'],
		'religion'         => $profile_row['religion'],
		'public_keywords'  => $profile_row['pub_keywords'],
		'private_keywords' => $profile_row['prv_keywords'],
		'likes'            => BBCode::convert(api_clean_plain_items($profile_row['likes'])    , false, 2),
		'dislikes'         => BBCode::convert(api_clean_plain_items($profile_row['dislikes']) , false, 2),
		'about'            => BBCode::convert(api_clean_plain_items($profile_row['about'])    , false, 2),
		'music'            => BBCode::convert(api_clean_plain_items($profile_row['music'])    , false, 2),
		'book'             => BBCode::convert(api_clean_plain_items($profile_row['book'])     , false, 2),
		'tv'               => BBCode::convert(api_clean_plain_items($profile_row['tv'])       , false, 2),
		'film'             => BBCode::convert(api_clean_plain_items($profile_row['film'])     , false, 2),
		'interest'         => BBCode::convert(api_clean_plain_items($profile_row['interest']) , false, 2),
		'romance'          => BBCode::convert(api_clean_plain_items($profile_row['romance'])  , false, 2),
		'work'             => BBCode::convert(api_clean_plain_items($profile_row['work'])     , false, 2),
		'education'        => BBCode::convert(api_clean_plain_items($profile_row['education']), false, 2),
		'social_networks'  => BBCode::convert(api_clean_plain_items($profile_row['contact'])  , false, 2),
		'homepage'         => $profile_row['homepage'],
		'users'            => null
	];
	return $profile;
}

/**
 * @brief format items to be returned by api
 *
 * @param array  $r           array of items
 * @param array  $user_info
 * @param bool   $filter_user filter items by $user_info
 * @param string $type        Return type (atom, rss, xml, json)
 * @return array
 * @throws BadRequestException
 * @throws ImagickException
 * @throws InternalServerErrorException
 * @throws UnauthorizedException
 */
function api_format_items($r, $user_info, $filter_user = false, $type = "json")
{
	$a = \get_app();

	$ret = [];

	foreach ((array)$r as $item) {
		localize_item($item);
		list($status_user, $owner_user) = api_item_get_user($a, $item);

		// Look if the posts are matching if they should be filtered by user id
		if ($filter_user && ($status_user["id"] != $user_info["id"])) {
			continue;
		}

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
			'friendica_owner' => $owner_user,
			'friendica_private' => $item['private'] == 1,
			//'entities' => NULL,
			'statusnet_html' => $converted["html"],
			'statusnet_conversation_id' => $item['parent'],
			'external_url' => System::baseUrl() . "/display/" . $item['guid'],
			'friendica_activities' => api_format_items_activities($item, $type),
		];

		if (count($converted["attachments"]) > 0) {
			$status["attachments"] = $converted["attachments"];
		}

		if (count($converted["entities"]) > 0) {
			$status["entities"] = $converted["entities"];
		}

		if ($status["source"] == 'web') {
			$status["source"] = ContactSelector::networkToName($item['network'], $item['author-link']);
		} elseif (ContactSelector::networkToName($item['network'], $item['author-link']) != $status["source"]) {
			$status["source"] = trim($status["source"].' ('.ContactSelector::networkToName($item['network'], $item['author-link']).')');
		}

		if ($item["id"] == $item["parent"]) {
			$retweeted_item = api_share_as_retweet($item);
			if ($retweeted_item !== false) {
				$retweeted_status = $status;
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
				$retweeted_status['friendica_activities'] = api_format_items_activities($retweeted_item, $type);
				$retweeted_status['created_at'] =  api_date($retweeted_item['created']);
				$status['retweeted_status'] = $retweeted_status;
			}
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
		$ret[] = $status;
	};
	return $ret;
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
	$a = \get_app();

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
	$a = \get_app();

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
	$count = defaults($_REQUEST, 'count', 20);
	$page = (!empty($_REQUEST['page']) ? $_REQUEST['page'] - 1 : 0);
	if ($page < 0) {
		$page = 0;
	}
	$since_id = defaults($_REQUEST, 'since_id', 0);
	$max_id = defaults($_REQUEST, 'max_id', 0);
	$exclude_replies = (!empty($_REQUEST['exclude_replies']) ? 1 : 0);
	$conversation_id = defaults($_REQUEST, 'conversation_id', 0);

	$start = $page * $count;

	$condition = ["`uid` = ? AND `gravity` IN (?, ?) AND `id` > ? AND `group_member`.`gid` = ?",
		api_user(), GRAVITY_PARENT, GRAVITY_COMMENT, $since_id, $_REQUEST['list_id']];

	if ($max_id > 0) {
		$condition[0] .= " AND `item`.`id` <= ?";
		$condition[] = $max_id;
	}
	if ($exclude_replies > 0) {
		$condition[0] .= ' AND `item`.`parent` = `item`.`id`';
	}
	if ($conversation_id > 0) {
		$condition[0] .= " AND `item`.`parent` = ?";
		$condition[] = $conversation_id;
	}

	$params = ['order' => ['id' => true], 'limit' => [$start, $count]];
	$statuses = Item::selectForUser(api_user(), [], $condition, $params);

	$items = api_format_items(Item::inArray($statuses), $user_info, false, $type);

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
 * Considers friends and followers lists to be private and won't return
 * anything if any user_id parameter is passed.
 *
 * @brief Returns either the friends of the follower list
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
	$a = \get_app();

	if (api_user() === false) {
		throw new ForbiddenException();
	}

	// pagination
	$count = defaults($_GET, 'count', 20);
	$page = defaults($_GET, 'page', 1);
	if ($page < 1) {
		$page = 1;
	}
	$start = ($page - 1) * $count;

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
 * Returns the user's friends.
 *
 * @brief      Returns the list of friends of the provided user
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
 * Returns the user's followers.
 *
 * @brief      Returns the list of followers of the provided user
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
	$a = \get_app();

	$name      = Config::get('config', 'sitename');
	$server    = $a->getHostName();
	$logo      = System::baseUrl() . '/images/friendica-64.png';
	$email     = Config::get('config', 'admin_email');
	$closed    = intval(Config::get('config', 'register_policy')) === REGISTER_CLOSED ? 'true' : 'false';
	$private   = Config::get('system', 'block_public') ? 'true' : 'false';
	$textlimit = (string) Config::get('config', 'api_import_size', Config::get('config', 'max_import_size', 200000));
	$ssl       = Config::get('system', 'have_ssl') ? 'true' : 'false';
	$sslserver = Config::get('system', 'have_ssl') ? str_replace('http:', 'https:', System::baseUrl()) : '';

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
 *
 * @param string $type Return type (atom, rss, xml, json)
 *
 * @return array|string|void
 * @throws BadRequestException
 * @throws ForbiddenException
 * @throws ImagickException
 * @throws InternalServerErrorException
 * @throws UnauthorizedException
 * @todo use api_format_data() to return data
 */
function api_ff_ids($type)
{
	if (!api_user()) {
		throw new ForbiddenException();
	}

	$a = \get_app();

	api_get_user($a);

	$stringify_ids = defaults($_REQUEST, 'stringify_ids', false);

	$r = q(
		"SELECT `pcontact`.`id` FROM `contact`
			INNER JOIN `contact` AS `pcontact` ON `contact`.`nurl` = `pcontact`.`nurl` AND `pcontact`.`uid` = 0
			WHERE `contact`.`uid` = %s AND NOT `contact`.`self`",
		intval(api_user())
	);
	if (!DBA::isResult($r)) {
		return;
	}

	$ids = [];
	foreach ($r as $rr) {
		if ($stringify_ids) {
			$ids[] = $rr['id'];
		} else {
			$ids[] = intval($rr['id']);
		}
	}

	return api_format_data("ids", $type, ['id' => $ids]);
}

/**
 * Returns the ID of every user the user is following.
 *
 * @param string $type Return type (atom, rss, xml, json)
 *
 * @return array|string
 * @throws BadRequestException
 * @throws ForbiddenException
 * @see https://developer.twitter.com/en/docs/accounts-and-users/follow-search-get-users/api-reference/get-friends-ids
 */
function api_friends_ids($type)
{
	return api_ff_ids($type);
}

/**
 * Returns the ID of every user following the user.
 *
 * @param string $type Return type (atom, rss, xml, json)
 *
 * @return array|string
 * @throws BadRequestException
 * @throws ForbiddenException
 * @see https://developer.twitter.com/en/docs/accounts-and-users/follow-search-get-users/api-reference/get-followers-ids
 */
function api_followers_ids($type)
{
	return api_ff_ids($type);
}

/// @TODO move to top of file or somewhere better
api_register_func('api/friends/ids', 'api_friends_ids', true);
api_register_func('api/followers/ids', 'api_followers_ids', true);

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
	$a = \get_app();

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
 * Destroys a direct message.
 *
 * @brief delete a direct_message from mail table through api
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
	$a = \get_app();

	if (api_user() === false) {
		throw new ForbiddenException();
	}

	// params
	$user_info = api_get_user($a);
	//required
	$id = defaults($_REQUEST, 'id', 0);
	// optional
	$parenturi = defaults($_REQUEST, 'friendica_parenturi', "");
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
 * @brief unfollow contact
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

	$contact_id = defaults($_REQUEST, 'user_id');

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
	$a = \get_app();
	if (api_user() === false) {
		throw new ForbiddenException();
	}
	// params
	$count = defaults($_GET, 'count', 20);
	$page = defaults($_REQUEST, 'page', 1) - 1;
	if ($page < 0) {
		$page = 0;
	}

	$since_id = defaults($_REQUEST, 'since_id', 0);
	$max_id = defaults($_REQUEST, 'max_id', 0);

	$user_id = defaults($_REQUEST, 'user_id', '');
	$screen_name = defaults($_REQUEST, 'screen_name', '');

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
	$start = $page * $count;

	$sql_extra = "";

	// filters
	if ($box=="sentbox") {
		$sql_extra = "`mail`.`from-url`='" . DBA::escape($profile_url) . "'";
	} elseif ($box == "conversation") {
		$sql_extra = "`mail`.`parent-uri`='" . DBA::escape(defaults($_GET, 'uri', ''))  . "'";
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
 * @brief delete a complete photoalbum with all containing photos from database through api
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
	$album = defaults($_REQUEST, 'album', "");

	// we do not allow calls without album string
	if ($album == "") {
		throw new BadRequestException("no albumname specified");
	}
	// check if album is existing
	$r = q(
		"SELECT DISTINCT `resource-id` FROM `photo` WHERE `uid` = %d AND `album` = '%s'",
		intval(api_user()),
		DBA::escape($album)
	);
	if (!DBA::isResult($r)) {
		throw new BadRequestException("album not available");
	}

	// function for setting the items to "deleted = 1" which ensures that comments, likes etc. are not shown anymore
	// to the user and the contacts of the users (drop_items() performs the federation of the deletion to other networks
	foreach ($r as $rr) {
		$condition = ['uid' => local_user(), 'resource-id' => $rr['resource-id'], 'type' => 'photo'];
		$photo_item = Item::selectFirstForUser(local_user(), ['id'], $condition);

		if (!DBA::isResult($photo_item)) {
			throw new InternalServerErrorException("problem with deleting items occured");
		}
		Item::deleteForUser(['id' => $photo_item['id']], api_user());
	}

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
 * @brief update the name of the album for all photos of an album
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
	$album = defaults($_REQUEST, 'album', "");
	$album_new = defaults($_REQUEST, 'album_new', "");

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
 * @brief list all photos of the authenticated user
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
		WHERE `uid` = %d AND `album` != 'Contact Photos' GROUP BY `resource-id`",
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
			$thumb = System::baseUrl() . "/photo/" . $rr['resource-id'] . "-" . $rr['scale'] . "." . $typetoext[$rr['type']];
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
 * @brief upload a new photo or change an existing photo
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
	$photo_id = defaults($_REQUEST, 'photo_id', null);
	$desc = defaults($_REQUEST, 'desc', (array_key_exists('desc', $_REQUEST) ? "" : null)) ; // extra check necessary to distinguish between 'not provided' and 'empty string'
	$album = defaults($_REQUEST, 'album', null);
	$album_new = defaults($_REQUEST, 'album_new', null);
	$allow_cid = defaults($_REQUEST, 'allow_cid', (array_key_exists('allow_cid', $_REQUEST) ? " " : null));
	$deny_cid  = defaults($_REQUEST, 'deny_cid' , (array_key_exists('deny_cid' , $_REQUEST) ? " " : null));
	$allow_gid = defaults($_REQUEST, 'allow_gid', (array_key_exists('allow_gid', $_REQUEST) ? " " : null));
	$deny_gid  = defaults($_REQUEST, 'deny_gid' , (array_key_exists('deny_gid' , $_REQUEST) ? " " : null));
	$visibility = !empty($_REQUEST['visibility']) && $_REQUEST['visibility'] !== "false";

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
 * @brief delete a single photo from the database through api
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
	$photo_id = defaults($_REQUEST, 'photo_id', null);

	// do several checks on input parameters
	// we do not allow calls without photo id
	if ($photo_id == null) {
		throw new BadRequestException("no photo_id specified");
	}
	// check if photo is existing in database
	$r = Photo::exists(['resource-id' => $photo_id, 'uid' => api_user()]);
	if (!$r) {
		throw new BadRequestException("photo not available");
	}
	// now we can perform on the deletion of the photo
	$result = Photo::delete(['uid' => api_user(), 'resource-id' => $photo_id]);

	// return success of deletion or error message
	if ($result) {
		// retrieve the id of the parent element (the photo element)
		$condition = ['uid' => local_user(), 'resource-id' => $photo_id, 'type' => 'photo'];
		$photo_item = Item::selectFirstForUser(local_user(), ['id'], $condition);

		if (!DBA::isResult($photo_item)) {
			throw new InternalServerErrorException("problem with deleting items occured");
		}
		// function for setting the items to "deleted = 1" which ensures that comments, likes etc. are not shown anymore
		// to the user and the contacts of the users (drop_items() do all the necessary magic to avoid orphans in database and federate deletion)
		Item::deleteForUser(['id' => $photo_item['id']], api_user());

		$answer = ['result' => 'deleted', 'message' => 'photo with id `' . $photo_id . '` has been deleted from server.'];
		return api_format_data("photo_delete", $type, ['$result' => $answer]);
	} else {
		throw new InternalServerErrorException("unknown error on deleting photo from database table");
	}
}


/**
 * @brief returns the details of a specified photo id, if scale is given, returns the photo data in base 64
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
 * Updates the user’s profile image.
 *
 * @brief updates the profile image for the user (either a specified profile or the default profile)
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
	$profile_id = defaults($_REQUEST, 'profile_id', 0);

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
	$data = save_media_to_database("profileimage", $media, $type, L10n::t('Profile Photos'), "", "", "", "", "", $is_default_profile);

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
		$fields = ['photo' => System::baseUrl() . '/photo/' . $data['photo']['id'] . '-4.' . $fileext,
			'thumb' => System::baseUrl() . '/photo/' . $data['photo']['id'] . '-5.' . $fileext];
		DBA::update('profile', $fields, ['id' => $_REQUEST['profile'], 'uid' => api_user()]);
	}

	Contact::updateSelfFromUserID(api_user(), true);

	// Update global directory in background
	$url = System::baseUrl() . '/profile/' . \get_app()->user['nickname'];
	if ($url && strlen(Config::get('system', 'directory'))) {
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
	$api_user = api_get_user(get_app());

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
	if ($api_user['url'] && strlen(Config::get('system', 'directory'))) {
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
	if ($acl_string == null || $acl_string == " ") {
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
 *
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

	if ($filetype == "") {
		$filetype=Image::guessType($filename);
	}
	$imagedata = @getimagesize($src);
	if ($imagedata) {
		$filetype = $imagedata['mime'];
	}
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
	$maximagesize = Config::get('system', 'maximagesize');
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
	$max_length = Config::get('system', 'max_image_length');
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
	$hash = ($photo_id == null) ? Photo::newResource() : $photo_id;

	if ($mediatype == "photo") {
		// upload normal image (scales 0, 1, 2)
		Logger::log("photo upload: starting new photo upload", Logger::DEBUG);

		$r = Photo::store($Image, local_user(), $visitor, $hash, $filename, $album, 0, 0, $allow_cid, $allow_gid, $deny_cid, $deny_gid, $desc);
		if (!$r) {
			Logger::log("photo upload: image upload with scale 0 (original size) failed");
		}
		if ($width > 640 || $height > 640) {
			$Image->scaleDown(640);
			$r = Photo::store($Image, local_user(), $visitor, $hash, $filename, $album, 1, 0, $allow_cid, $allow_gid, $deny_cid, $deny_gid, $desc);
			if (!$r) {
				Logger::log("photo upload: image upload with scale 1 (640x640) failed");
			}
		}

		if ($width > 320 || $height > 320) {
			$Image->scaleDown(320);
			$r = Photo::store($Image, local_user(), $visitor, $hash, $filename, $album, 2, 0, $allow_cid, $allow_gid, $deny_cid, $deny_gid, $desc);
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
			$r = Photo::store($Image, local_user(), $visitor, $hash, $filename, $album, 4, $profile, $allow_cid, $allow_gid, $deny_cid, $deny_gid, $desc);
			if (!$r) {
				Logger::log("photo upload: profile image upload with scale 4 (300x300) failed");
			}
		}

		if ($width > 80 || $height > 80) {
			$Image->scaleDown(80);
			$r = Photo::store($Image, local_user(), $visitor, $hash, $filename, $album, 5, $profile, $allow_cid, $allow_gid, $deny_cid, $deny_gid, $desc);
			if (!$r) {
				Logger::log("photo upload: profile image upload with scale 5 (80x80) failed");
			}
		}

		if ($width > 48 || $height > 48) {
			$Image->scaleDown(48);
			$r = Photo::store($Image, local_user(), $visitor, $hash, $filename, $album, 6, $profile, $allow_cid, $allow_gid, $deny_cid, $deny_gid, $desc);
			if (!$r) {
				Logger::log("photo upload: profile image upload with scale 6 (48x48) failed");
			}
		}
		$Image->__destruct();
		Logger::log("photo upload: new profile image upload ended", Logger::DEBUG);
	}

	if (isset($r) && $r) {
		// create entry in 'item'-table on new uploads to enable users to comment/like/dislike the photo
		if ($photo_id == null && $mediatype == "photo") {
			post_photo_item($hash, $allow_cid, $deny_cid, $allow_gid, $deny_gid, $filetype, $visibility);
		}
		// on success return image data in json/xml format (like /api/friendica/photo does when no scale is given)
		return prepare_photo_data($type, false, $hash);
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
	$arr['parent-uri']    = $uri;
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
	$arr['body'] = '[url=' . System::baseUrl() . '/photos/' . $owner_record['nick'] . '/image/' . $hash . ']'
				. '[img]' . System::baseUrl() . '/photo/' . $hash . '-' . "2" . '.'. $typetoext[$filetype] . '[/img]'
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
	$a = \get_app();
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
			FROM `photo` WHERE `uid` = %d AND `resource-id` = '%s' %s GROUP BY `resource-id`",
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
										"href" => System::baseUrl() . "/photo/" . $data['photo']['resource-id'] . "-" . $k . "." . $typetoext[$data['photo']['type']]];
			}
		} else {
			$data['photo']['link'] = [];
			// when we have profile images we could have only scales from 4 to 6, but index of array always needs to start with 0
			$i = 0;
			for ($k = intval($data['photo']['minscale']); $k <= intval($data['photo']['maxscale']); $k++) {
				$data['photo']['link'][$i] = System::baseUrl() . "/photo/" . $data['photo']['resource-id'] . "-" . $k . "." . $typetoext[$data['photo']['type']];
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
	$condition = ['uid' => local_user(), 'resource-id' => $photo_id, 'type' => 'photo'];
	$item = Item::selectFirstForUser(local_user(), ['id'], $condition);

	$data['photo']['friendica_activities'] = api_format_items_activities($item, $type);

	// retrieve comments on photo
	$condition = ["`parent` = ? AND `uid` = ? AND (`gravity` IN (?, ?) OR `type`='photo')",
		$item[0]['parent'], api_user(), GRAVITY_PARENT, GRAVITY_COMMENT];

	$statuses = Item::selectForUser(api_user(), [], $condition);

	// prepare output of comments
	$commentData = api_format_items(Item::inArray($statuses), $user_info, false, $type);
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
	$rights_mismatch = $data['photo']['allow_cid'] != $item[0]['allow_cid'] ||
		$data['photo']['deny_cid'] != $item[0]['deny_cid'] ||
		$data['photo']['allow_gid'] != $item[0]['allow_gid'] ||
		$data['photo']['deny_cid'] != $item[0]['deny_cid'];
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
	$url = defaults($_GET, 'url', '');
	$c_url = defaults($_GET, 'c_url', '');

	if ($url === '' || $c_url === '') {
		throw new BadRequestException("Wrong parameters.");
	}

	$c_url = Strings::normaliseLink($c_url);

	// traditional DFRN

	$contact = DBA::selectFirst('contact', [], ['uid' => api_user(), 'nurl' => $c_url]);

	if (!DBA::isResult($contact) || ($contact['network'] !== Protocol::DFRN)) {
		throw new BadRequestException("Unknown contact");
	}

	$cid = $contact['id'];

	$dfrn_id = defaults($contact, 'issued-id', $contact['dfrn-id']);

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
 * @brief Return the item shared, if the item contains only the [share] tag
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

	/// @TODO "$1" should maybe mean '$1' ?
	$attributes = preg_replace("/\[share(.*?)\]\s?(.*?)\s?\[\/share\]\s?/ism", "$1", $body);
	/*
	 * Skip if there is no shared message in there
	 * we already checked this in diaspora::isReshare()
	 * but better one more than one less...
	 */
	if (($body == $attributes) || empty($attributes)) {
		return false;
	}

	// build the fake reshared item
	$reshared_item = $item;

	$author = "";
	preg_match("/author='(.*?)'/ism", $attributes, $matches);
	if (!empty($matches[1])) {
		$author = html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8');
	}

	preg_match('/author="(.*?)"/ism', $attributes, $matches);
	if (!empty($matches[1])) {
		$author = $matches[1];
	}

	$profile = "";
	preg_match("/profile='(.*?)'/ism", $attributes, $matches);
	if (!empty($matches[1])) {
		$profile = $matches[1];
	}

	preg_match('/profile="(.*?)"/ism', $attributes, $matches);
	if (!empty($matches[1])) {
		$profile = $matches[1];
	}

	$avatar = "";
	preg_match("/avatar='(.*?)'/ism", $attributes, $matches);
	if (!empty($matches[1])) {
		$avatar = $matches[1];
	}

	preg_match('/avatar="(.*?)"/ism', $attributes, $matches);
	if (!empty($matches[1])) {
		$avatar = $matches[1];
	}

	$link = "";
	preg_match("/link='(.*?)'/ism", $attributes, $matches);
	if (!empty($matches[1])) {
		$link = $matches[1];
	}

	preg_match('/link="(.*?)"/ism', $attributes, $matches);
	if (!empty($matches[1])) {
		$link = $matches[1];
	}

	$posted = "";
	preg_match("/posted='(.*?)'/ism", $attributes, $matches);
	if (!empty($matches[1])) {
		$posted = $matches[1];
	}

	preg_match('/posted="(.*?)"/ism', $attributes, $matches);
	if (!empty($matches[1])) {
		$posted = $matches[1];
	}

	$shared_body = preg_replace("/\[share(.*?)\]\s?(.*?)\s?\[\/share\]\s?/ism", "$2", $body);

	if (($shared_body == "") || ($profile == "") || ($author == "") || ($avatar == "") || ($posted == "")) {
		return false;
	}

	$reshared_item["body"] = $shared_body;
	$reshared_item["author-id"] = Contact::getIdForURL($profile, 0, true);
	$reshared_item["author-name"] = $author;
	$reshared_item["author-link"] = $profile;
	$reshared_item["author-avatar"] = $avatar;
	$reshared_item["plink"] = $link;
	$reshared_item["created"] = $posted;
	$reshared_item["edited"] = $posted;

	return $reshared_item;
}

/**
 *
 * @param string $profile
 *
 * @return string|false
 * @throws InternalServerErrorException
 * @todo remove trailing junk from profile url
 * @todo pump.io check has to check the website
 */
function api_get_nick($profile)
{
	$nick = "";

	$r = q(
		"SELECT `nick` FROM `contact` WHERE `uid` = 0 AND `nurl` = '%s'",
		DBA::escape(Strings::normaliseLink($profile))
	);

	if (DBA::isResult($r)) {
		$nick = $r[0]["nick"];
	}

	if (!$nick == "") {
		$r = q(
			"SELECT `nick` FROM `contact` WHERE `uid` = 0 AND `nurl` = '%s'",
			DBA::escape(Strings::normaliseLink($profile))
		);

		if (DBA::isResult($r)) {
			$nick = $r[0]["nick"];
		}
	}

	if (!$nick == "") {
		$friendica = preg_replace("=https?://(.*)/profile/(.*)=ism", "$2", $profile);
		if ($friendica != $profile) {
			$nick = $friendica;
		}
	}

	if (!$nick == "") {
		$diaspora = preg_replace("=https?://(.*)/u/(.*)=ism", "$2", $profile);
		if ($diaspora != $profile) {
			$nick = $diaspora;
		}
	}

	if (!$nick == "") {
		$twitter = preg_replace("=https?://twitter.com/(.*)=ism", "$1", $profile);
		if ($twitter != $profile) {
			$nick = $twitter;
		}
	}


	if (!$nick == "") {
		$StatusnetHost = preg_replace("=https?://(.*)/user/(.*)=ism", "$1", $profile);
		if ($StatusnetHost != $profile) {
			$StatusnetUser = preg_replace("=https?://(.*)/user/(.*)=ism", "$2", $profile);
			if ($StatusnetUser != $profile) {
				$UserData = Network::fetchUrl("http://".$StatusnetHost."/api/users/show.json?user_id=".$StatusnetUser);
				$user = json_decode($UserData);
				if ($user) {
					$nick = $user->screen_name;
				}
			}
		}
	}

	// To-Do: look at the page if its really a pumpio site
	//if (!$nick == "") {
	//	$pumpio = preg_replace("=https?://(.*)/(.*)/=ism", "$2", $profile."/");
	//	if ($pumpio != $profile)
	//		$nick = $pumpio;
		//      <div class="media" id="profile-block" data-profile-id="acct:kabniel@microca.st">

	//}

	if ($nick != "") {
		return $nick;
	}

	return false;
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

	if (($item['thr-parent'] != $item['uri']) && (intval($item['parent']) != intval($item['id']))) {
		$parent = Item::selectFirst(['id'], ['uid' => $item['uid'], 'uri' => $item['thr-parent']]);
		if (DBA::isResult($parent)) {
			$in_reply_to['status_id'] = intval($parent['id']);
		} else {
			$in_reply_to['status_id'] = intval($item['parent']);
		}

		$in_reply_to['status_id_str'] = (string) intval($in_reply_to['status_id']);

		$fields = ['author-nick', 'author-name', 'author-id', 'author-link'];
		$parent = Item::selectFirst($fields, ['id' => $in_reply_to['status_id']]);

		if (DBA::isResult($parent)) {
			if ($parent['author-nick'] == "") {
				$parent['author-nick'] = api_get_nick($parent['author-link']);
			}

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
	$include_entities = strtolower(defaults($_REQUEST, 'include_entities', "false"));

	$text = BBCode::cleanPictureLinks($text);
	$URLSearchString = "^\[\]";

	$text = preg_replace("/([!#@])\[url\=([$URLSearchString]*)\](.*?)\[\/url\]/ism", '$1$3', $text);

	if ($include_entities == "true") {
		$text = preg_replace("/\[url\=([$URLSearchString]*)\](.*?)\[\/url\]/ism", '[url=$1]$1[/url]', $text);
	}

	// Simplify "attachment" element
	$text = api_clean_attachments($text);

	return $text;
}

/**
 * @brief Removes most sharing information for API text export
 *
 * @param string $body The original body
 *
 * @return string Cleaned body
 * @throws InternalServerErrorException
 */
function api_clean_attachments($body)
{
	$data = BBCode::getAttachmentData($body);

	if (empty($data)) {
		return $body;
	}
	$body = "";

	if (isset($data["text"])) {
		$body = $data["text"];
	}
	if (($body == "") && isset($data["title"])) {
		$body = $data["title"];
	}
	if (isset($data["url"])) {
		$body .= "\n".$data["url"];
	}
	$body .= $data["after"];

	return $body;
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
	$a = \get_app();

	if (api_user() === false) {
		throw new ForbiddenException();
	}

	// params
	$user_info = api_get_user($a);
	$gid = defaults($_REQUEST, 'gid', 0);
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
		$members = Contact::getByGroupId($rr['id']);
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
	$a = \get_app();

	if (api_user() === false) {
		throw new ForbiddenException();
	}

	// params
	$user_info = api_get_user($a);
	$gid = defaults($_REQUEST, 'gid', 0);
	$name = defaults($_REQUEST, 'name', "");
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
	$a = \get_app();

	if (api_user() === false) {
		throw new ForbiddenException();
	}

	// params
	$user_info = api_get_user($a);
	$gid = defaults($_REQUEST, 'list_id', 0);
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
	$a = \get_app();

	if (api_user() === false) {
		throw new ForbiddenException();
	}

	// params
	$user_info = api_get_user($a);
	$name = defaults($_REQUEST, 'name', "");
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
	$a = \get_app();

	if (api_user() === false) {
		throw new ForbiddenException();
	}

	// params
	$user_info = api_get_user($a);
	$name = defaults($_REQUEST, 'name', "");
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
	$a = \get_app();

	if (api_user() === false) {
		throw new ForbiddenException();
	}

	// params
	$user_info = api_get_user($a);
	$uid = $user_info['uid'];
	$gid = defaults($_REQUEST, 'gid', 0);
	$name = defaults($_REQUEST, 'name', "");
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
	$members = Contact::getByGroupId($gid);
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
	$a = \get_app();

	if (api_user() === false) {
		throw new ForbiddenException();
	}

	// params
	$user_info = api_get_user($a);
	$gid = defaults($_REQUEST, 'list_id', 0);
	$name = defaults($_REQUEST, 'name', "");
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
	$a = \get_app();

	if (api_user() === false) {
		throw new ForbiddenException();
	}
	$verb = strtolower($a->argv[3]);
	$verb = preg_replace("|\..*$|", "", $verb);

	$id = defaults($_REQUEST, 'id', 0);

	$res = Item::performLike($id, $verb);

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
 * @brief Returns notifications
 *
 * @param string $type Known types are 'atom', 'rss', 'xml' and 'json'
 * @return string|array
 * @throws BadRequestException
 * @throws ForbiddenException
 * @throws InternalServerErrorException
 */
function api_friendica_notification($type)
{
	$a = \get_app();

	if (api_user() === false) {
		throw new ForbiddenException();
	}
	if ($a->argc!==3) {
		throw new BadRequestException("Invalid argument count");
	}
	$nm = new NotificationsManager();

	$notes = $nm->getAll([], "+seen -date", 50);

	if ($type == "xml") {
		$xmlnotes = [];
		if (!empty($notes)) {
			foreach ($notes as $note) {
				$xmlnotes[] = ["@attributes" => $note];
			}
		}

		$notes = $xmlnotes;
	}
	return api_format_data("notes", $type, ['note' => $notes]);
}

/**
 * POST request with 'id' param as notification id
 *
 * @brief Set notification as seen and returns associated item (if possible)
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
	$a = \get_app();
	$user_info = api_get_user($a);

	if (api_user() === false || $user_info === false) {
		throw new ForbiddenException();
	}
	if ($a->argc!==4) {
		throw new BadRequestException("Invalid argument count");
	}

	$id = (!empty($_REQUEST['id']) ? intval($_REQUEST['id']) : 0);

	$nm = new NotificationsManager();
	$note = $nm->getByID($id);
	if (is_null($note)) {
		throw new BadRequestException("Invalid argument");
	}

	$nm->setSeen($note);
	if ($note['otype']=='item') {
		// would be really better with an ItemsManager and $im->getByID() :-P
		$item = Item::selectFirstForUser(api_user(), [], ['id' => $note['iid'], 'uid' => api_user()]);
		if (DBA::isResult($item)) {
			// we found the item, return it to the user
			$ret = api_format_items([$item], $user_info, false, $type);
			$data = ['status' => $ret];
			return api_format_data("status", $type, $data);
		}
		// the item can't be found, but we set the note as seen, so we count this as a success
	}
	return api_format_data('result', $type, ['result' => "success"]);
}

/// @TODO move to top of file or somewhere better
api_register_func('api/friendica/notification/seen', 'api_friendica_notification_seen', true, API_METHOD_POST);
api_register_func('api/friendica/notification', 'api_friendica_notification', true, API_METHOD_GET);

/**
 * @brief update a direct_message to seen state
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
	$a = \get_app();
	if (api_user() === false) {
		throw new ForbiddenException();
	}

	// params
	$user_info = api_get_user($a);
	$uid = $user_info['uid'];
	$id = defaults($_REQUEST, 'id', 0);

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
 * @brief search for direct_messages containing a searchstring through api
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
	$a = \get_app();

	if (api_user() === false) {
		throw new ForbiddenException();
	}

	// params
	$user_info = api_get_user($a);
	$searchstring = defaults($_REQUEST, 'searchstring', "");
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
 * @brief return data of all the profiles a user has to the client
 *
 * @param string $type Known types are 'atom', 'rss', 'xml' and 'json'
 * @return string|array
 * @throws BadRequestException
 * @throws ForbiddenException
 * @throws ImagickException
 * @throws InternalServerErrorException
 * @throws UnauthorizedException
 */
function api_friendica_profile_show($type)
{
	$a = \get_app();

	if (api_user() === false) {
		throw new ForbiddenException();
	}

	// input params
	$profile_id = defaults($_REQUEST, 'profile_id', 0);

	// retrieve general information about profiles for user
	$multi_profiles = Feature::isEnabled(api_user(), 'multi_profiles');
	$directory = Config::get('system', 'directory');

	// get data of the specified profile id or all profiles of the user if not specified
	if ($profile_id != 0) {
		$r = q(
			"SELECT * FROM `profile` WHERE `uid` = %d AND `id` = %d",
			intval(api_user()),
			intval($profile_id)
		);

		// error message if specified gid is not in database
		if (!DBA::isResult($r)) {
			throw new BadRequestException("profile_id not available");
		}
	} else {
		$r = q(
			"SELECT * FROM `profile` WHERE `uid` = %d",
			intval(api_user())
		);
	}
	// loop through all returned profiles and retrieve data and users
	$k = 0;
	$profiles = [];
	foreach ($r as $rr) {
		$profile = api_format_items_profiles($rr);

		// select all users from contact table, loop and prepare standard return for user data
		$users = [];
		$nurls = q(
			"SELECT `id`, `nurl` FROM `contact` WHERE `uid`= %d AND `profile-id` = %d",
			intval(api_user()),
			intval($rr['id'])
		);

		foreach ($nurls as $nurl) {
			$user = api_get_user($a, $nurl['nurl']);
			($type == "xml") ? $users[$k++ . ":user"] = $user : $users[] = $user;
		}
		$profile['users'] = $users;

		// add prepared profile data to array for final return
		if ($type == "xml") {
			$profiles[$k++ . ":profile"] = $profile;
		} else {
			$profiles[] = $profile;
		}
	}

	// return settings, authenticated user and profiles data
	$self = DBA::selectFirst('contact', ['nurl'], ['uid' => api_user(), 'self' => true]);

	$result = ['multi_profiles' => $multi_profiles ? true : false,
					'global_dir' => $directory,
					'friendica_owner' => api_get_user($a, $self['nurl']),
					'profiles' => $profiles];
	return api_format_data("friendica_profiles", $type, ['$result' => $result]);
}
api_register_func('api/friendica/profile/show', 'api_friendica_profile_show', true, API_METHOD_GET);

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
	while ($term = $terms->fetch()) {
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
 * Bind comment numbers(friendica_comments: Int) on each statuses page of *_timeline / favorites / search
 *
 * @brief Number of comments
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
	$sql = "SELECT `parent`, COUNT(*) as comments FROM `item` WHERE `parent` IN ($idStr) AND `deleted` = ? AND `gravity`= ? GROUP BY `parent`";
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
