<?php
/**
 * @file include/Probe.php
 * @brief Functions for probing URL
 *
 */

use \Friendica\Core\Config;
use \Friendica\Core\PConfig;

require_once("include/feed.php");
require_once('include/email.php');
require_once('include/network.php');

/**
 * @brief This class contain functions for probing URL
 *
 */
class Probe {

	/**
	 * @brief Rearrange the array so that it always has the same order
	 *
	 * @param array $data Unordered data
	 *
	 * @return array Ordered data
	 */
	private function rearrange_data($data) {
		$fields = array("name", "nick", "guid", "url", "addr", "alias",
				"photo", "community", "keywords", "location", "about",
				"batch", "notify", "poll", "request", "confirm", "poco",
				"priority", "network", "pubkey", "baseurl");

		$newdata = array();
		foreach ($fields AS $field)
			if (isset($data[$field]))
				$newdata[$field] = $data[$field];
			else
				$newdata[$field] = "";

		// We don't use the "priority" field anymore and replace it with a dummy.
		$newdata["priority"] = 0;

		return $newdata;
	}

	/**
	 * @brief Probes for XRD data
	 *
	 * @return array
	 *      'lrdd' => Link to LRDD endpoint
	 *      'lrdd-xml' => Link to LRDD endpoint in XML format
	 *      'lrdd-json' => Link to LRDD endpoint in JSON format
	 */
	private function xrd($host) {

		$ssl_url = "https://".$host."/.well-known/host-meta";
		$url = "http://".$host."/.well-known/host-meta";

		$xrd_timeout = Config::get('system','xrd_timeout', 20);
		$redirects = 0;

		$xml = fetch_url($ssl_url, false, $redirects, $xrd_timeout, "application/xrd+xml");
		$xrd = parse_xml_string($xml, false);

		if (!is_object($xrd)) {
			$xml = fetch_url($url, false, $redirects, $xrd_timeout, "application/xrd+xml");
			$xrd = parse_xml_string($xml, false);
		}
		if (!is_object($xrd))
			return false;

		$links = xml::element_to_array($xrd);
		if (!isset($links["xrd"]["link"]))
			return false;

		$xrd_data = array();

		foreach ($links["xrd"]["link"] AS $value => $link) {
			if (isset($link["@attributes"]))
				$attributes = $link["@attributes"];
			elseif ($value == "@attributes")
				$attributes = $link;
			else
				continue;

			if (($attributes["rel"] == "lrdd") AND
				($attributes["type"] == "application/xrd+xml"))
				$xrd_data["lrdd-xml"] = $attributes["template"];
			elseif (($attributes["rel"] == "lrdd") AND
				($attributes["type"] == "application/json"))
				$xrd_data["lrdd-json"] = $attributes["template"];
			elseif ($attributes["rel"] == "lrdd")
				$xrd_data["lrdd"] = $attributes["template"];
		}
		return $xrd_data;
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
	 * @param string $webbie Address that should be probed
	 * @param string $hcard Link to the hcard - is returned by reference
	 *
	 * @return string profile link
	 */

	public static function webfinger_dfrn($webbie, &$hcard) {

		$profile_link = '';

		$links = self::lrdd($webbie);
		logger('webfinger_dfrn: '.$webbie.':'.print_r($links,true), LOGGER_DATA);
		if (count($links)) {
			foreach ($links as $link) {
				if ($link['@attributes']['rel'] === NAMESPACE_DFRN)
					$profile_link = $link['@attributes']['href'];
				if (($link['@attributes']['rel'] === NAMESPACE_OSTATUSSUB) AND ($profile_link == ""))
					$profile_link = 'stat:'.$link['@attributes']['template'];
				if ($link['@attributes']['rel'] === 'http://microformats.org/profile/hcard')
					$hcard = $link['@attributes']['href'];
			}
		}
		return $profile_link;
	}

	/**
	 * @brief Check an URI for LRDD data
	 *
	 * this is a replacement for the "lrdd" function in include/network.php.
	 * It isn't used in this class and has some redundancies in the code.
	 * When time comes we can check the existing calls for "lrdd" if we can rework them.
	 *
	 * @param string $uri Address that should be probed
	 *
	 * @return array uri data
	 */
	public static function lrdd($uri) {

		$lrdd = self::xrd($uri);

		if (!$lrdd) {
			$parts = @parse_url($uri);
			if (!$parts)
				return array();

			$host = $parts["host"];

			$path_parts = explode("/", trim($parts["path"], "/"));

			do {
				$lrdd = self::xrd($host);
				$host .= "/".array_shift($path_parts);
			} while (!$lrdd AND (sizeof($path_parts) > 0));
		}

		if (!$lrdd)
			return array();

		foreach ($lrdd AS $key => $link) {
			if ($webfinger)
				continue;

			if (!in_array($key, array("lrdd", "lrdd-xml", "lrdd-json")))
				continue;

			$path = str_replace('{uri}', urlencode($uri), $link);
			$webfinger = self::webfinger($path);

			if (!$webfinger AND (strstr($uri, "@"))) {
				$path = str_replace('{uri}', urlencode("acct:".$uri), $link);
				$webfinger = self::webfinger($path);
			}
		}

		if (!is_array($webfinger["links"]))
			return false;

		$data = array();

		foreach ($webfinger["links"] AS $link)
			$data[] = array("@attributes" => $link);

		if (is_array($webfinger["aliases"]))
			foreach ($webfinger["aliases"] AS $alias)
				$data[] = array("@attributes" =>
							array("rel" => "alias",
								"href" => $alias));

		return $data;
	}

	/**
	 * @brief Fetch information (protocol endpoints and user information) about a given uri
	 *
	 * @param string $uri Address that should be probed
	 * @param string $network Test for this specific network
	 * @param integer $uid User ID for the probe (only used for mails)
	 * @param boolean $cache Use cached values?
	 *
	 * @return array uri data
	 */
	public static function uri($uri, $network = "", $uid = 0, $cache = true) {

		if ($cache) {
			$result = Cache::get("probe_url:".$network.":".$uri);
			if (!is_null($result)) {
				$result = unserialize($result);
				return $result;
			}
		}

		if ($uid == 0)
			$uid = local_user();

		$data = self::detect($uri, $network, $uid);

		if (!isset($data["url"]))
			$data["url"] = $uri;

		if ($data["photo"] != "")
			$data["baseurl"] = matching_url(normalise_link($data["baseurl"]), normalise_link($data["photo"]));
		else
			$data["photo"] = App::get_baseurl().'/images/person-175.jpg';

		if (!isset($data["name"]) OR ($data["name"] == "")) {
			if (isset($data["nick"]))
				$data["name"] = $data["nick"];

			if ($data["name"] == "")
				$data["name"] = $data["url"];
		}

		if (!isset($data["nick"]) OR ($data["nick"] == "")) {
			$data["nick"] = strtolower($data["name"]);

			if (strpos($data['nick'], ' '))
				$data['nick'] = trim(substr($data['nick'], 0, strpos($data['nick'], ' ')));
		}

		if (!isset($data["network"]))
			$data["network"] = NETWORK_PHANTOM;

		$data = self::rearrange_data($data);

		// Only store into the cache if the value seems to be valid
		if (!in_array($data['network'], array(NETWORK_PHANTOM, NETWORK_MAIL))) {
			Cache::set("probe_url:".$network.":".$uri,serialize($data), CACHE_DAY);

			/// @todo temporary fix - we need a real contact update function that updates only changing fields
			/// The biggest problem is the avatar picture that could have a reduced image size.
			/// It should only be updated if the existing picture isn't existing anymore.
			if (($data['network'] != NETWORK_FEED) AND ($mode == PROBE_NORMAL) AND
				$data["name"] AND $data["nick"] AND $data["url"] AND $data["addr"] AND $data["poll"])
				q("UPDATE `contact` SET `name` = '%s', `nick` = '%s', `url` = '%s', `addr` = '%s',
						`notify` = '%s', `poll` = '%s', `alias` = '%s', `success_update` = '%s'
					WHERE `nurl` = '%s' AND NOT `self` AND `uid` = 0",
					dbesc($data["name"]),
					dbesc($data["nick"]),
					dbesc($data["url"]),
					dbesc($data["addr"]),
					dbesc($data["notify"]),
					dbesc($data["poll"]),
					dbesc($data["alias"]),
					dbesc(datetime_convert()),
					dbesc(normalise_link($data['url']))
			);
		}
		return $data;
	}

	/**
	 * @brief Fetch information (protocol endpoints and user information) about a given uri
	 *
	 * This function is only called by the "uri" function that adds caching and rearranging of data.
	 *
	 * @param string $uri Address that should be probed
	 * @param string $network Test for this specific network
	 * @param integer $uid User ID for the probe (only used for mails)
	 *
	 * @return array uri data
	 */
	private function detect($uri, $network, $uid) {
		if (strstr($uri, '@')) {
			// If the URI starts with "mailto:" then jump directly to the mail detection
			if (strpos($url,'mailto:') !== false) {
				$uri = str_replace('mailto:', '', $url);
				return self::mail($uri, $uid);
			}

			if ($network == NETWORK_MAIL)
				return self::mail($uri, $uid);

			// Remove "acct:" from the URI
			$uri = str_replace('acct:', '', $uri);

			$host = substr($uri,strpos($uri, '@') + 1);
			$nick = substr($uri,0, strpos($uri, '@'));

			if (strpos($uri, '@twitter.com'))
				return array("network" => NETWORK_TWITTER);

			$lrdd = self::xrd($host);

			if (!$lrdd)
				return self::mail($uri, $uid);

			$addr = $uri;
		} else {
			$parts = parse_url($uri);
			if (!isset($parts["scheme"]) OR
				!isset($parts["host"]) OR
				!isset($parts["path"]))
				return false;

			// todo: Ports?
			$host = $parts["host"];

			if ($host == 'twitter.com')
				return array("network" => NETWORK_TWITTER);

			$lrdd = self::xrd($host);

			$path_parts = explode("/", trim($parts["path"], "/"));

			while (!$lrdd AND (sizeof($path_parts) > 1)) {
				$host .= "/".array_shift($path_parts);
				$lrdd = self::xrd($host);
			}
			if (!$lrdd)
				return self::feed($uri);

			$nick = array_pop($path_parts);
			$addr = $nick."@".$host;
		}
		$webfinger = false;

		/// @todo Do we need the prefix "acct:" or "acct://"?

		foreach ($lrdd AS $key => $link) {
			if ($webfinger)
				continue;

			if (!in_array($key, array("lrdd", "lrdd-xml", "lrdd-json")))
				continue;

			// Try webfinger with the address (user@domain.tld)
			$path = str_replace('{uri}', urlencode($addr), $link);
			$webfinger = self::webfinger($path);

			// Mastodon needs to have it with "acct:"
			if (!$webfinger) {
				$path = str_replace('{uri}', urlencode("acct:".$addr), $link);
				$webfinger = self::webfinger($path);
			}

			// If webfinger wasn't successful then try it with the URL - possibly in the format https://...
			if (!$webfinger AND ($uri != $addr)) {
				$path = str_replace('{uri}', urlencode($uri), $link);
				$webfinger = self::webfinger($path);

				// Since the detection with the address wasn't successful, we delete it.
				if ($webfinger) {
					$nick = "";
					$addr = "";
				}
			}

		}
		if (!$webfinger)
			return self::feed($uri);

		$result = false;

		logger("Probing ".$uri, LOGGER_DEBUG);

		if (in_array($network, array("", NETWORK_DFRN)))
			$result = self::dfrn($webfinger);
		if ((!$result AND ($network == "")) OR ($network == NETWORK_DIASPORA))
			$result = self::diaspora($webfinger);
		if ((!$result AND ($network == "")) OR ($network == NETWORK_OSTATUS))
			$result = self::ostatus($webfinger);
		if ((!$result AND ($network == "")) OR ($network == NETWORK_PUMPIO))
			$result = self::pumpio($webfinger);
		if ((!$result AND ($network == "")) OR ($network == NETWORK_FEED))
			$result = self::feed($uri);
		else {
			// We overwrite the detected nick with our try if the previois routines hadn't detected it.
			// Additionally it is overwritten when the nickname doesn't make sense (contains spaces).
			if ((!isset($result["nick"]) OR ($result["nick"] == "") OR (strstr($result["nick"], " "))) AND ($nick != ""))
				$result["nick"] = $nick;

			if ((!isset($result["addr"]) OR ($result["addr"] == "")) AND ($addr != ""))
				$result["addr"] = $addr;
		}

		logger($uri." is ".$result["network"], LOGGER_DEBUG);

		if (!isset($result["baseurl"]) OR ($result["baseurl"] == "")) {
			$pos = strpos($result["url"], $host);
			if ($pos)
				$result["baseurl"] = substr($result["url"], 0, $pos).$host;
		}

		return $result;
	}

	/**
	 * @brief Perform a webfinger request.
	 *
	 * For details see RFC 7033: <https://tools.ietf.org/html/rfc7033>
	 *
	 * @param string $url Address that should be probed
	 *
	 * @return array webfinger data
	 */
	private function webfinger($url) {

		$xrd_timeout = Config::get('system','xrd_timeout', 20);
		$redirects = 0;

		$data = fetch_url($url, false, $redirects, $xrd_timeout, "application/xrd+xml");
		$xrd = parse_xml_string($data, false);

		if (!is_object($xrd)) {
			// If it is not XML, maybe it is JSON
			$webfinger = json_decode($data, true);

			if (!isset($webfinger["links"]))
				return false;

			return $webfinger;
		}

		$xrd_arr = xml::element_to_array($xrd);
		if (!isset($xrd_arr["xrd"]["link"]))
			return false;

		$webfinger = array();

		if (isset($xrd_arr["xrd"]["subject"]))
			$webfinger["subject"] = $xrd_arr["xrd"]["subject"];

		if (isset($xrd_arr["xrd"]["alias"]))
			$webfinger["aliases"] = $xrd_arr["xrd"]["alias"];

		$webfinger["links"] = array();

		foreach ($xrd_arr["xrd"]["link"] AS $value => $data) {
			if (isset($data["@attributes"]))
				$attributes = $data["@attributes"];
			elseif ($value == "@attributes")
				$attributes = $data;
			else
				continue;

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
	 * @param string $noscrape Link to the noscrape page
	 * @param array $data The already fetched data
	 *
	 * @return array noscrape data
	 */
	private function poll_noscrape($noscrape, $data) {
		$content = fetch_url($noscrape);
		if (!$content)
			return false;

		$json = json_decode($content, true);
		if (!is_array($json))
			return false;

		if (isset($json["fn"]))
			$data["name"] = $json["fn"];

		if (isset($json["addr"]))
			$data["addr"] = $json["addr"];

		if (isset($json["nick"]))
			$data["nick"] = $json["nick"];

		if (isset($json["comm"]))
			$data["community"] = $json["comm"];

		if (isset($json["tags"])) {
			$keywords = implode(" ", $json["tags"]);
			if ($keywords != "")
				$data["keywords"] = $keywords;
		}

		$location = formatted_location($json);
		if ($location)
			$data["location"] = $location;

		if (isset($json["about"]))
			$data["about"] = $json["about"];

		if (isset($json["key"]))
			$data["pubkey"] = $json["key"];

		if (isset($json["photo"]))
			$data["photo"] = $json["photo"];

		if (isset($json["dfrn-request"]))
			$data["request"] = $json["dfrn-request"];

		if (isset($json["dfrn-confirm"]))
			$data["confirm"] = $json["dfrn-confirm"];

		if (isset($json["dfrn-notify"]))
			$data["notify"] = $json["dfrn-notify"];

		if (isset($json["dfrn-poll"]))
			$data["poll"] = $json["dfrn-poll"];

		return $data;
	}

	/**
	 * @brief Check for valid DFRN data
	 *
	 * @param array $data DFRN data
	 *
	 * @return int Number of errors
	 */
	public static function valid_dfrn($data) {
		$errors = 0;
		if(!isset($data['key']))
			$errors ++;
		if(!isset($data['dfrn-request']))
			$errors ++;
		if(!isset($data['dfrn-confirm']))
			$errors ++;
		if(!isset($data['dfrn-notify']))
			$errors ++;
		if(!isset($data['dfrn-poll']))
			$errors ++;
		return $errors;
	}

	/**
	 * @brief Fetch data from a DFRN profile page and via "noscrape"
	 *
	 * @param string $profile Link to the profile page
	 *
	 * @return array profile data
	 */
	public static function profile($profile) {

		$data = array();

		// Fetch data via noscrape - this is faster
		$noscrape = str_replace(array("/hcard/", "/profile/"), "/noscrape/", $profile);
		$data = self::poll_noscrape($noscrape, $data);

		if (!isset($data["notify"]) OR !isset($data["confirm"]) OR
			!isset($data["request"]) OR !isset($data["poll"]) OR
			!isset($data["poco"]) OR !isset($data["name"]) OR
			!isset($data["photo"]))
			$data = self::poll_hcard($profile, $data, true);

		$prof_data = array();
		$prof_data["addr"] = $data["addr"];
		$prof_data["nick"] = $data["nick"];
		$prof_data["dfrn-request"] = $data["request"];
		$prof_data["dfrn-confirm"] = $data["confirm"];
		$prof_data["dfrn-notify"] = $data["notify"];
		$prof_data["dfrn-poll"] = $data["poll"];
		$prof_data["dfrn-poco"] = $data["poco"];
		$prof_data["photo"] = $data["photo"];
		$prof_data["fn"] = $data["name"];
		$prof_data["key"] = $data["pubkey"];

		return $prof_data;
	}

	/**
	 * @brief Check for DFRN contact
	 *
	 * @param array $webfinger Webfinger data
	 *
	 * @return array DFRN data
	 */
	private function dfrn($webfinger) {

		$hcard = "";
		$data = array();
		foreach ($webfinger["links"] AS $link) {
			if (($link["rel"] == NAMESPACE_DFRN) AND ($link["href"] != ""))
				$data["network"] = NETWORK_DFRN;
			elseif (($link["rel"] == NAMESPACE_FEED) AND ($link["href"] != ""))
				$data["poll"] = $link["href"];
			elseif (($link["rel"] == "http://webfinger.net/rel/profile-page") AND
				($link["type"] == "text/html") AND ($link["href"] != ""))
				$data["url"] = $link["href"];
			elseif (($link["rel"] == "http://microformats.org/profile/hcard") AND ($link["href"] != ""))
				$hcard = $link["href"];
			elseif (($link["rel"] == NAMESPACE_POCO) AND ($link["href"] != ""))
				$data["poco"] = $link["href"];
			elseif (($link["rel"] == "http://webfinger.net/rel/avatar") AND ($link["href"] != ""))
				$data["photo"] = $link["href"];

			elseif (($link["rel"] == "http://joindiaspora.com/seed_location") AND ($link["href"] != ""))
				$data["baseurl"] = trim($link["href"], '/');
			elseif (($link["rel"] == "http://joindiaspora.com/guid") AND ($link["href"] != ""))
				$data["guid"] = $link["href"];
			elseif (($link["rel"] == "diaspora-public-key") AND ($link["href"] != "")) {
				$data["pubkey"] = base64_decode($link["href"]);

				//if (strstr($data["pubkey"], 'RSA ') OR ($link["type"] == "RSA"))
				if (strstr($data["pubkey"], 'RSA '))
					$data["pubkey"] = rsatopem($data["pubkey"]);
			}
		}

		if (!isset($data["network"]) OR ($hcard == ""))
			return false;

		// Fetch data via noscrape - this is faster
		$noscrape = str_replace("/hcard/", "/noscrape/", $hcard);
		$data = self::poll_noscrape($noscrape, $data);

		if (isset($data["notify"]) AND isset($data["confirm"]) AND isset($data["request"]) AND
			isset($data["poll"]) AND isset($data["name"]) AND isset($data["photo"]))
			return $data;

		$data = self::poll_hcard($hcard, $data, true);

		return $data;
	}

	/**
	 * @brief Poll the hcard page (Diaspora and Friendica specific)
	 *
	 * @param string $hcard Link to the hcard page
	 * @param array $data The already fetched data
	 * @param boolean $dfrn Poll DFRN specific data
	 *
	 * @return array hcard data
	 */
	private function poll_hcard($hcard, $data, $dfrn = false) {

		$content = fetch_url($hcard);
		if (!$content)
			return false;

		$doc = new DOMDocument();
		if (!@$doc->loadHTML($content))
			return false;

		$xpath = new DomXPath($doc);

		$vcards = $xpath->query("//div[contains(concat(' ', @class, ' '), ' vcard ')]");
		if (!is_object($vcards))
			return false;

		if ($vcards->length > 0) {
			$vcard = $vcards->item(0);

			// We have to discard the guid from the hcard in favour of the guid from lrdd
			// Reason: Hubzilla doesn't use the value "uid" in the hcard like Diaspora does.
			$search = $xpath->query("//*[contains(concat(' ', @class, ' '), ' uid ')]", $vcard); // */
			if (($search->length > 0) AND ($data["guid"] == ""))
				$data["guid"] = $search->item(0)->nodeValue;

			$search = $xpath->query("//*[contains(concat(' ', @class, ' '), ' nickname ')]", $vcard); // */
			if ($search->length > 0)
				$data["nick"] = $search->item(0)->nodeValue;

			$search = $xpath->query("//*[contains(concat(' ', @class, ' '), ' fn ')]", $vcard); // */
			if ($search->length > 0)
				$data["name"] = $search->item(0)->nodeValue;

			$search = $xpath->query("//*[contains(concat(' ', @class, ' '), ' searchable ')]", $vcard); // */
			if ($search->length > 0)
				$data["searchable"] = $search->item(0)->nodeValue;

			$search = $xpath->query("//*[contains(concat(' ', @class, ' '), ' key ')]", $vcard); // */
			if ($search->length > 0) {
				$data["pubkey"] = $search->item(0)->nodeValue;
				if (strstr($data["pubkey"], 'RSA '))
					$data["pubkey"] = rsatopem($data["pubkey"]);
			}

			$search = $xpath->query("//*[@id='pod_location']", $vcard); // */
			if ($search->length > 0)
				$data["baseurl"] = trim($search->item(0)->nodeValue, "/");
		}

		$avatar = array();
		$photos = $xpath->query("//*[contains(concat(' ', @class, ' '), ' photo ') or contains(concat(' ', @class, ' '), ' avatar ')]", $vcard); // */
		foreach ($photos AS $photo) {
			$attr = array();
			foreach ($photo->attributes as $attribute)
				$attr[$attribute->name] = trim($attribute->value);

			if (isset($attr["src"]) AND isset($attr["width"]))
				$avatar[$attr["width"]] = $attr["src"];
		}

		if (sizeof($avatar)) {
			ksort($avatar);
			$data["photo"] = array_pop($avatar);
		}

		if ($dfrn) {
			// Poll DFRN specific data
			$search = $xpath->query("//link[contains(concat(' ', @rel), ' dfrn-')]");
			if ($search->length > 0) {
				foreach ($search AS $link) {
					//$data["request"] = $search->item(0)->nodeValue;
					$attr = array();
					foreach ($link->attributes as $attribute)
						$attr[$attribute->name] = trim($attribute->value);

					$data[substr($attr["rel"], 5)] = $attr["href"];
				}
			}

			// Older Friendica versions had used the "uid" field differently than newer versions
			if ($data["nick"] == $data["guid"])
				unset($data["guid"]);
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
	private function diaspora($webfinger) {

		$hcard = "";
		$data = array();
		foreach ($webfinger["links"] AS $link) {
			if (($link["rel"] == "http://microformats.org/profile/hcard") AND ($link["href"] != ""))
				$hcard = $link["href"];
			elseif (($link["rel"] == "http://joindiaspora.com/seed_location") AND ($link["href"] != ""))
				$data["baseurl"] = trim($link["href"], '/');
			elseif (($link["rel"] == "http://joindiaspora.com/guid") AND ($link["href"] != ""))
				$data["guid"] = $link["href"];
			elseif (($link["rel"] == "http://webfinger.net/rel/profile-page") AND
				($link["type"] == "text/html") AND ($link["href"] != ""))
				$data["url"] = $link["href"];
			elseif (($link["rel"] == NAMESPACE_FEED) AND ($link["href"] != ""))
				$data["poll"] = $link["href"];
			elseif (($link["rel"] == NAMESPACE_POCO) AND ($link["href"] != ""))
				$data["poco"] = $link["href"];
			elseif (($link["rel"] == "salmon") AND ($link["href"] != ""))
				$data["notify"] = $link["href"];
			elseif (($link["rel"] == "diaspora-public-key") AND ($link["href"] != "")) {
				$data["pubkey"] = base64_decode($link["href"]);

				//if (strstr($data["pubkey"], 'RSA ') OR ($link["type"] == "RSA"))
				if (strstr($data["pubkey"], 'RSA '))
					$data["pubkey"] = rsatopem($data["pubkey"]);
			}
		}

		if (!isset($data["url"]) OR ($hcard == ""))
			return false;

		if (is_array($webfinger["aliases"]))
			foreach ($webfinger["aliases"] AS $alias)
				if (normalise_link($alias) != normalise_link($data["url"]) AND !strstr($alias, "@"))
					$data["alias"] = $alias;

		// Fetch further information from the hcard
		$data = self::poll_hcard($hcard, $data);

		if (!$data)
			return false;

		if (isset($data["url"]) AND isset($data["guid"]) AND isset($data["baseurl"]) AND
			isset($data["pubkey"]) AND ($hcard != "")) {
			$data["network"] = NETWORK_DIASPORA;

			// The Diaspora handle must always be lowercase
			$data["addr"] = strtolower($data["addr"]);

			// We have to overwrite the detected value for "notify" since Hubzilla doesn't send it
			$data["notify"] = $data["baseurl"]."/receive/users/".$data["guid"];
			$data["batch"] = $data["baseurl"]."/receive/public";
		} else
			return false;

		return $data;
	}

	/**
	 * @brief Check for OStatus contact
	 *
	 * @param array $webfinger Webfinger data
	 *
	 * @return array OStatus data
	 */
	private function ostatus($webfinger) {

		$data = array();
		if (is_array($webfinger["aliases"]))
			foreach($webfinger["aliases"] AS $alias)
				if (strstr($alias, "@"))
					$data["addr"] = str_replace('acct:', '', $alias);

		if (is_string($webfinger["subject"]) AND strstr($webfinger["subject"], "@"))
			$data["addr"] = str_replace('acct:', '', $webfinger["subject"]);

		$pubkey = "";
		foreach ($webfinger["links"] AS $link) {
			if (($link["rel"] == "http://webfinger.net/rel/profile-page") AND
				($link["type"] == "text/html") AND ($link["href"] != ""))
				$data["url"] = $link["href"];
			elseif (($link["rel"] == "salmon") AND ($link["href"] != ""))
				$data["notify"] = $link["href"];
			elseif (($link["rel"] == NAMESPACE_FEED) AND ($link["href"] != ""))
				$data["poll"] = $link["href"];
			elseif (($link["rel"] == "magic-public-key") AND ($link["href"] != "")) {
				$pubkey = $link["href"];

				if (substr($pubkey, 0, 5) === 'data:') {
					if (strstr($pubkey, ','))
						$pubkey = substr($pubkey, strpos($pubkey, ',') + 1);
					else
						$pubkey = substr($pubkey, 5);
				} elseif (normalise_link($pubkey) == 'http://')
					$pubkey = fetch_url($pubkey);

				$key = explode(".", $pubkey);

				if (sizeof($key) >= 3) {
					$m = base64url_decode($key[1]);
					$e = base64url_decode($key[2]);
					$data["pubkey"] = metopem($m,$e);
				}

			}
		}

		if (isset($data["notify"]) AND isset($data["pubkey"]) AND
			isset($data["poll"]) AND isset($data["url"])) {
			$data["network"] = NETWORK_OSTATUS;
		} else
			return false;

		// Fetch all additional data from the feed
		$feed = fetch_url($data["poll"]);
		$feed_data = feed_import($feed,$dummy1,$dummy2, $dummy3, true);
		if (!$feed_data)
			return false;

		if ($feed_data["header"]["author-name"] != "")
			$data["name"] = $feed_data["header"]["author-name"];

		if ($feed_data["header"]["author-nick"] != "")
			$data["nick"] = $feed_data["header"]["author-nick"];

		if ($feed_data["header"]["author-avatar"] != "")
			$data["photo"] = $feed_data["header"]["author-avatar"];

		if ($feed_data["header"]["author-id"] != "")
			$data["alias"] = $feed_data["header"]["author-id"];

		if ($feed_data["header"]["author-location"] != "")
			$data["location"] = $feed_data["header"]["author-location"];

		if ($feed_data["header"]["author-about"] != "")
			$data["about"] = $feed_data["header"]["author-about"];

		// OStatus has serious issues when the the url doesn't fit (ssl vs. non ssl)
		// So we take the value that we just fetched, although the other one worked as well
		if ($feed_data["header"]["author-link"] != "")
			$data["url"] = $feed_data["header"]["author-link"];

		/// @todo Fetch location and "about" from the feed as well
		return $data;
	}

	/**
	 * @brief Fetch data from a pump.io profile page
	 *
	 * @param string $profile Link to the profile page
	 *
	 * @return array profile data
	 */
	private function pumpio_profile_data($profile) {

		$doc = new DOMDocument();
		if (!@$doc->loadHTMLFile($profile))
			return false;

		$xpath = new DomXPath($doc);

		$data = array();

		// This is ugly - but pump.io doesn't seem to know a better way for it
		$data["name"] = trim($xpath->query("//h1[@class='media-header']")->item(0)->nodeValue);
		$pos = strpos($data["name"], chr(10));
		if ($pos)
			$data["name"] = trim(substr($data["name"], 0, $pos));

		$avatar = $xpath->query("//img[@class='img-rounded media-object']")->item(0);
		if ($avatar)
			foreach ($avatar->attributes as $attribute)
				if ($attribute->name == "src")
					$data["photo"] = trim($attribute->value);

		$data["location"] = $xpath->query("//p[@class='location']")->item(0)->nodeValue;
		$data["about"] = $xpath->query("//p[@class='summary']")->item(0)->nodeValue;

		return $data;
	}

	/**
	 * @brief Check for pump.io contact
	 *
	 * @param array $webfinger Webfinger data
	 *
	 * @return array pump.io data
	 */
	private function pumpio($webfinger) {

		$data = array();
		foreach ($webfinger["links"] AS $link) {
			if (($link["rel"] == "http://webfinger.net/rel/profile-page") AND
				($link["type"] == "text/html") AND ($link["href"] != ""))
				$data["url"] = $link["href"];
			elseif (($link["rel"] == "activity-inbox") AND ($link["href"] != ""))
				$data["notify"] = $link["href"];
			elseif (($link["rel"] == "activity-outbox") AND ($link["href"] != ""))
				$data["poll"] = $link["href"];
			elseif (($link["rel"] == "dialback") AND ($link["href"] != ""))
				$data["dialback"] = $link["href"];
		}
		if (isset($data["poll"]) AND isset($data["notify"]) AND
			isset($data["dialback"]) AND isset($data["url"])) {

			// by now we use these fields only for the network type detection
			// So we unset all data that isn't used at the moment
			unset($data["dialback"]);

			$data["network"] = NETWORK_PUMPIO;
		} else
			return false;

		$profile_data = self::pumpio_profile_data($data["url"]);

		if (!$profile_data)
			return false;

		$data = array_merge($data, $profile_data);

		return $data;
	}

	/**
	 * @brief Check page for feed link
	 *
	 * @param string $url Page link
	 *
	 * @return string feed link
	 */
	private function get_feed_link($url) {
		$doc = new DOMDocument();

		if (!@$doc->loadHTMLFile($url))
			return false;

		$xpath = new DomXPath($doc);

		//$feeds = $xpath->query("/html/head/link[@type='application/rss+xml']");
		$feeds = $xpath->query("/html/head/link[@type='application/rss+xml' and @rel='alternate']");
		if (!is_object($feeds))
			return false;

		if ($feeds->length == 0)
			return false;

		$feed_url = "";

		foreach ($feeds AS $feed) {
			$attr = array();
			foreach ($feed->attributes as $attribute)
			$attr[$attribute->name] = trim($attribute->value);

			if ($feed_url == "")
				$feed_url = $attr["href"];
		}

		return $feed_url;
	}

	/**
	 * @brief Check for feed contact
	 *
	 * @param string $url Profile link
	 * @param boolean $probe Do a probe if the page contains a feed link
	 *
	 * @return array feed data
	 */
	private function feed($url, $probe = true) {
		$feed = fetch_url($url);
		$feed_data = feed_import($feed, $dummy1, $dummy2, $dummy3, true);

		if (!$feed_data) {
			if (!$probe)
				return false;

			$feed_url = self::get_feed_link($url);

			if (!$feed_url)
				return false;

			return self::feed($feed_url, false);
		}

		if ($feed_data["header"]["author-name"] != "")
			$data["name"] = $feed_data["header"]["author-name"];

		if ($feed_data["header"]["author-nick"] != "")
			$data["nick"] = $feed_data["header"]["author-nick"];

		if ($feed_data["header"]["author-avatar"] != "")
			$data["photo"] = $feed_data["header"]["author-avatar"];

		if ($feed_data["header"]["author-id"] != "")
			$data["alias"] = $feed_data["header"]["author-id"];

		$data["url"] = $url;
		$data["poll"] = $url;

		if ($feed_data["header"]["author-link"] != "")
			$data["baseurl"] = $feed_data["header"]["author-link"];
		else
			$data["baseurl"] = $data["url"];

		$data["network"] = NETWORK_FEED;

		return $data;
	}

	/**
	 * @brief Check for mail contact
	 *
	 * @param string $uri Profile link
	 * @param integer $uid User ID
	 *
	 * @return array mail data
	 */
	private function mail($uri, $uid) {

		if (!validate_email($uri))
			return false;

		$x = q("SELECT `prvkey` FROM `user` WHERE `uid` = %d LIMIT 1", intval($uid));

		$r = q("SELECT * FROM `mailacct` WHERE `uid` = %d AND `server` != '' LIMIT 1", intval($uid));

		if(count($x) && count($r)) {
			$mailbox = construct_mailbox_name($r[0]);
			$password = '';
			openssl_private_decrypt(hex2bin($r[0]['pass']), $password,$x[0]['prvkey']);
			$mbox = email_connect($mailbox,$r[0]['user'], $password);
			if(!mbox)
				return false;
		}

		$msgs = email_poll($mbox, $uri);
		logger('searching '.$uri.', '.count($msgs).' messages found.', LOGGER_DEBUG);

		if (!count($msgs))
			return false;

		$data = array();

		$data["addr"] = $uri;
		$data["network"] = NETWORK_MAIL;
		$data["name"] = substr($uri, 0, strpos($uri,'@'));
		$data["nick"] = $data["name"];
		$data["photo"] = avatar_img($uri);

		$phost = substr($uri, strpos($uri,'@') + 1);
		$data["url"] = 'http://'.$phost."/".$data["nick"];
		$data["notify"] = 'smtp '.random_string();
		$data["poll"] = 'email '.random_string();

		$x = email_msg_meta($mbox, $msgs[0]);
		if(stristr($x[0]->from, $uri))
			$adr = imap_rfc822_parse_adrlist($x[0]->from, '');
		elseif(stristr($x[0]->to, $uri))
			$adr = imap_rfc822_parse_adrlist($x[0]->to, '');
		if(isset($adr)) {
			foreach($adr as $feadr) {
				if((strcasecmp($feadr->mailbox, $data["name"]) == 0)
					&&(strcasecmp($feadr->host, $phost) == 0)
					&& (strlen($feadr->personal))) {

					$personal = imap_mime_header_decode($feadr->personal);
					$data["name"] = "";
					foreach($personal as $perspart)
						if ($perspart->charset != "default")
							$data["name"] .= iconv($perspart->charset, 'UTF-8//IGNORE', $perspart->text);
						else
							$data["name"] .= $perspart->text;

					$data["name"] = notags($data["name"]);
				}
			}
		}
		imap_close($mbox);

		return $data;
	}
}
?>
