<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
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

use Friendica\Core\Hook;
use Friendica\Core\Logger;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Network\HTTPClient\Client\HttpClientAccept;
use Friendica\Network\HTTPClient\Client\HttpClientOptions;
use Friendica\Network\HTTPException\NotModifiedException;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\UriInterface;

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
	 *
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

		$xrd_timeout = DI::config()->get('system', 'xrd_timeout');
		$host = parse_url($url, PHP_URL_HOST);

		if (empty($host) || !(filter_var($host, FILTER_VALIDATE_IP) || @dns_get_record($host . '.', DNS_A + DNS_AAAA))) {
			return false;
		}

		if (in_array(parse_url($url, PHP_URL_SCHEME), ['https', 'http'])) {
			$options = [HttpClientOptions::VERIFY => true, HttpClientOptions::TIMEOUT => $xrd_timeout];
			$curlResult = DI::httpClient()->head($url, $options);

			// Workaround for systems that can't handle a HEAD request. Don't retry on timeouts.
			if (!$curlResult->isSuccess() && ($curlResult->getReturnCode() >= 400) && !in_array($curlResult->getReturnCode(), [408, 504])) {
				$curlResult = DI::httpClient()->get($url, HttpClientAccept::DEFAULT, $options);
			}

			if (!$curlResult->isSuccess()) {
				Logger::notice('Url not reachable', ['host' => $host, 'url' => $url]);
				return false;
			} elseif ($curlResult->isRedirectUrl()) {
				$url = $curlResult->getRedirectUrl();
			}
		}

		return $url;
	}

	/**
	 * Checks that email is an actual resolvable internet address
	 *
	 * @param string $addr The email address
	 * @return boolean True if it's a valid email address, false if it's not
	 */
	public static function isEmailDomainValid(string $addr): bool
	{
		if (DI::config()->get('system', 'disable_email_validation')) {
			return true;
		}

		if (! strpos($addr, '@')) {
			return false;
		}

		$h = substr($addr, strpos($addr, '@') + 1);

		// Concerning the @ see here: https://stackoverflow.com/questions/36280957/dns-get-record-a-temporary-server-error-occurred
		if ($h && (@dns_get_record($h, DNS_A + DNS_AAAA + DNS_MX) || filter_var($h, FILTER_VALIDATE_IP))) {
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
	public static function isUrlAllowed(string $url): bool
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
	 *
	 * @deprecated since 2023.03 Use isUriBlocked instead
	 */
	public static function isUrlBlocked(string $url): bool
	{
		try {
			return self::isUriBlocked(new Uri($url));
		} catch (\Throwable $e) {
			Logger::warning('Invalid URL', ['url' => $url]);
			return false;
		}
	}

	/**
	 * Checks if the provided URI domain is on the domain blocklist.
	 *
	 * @param UriInterface $uri
	 * @return boolean
	 */
	public static function isUriBlocked(UriInterface $uri): bool
	{
		if (!$uri->getHost()) {
			return false;
		}

		$domain_blocklist = DI::config()->get('system', 'blocklist', []);
		if (!$domain_blocklist) {
			return false;
		}

		foreach ($domain_blocklist as $domain_block) {
			if (fnmatch(strtolower($domain_block['domain']), strtolower($uri->getHost()))) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Checks if the provided url is on the list of domains where redirects are blocked.
	 * Returns true if it is or malformed URL, false if not.
	 *
	 * @param string $url The url to check the domain from
	 *
	 * @return boolean
	 */
	public static function isRedirectBlocked(string $url): bool
	{
		$host = @parse_url($url, PHP_URL_HOST);
		if (!$host) {
			return false;
		}

		$no_redirect_list = DI::config()->get('system', 'no_redirect_list', []);
		if (!$no_redirect_list) {
			return false;
		}

		foreach ($no_redirect_list as $no_redirect) {
			if (fnmatch(strtolower($no_redirect), strtolower($host))) {
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
	public static function isEmailDomainAllowed(string $email): bool
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
	 *
	 * @return boolean
	 */
	public static function isDomainAllowed(string $domain, array $domain_list): bool
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

	public static function lookupAvatarByEmail(string $email): string
	{
		$avatar['size'] = 300;
		$avatar['email'] = $email;
		$avatar['url'] = '';
		$avatar['success'] = false;

		Hook::callAll('avatar_lookup', $avatar);

		if (! $avatar['success']) {
			$avatar['url'] = DI::baseUrl() . Contact::DEFAULT_AVATAR_PHOTO;
		}

		Logger::info('Avatar: ' . $avatar['email'] . ' ' . $avatar['url']);
		return $avatar['url'];
	}

	/**
	 * Remove Google Analytics and other tracking platforms params from URL
	 *
	 * @param string $url Any user-submitted URL that may contain tracking params
	 *
	 * @return string The same URL stripped of tracking parameters
	 */
	public static function stripTrackingQueryParams(string $url): string
	{
		$urldata = parse_url($url);

		if (!empty($urldata['query'])) {
			$query = $urldata['query'];
			parse_str($query, $querydata);

			if (is_array($querydata)) {
				foreach ($querydata as $param => $value) {
					if (in_array(
						$param,
						[
							'utm_source', 'utm_medium', 'utm_term', 'utm_content', 'utm_campaign',
							// As seen from Purism
							'mtm_source', 'mtm_medium', 'mtm_term', 'mtm_content', 'mtm_campaign',
							'wt_mc', 'pk_campaign', 'pk_kwd', 'mc_cid', 'mc_eid',
							'fb_action_ids', 'fb_action_types', 'fb_ref',
							'awesm', 'wtrid',
							'woo_campaign', 'woo_source', 'woo_medium', 'woo_content', 'woo_term']
						)
					) {
						$pair = $param . '=' . urlencode($value);
						$url = str_replace($pair, '', $url);

						// Second try: if the url isn't encoded completely
						$pair = $param . '=' . str_replace(' ', '+', $value);
						$url = str_replace($pair, '', $url);

						// Third try: Maybey the url isn't encoded at all
						$pair = $param . '=' . $value;
						$url = str_replace($pair, '', $url);

						$url = str_replace(['?&', '&&'], ['?', ''], $url);
					}
				}
			}

			if (substr($url, -1, 1) == '?') {
				$url = substr($url, 0, -1);
			}
		}

		return $url;
	}

	/**
	 * Add a missing base path (scheme and host) to a given url
	 *
	 * @param string $url
	 * @param string $basepath
	 *
	 * @return string url
	 */
	public static function addBasePath(string $url, string $basepath): string
	{
		$url = trim($url);
		if (!empty(parse_url($url, PHP_URL_SCHEME)) || empty(parse_url($basepath, PHP_URL_SCHEME)) || empty($url) || empty(parse_url($url))) {
			return $url;
		}

		$base = [
			'scheme' => parse_url($basepath, PHP_URL_SCHEME),
			'host' => parse_url($basepath, PHP_URL_HOST),
		];

		$parts = array_merge($base, parse_url('/' . ltrim($url, '/')));
		return self::unparseURL($parts);
	}

	/**
	 * Find the matching part between two url
	 *
	 * @param string $url1
	 * @param string $url2
	 *
	 * @return string The matching part or empty string on error
	 */
	public static function getUrlMatch(string $url1, string $url2): string
	{
		if (($url1 == '') || ($url2 == '')) {
			return '';
		}

		$url1 = Strings::normaliseLink($url1);
		$url2 = Strings::normaliseLink($url2);

		$parts1 = parse_url($url1);
		$parts2 = parse_url($url2);

		if (!isset($parts1['host']) || !isset($parts2['host'])) {
			return '';
		}

		if (empty($parts1['scheme'])) {
			$parts1['scheme'] = '';
		}
		if (empty($parts2['scheme'])) {
			$parts2['scheme'] = '';
		}

		if ($parts1['scheme'] != $parts2['scheme']) {
			return '';
		}

		if (empty($parts1['host'])) {
			$parts1['host'] = '';
		}
		if (empty($parts2['host'])) {
			$parts2['host'] = '';
		}

		if ($parts1['host'] != $parts2['host']) {
			return '';
		}

		if (empty($parts1['port'])) {
			$parts1['port'] = '';
		}
		if (empty($parts2['port'])) {
			$parts2['port'] = '';
		}

		if ($parts1['port'] != $parts2['port']) {
			return '';
		}

		$match = $parts1['scheme'] . '://' . $parts1['host'];

		if ($parts1['port']) {
			$match .= ':' . $parts1['port'];
		}

		if (empty($parts1['path'])) {
			$parts1['path'] = '';
		}
		if (empty($parts2['path'])) {
			$parts2['path'] = '';
		}

		$pathparts1 = explode('/', $parts1['path']);
		$pathparts2 = explode('/', $parts2['path']);

		$i = 0;
		$path = '';
		do {
			$path1 = $pathparts1[$i] ?? '';
			$path2 = $pathparts2[$i] ?? '';

			if ($path1 == $path2) {
				$path .= $path1 . '/';
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
	 * @return string|null The glued URL or null on error
	 * @deprecated since version 2021.12, use GuzzleHttp\Psr7\Uri::fromParts($parts) instead
	 */
	public static function unparseURL(array $parsed): string
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
		$authority = ($userinfo !== null ? $userinfo . '@' : '') .
						$get('host') .
						($port ? ":$port" : '');

		return	(!empty($scheme) ? $scheme . ':' : '') .
			(!empty($authority) ? '//' . $authority : '') .
			$get('path') .
			(!empty($query) ? '?' . $query : '') .
			(!empty($fragment) ? '#' . $fragment : '');
	}

	/**
	 * Convert an URI to an IDN compatible URI
	 *
	 * @param string $uri
	 *
	 * @return string
	 */
	public static function convertToIdn(string $uri): string
	{
		$parts = parse_url($uri);
		if (!empty($parts['scheme']) && !empty($parts['host'])) {
			$parts['host'] = idn_to_ascii($parts['host']);
			$uri = (string)Uri::fromParts($parts);
		} else {
			$parts = explode('@', $uri);
			if (count($parts) == 2) {
				$uri = $parts[0] . '@' . idn_to_ascii($parts[1]);
			} else {
				$uri = idn_to_ascii($uri);
			}
		}

		return $uri;
	}

	/**
	 * Switch the scheme of an url between http and https
	 *
	 * @param string $url
	 *
	 * @return string Switched URL
	 */
	public static function switchScheme(string $url): string
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
	 *
	 * @return string
	 */
	public static function appendQueryParam(string $path, array $additionalParams): string
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

	/**
	 * Generates ETag and Last-Modified response headers and checks them against
	 * If-None-Match and If-Modified-Since request headers if present.
	 *
	 * Blocking function, sends 304 headers and exits if check passes.
	 *
	 * @param string $etag          The page etag
	 * @param string $last_modified The page last modification UTC date
	 *
	 * @return void
	 * @throws \Exception
	 */
	public static function checkEtagModified(string $etag, string $last_modified)
	{
		$last_modified = DateTimeFormat::utc($last_modified, 'D, d M Y H:i:s') . ' GMT';

		/**
		 * @see http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.26
		 */
		$if_none_match     = filter_input(INPUT_SERVER, 'HTTP_IF_NONE_MATCH');
		$if_modified_since = filter_input(INPUT_SERVER, 'HTTP_IF_MODIFIED_SINCE');
		$flag_not_modified = null;
		if ($if_none_match) {
			$result = [];
			preg_match('/^(?:W\/")?([^"]+)"?$/i', $etag, $result);
			$etagTrimmed = $result[1];
			// Lazy exact ETag match, could check weak/strong ETags
			$flag_not_modified = $if_none_match == '*' || strpos($if_none_match, $etagTrimmed) !== false;
		}

		if ($if_modified_since && (!$if_none_match || $flag_not_modified)) {
			// Lazy exact Last-Modified match, could check If-Modified-Since validity
			$flag_not_modified = $if_modified_since == $last_modified;
		}

		header('Etag: ' . $etag);
		header('Last-Modified: ' . $last_modified);

		if ($flag_not_modified) {
			throw new NotModifiedException();
		}
	}

	/**
	 * Check if the given URL is a local link
	 *
	 * @param string $url
	 *
	 * @return bool
	 */
	public static function isLocalLink(string $url): bool
	{
		return (strpos(Strings::normaliseLink($url), Strings::normaliseLink(DI::baseUrl())) !== false);
	}

	/**
	 * Check if the given URL is a valid HTTP/HTTPS URL
	 *
	 * @param string $url
	 * @return bool
	 */
	public static function isValidHttpUrl(string $url): bool
	{
		$scheme = parse_url($url, PHP_URL_SCHEME);
		return !empty($scheme) && in_array($scheme, ['http', 'https']) && parse_url($url, PHP_URL_HOST);
	}
}
