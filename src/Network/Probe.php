<?php
/**
 * @file src/Network/Probe.php
 */
namespace Friendica\Network;

/**
 * @file src/Network/Probe.php
 * @brief Functions for probing URL
 */

use Friendica\Core\Cache;
use Friendica\Core\Config;
use Friendica\Core\System;
use Friendica\Database\DBM;
use Friendica\Model\Profile;
use Friendica\Protocol\Email;
use Friendica\Protocol\Feed;
use Friendica\Util\Crypto;
use Friendica\Util\Network;
use Friendica\Util\XML;
use dba;
use DOMDocument;
use DOMXPath;

require_once 'include/dba.php';

/**
 * @brief This class contain functions for probing URL
 *
 */
class Probe
{
	private static $baseurl;

	/**
	 * @brief Rearrange the array so that it always has the same order
	 *
	 * @param array $data Unordered data
	 *
	 * @return array Ordered data
	 */
	private static function rearrangeData($data)
	{
		$fields = ["name", "nick", "guid", "url", "addr", "alias",
				"photo", "community", "keywords", "location", "about",
				"batch", "notify", "poll", "request", "confirm", "poco",
				"priority", "network", "pubkey", "baseurl"];

		$newdata = [];
		foreach ($fields as $field) {
			if (isset($data[$field])) {
				$newdata[$field] = $data[$field];
			} else {
				$newdata[$field] = "";
			}
		}

		// We don't use the "priority" field anymore and replace it with a dummy.
		$newdata["priority"] = 0;

		return $newdata;
	}

	/**
	 * @brief Check if the hostname belongs to the own server
	 *
	 * @param string $host The hostname that is to be checked
	 *
	 * @return bool Does the testes hostname belongs to the own server?
	 */
	private static function ownHost($host)
	{
		$own_host = get_app()->get_hostname();

		$parts = parse_url($host);

		if (!isset($parts['scheme'])) {
			$parts = parse_url('http://'.$host);
		}

		if (!isset($parts['host'])) {
			return false;
		}
		return $parts['host'] == $own_host;
	}

	/**
	 * @brief Probes for webfinger path via "host-meta"
	 *
	 * We have to check if the servers in the future still will offer this.
	 * It seems as if it was dropped from the standard.
	 *
	 * @param string $host The host part of an url
	 *
	 * @return array with template and type of the webfinger template for JSON or XML
	 */
	private static function hostMeta($host)
	{
		// Reset the static variable
		self::$baseurl = '';

		$ssl_url = "https://".$host."/.well-known/host-meta";
		$url = "http://".$host."/.well-known/host-meta";

		$xrd_timeout = Config::get('system', 'xrd_timeout', 20);
		$redirects = 0;

		logger("Probing for ".$host, LOGGER_DEBUG);

		$ret = Network::curl($ssl_url, false, $redirects, ['timeout' => $xrd_timeout, 'accept_content' => 'application/xrd+xml']);
		if ($ret['success']) {
			$xml = $ret['body'];
			$xrd = XML::parseString($xml, false);
			$host_url = 'https://'.$host;
		}

		if (!is_object($xrd)) {
			$ret = Network::curl($url, false, $redirects, ['timeout' => $xrd_timeout, 'accept_content' => 'application/xrd+xml']);
			if ($ret['errno'] == CURLE_OPERATION_TIMEDOUT) {
				logger("Probing timeout for ".$url, LOGGER_DEBUG);
				return false;
			}
			$xml = $ret['body'];
			$xrd = XML::parseString($xml, false);
			$host_url = 'http://'.$host;
		}
		if (!is_object($xrd)) {
			logger("No xrd object found for ".$host, LOGGER_DEBUG);
			return [];
		}

		$links = XML::elementToArray($xrd);
		if (!isset($links["xrd"]["link"])) {
			logger("No xrd data found for ".$host, LOGGER_DEBUG);
			return [];
		}

		$lrdd = [];
		// The following webfinger path is defined in RFC 7033 https://tools.ietf.org/html/rfc7033
		// Problem is that Hubzilla currently doesn't provide all data in the JSON webfinger
		// compared to the XML webfinger. So this is commented out by now.
		// $lrdd = array("application/jrd+json" => $host_url.'/.well-known/webfinger?resource={uri}');

		foreach ($links["xrd"]["link"] as $value => $link) {
			if (!empty($link["@attributes"])) {
				$attributes = $link["@attributes"];
			} elseif ($value == "@attributes") {
				$attributes = $link;
			} else {
				continue;
			}

			if (($attributes["rel"] == "lrdd") && !empty($attributes["template"])) {
				$type = (empty($attributes["type"]) ? '' : $attributes["type"]);

				$lrdd[$type] = $attributes["template"];
			}
		}

		self::$baseurl = "http://".$host;

		logger("Probing successful for ".$host, LOGGER_DEBUG);

		return $lrdd;
	}

	/**
	 * @brief Perform Webfinger lookup and return DFRN data
	 *
	 * Given an email style address, perform webfinger lookup and
	 * return the resulting DFRN profile URL, or if no DFRN profile URL
	 * is located, returns an OStatus subscription template (prefixed
	 * with the string 'stat:' to identify it as on OStatus template).
	 * If this isn't an email style address just return $webbie.
	 * Return an empty string if email-style addresses but webfinger fails,
	 * or if the resultant personal XRD doesn't contain a supported
	 * subscription/friend-request attribute.
	 *
	 * amended 7/9/2011 to return an hcard which could save potentially loading
	 * a lengthy content page to scrape dfrn attributes
	 *
	 * @param string $webbie    Address that should be probed
	 * @param string $hcard_url Link to the hcard - is returned by reference
	 *
	 * @return string profile link
	 */
	public static function webfingerDfrn($webbie, &$hcard_url)
	{
		$profile_link = '';

		$links = self::lrdd($webbie);
		logger('webfingerDfrn: '.$webbie.':'.print_r($links, true), LOGGER_DATA);
		if (count($links)) {
			foreach ($links as $link) {
				if ($link['@attributes']['rel'] === NAMESPACE_DFRN) {
					$profile_link = $link['@attributes']['href'];
				}
				if (($link['@attributes']['rel'] === NAMESPACE_OSTATUSSUB) && ($profile_link == "")) {
					$profile_link = 'stat:'.$link['@attributes']['template'];
				}
				if ($link['@attributes']['rel'] === 'http://microformats.org/profile/hcard') {
					$hcard_url = $link['@attributes']['href'];
				}
			}
		}
		return $profile_link;
	}

	/**
	 * @brief Check an URI for LRDD data
	 *
	 * this is a replacement for the "lrdd" function.
	 * It isn't used in this class and has some redundancies in the code.
	 * When time comes we can check the existing calls for "lrdd" if we can rework them.
	 *
	 * @param string $uri Address that should be probed
	 *
	 * @return array uri data
	 */
	public static function lrdd($uri)
	{
		$lrdd = self::hostMeta($uri);
		$webfinger = null;

		if (is_bool($lrdd)) {
			return [];
		}

		if (!$lrdd) {
			$parts = @parse_url($uri);
			if (!$parts) {
				return [];
			}

			$host = $parts["host"];
			if (!empty($parts["port"])) {
				$host .= ':'.$parts["port"];
			}

			$path_parts = explode("/", trim($parts["path"], "/"));

			$nick = array_pop($path_parts);

			do {
				$lrdd = self::hostMeta($host);
				$host .= "/".array_shift($path_parts);
			} while (!$lrdd && (sizeof($path_parts) > 0));
		}

		if (!$lrdd) {
			logger("No lrdd data found for ".$uri, LOGGER_DEBUG);
			return [];
		}

		foreach ($lrdd as $type => $template) {
			if ($webfinger) {
				continue;
			}

			$path = str_replace('{uri}', urlencode($uri), $template);
			$webfinger = self::webfinger($path, $type);

			if (!$webfinger && (strstr($uri, "@"))) {
				$path = str_replace('{uri}', urlencode("acct:".$uri), $template);
				$webfinger = self::webfinger($path, $type);
			}

			// Special treatment for Mastodon
			// Problem is that Mastodon uses an URL format like http://domain.tld/@nick
			// But the webfinger for this format fails.
			if (!$webfinger && !empty($nick)) {
				// Mastodon uses a "@" as prefix for usernames in their url format
				$nick = ltrim($nick, '@');

				$addr = $nick."@".$host;

				$path = str_replace('{uri}', urlencode("acct:".$addr), $template);
				$webfinger = self::webfinger($path, $type);
			}
		}

		if (!is_array($webfinger["links"])) {
			logger("No webfinger links found for ".$uri, LOGGER_DEBUG);
			return false;
		}

		$data = [];

		foreach ($webfinger["links"] as $link) {
			$data[] = ["@attributes" => $link];
		}

		if (is_array($webfinger["aliases"])) {
			foreach ($webfinger["aliases"] as $alias) {
				$data[] = ["@attributes" =>
							["rel" => "alias",
								"href" => $alias]];
			}
		}

		return $data;
	}

	/**
	 * @brief Fetch information (protocol endpoints and user information) about a given uri
	 *
	 * @param string  $uri     Address that should be probed
	 * @param string  $network Test for this specific network
	 * @param integer $uid     User ID for the probe (only used for mails)
	 * @param boolean $cache   Use cached values?
	 *
	 * @return array uri data
	 */
	public static function uri($uri, $network = "", $uid = -1, $cache = true)
	{
		if ($cache) {
			$result = Cache::get("Probe::uri:".$network.":".$uri);
			if (!is_null($result)) {
				return $result;
			}
		}

		if ($uid == -1) {
			$uid = local_user();
		}

		$data = self::detect($uri, $network, $uid);

		if (!isset($data["url"])) {
			$data["url"] = $uri;
		}

		if (x($data, "photo")) {
			$data["baseurl"] = Network::getUrlMatch(normalise_link($data["baseurl"]), normalise_link($data["photo"]));
		} else {
			$data["photo"] = System::baseUrl().'/images/person-175.jpg';
		}

		if (empty($data["name"])) {
			if (!empty($data["nick"])) {
				$data["name"] = $data["nick"];
			}

			if (!x($data, "name")) {
				$data["name"] = $data["url"];
			}
		}

		if (empty($data["nick"])) {
			$data["nick"] = strtolower($data["name"]);

			if (strpos($data['nick'], ' ')) {
				$data['nick'] = trim(substr($data['nick'], 0, strpos($data['nick'], ' ')));
			}
		}

		if (self::$baseurl != "") {
			$data["baseurl"] = self::$baseurl;
		}

		if (!isset($data["network"])) {
			$data["network"] = NETWORK_PHANTOM;
		}

		$data = self::rearrangeData($data);

		// Only store into the cache if the value seems to be valid
		if (!in_array($data['network'], [NETWORK_PHANTOM, NETWORK_MAIL])) {
			Cache::set("Probe::uri:".$network.":".$uri, $data, CACHE_DAY);

			/// @todo temporary fix - we need a real contact update function that updates only changing fields
			/// The biggest problem is the avatar picture that could have a reduced image size.
			/// It should only be updated if the existing picture isn't existing anymore.
			/// We only update the contact when it is no probing for a specific network.
			if (($data['network'] != NETWORK_FEED)
				&& ($network == "")
				&& $data["name"]
				&& $data["nick"]
				&& $data["url"]
				&& $data["addr"]
				&& $data["poll"]
			) {
				$fields = ['name' => $data['name'],
						'nick' => $data['nick'],
						'url' => $data['url'],
						'addr' => $data['addr'],
						'photo' => $data['photo'],
						'keywords' => $data['keywords'],
						'location' => $data['location'],
						'about' => $data['about'],
						'notify' => $data['notify'],
						'network' => $data['network'],
						'server_url' => $data['baseurl']];

				$fieldnames = [];

				foreach ($fields as $key => $val) {
					if (empty($val)) {
						unset($fields[$key]);
					} else {
						$fieldnames[] = $key;
					}
				}

				$fields['updated'] = DBM::date();

				$condition = ['nurl' => normalise_link($data["url"])];

				$old_fields = dba::selectFirst('gcontact', $fieldnames, $condition);

				dba::update('gcontact', $fields, $condition, $old_fields);

				$fields = ['name' => $data['name'],
						'nick' => $data['nick'],
						'url' => $data['url'],
						'addr' => $data['addr'],
						'alias' => $data['alias'],
						'keywords' => $data['keywords'],
						'location' => $data['location'],
						'about' => $data['about'],
						'batch' => $data['batch'],
						'notify' => $data['notify'],
						'poll' => $data['poll'],
						'request' => $data['request'],
						'confirm' => $data['confirm'],
						'poco' => $data['poco'],
						'network' => $data['network'],
						'success_update' => DBM::date()];

				$fieldnames = [];

				foreach ($fields as $key => $val) {
					if (empty($val)) {
						unset($fields[$key]);
					} else {
						$fieldnames[] = $key;
					}
				}

				$condition = ['nurl' => normalise_link($data["url"]), 'self' => false, 'uid' => 0];

				$old_fields = dba::selectFirst('contact', $fieldnames, $condition);

				dba::update('contact', $fields, $condition, $old_fields);
			}
		}

		return $data;
	}

	/**
	 * @brief Switch the scheme of an url between http and https
	 *
	 * @param string $url URL
	 *
	 * @return string switched URL
	 */
	private static function switchScheme($url)
	{
		$parts = parse_url($url);

		if (!isset($parts['scheme'])) {
			return $url;
		}

		if ($parts['scheme'] == 'http') {
			$url = str_replace('http://', 'https://', $url);
		} elseif ($parts['scheme'] == 'https') {
			$url = str_replace('https://', 'http://', $url);
		}

		return $url;
	}

	/**
	 * @brief Checks if a profile url should be OStatus but only provides partial information
	 *
	 * @param array  $webfinger Webfinger data
	 * @param string $lrdd      Path template for webfinger request
	 * @param string $type      type
	 *
	 * @return array fixed webfinger data
	 */
	private static function fixOStatus($webfinger, $lrdd, $type)
	{
		if (empty($webfinger['links']) || empty($webfinger['subject'])) {
			return $webfinger;
		}

		$is_ostatus = false;
		$has_key = false;

		foreach ($webfinger['links'] as $link) {
			if ($link['rel'] == NAMESPACE_OSTATUSSUB) {
				$is_ostatus = true;
			}
			if ($link['rel'] == 'magic-public-key') {
				$has_key = true;
			}
		}

		if (!$is_ostatus || $has_key) {
			return $webfinger;
		}

		$url = self::switchScheme($webfinger['subject']);
		$path = str_replace('{uri}', urlencode($url), $lrdd);
		$webfinger2 = self::webfinger($path, $type);

		// Is the new webfinger detectable as OStatus?
		if (self::ostatus($webfinger2, true)) {
			$webfinger = $webfinger2;
		}

		return $webfinger;
	}

	/**
	 * @brief Fetch information (protocol endpoints and user information) about a given uri
	 *
	 * This function is only called by the "uri" function that adds caching and rearranging of data.
	 *
	 * @param string  $uri     Address that should be probed
	 * @param string  $network Test for this specific network
	 * @param integer $uid     User ID for the probe (only used for mails)
	 *
	 * @return array uri data
	 */
	private static function detect($uri, $network, $uid)
	{
		$parts = parse_url($uri);

		if (!empty($parts["scheme"]) && !empty($parts["host"]) && !empty($parts["path"])) {
			$host = $parts["host"];
			if (!empty($parts["port"])) {
				$host .= ':'.$parts["port"];
			}

			if ($host == 'twitter.com') {
				return ["network" => NETWORK_TWITTER];
			}
			$lrdd = self::hostMeta($host);

			if (is_bool($lrdd)) {
				return [];
			}

			$path_parts = explode("/", trim($parts["path"], "/"));

			while (!$lrdd && (sizeof($path_parts) > 1)) {
				$host .= "/".array_shift($path_parts);
				$lrdd = self::hostMeta($host);
			}
			if (!$lrdd) {
				logger('No XRD data was found for '.$uri, LOGGER_DEBUG);
				return self::feed($uri);
			}
			$nick = array_pop($path_parts);

			// Mastodon uses a "@" as prefix for usernames in their url format
			$nick = ltrim($nick, '@');

			$addr = $nick."@".$host;
		} elseif (strstr($uri, '@')) {
			// If the URI starts with "mailto:" then jump directly to the mail detection
			if (strpos($uri, 'mailto:') !== false) {
				$uri = str_replace('mailto:', '', $uri);
				return self::mail($uri, $uid);
			}

			if ($network == NETWORK_MAIL) {
				return self::mail($uri, $uid);
			}
			// Remove "acct:" from the URI
			$uri = str_replace('acct:', '', $uri);

			$host = substr($uri, strpos($uri, '@') + 1);
			$nick = substr($uri, 0, strpos($uri, '@'));

			if (strpos($uri, '@twitter.com')) {
				return ["network" => NETWORK_TWITTER];
			}
			$lrdd = self::hostMeta($host);

			if (is_bool($lrdd)) {
				return [];
			}

			if (!$lrdd) {
				logger('No XRD data was found for '.$uri, LOGGER_DEBUG);
				return self::mail($uri, $uid);
			}
			$addr = $uri;
		} else {
			logger("Uri ".$uri." was not detectable", LOGGER_DEBUG);
			return false;
		}

		$webfinger = false;

		/// @todo Do we need the prefix "acct:" or "acct://"?

		foreach ($lrdd as $type => $template) {
			if ($webfinger) {
				continue;
			}

			// At first try it with the given uri
			$path = str_replace('{uri}', urlencode($uri), $template);
			$webfinger = self::webfinger($path, $type);

			// Fix possible problems with GNU Social probing to wrong scheme
			$webfinger = self::fixOStatus($webfinger, $template, $type);

			// We cannot be sure that the detected address was correct, so we don't use the values
			if ($webfinger && ($uri != $addr)) {
				$nick = "";
				$addr = "";
			}

			// Try webfinger with the address (user@domain.tld)
			if (!$webfinger) {
				$path = str_replace('{uri}', urlencode($addr), $template);
				$webfinger = self::webfinger($path, $type);
			}

			// Mastodon needs to have it with "acct:"
			if (!$webfinger) {
				$path = str_replace('{uri}', urlencode("acct:".$addr), $template);
				$webfinger = self::webfinger($path, $type);
			}
		}

		if (!$webfinger) {
			return self::feed($uri);
		}

		$result = false;

		logger("Probing ".$uri, LOGGER_DEBUG);

		if (in_array($network, ["", NETWORK_DFRN])) {
			$result = self::dfrn($webfinger);
		}
		if ((!$result && ($network == "")) || ($network == NETWORK_DIASPORA)) {
			$result = self::diaspora($webfinger);
		}
		if ((!$result && ($network == "")) || ($network == NETWORK_OSTATUS)) {
			$result = self::ostatus($webfinger);
		}
		if ((!$result && ($network == "")) || ($network == NETWORK_PUMPIO)) {
			$result = self::pumpio($webfinger, $addr);
		}
		if ((!$result && ($network == "")) || ($network == NETWORK_FEED)) {
			$result = self::feed($uri);
		} else {
			// We overwrite the detected nick with our try if the previois routines hadn't detected it.
			// Additionally it is overwritten when the nickname doesn't make sense (contains spaces).
			if ((empty($result["nick"]) || (strstr($result["nick"], " "))) && ($nick != "")) {
				$result["nick"] = $nick;
			}

			if (empty($result["addr"]) && ($addr != "")) {
				$result["addr"] = $addr;
			}
		}

		logger($uri." is ".$result["network"], LOGGER_DEBUG);

		if (empty($result["baseurl"])) {
			$pos = strpos($result["url"], $host);
			if ($pos) {
				$result["baseurl"] = substr($result["url"], 0, $pos).$host;
			}
		}
		return $result;
	}

	/**
	 * @brief Perform a webfinger request.
	 *
	 * For details see RFC 7033: <https://tools.ietf.org/html/rfc7033>
	 *
	 * @param string $url  Address that should be probed
	 * @param string $type type
	 *
	 * @return array webfinger data
	 */
	private static function webfinger($url, $type)
	{
		$xrd_timeout = Config::get('system', 'xrd_timeout', 20);
		$redirects = 0;

		$ret = Network::curl($url, false, $redirects, ['timeout' => $xrd_timeout, 'accept_content' => $type]);
		if ($ret['errno'] == CURLE_OPERATION_TIMEDOUT) {
			return false;
		}
		$data = $ret['body'];

		$webfinger = json_decode($data, true);
		if (is_array($webfinger)) {
			if (!isset($webfinger["links"])) {
				logger("No json webfinger links for ".$url, LOGGER_DEBUG);
				return false;
			}
			return $webfinger;
		}

		// If it is not JSON, maybe it is XML
		$xrd = XML::parseString($data, false);
		if (!is_object($xrd)) {
			logger("No webfinger data retrievable for ".$url, LOGGER_DEBUG);
			return false;
		}

		$xrd_arr = XML::elementToArray($xrd);
		if (!isset($xrd_arr["xrd"]["link"])) {
			logger("No XML webfinger links for ".$url, LOGGER_DEBUG);
			return false;
		}

		$webfinger = [];

		if (!empty($xrd_arr["xrd"]["subject"])) {
			$webfinger["subject"] = $xrd_arr["xrd"]["subject"];
		}

		if (!empty($xrd_arr["xrd"]["alias"])) {
			$webfinger["aliases"] = $xrd_arr["xrd"]["alias"];
		}

		$webfinger["links"] = [];

		foreach ($xrd_arr["xrd"]["link"] as $value => $data) {
			if (!empty($data["@attributes"])) {
				$attributes = $data["@attributes"];
			} elseif ($value == "@attributes") {
				$attributes = $data;
			} else {
				continue;
			}

			$webfinger["links"][] = $attributes;
		}
		return $webfinger;
	}

	/**
	 * @brief Poll the Friendica specific noscrape page.
	 *
	 * "noscrape" is a faster alternative to fetch the data from the hcard.
	 * This functionality was originally created for the directory.
	 *
	 * @param string $noscrape_url Link to the noscrape page
	 * @param array  $data         The already fetched data
	 *
	 * @return array noscrape data
	 */
	private static function pollNoscrape($noscrape_url, $data)
	{
		$ret = Network::curl($noscrape_url);
		if ($ret['errno'] == CURLE_OPERATION_TIMEDOUT) {
			return false;
		}
		$content = $ret['body'];
		if (!$content) {
			logger("Empty body for ".$noscrape_url, LOGGER_DEBUG);
			return false;
		}

		$json = json_decode($content, true);
		if (!is_array($json)) {
			logger("No json data for ".$noscrape_url, LOGGER_DEBUG);
			return false;
		}

		if (!empty($json["fn"])) {
			$data["name"] = $json["fn"];
		}

		if (!empty($json["addr"])) {
			$data["addr"] = $json["addr"];
		}

		if (!empty($json["nick"])) {
			$data["nick"] = $json["nick"];
		}

		if (!empty($json["guid"])) {
			$data["guid"] = $json["guid"];
		}

		if (!empty($json["comm"])) {
			$data["community"] = $json["comm"];
		}

		if (!empty($json["tags"])) {
			$keywords = implode(" ", $json["tags"]);
			if ($keywords != "") {
				$data["keywords"] = $keywords;
			}
		}

		$location = Profile::formatLocation($json);
		if ($location) {
			$data["location"] = $location;
		}

		if (!empty($json["about"])) {
			$data["about"] = $json["about"];
		}

		if (!empty($json["key"])) {
			$data["pubkey"] = $json["key"];
		}

		if (!empty($json["photo"])) {
			$data["photo"] = $json["photo"];
		}

		if (!empty($json["dfrn-request"])) {
			$data["request"] = $json["dfrn-request"];
		}

		if (!empty($json["dfrn-confirm"])) {
			$data["confirm"] = $json["dfrn-confirm"];
		}

		if (!empty($json["dfrn-notify"])) {
			$data["notify"] = $json["dfrn-notify"];
		}

		if (!empty($json["dfrn-poll"])) {
			$data["poll"] = $json["dfrn-poll"];
		}

		return $data;
	}

	/**
	 * @brief Check for valid DFRN data
	 *
	 * @param array $data DFRN data
	 *
	 * @return int Number of errors
	 */
	public static function validDfrn($data)
	{
		$errors = 0;
		if (!isset($data['key'])) {
			$errors ++;
		}
		if (!isset($data['dfrn-request'])) {
			$errors ++;
		}
		if (!isset($data['dfrn-confirm'])) {
			$errors ++;
		}
		if (!isset($data['dfrn-notify'])) {
			$errors ++;
		}
		if (!isset($data['dfrn-poll'])) {
			$errors ++;
		}
		return $errors;
	}

	/**
	 * @brief Fetch data from a DFRN profile page and via "noscrape"
	 *
	 * @param string $profile_link Link to the profile page
	 *
	 * @return array profile data
	 */
	public static function profile($profile_link)
	{
		$data = [];

		logger("Check profile ".$profile_link, LOGGER_DEBUG);

		// Fetch data via noscrape - this is faster
		$noscrape_url = str_replace(["/hcard/", "/profile/"], "/noscrape/", $profile_link);
		$data = self::pollNoscrape($noscrape_url, $data);

		if (!isset($data["notify"])
			|| !isset($data["confirm"])
			|| !isset($data["request"])
			|| !isset($data["poll"])
			|| !isset($data["poco"])
			|| !isset($data["name"])
			|| !isset($data["photo"])
		) {
			$data = self::pollHcard($profile_link, $data, true);
		}

		$prof_data = [];
		$prof_data["addr"]         = $data["addr"];
		$prof_data["nick"]         = $data["nick"];
		$prof_data["dfrn-request"] = $data["request"];
		$prof_data["dfrn-confirm"] = $data["confirm"];
		$prof_data["dfrn-notify"]  = $data["notify"];
		$prof_data["dfrn-poll"]    = $data["poll"];
		$prof_data["dfrn-poco"]    = $data["poco"];
		$prof_data["photo"]        = $data["photo"];
		$prof_data["fn"]           = $data["name"];
		$prof_data["key"]          = $data["pubkey"];

		logger("Result for profile ".$profile_link.": ".print_r($prof_data, true), LOGGER_DEBUG);

		return $prof_data;
	}

	/**
	 * @brief Check for DFRN contact
	 *
	 * @param array $webfinger Webfinger data
	 *
	 * @return array DFRN data
	 */
	private static function dfrn($webfinger)
	{
		$hcard_url = "";
		$data = [];
		foreach ($webfinger["links"] as $link) {
			if (($link["rel"] == NAMESPACE_DFRN) && ($link["href"] != "")) {
				$data["network"] = NETWORK_DFRN;
			} elseif (($link["rel"] == NAMESPACE_FEED) && ($link["href"] != "")) {
				$data["poll"] = $link["href"];
			} elseif (($link["rel"] == "http://webfinger.net/rel/profile-page") && ($link["type"] == "text/html") && ($link["href"] != "")) {
				$data["url"] = $link["href"];
			} elseif (($link["rel"] == "http://microformats.org/profile/hcard") && ($link["href"] != "")) {
				$hcard_url = $link["href"];
			} elseif (($link["rel"] == NAMESPACE_POCO) && ($link["href"] != "")) {
				$data["poco"] = $link["href"];
			} elseif (($link["rel"] == "http://webfinger.net/rel/avatar") && ($link["href"] != "")) {
				$data["photo"] = $link["href"];
			} elseif (($link["rel"] == "http://joindiaspora.com/seed_location") && ($link["href"] != "")) {
				$data["baseurl"] = trim($link["href"], '/');
			} elseif (($link["rel"] == "http://joindiaspora.com/guid") && ($link["href"] != "")) {
				$data["guid"] = $link["href"];
			} elseif (($link["rel"] == "diaspora-public-key") && ($link["href"] != "")) {
				$data["pubkey"] = base64_decode($link["href"]);

				//if (strstr($data["pubkey"], 'RSA ') || ($link["type"] == "RSA"))
				if (strstr($data["pubkey"], 'RSA ')) {
					$data["pubkey"] = Crypto::rsaToPem($data["pubkey"]);
				}
			}
		}

		if (is_array($webfinger["aliases"])) {
			foreach ($webfinger["aliases"] as $alias) {
				if (normalise_link($alias) != normalise_link($data["url"]) && ! strstr($alias, "@")) {
					$data["alias"] = $alias;
				} elseif (substr($alias, 0, 5) == 'acct:') {
					$data["addr"] = substr($alias, 5);
				}
			}
		}

		if (substr($webfinger["subject"], 0, 5) == "acct:") {
			$data["addr"] = substr($webfinger["subject"], 5);
		}

		if (!isset($data["network"]) || ($hcard_url == "")) {
			return false;
		}

		// Fetch data via noscrape - this is faster
		$noscrape_url = str_replace("/hcard/", "/noscrape/", $hcard_url);
		$data = self::pollNoscrape($noscrape_url, $data);

		if (isset($data["notify"])
			&& isset($data["confirm"])
			&& isset($data["request"])
			&& isset($data["poll"])
			&& isset($data["name"])
			&& isset($data["photo"])
		) {
			return $data;
		}

		$data = self::pollHcard($hcard_url, $data, true);

		return $data;
	}

	/**
	 * @brief Poll the hcard page (Diaspora and Friendica specific)
	 *
	 * @param string  $hcard_url Link to the hcard page
	 * @param array   $data      The already fetched data
	 * @param boolean $dfrn      Poll DFRN specific data
	 *
	 * @return array hcard data
	 */
	private static function pollHcard($hcard_url, $data, $dfrn = false)
	{
		$ret = Network::curl($hcard_url);
		if ($ret['errno'] == CURLE_OPERATION_TIMEDOUT) {
			return false;
		}
		$content = $ret['body'];
		if (!$content) {
			return false;
		}

		$doc = new DOMDocument();
		if (!@$doc->loadHTML($content)) {
			return false;
		}

		$xpath = new DOMXPath($doc);

		$vcards = $xpath->query("//div[contains(concat(' ', @class, ' '), ' vcard ')]");
		if (!is_object($vcards)) {
			return false;
		}

		if ($vcards->length > 0) {
			$vcard = $vcards->item(0);

			// We have to discard the guid from the hcard in favour of the guid from lrdd
			// Reason: Hubzilla doesn't use the value "uid" in the hcard like Diaspora does.
			$search = $xpath->query("//*[contains(concat(' ', @class, ' '), ' uid ')]", $vcard); // */
			if (($search->length > 0) && ($data["guid"] == "")) {
				$data["guid"] = $search->item(0)->nodeValue;
			}

			$search = $xpath->query("//*[contains(concat(' ', @class, ' '), ' nickname ')]", $vcard); // */
			if ($search->length > 0) {
				$data["nick"] = $search->item(0)->nodeValue;
			}

			$search = $xpath->query("//*[contains(concat(' ', @class, ' '), ' fn ')]", $vcard); // */
			if ($search->length > 0) {
				$data["name"] = $search->item(0)->nodeValue;
			}

			$search = $xpath->query("//*[contains(concat(' ', @class, ' '), ' searchable ')]", $vcard); // */
			if ($search->length > 0) {
				$data["searchable"] = $search->item(0)->nodeValue;
			}

			$search = $xpath->query("//*[contains(concat(' ', @class, ' '), ' key ')]", $vcard); // */
			if ($search->length > 0) {
				$data["pubkey"] = $search->item(0)->nodeValue;
				if (strstr($data["pubkey"], 'RSA ')) {
					$data["pubkey"] = Crypto::rsaToPem($data["pubkey"]);
				}
			}

			$search = $xpath->query("//*[@id='pod_location']", $vcard); // */
			if ($search->length > 0) {
				$data["baseurl"] = trim($search->item(0)->nodeValue, "/");
			}
		}

		$avatar = [];
		$photos = $xpath->query("//*[contains(concat(' ', @class, ' '), ' photo ') or contains(concat(' ', @class, ' '), ' avatar ')]", $vcard); // */
		foreach ($photos as $photo) {
			$attr = [];
			foreach ($photo->attributes as $attribute) {
				$attr[$attribute->name] = trim($attribute->value);
			}

			if (isset($attr["src"]) && isset($attr["width"])) {
				$avatar[$attr["width"]] = $attr["src"];
			}

			// We don't have a width. So we just take everything that we got.
			// This is a Hubzilla workaround which doesn't send a width.
			if ((sizeof($avatar) == 0) && !empty($attr["src"])) {
				$avatar[] = $attr["src"];
			}
		}

		if (sizeof($avatar)) {
			ksort($avatar);
			$data["photo"] = self::fixAvatar(array_pop($avatar), $data["baseurl"]);
		}

		if ($dfrn) {
			// Poll DFRN specific data
			$search = $xpath->query("//link[contains(concat(' ', @rel), ' dfrn-')]");
			if ($search->length > 0) {
				foreach ($search as $link) {
					//$data["request"] = $search->item(0)->nodeValue;
					$attr = [];
					foreach ($link->attributes as $attribute) {
						$attr[$attribute->name] = trim($attribute->value);
					}

					$data[substr($attr["rel"], 5)] = $attr["href"];
				}
			}

			// Older Friendica versions had used the "uid" field differently than newer versions
			if ($data["nick"] == $data["guid"]) {
				unset($data["guid"]);
			}
		}


		return $data;
	}

	/**
	 * @brief Check for Diaspora contact
	 *
	 * @param array $webfinger Webfinger data
	 *
	 * @return array Diaspora data
	 */
	private static function diaspora($webfinger)
	{
		$hcard_url = "";
		$data = [];
		foreach ($webfinger["links"] as $link) {
			if (($link["rel"] == "http://microformats.org/profile/hcard") && ($link["href"] != "")) {
				$hcard_url = $link["href"];
			} elseif (($link["rel"] == "http://joindiaspora.com/seed_location") && ($link["href"] != "")) {
				$data["baseurl"] = trim($link["href"], '/');
			} elseif (($link["rel"] == "http://joindiaspora.com/guid") && ($link["href"] != "")) {
				$data["guid"] = $link["href"];
			} elseif (($link["rel"] == "http://webfinger.net/rel/profile-page") && ($link["type"] == "text/html") && ($link["href"] != "")) {
				$data["url"] = $link["href"];
			} elseif (($link["rel"] == NAMESPACE_FEED) && ($link["href"] != "")) {
				$data["poll"] = $link["href"];
			} elseif (($link["rel"] == NAMESPACE_POCO) && ($link["href"] != "")) {
				$data["poco"] = $link["href"];
			} elseif (($link["rel"] == "salmon") && ($link["href"] != "")) {
				$data["notify"] = $link["href"];
			} elseif (($link["rel"] == "diaspora-public-key") && ($link["href"] != "")) {
				$data["pubkey"] = base64_decode($link["href"]);

				//if (strstr($data["pubkey"], 'RSA ') || ($link["type"] == "RSA"))
				if (strstr($data["pubkey"], 'RSA ')) {
					$data["pubkey"] = Crypto::rsaToPem($data["pubkey"]);
				}
			}
		}

		if (!isset($data["url"]) || ($hcard_url == "")) {
			return false;
		}

		if (is_array($webfinger["aliases"])) {
			foreach ($webfinger["aliases"] as $alias) {
				if (normalise_link($alias) != normalise_link($data["url"]) && ! strstr($alias, "@")) {
					$data["alias"] = $alias;
				} elseif (substr($alias, 0, 5) == 'acct:') {
					$data["addr"] = substr($alias, 5);
				}
			}
		}

		if (!empty($webfinger["subject"]) && (substr($webfinger["subject"], 0, 5) == 'acct:')) {
			$data["addr"] = substr($webfinger["subject"], 5);
		}

		// Fetch further information from the hcard
		$data = self::pollHcard($hcard_url, $data);

		if (!$data) {
			return false;
		}

		if (isset($data["url"])
			&& isset($data["guid"])
			&& isset($data["baseurl"])
			&& isset($data["pubkey"])
			&& ($hcard_url != "")
		) {
			$data["network"] = NETWORK_DIASPORA;

			// The Diaspora handle must always be lowercase
			$data["addr"] = strtolower($data["addr"]);

			// We have to overwrite the detected value for "notify" since Hubzilla doesn't send it
			$data["notify"] = $data["baseurl"] . "/receive/users/" . $data["guid"];
			$data["batch"]  = $data["baseurl"] . "/receive/public";
		} else {
			return false;
		}

		return $data;
	}

	/**
	 * @brief Check for OStatus contact
	 *
	 * @param array $webfinger Webfinger data
	 * @param bool  $short     Short detection mode
	 *
	 * @return array|bool OStatus data or "false" on error or "true" on short mode
	 */
	private static function ostatus($webfinger, $short = false)
	{
		$data = [];

		if (is_array($webfinger["aliases"])) {
			foreach ($webfinger["aliases"] as $alias) {
				if (strstr($alias, "@") && !strstr(normalise_link($alias), "http://")) {
					$data["addr"] = str_replace('acct:', '', $alias);
				}
			}
		}

		if (is_string($webfinger["subject"]) && strstr($webfinger["subject"], "@")
			&& !strstr(normalise_link($webfinger["subject"]), "http://")
		) {
			$data["addr"] = str_replace('acct:', '', $webfinger["subject"]);
		}

		$pubkey = "";
		if (is_array($webfinger["links"])) {
			foreach ($webfinger["links"] as $link) {
				if (($link["rel"] == "http://webfinger.net/rel/profile-page")
					&& ($link["type"] == "text/html")
					&& ($link["href"] != "")
				) {
					$data["url"] = $link["href"];
				} elseif (($link["rel"] == "salmon") && ($link["href"] != "")) {
					$data["notify"] = $link["href"];
				} elseif (($link["rel"] == NAMESPACE_FEED) && ($link["href"] != "")) {
					$data["poll"] = $link["href"];
				} elseif (($link["rel"] == "magic-public-key") && ($link["href"] != "")) {
					$pubkey = $link["href"];

					if (substr($pubkey, 0, 5) === 'data:') {
						if (strstr($pubkey, ',')) {
							$pubkey = substr($pubkey, strpos($pubkey, ',') + 1);
						} else {
							$pubkey = substr($pubkey, 5);
						}
					} elseif (normalise_link($pubkey) == 'http://') {
						$ret = Network::curl($pubkey);
						if ($ret['errno'] == CURLE_OPERATION_TIMEDOUT) {
							return false;
						}
						$pubkey = $ret['body'];
					}

					$key = explode(".", $pubkey);

					if (sizeof($key) >= 3) {
						$m = base64url_decode($key[1]);
						$e = base64url_decode($key[2]);
						$data["pubkey"] = Crypto::meToPem($m, $e);
					}
				}
			}
		}

		if (isset($data["notify"]) && isset($data["pubkey"])
			&& isset($data["poll"])
			&& isset($data["url"])
		) {
			$data["network"] = NETWORK_OSTATUS;
		} else {
			return false;
		}

		if ($short) {
			return true;
		}

		// Fetch all additional data from the feed
		$ret = Network::curl($data["poll"]);
		if ($ret['errno'] == CURLE_OPERATION_TIMEDOUT) {
			return false;
		}
		$feed = $ret['body'];
		$feed_data = Feed::import($feed, $dummy1, $dummy2, $dummy3, true);
		if (!$feed_data) {
			return false;
		}

		if ($feed_data["header"]["author-name"] != "") {
			$data["name"] = $feed_data["header"]["author-name"];
		}
		if ($feed_data["header"]["author-nick"] != "") {
			$data["nick"] = $feed_data["header"]["author-nick"];
		}
		if ($feed_data["header"]["author-avatar"] != "") {
			$data["photo"] = self::fixAvatar($feed_data["header"]["author-avatar"], $data["url"]);
		}
		if ($feed_data["header"]["author-id"] != "") {
			$data["alias"] = $feed_data["header"]["author-id"];
		}
		if ($feed_data["header"]["author-location"] != "") {
			$data["location"] = $feed_data["header"]["author-location"];
		}
		if ($feed_data["header"]["author-about"] != "") {
			$data["about"] = $feed_data["header"]["author-about"];
		}
		// OStatus has serious issues when the the url doesn't fit (ssl vs. non ssl)
		// So we take the value that we just fetched, although the other one worked as well
		if ($feed_data["header"]["author-link"] != "") {
			$data["url"] = $feed_data["header"]["author-link"];
		}

		if (($data['poll'] == $data['url']) && ($data["alias"] != '')) {
			$data['url'] = $data["alias"];
			$data["alias"] = '';
		}

		/// @todo Fetch location and "about" from the feed as well
		return $data;
	}

	/**
	 * @brief Fetch data from a pump.io profile page
	 *
	 * @param string $profile_link Link to the profile page
	 *
	 * @return array profile data
	 */
	private static function pumpioProfileData($profile_link)
	{
		$doc = new DOMDocument();
		if (!@$doc->loadHTMLFile($profile_link)) {
			return false;
		}

		$xpath = new DOMXPath($doc);

		$data = [];

		$data["name"] = $xpath->query("//span[contains(@class, 'p-name')]")->item(0)->nodeValue;

		if ($data["name"] == '') {
			// This is ugly - but pump.io doesn't seem to know a better way for it
			$data["name"] = trim($xpath->query("//h1[@class='media-header']")->item(0)->nodeValue);
			$pos = strpos($data["name"], chr(10));
			if ($pos) {
				$data["name"] = trim(substr($data["name"], 0, $pos));
			}
		}

		$data["location"] = $xpath->query("//p[contains(@class, 'p-locality')]")->item(0)->nodeValue;

		if ($data["location"] == '') {
			$data["location"] = $xpath->query("//p[contains(@class, 'location')]")->item(0)->nodeValue;
		}

		$data["about"] = $xpath->query("//p[contains(@class, 'p-note')]")->item(0)->nodeValue;

		if ($data["about"] == '') {
			$data["about"] = $xpath->query("//p[contains(@class, 'summary')]")->item(0)->nodeValue;
		}

		$avatar = $xpath->query("//img[contains(@class, 'u-photo')]")->item(0);
		if (!$avatar) {
			$avatar = $xpath->query("//img[@class='img-rounded media-object']")->item(0);
		}
		if ($avatar) {
			foreach ($avatar->attributes as $attribute) {
				if ($attribute->name == "src") {
					$data["photo"] = trim($attribute->value);
				}
			}
		}

		return $data;
	}

	/**
	 * @brief Check for pump.io contact
	 *
	 * @param array $webfinger Webfinger data
	 *
	 * @return array pump.io data
	 */
	private static function pumpio($webfinger, $addr)
	{
		$data = [];
		foreach ($webfinger["links"] as $link) {
			if (($link["rel"] == "http://webfinger.net/rel/profile-page")
				&& ($link["type"] == "text/html")
				&& ($link["href"] != "")
			) {
				$data["url"] = $link["href"];
			} elseif (($link["rel"] == "activity-inbox") && ($link["href"] != "")) {
				$data["notify"] = $link["href"];
			} elseif (($link["rel"] == "activity-outbox") && ($link["href"] != "")) {
				$data["poll"] = $link["href"];
			} elseif (($link["rel"] == "dialback") && ($link["href"] != "")) {
				$data["dialback"] = $link["href"];
			}
		}
		if (isset($data["poll"]) && isset($data["notify"])
			&& isset($data["dialback"])
			&& isset($data["url"])
		) {
			// by now we use these fields only for the network type detection
			// So we unset all data that isn't used at the moment
			unset($data["dialback"]);

			$data["network"] = NETWORK_PUMPIO;
		} else {
			return false;
		}

		$profile_data = self::pumpioProfileData($data["url"]);

		if (!$profile_data) {
			return false;
		}

		$data = array_merge($data, $profile_data);

		if (($addr != '') && ($data['name'] != '')) {
			$name = trim(str_replace($addr, '', $data['name']));
			if ($name != '') {
				$data['name'] = $name;
			}
		}

		return $data;
	}

	/**
	 * @brief Check page for feed link
	 *
	 * @param string $url Page link
	 *
	 * @return string feed link
	 */
	private static function getFeedLink($url)
	{
		$doc = new DOMDocument();

		if (!@$doc->loadHTMLFile($url)) {
			return false;
		}

		$xpath = new DOMXPath($doc);

		//$feeds = $xpath->query("/html/head/link[@type='application/rss+xml']");
		$feeds = $xpath->query("/html/head/link[@type='application/rss+xml' and @rel='alternate']");
		if (!is_object($feeds)) {
			return false;
		}

		if ($feeds->length == 0) {
			return false;
		}

		$feed_url = "";

		foreach ($feeds as $feed) {
			$attr = [];
			foreach ($feed->attributes as $attribute) {
				$attr[$attribute->name] = trim($attribute->value);
			}

			if ($feed_url == "") {
				$feed_url = $attr["href"];
			}
		}

		return $feed_url;
	}

	/**
	 * @brief Check for feed contact
	 *
	 * @param string  $url   Profile link
	 * @param boolean $probe Do a probe if the page contains a feed link
	 *
	 * @return array feed data
	 */
	private static function feed($url, $probe = true)
	{
		$ret = Network::curl($url);
		if ($ret['errno'] == CURLE_OPERATION_TIMEDOUT) {
			return false;
		}
		$feed = $ret['body'];
		$feed_data = Feed::import($feed, $dummy1, $dummy2, $dummy3, true);

		if (!$feed_data) {
			if (!$probe) {
				return false;
			}

			$feed_url = self::getFeedLink($url);

			if (!$feed_url) {
				return false;
			}

			return self::feed($feed_url, false);
		}

		if ($feed_data["header"]["author-name"] != "") {
			$data["name"] = $feed_data["header"]["author-name"];
		}

		if ($feed_data["header"]["author-nick"] != "") {
			$data["nick"] = $feed_data["header"]["author-nick"];
		}

		if ($feed_data["header"]["author-avatar"] != "") {
			$data["photo"] = $feed_data["header"]["author-avatar"];
		}

		if ($feed_data["header"]["author-id"] != "") {
			$data["alias"] = $feed_data["header"]["author-id"];
		}

		$data["url"] = $url;
		$data["poll"] = $url;

		if ($feed_data["header"]["author-link"] != "") {
			$data["baseurl"] = $feed_data["header"]["author-link"];
		} else {
			$data["baseurl"] = $data["url"];
		}

		$data["network"] = NETWORK_FEED;

		return $data;
	}

	/**
	 * @brief Check for mail contact
	 *
	 * @param string  $uri Profile link
	 * @param integer $uid User ID
	 *
	 * @return array mail data
	 */
	private static function mail($uri, $uid)
	{
		if (!Network::isEmailDomainValid($uri)) {
			return false;
		}

		if ($uid == 0) {
			return false;
		}

		$x = q("SELECT `prvkey` FROM `user` WHERE `uid` = %d LIMIT 1", intval($uid));

		$r = q("SELECT * FROM `mailacct` WHERE `uid` = %d AND `server` != '' LIMIT 1", intval($uid));

		if (DBM::is_result($x) && DBM::is_result($r)) {
			$mailbox = Email::constructMailboxName($r[0]);
			$password = '';
			Crypto::opensslPrivateDecrypt(hex2bin($r[0]['pass']), $password, $x[0]['prvkey']);
			$mbox = Email::connect($mailbox, $r[0]['user'], $password);
			if (!$mbox) {
				return false;
			}
		}

		$msgs = Email::poll($mbox, $uri);
		logger('searching '.$uri.', '.count($msgs).' messages found.', LOGGER_DEBUG);

		if (!count($msgs)) {
			return false;
		}

		$phost = substr($uri, strpos($uri, '@') + 1);

		$data = [];
		$data["addr"]    = $uri;
		$data["network"] = NETWORK_MAIL;
		$data["name"]    = substr($uri, 0, strpos($uri, '@'));
		$data["nick"]    = $data["name"];
		$data["photo"]   = Network::lookupAvatarByEmail($uri);
		$data["url"]     = 'mailto:'.$uri;
		$data["notify"]  = 'smtp '.random_string();
		$data["poll"]    = 'email '.random_string();

		$x = Email::messageMeta($mbox, $msgs[0]);
		if (stristr($x[0]->from, $uri)) {
			$adr = imap_rfc822_parse_adrlist($x[0]->from, '');
		} elseif (stristr($x[0]->to, $uri)) {
			$adr = imap_rfc822_parse_adrlist($x[0]->to, '');
		}
		if (isset($adr)) {
			foreach ($adr as $feadr) {
				if ((strcasecmp($feadr->mailbox, $data["name"]) == 0)
					&&(strcasecmp($feadr->host, $phost) == 0)
					&& (strlen($feadr->personal))
				) {
					$personal = imap_mime_header_decode($feadr->personal);
					$data["name"] = "";
					foreach ($personal as $perspart) {
						if ($perspart->charset != "default") {
							$data["name"] .= iconv($perspart->charset, 'UTF-8//IGNORE', $perspart->text);
						} else {
							$data["name"] .= $perspart->text;
						}
					}

					$data["name"] = notags($data["name"]);
				}
			}
		}
		if (!empty($mbox)) {
			imap_close($mbox);
		}

		return $data;
	}

	/**
	 * @brief Mix two paths together to possibly fix missing parts
	 *
	 * @param string $avatar Path to the avatar
	 * @param string $base   Another path that is hopefully complete
	 *
	 * @return string fixed avatar path
	 */
	public static function fixAvatar($avatar, $base)
	{
		$base_parts = parse_url($base);

		// Remove all parts that could create a problem
		unset($base_parts['path']);
		unset($base_parts['query']);
		unset($base_parts['fragment']);

		$avatar_parts = parse_url($avatar);

		// Now we mix them
		$parts = array_merge($base_parts, $avatar_parts);

		// And put them together again
		$scheme   = isset($parts['scheme'])   ? $parts['scheme'] . '://' : '';
		$host     = isset($parts['host'])     ? $parts['host']           : '';
		$port     = isset($parts['port'])     ? ':' . $parts['port']     : '';
		$path     = isset($parts['path'])     ? $parts['path']           : '';
		$query    = isset($parts['query'])    ? '?' . $parts['query']    : '';
		$fragment = isset($parts['fragment']) ? '#' . $parts['fragment'] : '';

		$fixed = $scheme.$host.$port.$path.$query.$fragment;

		logger('Base: '.$base.' - Avatar: '.$avatar.' - Fixed: '.$fixed, LOGGER_DATA);

		return $fixed;
	}
}
