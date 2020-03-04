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

namespace Friendica\Util;

use DOMDocument;
use DomXPath;
use Friendica\Core\Hook;
use Friendica\Core\Logger;
use Friendica\Core\System;
use Friendica\DI;
use Friendica\Network\CurlResult;

class Network
{

	/**
	 * Return raw post data from a post request
	 *
	 * @return string post data
	 */
	public static function postdata()
	{
		return file_get_contents('php://input');
	}

	/**
	 * Check URL to see if it's real
	 *
	 * Take a URL from the wild, prepend http:// if necessary
	 * and check DNS to see if it's real (or check if is a valid IP address)
	 *
	 * @param string $url The URL to be validated
	 * @return string|boolean The actual working URL, false else
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function isUrlValid(string $url)
	{
		if (DI::config()->get('system', 'disable_url_validation')) {
			return $url;
		}

		// no naked subdomains (allow localhost for tests)
		if (strpos($url, '.') === false && strpos($url, '/localhost/') === false) {
			return false;
		}

		if (substr($url, 0, 4) != 'http') {
			$url = 'http://' . $url;
		}

		/// @TODO Really suppress function outcomes? Why not find them + debug them?
		$h = @parse_url($url);

		if (!empty($h['host']) && (@dns_get_record($h['host'], DNS_A + DNS_CNAME) || filter_var($h['host'], FILTER_VALIDATE_IP))) {
			return $url;
		}

		return false;
	}

	/**
	 * Checks that email is an actual resolvable internet address
	 *
	 * @param string $addr The email address
	 * @return boolean True if it's a valid email address, false if it's not
	 */
	public static function isEmailDomainValid(string $addr)
	{
		if (DI::config()->get('system', 'disable_email_validation')) {
			return true;
		}

		if (! strpos($addr, '@')) {
			return false;
		}

		$h = substr($addr, strpos($addr, '@') + 1);

		// Concerning the @ see here: https://stackoverflow.com/questions/36280957/dns-get-record-a-temporary-server-error-occurred
		if ($h && (@dns_get_record($h, DNS_A + DNS_MX) || filter_var($h, FILTER_VALIDATE_IP))) {
			return true;
		}
		if ($h && @dns_get_record($h, DNS_CNAME + DNS_MX)) {
			return true;
		}
		return false;
	}

	/**
	 * Check if URL is allowed
	 *
	 * Check $url against our list of allowed sites,
	 * wildcards allowed. If allowed_sites is unset return true;
	 *
	 * @param string $url URL which get tested
	 * @return boolean True if url is allowed otherwise return false
	 */
	public static function isUrlAllowed(string $url)
	{
		$h = @parse_url($url);

		if (! $h) {
			return false;
		}

		$str_allowed = DI::config()->get('system', 'allowed_sites');
		if (! $str_allowed) {
			return true;
		}

		$found = false;

		$host = strtolower($h['host']);

		// always allow our own site
		if ($host == strtolower($_SERVER['SERVER_NAME'])) {
			return true;
		}

		$fnmatch = function_exists('fnmatch');
		$allowed = explode(',', $str_allowed);

		if (count($allowed)) {
			foreach ($allowed as $a) {
				$pat = strtolower(trim($a));
				if (($fnmatch && fnmatch($pat, $host)) || ($pat == $host)) {
					$found = true;
					break;
				}
			}
		}
		return $found;
	}

	/**
	 * Checks if the provided url domain is on the domain blocklist.
	 * Returns true if it is or malformed URL, false if not.
	 *
	 * @param string $url The url to check the domain from
	 *
	 * @return boolean
	 */
	public static function isUrlBlocked(string $url)
	{
		$host = @parse_url($url, PHP_URL_HOST);
		if (!$host) {
			return false;
		}

		$domain_blocklist = DI::config()->get('system', 'blocklist', []);
		if (!$domain_blocklist) {
			return false;
		}

		foreach ($domain_blocklist as $domain_block) {
			if (fnmatch(strtolower($domain_block['domain']), strtolower($host))) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if email address is allowed to register here.
	 *
	 * Compare against our list (wildcards allowed).
	 *
	 * @param  string $email email address
	 * @return boolean False if not allowed, true if allowed
	 *                       or if allowed list is not configured
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function isEmailDomainAllowed(string $email)
	{
		$domain = strtolower(substr($email, strpos($email, '@') + 1));
		if (!$domain) {
			return false;
		}

		$str_allowed = DI::config()->get('system', 'allowed_email', '');
		if (empty($str_allowed)) {
			return true;
		}

		$allowed = explode(',', $str_allowed);

		return self::isDomainAllowed($domain, $allowed);
	}

	/**
	 * Checks for the existence of a domain in a domain list
	 *
	 * @param string $domain
	 * @param array  $domain_list
	 * @return boolean
	 */
	public static function isDomainAllowed(string $domain, array $domain_list)
	{
		$found = false;

		foreach ($domain_list as $item) {
			$pat = strtolower(trim($item));
			if (fnmatch($pat, $domain) || ($pat == $domain)) {
				$found = true;
				break;
			}
		}

		return $found;
	}

	public static function lookupAvatarByEmail(string $email)
	{
		$avatar['size'] = 300;
		$avatar['email'] = $email;
		$avatar['url'] = '';
		$avatar['success'] = false;

		Hook::callAll('avatar_lookup', $avatar);

		if (! $avatar['success']) {
			$avatar['url'] = DI::baseUrl() . '/images/person-300.jpg';
		}

		Logger::log('Avatar: ' . $avatar['email'] . ' ' . $avatar['url'], Logger::DEBUG);
		return $avatar['url'];
	}

	/**
	 * Remove Google Analytics and other tracking platforms params from URL
	 *
	 * @param string $url Any user-submitted URL that may contain tracking params
	 * @return string The same URL stripped of tracking parameters
	 */
	public static function stripTrackingQueryParams(string $url)
	{
		$urldata = parse_url($url);
		if (!empty($urldata["query"])) {
			$query = $urldata["query"];
			parse_str($query, $querydata);

			if (is_array($querydata)) {
				foreach ($querydata as $param => $value) {
					if (in_array(
						$param,
						[
							"utm_source", "utm_medium", "utm_term", "utm_content", "utm_campaign",
							"wt_mc", "pk_campaign", "pk_kwd", "mc_cid", "mc_eid",
							"fb_action_ids", "fb_action_types", "fb_ref",
							"awesm", "wtrid",
							"woo_campaign", "woo_source", "woo_medium", "woo_content", "woo_term"]
						)
					) {
						$pair = $param . "=" . urlencode($value);
						$url = str_replace($pair, "", $url);

						// Second try: if the url isn't encoded completely
						$pair = $param . "=" . str_replace(" ", "+", $value);
						$url = str_replace($pair, "", $url);

						// Third try: Maybey the url isn't encoded at all
						$pair = $param . "=" . $value;
						$url = str_replace($pair, "", $url);

						$url = str_replace(["?&", "&&"], ["?", ""], $url);
					}
				}
			}

			if (substr($url, -1, 1) == "?") {
				$url = substr($url, 0, -1);
			}
		}

		return $url;
	}

	/**
	 * Returns the original URL of the provided URL
	 *
	 * This function strips tracking query params and follows redirections, either
	 * through HTTP code or meta refresh tags. Stops after 10 redirections.
	 *
	 * @todo  Remove the $fetchbody parameter that generates an extraneous HEAD request
	 *
	 * @see   ParseUrl::getSiteinfo
	 *
	 * @param string $url       A user-submitted URL
	 * @param int    $depth     The current redirection recursion level (internal)
	 * @param bool   $fetchbody Wether to fetch the body or not after the HEAD requests
	 * @return string A canonical URL
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function finalUrl(string $url, int $depth = 1, bool $fetchbody = false)
	{
		$a = DI::app();

		$url = self::stripTrackingQueryParams($url);

		if ($depth > 10) {
			return $url;
		}

		$url = trim($url, "'");

		$stamp1 = microtime(true);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, 1);
		curl_setopt($ch, CURLOPT_NOBODY, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_USERAGENT, $a->getUserAgent());

		curl_exec($ch);
		$curl_info = @curl_getinfo($ch);
		$http_code = $curl_info['http_code'];
		curl_close($ch);

		DI::profiler()->saveTimestamp($stamp1, "network", System::callstack());

		if ($http_code == 0) {
			return $url;
		}

		if (in_array($http_code, ['301', '302'])) {
			if (!empty($curl_info['redirect_url'])) {
				return self::finalUrl($curl_info['redirect_url'], ++$depth, $fetchbody);
			} elseif (!empty($curl_info['location'])) {
				return self::finalUrl($curl_info['location'], ++$depth, $fetchbody);
			}
		}

		// Check for redirects in the meta elements of the body if there are no redirects in the header.
		if (!$fetchbody) {
			return(self::finalUrl($url, ++$depth, true));
		}

		// if the file is too large then exit
		if ($curl_info["download_content_length"] > 1000000) {
			return $url;
		}

		// if it isn't a HTML file then exit
		if (!empty($curl_info["content_type"]) && !strstr(strtolower($curl_info["content_type"]), "html")) {
			return $url;
		}

		$stamp1 = microtime(true);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_NOBODY, 0);
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_USERAGENT, $a->getUserAgent());

		$body = curl_exec($ch);
		curl_close($ch);

		DI::profiler()->saveTimestamp($stamp1, "network", System::callstack());

		if (trim($body) == "") {
			return $url;
		}

		// Check for redirect in meta elements
		$doc = new DOMDocument();
		@$doc->loadHTML($body);

		$xpath = new DomXPath($doc);

		$list = $xpath->query("//meta[@content]");
		foreach ($list as $node) {
			$attr = [];
			if ($node->attributes->length) {
				foreach ($node->attributes as $attribute) {
					$attr[$attribute->name] = $attribute->value;
				}
			}

			if (@$attr["http-equiv"] == 'refresh') {
				$path = $attr["content"];
				$pathinfo = explode(";", $path);
				foreach ($pathinfo as $value) {
					if (substr(strtolower($value), 0, 4) == "url=") {
						return self::finalUrl(substr($value, 4), ++$depth);
					}
				}
			}
		}

		return $url;
	}

	/**
	 * Find the matching part between two url
	 *
	 * @param string $url1
	 * @param string $url2
	 * @return string The matching part
	 */
	public static function getUrlMatch(string $url1, string $url2)
	{
		if (($url1 == "") || ($url2 == "")) {
			return "";
		}

		$url1 = Strings::normaliseLink($url1);
		$url2 = Strings::normaliseLink($url2);

		$parts1 = parse_url($url1);
		$parts2 = parse_url($url2);

		if (!isset($parts1["host"]) || !isset($parts2["host"])) {
			return "";
		}

		if (empty($parts1["scheme"])) {
			$parts1["scheme"] = '';
		}
		if (empty($parts2["scheme"])) {
			$parts2["scheme"] = '';
		}

		if ($parts1["scheme"] != $parts2["scheme"]) {
			return "";
		}

		if (empty($parts1["host"])) {
			$parts1["host"] = '';
		}
		if (empty($parts2["host"])) {
			$parts2["host"] = '';
		}

		if ($parts1["host"] != $parts2["host"]) {
			return "";
		}

		if (empty($parts1["port"])) {
			$parts1["port"] = '';
		}
		if (empty($parts2["port"])) {
			$parts2["port"] = '';
		}

		if ($parts1["port"] != $parts2["port"]) {
			return "";
		}

		$match = $parts1["scheme"]."://".$parts1["host"];

		if ($parts1["port"]) {
			$match .= ":".$parts1["port"];
		}

		if (empty($parts1["path"])) {
			$parts1["path"] = '';
		}
		if (empty($parts2["path"])) {
			$parts2["path"] = '';
		}

		$pathparts1 = explode("/", $parts1["path"]);
		$pathparts2 = explode("/", $parts2["path"]);

		$i = 0;
		$path = "";
		do {
			$path1 = $pathparts1[$i] ?? '';
			$path2 = $pathparts2[$i] ?? '';

			if ($path1 == $path2) {
				$path .= $path1."/";
			}
		} while (($path1 == $path2) && ($i++ <= count($pathparts1)));

		$match .= $path;

		return Strings::normaliseLink($match);
	}

	/**
	 * Glue url parts together
	 *
	 * @param array $parsed URL parts
	 *
	 * @return string The glued URL
	 */
	public static function unparseURL(array $parsed)
	{
		$get = function ($key) use ($parsed) {
			return isset($parsed[$key]) ? $parsed[$key] : null;
		};

		$pass      = $get('pass');
		$user      = $get('user');
		$userinfo  = $pass !== null ? "$user:$pass" : $user;
		$port      = $get('port');
		$scheme    = $get('scheme');
		$query     = $get('query');
		$fragment  = $get('fragment');
		$authority = ($userinfo !== null ? $userinfo."@" : '') .
						$get('host') .
						($port ? ":$port" : '');

		return	(strlen($scheme) ? $scheme.":" : '') .
			(strlen($authority) ? "//".$authority : '') .
			$get('path') .
			(strlen($query) ? "?".$query : '') .
			(strlen($fragment) ? "#".$fragment : '');
	}


	/**
	 * Switch the scheme of an url between http and https
	 *
	 * @param string $url URL
	 *
	 * @return string switched URL
	 */
	public static function switchScheme(string $url)
	{
		$scheme = parse_url($url, PHP_URL_SCHEME);
		if (empty($scheme)) {
			return $url;
		}

		if ($scheme === 'http') {
			$url = str_replace('http://', 'https://', $url);
		} elseif ($scheme === 'https') {
			$url = str_replace('https://', 'http://', $url);
		}

		return $url;
	}

	/**
	 * Adds query string parameters to the provided URI. Replace the value of existing keys.
	 *
	 * @param string $path
	 * @param array  $additionalParams Associative array of parameters
	 * @return string
	 */
	public static function appendQueryParam(string $path, array $additionalParams)
	{
		$parsed = parse_url($path);

		$params = [];
		if (!empty($parsed['query'])) {
			parse_str($parsed['query'], $params);
		}

		$params = array_merge($params, $additionalParams);

		$parsed['query'] = http_build_query($params);

		return self::unparseURL($parsed);
	}
}
