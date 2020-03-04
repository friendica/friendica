<?php
/**
 * @copyright Copyright (C) 2020, Friendica
 *
 * @license GNU APGL version 3 or any later version
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

use Friendica\App;
use Friendica\Core\Config\IConfig;
use Friendica\Core\Logger;
use Friendica\Core\System;
use Friendica\Util\Network;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

/**
 * Performs HTTP requests to a given URL
 */
class HTTPRequest
{
	/** @var LoggerInterface */
	private $logger;
	/** @var Profiler */
	private $profiler;
	/** @var IConfig */
	private $config;
	/** @var string */
	private $userAgent;

	public function __construct(LoggerInterface $logger, Profiler $profiler, IConfig $config, App $a)
	{
		$this->logger    = $logger;
		$this->profiler  = $profiler;
		$this->config    = $config;
		$this->userAgent = $a->getUserAgent();
	}

	/**
	 * fetches an URL.
	 *
	 * @param string $url        URL to fetch
	 * @param bool   $binary     default false
	 *                           TRUE if asked to return binary results (file download)
	 * @param array  $opts       (optional parameters) assoziative array with:
	 *                           'accept_content' => supply Accept: header with 'accept_content' as the value
	 *                           'timeout' => int Timeout in seconds, default system config value or 60 seconds
	 *                           'http_auth' => username:password
	 *                           'novalidate' => do not validate SSL certs, default is to validate using our CA list
	 *                           'nobody' => only return the header
	 *                           'cookiejar' => path to cookie jar file
	 *                           'header' => header array
	 * @param int    $redirects  The recursion counter for internal use - default 0
	 *
	 * @return CurlResult
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public function curl(string $url, bool $binary = false, array $opts = [], int &$redirects = 0)
	{
		$stamp1 = microtime(true);

		if (strlen($url) > 1000) {
			$this->logger->debug('URL is longer than 1000 characters.', ['url' => $url, 'callstack' => System::callstack(20)]);
			return CurlResult::createErrorCurl(substr($url, 0, 200));
		}

		$parts2     = [];
		$parts      = parse_url($url);
		$path_parts = explode('/', $parts['path'] ?? '');
		foreach ($path_parts as $part) {
			if (strlen($part) <> mb_strlen($part)) {
				$parts2[] = rawurlencode($part);
			} else {
				$parts2[] = $part;
			}
		}
		$parts['path'] = implode('/', $parts2);
		$url           = Network::unparseURL($parts);

		if (Network::isUrlBlocked($url)) {
			$this->logger->info('Domain is blocked.', ['url' => $url]);
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
		@curl_setopt($ch, CURLOPT_USERAGENT, $this->userAgent);

		$range = intval($this->config->get('system', 'curl_range_bytes', 0));

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
			$curl_time = $this->config->get('system', 'curl_timeout', 60);
			@curl_setopt($ch, CURLOPT_TIMEOUT, intval($curl_time));
		}

		// by default we will allow self-signed certs
		// but you can override this

		$check_cert = $this->config->get('system', 'verifyssl');
		@curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, (($check_cert) ? true : false));

		if ($check_cert) {
			@curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		}

		$proxy = $this->config->get('system', 'proxy');

		if (!empty($proxy)) {
			@curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, 1);
			@curl_setopt($ch, CURLOPT_PROXY, $proxy);
			$proxyuser = $this->config->get('system', 'proxyuser');

			if (!empty($proxyuser)) {
				@curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxyuser);
			}
		}

		if ($this->config->get('system', 'ipv4_resolve', false)) {
			curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
		}

		if ($binary) {
			@curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
		}

		// don't let curl abort the entire application
		// if it throws any errors.

		$s         = @curl_exec($ch);
		$curl_info = @curl_getinfo($ch);

		// Special treatment for HTTP Code 416
		// See https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/416
		if (($curl_info['http_code'] == 416) && ($range > 0)) {
			@curl_setopt($ch, CURLOPT_RANGE, '');
			$s         = @curl_exec($ch);
			$curl_info = @curl_getinfo($ch);
		}

		$curlResponse = new CurlResult($url, $s, $curl_info, curl_errno($ch), curl_error($ch));

		if ($curlResponse->isRedirectUrl()) {
			$redirects++;
			$this->logger->notice('Curl redirect.', ['url' => $url, 'to' => $curlResponse->getRedirectUrl()]);
			@curl_close($ch);
			return self::curl($curlResponse->getRedirectUrl(), $binary, $opts, $redirects);
		}

		@curl_close($ch);

		$this->profiler->saveTimestamp($stamp1, 'network', System::callstack());

		return $curlResponse;
	}

	/**
	 * Send POST request to $url
	 *
	 * @param string $url       URL to post
	 * @param mixed  $params    array of POST variables
	 * @param array  $headers   HTTP headers
	 * @param int    $redirects Recursion counter for internal use - default = 0
	 * @param int    $timeout   The timeout in seconds, default system config value or 60 seconds
	 *
	 * @return CurlResult The content
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public function post(string $url, $params, array $headers = [], int $timeout = 0, int &$redirects = 0)
	{
		$stamp1 = microtime(true);

		if (Network::isUrlBlocked($url)) {
			$this->logger->info('Domain is blocked.'. ['url' => $url]);
			return CurlResult::createErrorCurl($url);
		}

		$ch = curl_init($url);

		if (($redirects > 8) || (!$ch)) {
			return CurlResult::createErrorCurl($url);
		}

		$this->logger->debug('Post_url: start.', ['url' => $url]);

		curl_setopt($ch, CURLOPT_HEADER, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
		curl_setopt($ch, CURLOPT_USERAGENT, $a->getUserAgent());

		if ($this->config->get('system', 'ipv4_resolve', false)) {
			curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
		}

		if (intval($timeout)) {
			curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
		} else {
			$curl_time = $this->config->get('system', 'curl_timeout', 60);
			curl_setopt($ch, CURLOPT_TIMEOUT, intval($curl_time));
		}

		if (!empty($headers)) {
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		}

		$check_cert = $this->config->get('system', 'verifyssl');
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, (($check_cert) ? true : false));

		if ($check_cert) {
			@curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		}

		$proxy = $this->config->get('system', 'proxy');

		if (!empty($proxy)) {
			curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, 1);
			curl_setopt($ch, CURLOPT_PROXY, $proxy);
			$proxyuser = $this->config->get('system', 'proxyuser');
			if (!empty($proxyuser)) {
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
			$this->logger->info('Post redirect.', ['url' => $url, 'to' => $curlResponse->getRedirectUrl()]);
			curl_close($ch);
			return self::post($curlResponse->getRedirectUrl(), $params, $headers, $redirects, $timeout);
		}

		curl_close($ch);

		$this->profiler->saveTimestamp($stamp1, 'network', System::callstack());

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
	 * Curl wrapper
	 *
	 * If binary flag is true, return binary results.
	 * Set the cookiejar argument to a string (e.g. "/tmp/friendica-cookies.txt")
	 * to preserve cookies from one request to the next.
	 *
	 * @param string $url             URL to fetch
	 * @param bool   $binary          default false
	 *                                TRUE if asked to return binary results (file download)
	 * @param int    $timeout         Timeout in seconds, default system config value or 60 seconds
	 * @param string $accept_content  supply Accept: header with 'accept_content' as the value
	 * @param string $cookiejar       Path to cookie jar file
	 * @param int    $redirects       The recursion counter for internal use - default 0
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
	 * @param string $url             URL to fetch
	 * @param bool   $binary          default false
	 *                                TRUE if asked to return binary results (file download)
	 * @param int    $timeout         Timeout in seconds, default system config value or 60 seconds
	 * @param string $accept_content  supply Accept: header with 'accept_content' as the value
	 * @param string $cookiejar       Path to cookie jar file
	 * @param int    $redirects       The recursion counter for internal use - default 0
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
}
