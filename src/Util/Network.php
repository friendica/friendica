<?php
/**
 * @file src/Util/Network.php
 */
namespace Friendica\Util;

use DOMDocument;
use DomXPath;
use Friendica\Core\Config;
use Friendica\Core\Hook;
use Friendica\Core\Logger;
use Friendica\Core\System;
use Friendica\DI;
use Friendica\Network\CurlResult;

class Network
{
	/**
	 * Curl wrapper
	 *
	 * If binary flag is true, return binary results.
	 * Set the cookiejar argument to a string (e.g. "/tmp/friendica-cookies.txt")
	 * to preserve cookies from one request to the next.
	 *
	 * @brief Curl wrapper
	 * @param string  $url            URL to fetch
	 * @param bool    $binary         default false
	 *                                TRUE if asked to return binary results (file download)
	 * @param int     $timeout        Timeout in seconds, default system config value or 60 seconds
	 * @param string  $accept_content supply Accept: header with 'accept_content' as the value
	 * @param string  $cookiejar      Path to cookie jar file
	 * @param int     $redirects      The recursion counter for internal use - default 0
	 *
	 * @return string The fetched content
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function fetchUrl(string $url, bool $binary = false, int $timeout = 0, string $accept_content = '', string $cookiejar = '', int &$redirects = 0)
	{
		$ret = self::fetchUrlFull($url, $binary, $timeout, $accept_content, $cookiejar, $redirects);

		return $ret->getBody();
	}

	/**
	 * Curl wrapper with array of return values.
	 *
	 * Inner workings and parameters are the same as @ref fetchUrl but returns an array with
	 * all the information collected during the fetch.
	 *
	 * @brief Curl wrapper with array of return values.
	 * @param string  $url            URL to fetch
	 * @param bool    $binary         default false
	 *                                TRUE if asked to return binary results (file download)
	 * @param int     $timeout        Timeout in seconds, default system config value or 60 seconds
	 * @param string  $accept_content supply Accept: header with 'accept_content' as the value
	 * @param string  $cookiejar      Path to cookie jar file
	 * @param int     $redirects      The recursion counter for internal use - default 0
	 *
	 * @return CurlResult With all relevant information, 'body' contains the actual fetched content.
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function fetchUrlFull(string $url, bool $binary = false, int $timeout = 0, string $accept_content = '', string $cookiejar = '', int &$redirects = 0)
	{
		return self::curl(
			$url,
			$binary,
			[
				'timeout'        => $timeout,
				'accept_content' => $accept_content,
				'cookiejar'      => $cookiejar
			],
			$redirects
		);
	}

	/**
	 * @brief fetches an URL.
	 *
	 * @param string  $url       URL to fetch
	 * @param bool    $binary    default false
	 *                           TRUE if asked to return binary results (file download)
	 * @param array   $opts      (optional parameters) assoziative array with:
	 *                           'accept_content' => supply Accept: header with 'accept_content' as the value
	 *                           'timeout' => int Timeout in seconds, default system config value or 60 seconds
	 *                           'http_auth' => username:password
	 *                           'novalidate' => do not validate SSL certs, default is to validate using our CA list
	 *                           'nobody' => only return the header
	 *                           'cookiejar' => path to cookie jar file
	 *                           'header' => header array
	 * @param int     $redirects The recursion counter for internal use - default 0
	 *
	 * @return CurlResult
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function curl(string $url, bool $binary = false, array $opts = [], int &$redirects = 0)
	{
		$stamp1 = microtime(true);

		$a = \get_app();

		if (strlen($url) > 1000) {
			Logger::log('URL is longer than 1000 characters. Callstack: ' . System::callstack(20), Logger::DEBUG);
			return CurlResult::createErrorCurl(substr($url, 0, 200));
		}

		$parts2 = [];
		$parts = parse_url($url);
		$path_parts = explode('/', $parts['path'] ?? '');
		foreach ($path_parts as $part) {
			if (strlen($part) <> mb_strlen($part)) {
				$parts2[] = rawurlencode($part);
			} else {
				$parts2[] = $part;
			}
		}
		$parts['path'] = implode('/', $parts2);
		$url = self::unparseURL($parts);

		if (self::isUrlBlocked($url)) {
			Logger::log('domain of ' . $url . ' is blocked', Logger::DATA);
			return CurlResult::createErrorCurl($url);
		}

		$ch = @curl_init($url);

		if (($redirects > 8) || (!$ch)) {
			return CurlResult::createErrorCurl($url);
		}

		@curl_setopt($ch, CURLOPT_HEADER, true);

		if (!empty($opts['cookiejar'])) {
			curl_setopt($ch, CURLOPT_COOKIEJAR, $opts["cookiejar"]);
			curl_setopt($ch, CURLOPT_COOKIEFILE, $opts["cookiejar"]);
		}

		// These settings aren't needed. We're following the location already.
		//	@curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		//	@curl_setopt($ch, CURLOPT_MAXREDIRS, 5);

		if (!empty($opts['accept_content'])) {
			curl_setopt(
				$ch,
				CURLOPT_HTTPHEADER,
				['Accept: ' . $opts['accept_content']]
			);
		}

		if (!empty($opts['header'])) {
			curl_setopt($ch, CURLOPT_HTTPHEADER, $opts['header']);
		}

		@curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		@curl_setopt($ch, CURLOPT_USERAGENT, $a->getUserAgent());

		$range = intval(Config::get('system', 'curl_range_bytes', 0));

		if ($range > 0) {
			@curl_setopt($ch, CURLOPT_RANGE, '0-' . $range);
		}

		// Without this setting it seems as if some webservers send compressed content
		// This seems to confuse curl so that it shows this uncompressed.
		/// @todo  We could possibly set this value to "gzip" or something similar
		curl_setopt($ch, CURLOPT_ENCODING, '');

		if (!empty($opts['headers'])) {
			@curl_setopt($ch, CURLOPT_HTTPHEADER, $opts['headers']);
		}

		if (!empty($opts['nobody'])) {
			@curl_setopt($ch, CURLOPT_NOBODY, $opts['nobody']);
		}

		if (!empty($opts['timeout'])) {
			@curl_setopt($ch, CURLOPT_TIMEOUT, $opts['timeout']);
		} else {
			$curl_time = Config::get('system', 'curl_timeout', 60);
			@curl_setopt($ch, CURLOPT_TIMEOUT, intval($curl_time));
		}

		// by default we will allow self-signed certs
		// but you can override this

		$check_cert = Config::get('system', 'verifyssl');
		@curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, (($check_cert) ? true : false));

		if ($check_cert) {
			@curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		}

		$proxy = Config::get('system', 'proxy');

		if (strlen($proxy)) {
			@curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, 1);
			@curl_setopt($ch, CURLOPT_PROXY, $proxy);
			$proxyuser = @Config::get('system', 'proxyuser');

			if (strlen($proxyuser)) {
				@curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxyuser);
			}
		}

		if (Config::get('system', 'ipv4_resolve', false)) {
			curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
		}

		if ($binary) {
			@curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
		}

		// don't let curl abort the entire application
		// if it throws any errors.

		$s = @curl_exec($ch);
		$curl_info = @curl_getinfo($ch);

		// Special treatment for HTTP Code 416
		// See https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/416
		if (($curl_info['http_code'] == 416) && ($range > 0)) {
			@curl_setopt($ch, CURLOPT_RANGE, '');
			$s = @curl_exec($ch);
			$curl_info = @curl_getinfo($ch);
		}

		$curlResponse = new CurlResult($url, $s, $curl_info, curl_errno($ch), curl_error($ch));

		if ($curlResponse->isRedirectUrl()) {
			$redirects++;
			Logger::log('curl: redirect ' . $url . ' to ' . $curlResponse->getRedirectUrl());
			@curl_close($ch);
			return self::curl($curlResponse->getRedirectUrl(), $binary, $opts, $redirects);
		}

		@curl_close($ch);

		DI::profiler()->saveTimestamp($stamp1, 'network', System::callstack());

		return $curlResponse;
	}

	/**
	 * @brief Send POST request to $url
	 *
	 * @param string  $url       URL to post
	 * @param mixed   $params    array of POST variables
	 * @param array   $headers   HTTP headers
	 * @param int     $redirects Recursion counter for internal use - default = 0
	 * @param int     $timeout   The timeout in seconds, default system config value or 60 seconds
	 *
	 * @return CurlResult The content
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function post(string $url, $params, array $headers = [], int $timeout = 0, int &$redirects = 0)
	{
		$stamp1 = microtime(true);

		if (self::isUrlBlocked($url)) {
			Logger::log('post_url: domain of ' . $url . ' is blocked', Logger::DATA);
			return CurlResult::createErrorCurl($url);
		}

		$a = \get_app();
		$ch = curl_init($url);

		if (($redirects > 8) || (!$ch)) {
			return CurlResult::createErrorCurl($url);
		}

		Logger::log('post_url: start ' . $url, Logger::DATA);

		curl_setopt($ch, CURLOPT_HEADER, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
		curl_setopt($ch, CURLOPT_USERAGENT, $a->getUserAgent());

		if (Config::get('system', 'ipv4_resolve', false)) {
			curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
		}

		if (intval($timeout)) {
			curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
		} else {
			$curl_time = Config::get('system', 'curl_timeout', 60);
			curl_setopt($ch, CURLOPT_TIMEOUT, intval($curl_time));
		}

		if (!empty($headers)) {
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		}

		$check_cert = Config::get('system', 'verifyssl');
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, (($check_cert) ? true : false));

		if ($check_cert) {
			@curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		}

		$proxy = Config::get('system', 'proxy');

		if (strlen($proxy)) {
			curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, 1);
			curl_setopt($ch, CURLOPT_PROXY, $proxy);
			$proxyuser = Config::get('system', 'proxyuser');
			if (strlen($proxyuser)) {
				curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxyuser);
			}
		}

		// don't let curl abort the entire application
		// if it throws any errors.

		$s = @curl_exec($ch);

		$curl_info = curl_getinfo($ch);

		$curlResponse = new CurlResult($url, $s, $curl_info, curl_errno($ch), curl_error($ch));

		if ($curlResponse->isRedirectUrl()) {
			$redirects++;
			Logger::log('post_url: redirect ' . $url . ' to ' . $curlResponse->getRedirectUrl());
			curl_close($ch);
			return self::post($curlResponse->getRedirectUrl(), $params, $headers, $redirects, $timeout);
		}

		curl_close($ch);

		DI::profiler()->saveTimestamp($stamp1, 'network', System::callstack());

		// Very old versions of Lighttpd don't like the "Expect" header, so we remove it when needed
		if ($curlResponse->getReturnCode() == 417) {
			$redirects++;

			if (empty($headers)) {
				$headers = ['Expect:'];
			} else {
				if (!in_array('Expect:', $headers)) {
					array_push($headers, 'Expect:');
				}
			}
			Logger::info('Server responds with 417, applying workaround', ['url' => $url]);
			return self::post($url, $params, $headers, $redirects, $timeout);
		}

		Logger::log('post_url: end ' . $url, Logger::DATA);

		return $curlResponse;
	}

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
	 * @brief Check URL to see if it's real
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
		if (Config::get('system', 'disable_url_validation')) {
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
	 * @brief Checks that email is an actual resolvable internet address
	 *
	 * @param string $addr The email address
	 * @return boolean True if it's a valid email address, false if it's not
	 */
	public static function isEmailDomainValid(string $addr)
	{
		if (Config::get('system', 'disable_email_validation')) {
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
	 * @brief Check if URL is allowed
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

		$str_allowed = Config::get('system', 'allowed_sites');
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

		$domain_blocklist = Config::get('system', 'blocklist', []);
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
	 * @brief Check if email address is allowed to register here.
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

		$str_allowed = Config::get('system', 'allowed_email', '');
		if (empty($str_allowed)) {
			return true;
		}

		$allowed = explode(',', $str_allowed);

		return self::isDomainAllowed($domain, $allowed);
	}

	/**
	 * Checks for the existence of a domain in a domain list
	 *
	 * @brief Checks for the existence of a domain in a domain list
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
			$avatar['url'] = System::baseUrl() . '/images/person-300.jpg';
		}

		Logger::log('Avatar: ' . $avatar['email'] . ' ' . $avatar['url'], Logger::DEBUG);
		return $avatar['url'];
	}

	/**
	 * @brief Remove Google Analytics and other tracking platforms params from URL
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
	 * @brief Returns the original URL of the provided URL
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
		$a = \get_app();

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
	 * @brief Find the matching part between two url
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
	 * @brief Glue url parts together
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
}
