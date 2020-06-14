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
 */

namespace Friendica\Network;

use DOMDocument;
use DomXPath;
use Friendica\Core\Cache\Duration;
use Friendica\Core\Hook;
use Friendica\Core\Logger;
use Friendica\Core\Protocol;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\GServer;
use Friendica\Model\Profile;
use Friendica\Protocol\ActivityNamespace;
use Friendica\Protocol\ActivityPub;
use Friendica\Protocol\Email;
use Friendica\Protocol\Feed;
use Friendica\Util\Crypto;
use Friendica\Util\Network;
use Friendica\Util\Strings;
use Friendica\Util\XML;

/**
 * This class contain functions for probing URL
 */
class Probe
{
	const WEBFINGER = '/.well-known/webfinger?resource={uri}';

	private static $baseurl;
	private static $istimeout;

	/**
	 * Remove stuff from an URI that doesn't belong there
	 *
	 * @param string $URI
	 * @return string Cleaned URI
	 */
	public static function cleanURI(string $URI)
	{
		// At first remove leading and trailing junk
		$URI = trim($URI, "@#?:/ \t\n\r\0\x0B");

		$parts = parse_url($URI);

		if (empty($parts['scheme'])) {
			return $URI;
		}

		// Remove the URL fragment, since these shouldn't be part of any profile URL
		unset($parts['fragment']);

		$URI = Network::unparseURL($parts);

		return $URI;
	}

	/**
	 * Rearrange the array so that it always has the same order
	 *
	 * @param array $data Unordered data
	 *
	 * @return array Ordered data
	 */
	private static function rearrangeData($data)
	{
		$fields = ["name", "nick", "guid", "url", "addr", "alias", "photo", "account-type",
				"community", "keywords", "location", "about", "hide",
				"batch", "notify", "poll", "request", "confirm", "subscribe", "poco",
				"following", "followers", "inbox", "outbox", "sharedinbox",
				"priority", "network", "pubkey", "baseurl", "gsid"];

		$newdata = [];
		foreach ($fields as $field) {
			if (isset($data[$field])) {
				if (in_array($field, ["gsid", "hide", "account-type"])) {
					$newdata[$field] = (int)$data[$field];
				} else {	
					$newdata[$field] = $data[$field];
				}
			} elseif ($field != "gsid") {
				$newdata[$field] = "";
			} else {
				$newdata[$field] = null;
			}
		}

		// We don't use the "priority" field anymore and replace it with a dummy.
		$newdata["priority"] = 0;

		return $newdata;
	}

	/**
	 * Check if the hostname belongs to the own server
	 *
	 * @param string $host The hostname that is to be checked
	 *
	 * @return bool Does the testes hostname belongs to the own server?
	 */
	private static function ownHost($host)
	{
		$own_host = DI::baseUrl()->getHostname();

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
	 * Probes for webfinger path via "host-meta"
	 *
	 * We have to check if the servers in the future still will offer this.
	 * It seems as if it was dropped from the standard.
	 *
	 * @param string $host The host part of an url
	 *
	 * @return array with template and type of the webfinger template for JSON or XML
	 * @throws HTTPException\InternalServerErrorException
	 */
	private static function hostMeta($host)
	{
		// Reset the static variable
		self::$baseurl = '';

		// Handles the case when the hostname contains the scheme
		if (!parse_url($host, PHP_URL_SCHEME)) {
			$ssl_url = "https://" . $host . "/.well-known/host-meta";
			$url = "http://" . $host . "/.well-known/host-meta";
		} else {
			$ssl_url = $host . "/.well-known/host-meta";
			$url = '';
		}

		$xrd_timeout = DI::config()->get('system', 'xrd_timeout', 20);

		Logger::info('Probing', ['host' => $host, 'ssl_url' => $ssl_url, 'url' => $url, 'callstack' => System::callstack(20)]);
		$xrd = null;

		$curlResult = Network::curl($ssl_url, false, ['timeout' => $xrd_timeout, 'accept_content' => 'application/xrd+xml']);
		$ssl_connection_error = ($curlResult->getErrorNumber() == CURLE_COULDNT_CONNECT) || ($curlResult->getReturnCode() == 0);
		if ($curlResult->isSuccess()) {
			$xml = $curlResult->getBody();
			$xrd = XML::parseString($xml, true);
			if (!empty($url)) {
				$host_url = 'https://' . $host;
			} else {
				$host_url = $host;
			}
		} elseif ($curlResult->isTimeout()) {
			Logger::info('Probing timeout', ['url' => $ssl_url], Logger::DEBUG);
			self::$istimeout = true;
			return [];
		}

		if (!is_object($xrd) && !empty($url)) {
			$curlResult = Network::curl($url, false, ['timeout' => $xrd_timeout, 'accept_content' => 'application/xrd+xml']);
			$connection_error = ($curlResult->getErrorNumber() == CURLE_COULDNT_CONNECT) || ($curlResult->getReturnCode() == 0);
			if ($curlResult->isTimeout()) {
				Logger::info('Probing timeout', ['url' => $url], Logger::DEBUG);
				self::$istimeout = true;
				return [];
			} elseif ($connection_error && $ssl_connection_error) {
				self::$istimeout = true;
				return [];
			}

			$xml = $curlResult->getBody();
			$xrd = XML::parseString($xml, true);
			$host_url = 'http://'.$host;
		}
		if (!is_object($xrd)) {
			Logger::log("No xrd object found for ".$host, Logger::DEBUG);
			return [];
		}

		$links = XML::elementToArray($xrd);
		if (!isset($links["xrd"]["link"])) {
			Logger::log("No xrd data found for ".$host, Logger::DEBUG);
			return [];
		}

		$lrdd = [];

		foreach ($links["xrd"]["link"] as $value => $link) {
			if (!empty($link["@attributes"])) {
				$attributes = $link["@attributes"];
			} elseif ($value == "@attributes") {
				$attributes = $link;
			} else {
				continue;
			}

			if (!empty($attributes["rel"]) && $attributes["rel"] == "lrdd" && !empty($attributes["template"])) {
				$type = (empty($attributes["type"]) ? '' : $attributes["type"]);

				$lrdd[$type] = $attributes["template"];
			}
		}

		self::$baseurl = $host_url;

		Logger::log("Probing successful for ".$host, Logger::DEBUG);

		return $lrdd;
	}

	/**
	 * Perform Webfinger lookup and return DFRN data
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
	 * @throws HTTPException\InternalServerErrorException
	 */
	public static function webfingerDfrn($webbie, &$hcard_url)
	{
		$profile_link = '';

		$links = self::lrdd($webbie);
		Logger::log('webfingerDfrn: '.$webbie.':'.print_r($links, true), Logger::DATA);
		if (!empty($links) && is_array($links)) {
			foreach ($links as $link) {
				if ($link['@attributes']['rel'] === ActivityNamespace::DFRN) {
					$profile_link = $link['@attributes']['href'];
				}
				if (($link['@attributes']['rel'] === ActivityNamespace::OSTATUSSUB) && ($profile_link == "")) {
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
	 * Check an URI for LRDD data
	 *
	 * @param string $uri     Address that should be probed
	 *
	 * @return array uri data
	 * @throws HTTPException\InternalServerErrorException
	 */
	public static function lrdd(string $uri)
	{
		$data = self::getWebfingerArray($uri);
		if (empty($data)) {
			return [];
		}
		$webfinger = $data['webfinger'];

		if (empty($webfinger["links"])) {
			Logger::log("No webfinger links found for ".$uri, Logger::DEBUG);
			return [];
		}

		$data = [];

		foreach ($webfinger["links"] as $link) {
			$data[] = ["@attributes" => $link];
		}

		if (!empty($webfinger["aliases"]) && is_array($webfinger["aliases"])) {
			foreach ($webfinger["aliases"] as $alias) {
				$data[] = ["@attributes" =>
							["rel" => "alias",
								"href" => $alias]];
			}
		}

		return $data;
	}

	/**
	 * Fetch information (protocol endpoints and user information) about a given uri
	 *
	 * @param string  $uri     Address that should be probed
	 * @param string  $network Test for this specific network
	 * @param integer $uid     User ID for the probe (only used for mails)
	 * @param boolean $cache   Use cached values?
	 *
	 * @return array uri data
	 * @throws HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function uri($uri, $network = '', $uid = -1, $cache = true)
	{
		// Accept the mobile twitter address as well
		if (!parse_url($uri, PHP_URL_SCHEME)) {
			$uri = str_replace('@mobile.twitter.com', '@twitter.com', $uri);
		} else {
			$uri = str_replace('://mobile.twitter.com', '://twitter.com', $uri);
		}

		$cachekey = 'Probe::uri:' . $network . ':' . $uri;
		if ($cache) {
			$result = DI::cache()->get($cachekey);
			if (!is_null($result)) {
				return $result;
			}
		}

		if ($uid == -1) {
			$uid = local_user();
		}

		self::$istimeout = false;

		if ($network != Protocol::ACTIVITYPUB) {
			$data = self::detect($uri, $network, $uid);
			if (!is_array($data)) {
				$data = [];
			}
		} else {
			$data = [];
		}

		// When the previous detection process had got a time out
		// we could falsely detect a Friendica profile as AP profile.
		if (!self::$istimeout && (empty($network) || $network == Protocol::ACTIVITYPUB)) {
			$ap_profile = ActivityPub::probeProfile($uri, !$cache);

			if (empty($data) || (!empty($ap_profile) && empty($network) && (($data['network'] ?? '') != Protocol::DFRN))) {
				$data = $ap_profile;
			} elseif (!empty($ap_profile)) {
				$ap_profile['batch'] = '';
				$data = array_merge($ap_profile, $data);
			}
		}

		if (!isset($data['url'])) {
			$data['url'] = $uri;
		}

		if (empty($data['photo'])) {
			$data['photo'] = DI::baseUrl() . '/images/person-300.jpg';
		}

		if (empty($data['name'])) {
			if (!empty($data['nick'])) {
				$data['name'] = $data['nick'];
			}

			if (empty($data['name'])) {
				$data['name'] = $data['url'];
			}
		}

		if (empty($data['nick'])) {
			$data['nick'] = strtolower($data['name']);

			if (strpos($data['nick'], ' ')) {
				$data['nick'] = trim(substr($data['nick'], 0, strpos($data['nick'], ' ')));
			}
		}

		if (!empty($data['baseurl']) && empty($data['gsid'])) {
			$data['gsid'] = GServer::getID($data['baseurl']);
		}

		if (empty($data['network'])) {
			$data['network'] = Protocol::PHANTOM;
		}

		// Ensure that local connections always are DFRN
		if (($network == '') && ($data['network'] != Protocol::PHANTOM) && (self::ownHost($data['baseurl'] ?? '') || self::ownHost($data['url']))) {
			$data['network'] = Protocol::DFRN;
		}

		if (!isset($data['hide']) && in_array($data['network'], Protocol::FEDERATED)) {
			$data['hide'] = self::getHideStatus($data['url']);
		}

		$data = self::rearrangeData($data);

		// Only store into the cache if the value seems to be valid
		if (!in_array($data['network'], [Protocol::PHANTOM, Protocol::MAIL])) {
			DI::cache()->set($cachekey, $data, Duration::DAY);
		}

		return $data;
	}


	/**
	 * Fetches the "hide" status from the profile
	 *
	 * @param string $url URL of the profile
	 *
	 * @return boolean "hide" status
	 */
	private static function getHideStatus($url)
	{
		$curlResult = Network::curl($url);
		if (!$curlResult->isSuccess()) {
			return false;
		}

		// If the file is too large then exit
		if (($curlResult->getInfo()['download_content_length'] ?? 0) > 1000000) {
			return false;
		}

		// If it isn't a HTML file then exit
		if (($curlResult->getContentType() != '') && !strstr(strtolower($curlResult->getContentType()), 'html')) {
			return false;
		}

		$body = $curlResult->getBody();

		$doc = new DOMDocument();
		@$doc->loadHTML($body);

		$xpath = new DOMXPath($doc);

		$list = $xpath->query('//meta[@name]');
		foreach ($list as $node) {
			$meta_tag = [];
			if ($node->attributes->length) {
				foreach ($node->attributes as $attribute) {
					$meta_tag[$attribute->name] = $attribute->value;
				}
			}

			if (empty($meta_tag['content'])) {
				continue;
			}

			$content = strtolower(trim($meta_tag['content']));

			switch (strtolower(trim($meta_tag['name']))) {
				case 'dfrn-global-visibility':
					if ($content == 'false') {
						return true;
					}
					break;
				case 'robots':
					if (strpos($content, 'noindex') !== false) {
						return true;
					}
					break;
			}
		}

		return false;
	}

	/**
	 * Fetch the "subscribe" and add it to the result
	 *
	 * @param array $result
	 * @param array $webfinger
	 * @return array result
	 */
	private static function getSubscribeLink(array $result, array $webfinger)
	{
		if (empty($webfinger['links'])) {
			return $result;
		}

		foreach ($webfinger['links'] as $link) {
			if (!empty($link['template']) && ($link['rel'] === ActivityNamespace::OSTATUSSUB)) {
				$result['subscribe'] = $link['template'];
			}
		}

		return $result;
	}

	/**
	 * Get webfinger data from a given URI
	 *
	 * @param string $uri
	 * @return array Webfinger array
	 */
	private static function getWebfingerArray(string $uri)
	{
		$parts = parse_url($uri);

		if (!empty($parts['scheme']) && !empty($parts['host'])) {
			$host = $parts['host'];
			if (!empty($parts['port'])) {
				$host .= ':'.$parts['port'];
			}

			$baseurl = $parts['scheme'] . '://' . $host;

			$nick = '';
			$addr = '';

			$path_parts = explode("/", trim($parts['path'] ?? '', "/"));
			if (!empty($path_parts)) {
				$nick = ltrim(end($path_parts), '@');
				// When the last part of the URI is numeric then it is most likely an ID and not a nick name
				if (!is_numeric($nick)) {
					$addr = $nick."@".$host;
				} else {
					$nick = '';
				}
			}

			$webfinger = self::getWebfinger($parts['scheme'] . '://' . $host . self::WEBFINGER, 'application/jrd+json', $uri, $addr);
			if (empty($webfinger)) {
				$lrdd = self::hostMeta($host);
			}

			if (empty($webfinger) && empty($lrdd)) {
				while (empty($lrdd) && empty($webfinger) && (sizeof($path_parts) > 1)) {
					$host .= "/".array_shift($path_parts);
					$baseurl = $parts['scheme'] . '://' . $host;

					if (!empty($nick)) {
						$addr = $nick."@".$host;
					}

					$webfinger = self::getWebfinger($parts['scheme'] . '://' . $host . self::WEBFINGER, 'application/jrd+json', $uri, $addr);
					if (empty($webfinger)) {
						$lrdd = self::hostMeta($host);
					}
				}

				if (empty($lrdd) && empty($webfinger)) {
					return [];
				}
			}
		} elseif (strstr($uri, '@')) {
			// Remove "acct:" from the URI
			$uri = str_replace('acct:', '', $uri);

			$host = substr($uri, strpos($uri, '@') + 1);
			$nick = substr($uri, 0, strpos($uri, '@'));
			$addr = $uri;

			$webfinger = self::getWebfinger('https://' . $host . self::WEBFINGER, 'application/jrd+json', $uri, $addr);
			if (self::$istimeout) {
				return [];
			}

			if (empty($webfinger)) {
				$webfinger = self::getWebfinger('http://' . $host . self::WEBFINGER, 'application/jrd+json', $uri, $addr);
				if (self::$istimeout) {
					return [];
				}
			} else {
				$baseurl = 'https://' . $host;
			}

			if (empty($webfinger)) {
				$lrdd = self::hostMeta($host);
				if (self::$istimeout) {
					return [];
				}
				$baseurl = self::$baseurl;
			} else {
				$baseurl = 'http://' . $host;
			}
		} else {
			Logger::info('URI was not detectable', ['uri' => $uri]);
			return [];
		}

		if (empty($webfinger)) {
			foreach ($lrdd as $type => $template) {
				if ($webfinger) {
					continue;
				}

				$webfinger = self::getWebfinger($template, $type, $uri, $addr);
			}
		}

		if (empty($webfinger)) {
			return [];
		}

		if ($webfinger['detected'] == $addr) {
			$webfinger['nick'] = $nick;
			$webfinger['addr'] = $addr;
		}

		$webfinger['baseurl'] = $baseurl;

		return $webfinger;
	}

	/**
	 * Perform network request for webfinger data
	 *
	 * @param string $template
	 * @param string $type
	 * @param string $uri
	 * @param string $addr
	 * @return array webfinger results
	 */
	private static function getWebfinger(string $template, string $type, string $uri, string $addr)
	{
		// First try the address because this is the primary purpose of webfinger
		if (!empty($addr)) {
			$detected = $addr;
			$path = str_replace('{uri}', urlencode("acct:" . $addr), $template);
			$webfinger = self::webfinger($path, $type);
			if (self::$istimeout) {
				return [];
			}
		}

		// Then try the URI
		if (empty($webfinger) && $uri != $addr) {
			$detected = $uri;
			$path = str_replace('{uri}', urlencode($uri), $template);
			$webfinger = self::webfinger($path, $type);
			if (self::$istimeout) {
				return [];
			}
		}

		if (empty($webfinger)) {
			return [];
		}

		return ['webfinger' => $webfinger, 'detected' => $detected];
	}

	/**
	 * Fetch information (protocol endpoints and user information) about a given uri
	 *
	 * This function is only called by the "uri" function that adds caching and rearranging of data.
	 *
	 * @param string  $uri     Address that should be probed
	 * @param string  $network Test for this specific network
	 * @param integer $uid     User ID for the probe (only used for mails)
	 *
	 * @return array uri data
	 * @throws HTTPException\InternalServerErrorException
	 */
	private static function detect($uri, $network, $uid)
	{
		$hookData = [
			'uri'     => $uri,
			'network' => $network,
			'uid'     => $uid,
			'result'  => [],
		];

		Hook::callAll('probe_detect', $hookData);

		if ($hookData['result']) {
			if (!is_array($hookData['result'])) {
				return [];
			} else {
				return $hookData['result'];
			}
		}

		$parts = parse_url($uri);

		if (!empty($parts['scheme']) && !empty($parts['host'])) {
			if ($parts['host'] == 'twitter.com') {
				return self::twitter($uri);
			}
		} elseif (strstr($uri, '@')) {
			// If the URI starts with "mailto:" then jump directly to the mail detection
			if (strpos($uri, 'mailto:') !== false) {
				$uri = str_replace('mailto:', '', $uri);
				return self::mail($uri, $uid);
			}

			if ($network == Protocol::MAIL) {
				return self::mail($uri, $uid);
			}

			if (strpos($uri, '@twitter.com')) {
				return self::twitter($uri);
			}
		} else {
			Logger::info('URI was not detectable', ['uri' => $uri]);
			return [];
		}

		Logger::info('Probing start', ['uri' => $uri]);

		$data = self::getWebfingerArray($uri);
		if (empty($data)) {
			if (!empty($parts['scheme'])) {
				return self::feed($uri);
			} elseif (!empty($uid)) {
				return self::mail($uri, $uid);
			} else {
				return [];
			}
		}

		$webfinger = $data['webfinger'];
		$nick = $data['nick'] ?? '';
		$addr = $data['addr'] ?? '';
		$baseurl = $data['baseurl'] ?? '';

		$result = [];

		if (in_array($network, ["", Protocol::DFRN])) {
			$result = self::dfrn($webfinger);
		}
		if ((!$result && ($network == "")) || ($network == Protocol::DIASPORA)) {
			$result = self::diaspora($webfinger);
		}
		if ((!$result && ($network == "")) || ($network == Protocol::OSTATUS)) {
			$result = self::ostatus($webfinger);
		}
		if (in_array($network, ['', Protocol::ZOT])) {
			$result = self::zot($webfinger, $result, $baseurl);
		}
		if ((!$result && ($network == "")) || ($network == Protocol::PUMPIO)) {
			$result = self::pumpio($webfinger, $addr);
		}
		if ((!$result && ($network == "")) || ($network == Protocol::FEED)) {
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

		$result = self::getSubscribeLink($result, $webfinger);

		if (empty($result["network"])) {
			$result["network"] = Protocol::PHANTOM;
		}

		if (empty($result['baseurl']) && !empty($baseurl)) {
			$result['baseurl'] = $baseurl;
		}

		if (empty($result["url"])) {
			$result["url"] = $uri;
		}

		Logger::info('Probing done', ['uri' => $uri, 'network' => $result["network"]]);

		return $result;
	}

	/**
	 * Check for Zot contact
	 *
	 * @param array $webfinger Webfinger data
	 * @param array $data      previously probed data
	 *
	 * @return array Zot data
	 * @throws HTTPException\InternalServerErrorException
	 */
	private static function zot($webfinger, $data, $baseurl)
	{
		if (!empty($webfinger["aliases"]) && is_array($webfinger["aliases"])) {
			foreach ($webfinger["aliases"] as $alias) {
				if (substr($alias, 0, 5) == 'acct:') {
					$data["addr"] = substr($alias, 5);
				}
			}
		}

		if (!empty($webfinger["subject"]) && (substr($webfinger["subject"], 0, 5) == "acct:")) {
			$data["addr"] = substr($webfinger["subject"], 5);
		}

		$zot_url = '';
		foreach ($webfinger['links'] as $link) {
			if (($link['rel'] == 'http://purl.org/zot/protocol') && !empty($link['href'])) {
				$zot_url = $link['href'];
			}
		}

		if (empty($zot_url) && !empty($data['addr']) && !empty($baseurl)) {
			$condition = ['nurl' => Strings::normaliseLink($baseurl), 'platform' => ['hubzilla']];
			if (!DBA::exists('gserver', $condition)) {
				return $data;
			}
			$zot_url = $baseurl . '/.well-known/zot-info?address=' . $data['addr'];
		}

		if (empty($zot_url)) {
			return $data;
		}

		$data = self::pollZot($zot_url, $data);

		if (!empty($data['url']) && !empty($webfinger['aliases']) && is_array($webfinger['aliases'])) {
			foreach ($webfinger['aliases'] as $alias) {
				if (!strstr($alias, '@') && Strings::normaliseLink($alias) != Strings::normaliseLink($data['url'])) {
					$data['alias'] = $alias;
				}
			}
		}

		return $data;
	}

	public static function pollZot($url, $data)
	{
		$curlResult = Network::curl($url);
		if ($curlResult->isTimeout()) {
			return $data;
		}
		$content = $curlResult->getBody();
		if (!$content) {
			return $data;
		}

		$json = json_decode($content, true);
		if (!is_array($json)) {
			return $data;
		}

		if (empty($data['network'])) {
			if (!empty($json['protocols']) && in_array('zot', $json['protocols'])) {
				$data['network'] = Protocol::ZOT;
			} elseif (!isset($json['protocols'])) {
				$data['network'] = Protocol::ZOT;
			}
		}

		if (!empty($json['guid']) && empty($data['guid'])) {
			$data['guid'] = $json['guid'];
		}
		if (!empty($json['key']) && empty($data['pubkey'])) {
			$data['pubkey'] = $json['key'];
		}
		if (!empty($json['name'])) {
			$data['name'] = $json['name'];
		}
		if (!empty($json['photo'])) {
			$data['photo'] = $json['photo'];
			if (!empty($json['photo_updated'])) {
				$data['photo'] .= '?rev=' . urlencode($json['photo_updated']);
			}
		}
		if (!empty($json['address'])) {
			$data['addr'] = $json['address'];
		}
		if (!empty($json['url'])) {
			$data['url'] = $json['url'];
		}
		if (!empty($json['connections_url'])) {
			$data['poco'] = $json['connections_url'];
		}
		if (isset($json['searchable'])) {
			$data['hide'] = !$json['searchable'];
		}
		if (!empty($json['public_forum'])) {
			$data['community'] = $json['public_forum'];
			$data['account-type'] = Contact::PAGE_COMMUNITY;
		}

		if (!empty($json['profile'])) {
			$profile = $json['profile'];
			if (!empty($profile['description'])) {
				$data['about'] = $profile['description'];
			}
			if (!empty($profile['keywords'])) {
				$keywords = implode(', ', $profile['keywords']);
				if (!empty($keywords)) {
					$data['keywords'] = $keywords;
				}
			}

			$loc = [];
			if (!empty($profile['region'])) {
				$loc['region'] = $profile['region'];
			}
			if (!empty($profile['country'])) {
				$loc['country-name'] = $profile['country'];
			}
			$location = Profile::formatLocation($loc);
			if (!empty($location)) {
				$data['location'] = $location;
			}
		}

		return $data;
	}

	/**
	 * Perform a webfinger request.
	 *
	 * For details see RFC 7033: <https://tools.ietf.org/html/rfc7033>
	 *
	 * @param string $url  Address that should be probed
	 * @param string $type type
	 *
	 * @return array webfinger data
	 * @throws HTTPException\InternalServerErrorException
	 */
	public static function webfinger($url, $type)
	{
		$xrd_timeout = DI::config()->get('system', 'xrd_timeout', 20);

		$curlResult = Network::curl($url, false, ['timeout' => $xrd_timeout, 'accept_content' => $type]);
		if ($curlResult->isTimeout()) {
			self::$istimeout = true;
			return [];
		}
		$data = $curlResult->getBody();

		$webfinger = json_decode($data, true);
		if (!empty($webfinger)) {
			if (!isset($webfinger["links"])) {
				Logger::log("No json webfinger links for ".$url, Logger::DEBUG);
				return [];
			}
			return $webfinger;
		}

		// If it is not JSON, maybe it is XML
		$xrd = XML::parseString($data, true);
		if (!is_object($xrd)) {
			Logger::log("No webfinger data retrievable for ".$url, Logger::DEBUG);
			return [];
		}

		$xrd_arr = XML::elementToArray($xrd);
		if (!isset($xrd_arr["xrd"]["link"])) {
			Logger::log("No XML webfinger links for ".$url, Logger::DEBUG);
			return [];
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
	 * Poll the Friendica specific noscrape page.
	 *
	 * "noscrape" is a faster alternative to fetch the data from the hcard.
	 * This functionality was originally created for the directory.
	 *
	 * @param string $noscrape_url Link to the noscrape page
	 * @param array  $data         The already fetched data
	 *
	 * @return array noscrape data
	 * @throws HTTPException\InternalServerErrorException
	 */
	private static function pollNoscrape($noscrape_url, $data)
	{
		$curlResult = Network::curl($noscrape_url);
		if ($curlResult->isTimeout()) {
			self::$istimeout = true;
			return [];
		}
		$content = $curlResult->getBody();
		if (!$content) {
			Logger::log("Empty body for ".$noscrape_url, Logger::DEBUG);
			return [];
		}

		$json = json_decode($content, true);
		if (!is_array($json)) {
			Logger::log("No json data for ".$noscrape_url, Logger::DEBUG);
			return [];
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
			$keywords = implode(", ", $json["tags"]);
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

		if (isset($json["hide"])) {
			$data["hide"] = (bool)$json["hide"];
		} else {
			$data["hide"] = false;
		}

		return $data;
	}

	/**
	 * Check for valid DFRN data
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
	 * Fetch data from a DFRN profile page and via "noscrape"
	 *
	 * @param string $profile_link Link to the profile page
	 *
	 * @return array profile data
	 * @throws HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function profile($profile_link)
	{
		$data = [];

		Logger::log("Check profile ".$profile_link, Logger::DEBUG);

		// Fetch data via noscrape - this is faster
		$noscrape_url = str_replace(["/hcard/", "/profile/"], "/noscrape/", $profile_link);
		$data = self::pollNoscrape($noscrape_url, $data);

		if (!isset($data["notify"])
			|| !isset($data["confirm"])
			|| !isset($data["request"])
			|| !isset($data["poll"])
			|| !isset($data["name"])
			|| !isset($data["photo"])
		) {
			$data = self::pollHcard($profile_link, $data, true);
		}

		$prof_data = [];

		if (empty($data["addr"]) || empty($data["nick"])) {
			$probe_data = self::uri($profile_link);
			$data["addr"] = ($data["addr"] ?? '') ?: $probe_data["addr"];
			$data["nick"] = ($data["nick"] ?? '') ?: $probe_data["nick"];
		}

		$prof_data["addr"]         = $data["addr"];
		$prof_data["nick"]         = $data["nick"];
		$prof_data["dfrn-request"] = $data['request'] ?? null;
		$prof_data["dfrn-confirm"] = $data['confirm'] ?? null;
		$prof_data["dfrn-notify"]  = $data['notify']  ?? null;
		$prof_data["dfrn-poll"]    = $data['poll']    ?? null;
		$prof_data["photo"]        = $data['photo']   ?? null;
		$prof_data["fn"]           = $data['name']    ?? null;
		$prof_data["key"]          = $data['pubkey']  ?? null;

		Logger::log("Result for profile ".$profile_link.": ".print_r($prof_data, true), Logger::DEBUG);

		return $prof_data;
	}

	/**
	 * Check for DFRN contact
	 *
	 * @param array $webfinger Webfinger data
	 *
	 * @return array DFRN data
	 * @throws HTTPException\InternalServerErrorException
	 */
	private static function dfrn($webfinger)
	{
		$hcard_url = "";
		$data = [];
		// The array is reversed to take into account the order of preference for same-rel links
		// See: https://tools.ietf.org/html/rfc7033#section-4.4.4
		foreach (array_reverse($webfinger["links"]) as $link) {
			if (($link["rel"] == ActivityNamespace::DFRN) && !empty($link["href"])) {
				$data["network"] = Protocol::DFRN;
			} elseif (($link["rel"] == ActivityNamespace::FEED) && !empty($link["href"])) {
				$data["poll"] = $link["href"];
			} elseif (($link["rel"] == "http://webfinger.net/rel/profile-page") && (($link["type"] ?? "") == "text/html") && !empty($link["href"])) {
				$data["url"] = $link["href"];
			} elseif (($link["rel"] == "http://microformats.org/profile/hcard") && !empty($link["href"])) {
				$hcard_url = $link["href"];
			} elseif (($link["rel"] == ActivityNamespace::POCO) && !empty($link["href"])) {
				$data["poco"] = $link["href"];
			} elseif (($link["rel"] == "http://webfinger.net/rel/avatar") && !empty($link["href"])) {
				$data["photo"] = $link["href"];
			} elseif (($link["rel"] == "http://joindiaspora.com/seed_location") && !empty($link["href"])) {
				$data["baseurl"] = trim($link["href"], '/');
			} elseif (($link["rel"] == "http://joindiaspora.com/guid") && !empty($link["href"])) {
				$data["guid"] = $link["href"];
			} elseif (($link["rel"] == "diaspora-public-key") && !empty($link["href"])) {
				$data["pubkey"] = base64_decode($link["href"]);

				//if (strstr($data["pubkey"], 'RSA ') || ($link["type"] == "RSA"))
				if (strstr($data["pubkey"], 'RSA ')) {
					$data["pubkey"] = Crypto::rsaToPem($data["pubkey"]);
				}
			}
		}

		if (!empty($webfinger["aliases"]) && is_array($webfinger["aliases"])) {
			foreach ($webfinger["aliases"] as $alias) {
				if (empty($data["url"]) && !strstr($alias, "@")) {
					$data["url"] = $alias;
				} elseif (!strstr($alias, "@") && Strings::normaliseLink($alias) != Strings::normaliseLink($data["url"])) {
					$data["alias"] = $alias;
				} elseif (substr($alias, 0, 5) == 'acct:') {
					$data["addr"] = substr($alias, 5);
				}
			}
		}

		if (!empty($webfinger["subject"]) && (substr($webfinger["subject"], 0, 5) == "acct:")) {
			$data["addr"] = substr($webfinger["subject"], 5);
		}

		if (!isset($data["network"]) || ($hcard_url == "")) {
			return [];
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
	 * Poll the hcard page (Diaspora and Friendica specific)
	 *
	 * @param string  $hcard_url Link to the hcard page
	 * @param array   $data      The already fetched data
	 * @param boolean $dfrn      Poll DFRN specific data
	 *
	 * @return array hcard data
	 * @throws HTTPException\InternalServerErrorException
	 */
	private static function pollHcard($hcard_url, $data, $dfrn = false)
	{
		$curlResult = Network::curl($hcard_url);
		if ($curlResult->isTimeout()) {
			self::$istimeout = true;
			return [];
		}
		$content = $curlResult->getBody();
		if (!$content) {
			return [];
		}

		$doc = new DOMDocument();
		if (!@$doc->loadHTML($content)) {
			return [];
		}

		$xpath = new DomXPath($doc);

		$vcards = $xpath->query("//div[contains(concat(' ', @class, ' '), ' vcard ')]");
		if (!is_object($vcards)) {
			return [];
		}

		if (!isset($data["baseurl"])) {
			$data["baseurl"] = "";
		}

		if ($vcards->length > 0) {
			$vcard = $vcards->item(0);

			// We have to discard the guid from the hcard in favour of the guid from lrdd
			// Reason: Hubzilla doesn't use the value "uid" in the hcard like Diaspora does.
			$search = $xpath->query("//*[contains(concat(' ', @class, ' '), ' uid ')]", $vcard); // */
			if (($search->length > 0) && empty($data["guid"])) {
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
		if (!empty($vcard)) {
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
			if (!empty($data["nick"]) && !empty($data["guid"]) && ($data["nick"] == $data["guid"])) {
				unset($data["guid"]);
			}
		}


		return $data;
	}

	/**
	 * Check for Diaspora contact
	 *
	 * @param array $webfinger Webfinger data
	 *
	 * @return array Diaspora data
	 * @throws HTTPException\InternalServerErrorException
	 */
	private static function diaspora($webfinger)
	{
		$hcard_url = "";
		$data = [];

		// The array is reversed to take into account the order of preference for same-rel links
		// See: https://tools.ietf.org/html/rfc7033#section-4.4.4
		foreach (array_reverse($webfinger["links"]) as $link) {
			if (($link["rel"] == "http://microformats.org/profile/hcard") && !empty($link["href"])) {
				$hcard_url = $link["href"];
			} elseif (($link["rel"] == "http://joindiaspora.com/seed_location") && !empty($link["href"])) {
				$data["baseurl"] = trim($link["href"], '/');
			} elseif (($link["rel"] == "http://joindiaspora.com/guid") && !empty($link["href"])) {
				$data["guid"] = $link["href"];
			} elseif (($link["rel"] == "http://webfinger.net/rel/profile-page") && (($link["type"] ?? "") == "text/html") && !empty($link["href"])) {
				$data["url"] = $link["href"];
			} elseif (($link["rel"] == ActivityNamespace::FEED) && !empty($link["href"])) {
				$data["poll"] = $link["href"];
			} elseif (($link["rel"] == ActivityNamespace::POCO) && !empty($link["href"])) {
				$data["poco"] = $link["href"];
			} elseif (($link["rel"] == "salmon") && !empty($link["href"])) {
				$data["notify"] = $link["href"];
			} elseif (($link["rel"] == "diaspora-public-key") && !empty($link["href"])) {
				$data["pubkey"] = base64_decode($link["href"]);

				//if (strstr($data["pubkey"], 'RSA ') || ($link["type"] == "RSA"))
				if (strstr($data["pubkey"], 'RSA ')) {
					$data["pubkey"] = Crypto::rsaToPem($data["pubkey"]);
				}
			}
		}

		if (empty($data["url"]) || empty($hcard_url)) {
			return [];
		}

		if (!empty($webfinger["aliases"]) && is_array($webfinger["aliases"])) {
			foreach ($webfinger["aliases"] as $alias) {
				if (Strings::normaliseLink($alias) != Strings::normaliseLink($data["url"]) && ! strstr($alias, "@")) {
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
			return [];
		}

		if (!empty($data["url"])
			&& !empty($data["guid"])
			&& !empty($data["baseurl"])
			&& !empty($data["pubkey"])
			&& !empty($hcard_url)
		) {
			$data["network"] = Protocol::DIASPORA;

			// The Diaspora handle must always be lowercase
			if (!empty($data["addr"])) {
				$data["addr"] = strtolower($data["addr"]);
			}

			// We have to overwrite the detected value for "notify" since Hubzilla doesn't send it
			$data["notify"] = $data["baseurl"] . "/receive/users/" . $data["guid"];
			$data["batch"]  = $data["baseurl"] . "/receive/public";
		} else {
			return [];
		}

		return $data;
	}

	/**
	 * Check for OStatus contact
	 *
	 * @param array $webfinger Webfinger data
	 * @param bool  $short     Short detection mode
	 *
	 * @return array|bool OStatus data or "false" on error or "true" on short mode
	 * @throws HTTPException\InternalServerErrorException
	 */
	private static function ostatus($webfinger, $short = false)
	{
		$data = [];

		if (!empty($webfinger["aliases"]) && is_array($webfinger["aliases"])) {
			foreach ($webfinger["aliases"] as $alias) {
				if (strstr($alias, "@") && !strstr(Strings::normaliseLink($alias), "http://")) {
					$data["addr"] = str_replace('acct:', '', $alias);
				}
			}
		}

		if (!empty($webfinger["subject"]) && strstr($webfinger["subject"], "@")
			&& !strstr(Strings::normaliseLink($webfinger["subject"]), "http://")
		) {
			$data["addr"] = str_replace('acct:', '', $webfinger["subject"]);
		}

		if (!empty($webfinger["links"])) {
			// The array is reversed to take into account the order of preference for same-rel links
			// See: https://tools.ietf.org/html/rfc7033#section-4.4.4
			foreach (array_reverse($webfinger["links"]) as $link) {
				if (($link["rel"] == "http://webfinger.net/rel/profile-page")
					&& (($link["type"] ?? "") == "text/html")
					&& ($link["href"] != "")
				) {
					$data["url"] = $data["alias"] = $link["href"];
				} elseif (($link["rel"] == "salmon") && !empty($link["href"])) {
					$data["notify"] = $link["href"];
				} elseif (($link["rel"] == ActivityNamespace::FEED) && !empty($link["href"])) {
					$data["poll"] = $link["href"];
				} elseif (($link["rel"] == "magic-public-key") && !empty($link["href"])) {
					$pubkey = $link["href"];

					if (substr($pubkey, 0, 5) === 'data:') {
						if (strstr($pubkey, ',')) {
							$pubkey = substr($pubkey, strpos($pubkey, ',') + 1);
						} else {
							$pubkey = substr($pubkey, 5);
						}
					} elseif (Strings::normaliseLink($pubkey) == 'http://') {
						$curlResult = Network::curl($pubkey);
						if ($curlResult->isTimeout()) {
							self::$istimeout = true;
							return $short ? false : [];
						}
						$pubkey = $curlResult->getBody();
					}

					$key = explode(".", $pubkey);

					if (sizeof($key) >= 3) {
						$m = Strings::base64UrlDecode($key[1]);
						$e = Strings::base64UrlDecode($key[2]);
						$data["pubkey"] = Crypto::meToPem($m, $e);
					}
				}
			}
		}

		if (isset($data["notify"]) && isset($data["pubkey"])
			&& isset($data["poll"])
			&& isset($data["url"])
		) {
			$data["network"] = Protocol::OSTATUS;
		} else {
			return $short ? false : [];
		}

		if ($short) {
			return true;
		}

		// Fetch all additional data from the feed
		$curlResult = Network::curl($data["poll"]);
		if ($curlResult->isTimeout()) {
			self::$istimeout = true;
			return [];
		}
		$feed = $curlResult->getBody();
		$feed_data = Feed::import($feed);
		if (!$feed_data) {
			return [];
		}

		if (!empty($feed_data["header"]["author-name"])) {
			$data["name"] = $feed_data["header"]["author-name"];
		}
		if (!empty($feed_data["header"]["author-nick"])) {
			$data["nick"] = $feed_data["header"]["author-nick"];
		}
		if (!empty($feed_data["header"]["author-avatar"])) {
			$data["photo"] = self::fixAvatar($feed_data["header"]["author-avatar"], $data["url"]);
		}
		if (!empty($feed_data["header"]["author-id"])) {
			$data["alias"] = $feed_data["header"]["author-id"];
		}
		if (!empty($feed_data["header"]["author-location"])) {
			$data["location"] = $feed_data["header"]["author-location"];
		}
		if (!empty($feed_data["header"]["author-about"])) {
			$data["about"] = $feed_data["header"]["author-about"];
		}
		// OStatus has serious issues when the the url doesn't fit (ssl vs. non ssl)
		// So we take the value that we just fetched, although the other one worked as well
		if (!empty($feed_data["header"]["author-link"])) {
			$data["url"] = $feed_data["header"]["author-link"];
		}

		if ($data["url"] == $data["alias"]) {
			$data["alias"] = '';
		}

		/// @todo Fetch location and "about" from the feed as well
		return $data;
	}

	/**
	 * Fetch data from a pump.io profile page
	 *
	 * @param string $profile_link Link to the profile page
	 *
	 * @return array profile data
	 */
	private static function pumpioProfileData($profile_link)
	{
		$curlResult = Network::curl($profile_link);
		if (!$curlResult->isSuccess()) {
			return [];
		}

		$doc = new DOMDocument();
		if (!@$doc->loadHTML($curlResult->getBody())) {
			return [];
		}

		$xpath = new DomXPath($doc);

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

		$data["location"] = XML::getFirstNodeValue($xpath, "//p[contains(@class, 'p-locality')]");

		if ($data["location"] == '') {
			$data["location"] = XML::getFirstNodeValue($xpath, "//p[contains(@class, 'location')]");
		}

		$data["about"] = XML::getFirstNodeValue($xpath, "//p[contains(@class, 'p-note')]");

		if ($data["about"] == '') {
			$data["about"] = XML::getFirstNodeValue($xpath, "//p[contains(@class, 'summary')]");
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
	 * Check for pump.io contact
	 *
	 * @param array  $webfinger Webfinger data
	 * @param string $addr
	 * @return array pump.io data
	 */
	private static function pumpio($webfinger, $addr)
	{
		$data = [];
		// The array is reversed to take into account the order of preference for same-rel links
		// See: https://tools.ietf.org/html/rfc7033#section-4.4.4
		foreach (array_reverse($webfinger["links"]) as $link) {
			if (($link["rel"] == "http://webfinger.net/rel/profile-page")
				&& (($link["type"] ?? "") == "text/html")
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

			$data["network"] = Protocol::PUMPIO;
		} else {
			return [];
		}

		$profile_data = self::pumpioProfileData($data["url"]);

		if (!$profile_data) {
			return [];
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
	 * Check for twitter contact
	 *
	 * @param string $uri
	 *
	 * @return array twitter data
	 */
	private static function twitter($uri)
	{
		if (preg_match('=(.*)@twitter.com=i', $uri, $matches)) {
			$nick = $matches[1];
		} elseif (preg_match('=https?://twitter.com/(.*)=i', $uri, $matches)) {
			$nick = $matches[1];
		} else {
			return [];
		}

		$data = [];
		$data['url'] = 'https://twitter.com/' . $nick;
		$data['addr'] = $nick . '@twitter.com';
		$data['nick'] = $data['name'] = $nick;
		$data['network'] = Protocol::TWITTER;
		$data['baseurl'] = 'https://twitter.com';

		return $data;
	}

	/**
	 * Checks HTML page for RSS feed link
	 *
	 * @param string $url  Page link
	 * @param string $body Page body string
	 * @return string|false Feed link or false if body was invalid HTML document
	 */
	public static function getFeedLink(string $url, string $body)
	{
		$doc = new DOMDocument();
		if (!@$doc->loadHTML($body)) {
			return false;
		}

		$xpath = new DOMXPath($doc);

		$feedUrl = $xpath->evaluate('string(/html/head/link[@type="application/rss+xml" and @rel="alternate"]/@href)');

		$feedUrl = $feedUrl ? self::ensureAbsoluteLinkFromHTMLDoc($feedUrl, $url, $xpath) : '';

		return $feedUrl;
	}

	/**
	 * Return an absolute URL in the context of a HTML document retrieved from the provided URL.
	 *
	 * Loosely based on RFC 1808
	 *
	 * @see https://tools.ietf.org/html/rfc1808
	 *
	 * @param string   $href  The potential relative href found in the HTML document
	 * @param string   $base  The HTML document URL
	 * @param DOMXPath $xpath The HTML document XPath
	 * @return string
	 */
	private static function ensureAbsoluteLinkFromHTMLDoc(string $href, string $base, DOMXPath $xpath)
	{
		if (filter_var($href, FILTER_VALIDATE_URL)) {
			return $href;
		}

		$base = $xpath->evaluate('string(/html/head/base/@href)') ?: $base;

		$baseParts = parse_url($base);

		// Naked domain case (scheme://basehost)
		$path = $baseParts['path'] ?? '/';

		// Remove the filename part of the path if it exists (/base/path/file)
		$path = implode('/', array_slice(explode('/', $path), 0, -1));

		$hrefParts = parse_url($href);

		// Root path case (/path) including relative scheme case (//host/path)
		if ($hrefParts['path'] && $hrefParts['path'][0] == '/') {
			$path = $hrefParts['path'];
		} else {
			$path = $path . '/' . $hrefParts['path'];

			// Resolve arbitrary relative path
			// Lifted from https://www.php.net/manual/en/function.realpath.php#84012
			$parts = array_filter(explode('/', $path), 'strlen');
			$absolutes = array();
			foreach ($parts as $part) {
				if ('.' == $part) continue;
				if ('..' == $part) {
					array_pop($absolutes);
				} else {
					$absolutes[] = $part;
				}
			}

			$path = '/' . implode('/', $absolutes);
		}

		// Relative scheme case (//host/path)
		$baseParts['host'] = $hrefParts['host'] ?? $baseParts['host'];
		$baseParts['path'] = $path;
		unset($baseParts['query']);
		unset($baseParts['fragment']);

		return Network::unparseURL($baseParts);
	}

	/**
	 * Check for feed contact
	 *
	 * @param string  $url   Profile link
	 * @param boolean $probe Do a probe if the page contains a feed link
	 *
	 * @return array feed data
	 * @throws HTTPException\InternalServerErrorException
	 */
	private static function feed($url, $probe = true)
	{
		$curlResult = Network::curl($url);
		if ($curlResult->isTimeout()) {
			self::$istimeout = true;
			return [];
		}
		$feed = $curlResult->getBody();
		$feed_data = Feed::import($feed);

		if (!$feed_data) {
			if (!$probe) {
				return [];
			}

			$feed_url = self::getFeedLink($url, $feed);

			if (!$feed_url) {
				return [];
			}

			return self::feed($feed_url, false);
		}

		if (!empty($feed_data["header"]["author-name"])) {
			$data["name"] = $feed_data["header"]["author-name"];
		}

		if (!empty($feed_data["header"]["author-nick"])) {
			$data["nick"] = $feed_data["header"]["author-nick"];
		}

		if (!empty($feed_data["header"]["author-avatar"])) {
			$data["photo"] = $feed_data["header"]["author-avatar"];
		}

		if (!empty($feed_data["header"]["author-id"])) {
			$data["alias"] = $feed_data["header"]["author-id"];
		}

		$data["url"] = $url;
		$data["poll"] = $url;

		if (!empty($feed_data["header"]["author-link"])) {
			$data["baseurl"] = $feed_data["header"]["author-link"];
		} else {
			$data["baseurl"] = $data["url"];
		}

		$data["network"] = Protocol::FEED;

		return $data;
	}

	/**
	 * Check for mail contact
	 *
	 * @param string  $uri Profile link
	 * @param integer $uid User ID
	 *
	 * @return array mail data
	 * @throws \Exception
	 */
	private static function mail($uri, $uid)
	{
		if (!Network::isEmailDomainValid($uri)) {
			return [];
		}

		if ($uid == 0) {
			return [];
		}

		$user = DBA::selectFirst('user', ['prvkey'], ['uid' => $uid]);

		$condition = ["`uid` = ? AND `server` != ''", $uid];
		$fields = ['pass', 'user', 'server', 'port', 'ssltype', 'mailbox'];
		$mailacct = DBA::selectFirst('mailacct', $fields, $condition);

		if (!DBA::isResult($user) || !DBA::isResult($mailacct)) {
			return [];
		}

		$mailbox = Email::constructMailboxName($mailacct);
		$password = '';
		openssl_private_decrypt(hex2bin($mailacct['pass']), $password, $user['prvkey']);
		$mbox = Email::connect($mailbox, $mailacct['user'], $password);
		if (!$mbox) {
			return [];
		}

		$msgs = Email::poll($mbox, $uri);
		Logger::log('searching '.$uri.', '.count($msgs).' messages found.', Logger::DEBUG);

		if (!count($msgs)) {
			return [];
		}

		$phost = substr($uri, strpos($uri, '@') + 1);

		$data = [];
		$data["addr"]    = $uri;
		$data["network"] = Protocol::MAIL;
		$data["name"]    = substr($uri, 0, strpos($uri, '@'));
		$data["nick"]    = $data["name"];
		$data["photo"]   = Network::lookupAvatarByEmail($uri);
		$data["url"]     = 'mailto:'.$uri;
		$data["notify"]  = 'smtp ' . Strings::getRandomHex();
		$data["poll"]    = 'email ' . Strings::getRandomHex();

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

					$data["name"] = Strings::escapeTags($data["name"]);
				}
			}
		}
		if (!empty($mbox)) {
			imap_close($mbox);
		}
		return $data;
	}

	/**
	 * Mix two paths together to possibly fix missing parts
	 *
	 * @param string $avatar Path to the avatar
	 * @param string $base   Another path that is hopefully complete
	 *
	 * @return string fixed avatar path
	 * @throws \Exception
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

		Logger::log('Base: '.$base.' - Avatar: '.$avatar.' - Fixed: '.$fixed, Logger::DATA);

		return $fixed;
	}
}
