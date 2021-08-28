<?php
/**
 * @copyright Copyright (C) 2010-2021, the Friendica project
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

use Friendica\Core\System;
use Friendica\Util\Network;
use Friendica\Util\Profiler;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\FileCookieJar;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\RequestOptions;
use mattwright\URLResolver;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\InvalidArgumentException;
use Psr\Log\LoggerInterface;

/**
 * Performs HTTP requests to a given URL
 */
class HTTPClient implements IHTTPClient
{
	/** @var LoggerInterface */
	private $logger;
	/** @var Profiler */
	private $profiler;
	/** @var Client */
	private $client;
	/** @var URLResolver */
	private $resolver;

	public function __construct(LoggerInterface $logger, Profiler $profiler, Client $client, URLResolver $resolver)
	{
		$this->logger   = $logger;
		$this->profiler = $profiler;
		$this->client   = $client;
		$this->resolver = $resolver;
	}

	/**
	 * {@inheritDoc}
	 */
	public function request(string $method, string $url, array $opts = []): IHTTPResult
	{
		$this->profiler->startRecording('network');
		$this->logger->debug('Request start.', ['url' => $url, 'method' => $method]);

		if (Network::isLocalLink($url)) {
			$this->logger->info('Local link', ['url' => $url, 'callstack' => System::callstack(20)]);
		}

		if (strlen($url) > 1000) {
			$this->logger->debug('URL is longer than 1000 characters.', ['url' => $url, 'callstack' => System::callstack(20)]);
			$this->profiler->stopRecording();
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
			$this->profiler->stopRecording();
			return CurlResult::createErrorCurl($url);
		}

		$conf = [];

		if (!empty($opts[HTTPClientOptions::COOKIEJAR])) {
			$jar                           = new FileCookieJar($opts[HTTPClientOptions::COOKIEJAR]);
			$conf[RequestOptions::COOKIES] = $jar;
		}

		$headers = [];

		if (!empty($opts[HTTPClientOptions::ACCEPT_CONTENT])) {
			$headers['Accept'] = $opts[HTTPClientOptions::ACCEPT_CONTENT];
		}

		if (!empty($opts[HTTPClientOptions::LEGACY_HEADER])) {
			$this->logger->notice('Wrong option \'headers\' used.');
			$headers = array_merge($opts[HTTPClientOptions::LEGACY_HEADER], $headers);
		}

		if (!empty($opts[HTTPClientOptions::HEADERS])) {
			$headers = array_merge($opts[HTTPClientOptions::HEADERS], $headers);
		}

		$conf[RequestOptions::HEADERS] = array_merge($this->client->getConfig(RequestOptions::HEADERS), $headers);

		if (!empty($opts[HTTPClientOptions::TIMEOUT])) {
			$conf[RequestOptions::TIMEOUT] = $opts[HTTPClientOptions::TIMEOUT];
		}

		if (!empty($opts[HTTPClientOptions::BODY])) {
			$conf[RequestOptions::BODY] = $opts[HTTPClientOptions::BODY];
		}

		if (!empty($opts[HTTPClientOptions::AUTH])) {
			$conf[RequestOptions::AUTH] = $opts[HTTPClientOptions::AUTH];
		}

		$conf[RequestOptions::ON_HEADERS] = function (ResponseInterface $response) use ($opts) {
			if (!empty($opts[HTTPClientOptions::CONTENT_LENGTH]) &&
				(int)$response->getHeaderLine('Content-Length') > $opts[HTTPClientOptions::CONTENT_LENGTH]) {
				throw new TransferException('The file is too big!');
			}
		};

		try {
			$this->logger->debug('http request config.', ['url' => $url, 'method' => $method, 'options' => $conf]);

			$response = $this->client->request($method, $url, $conf);
			return new GuzzleResponse($response, $url);
		} catch (TransferException $exception) {
			if ($exception instanceof RequestException &&
				$exception->hasResponse()) {
				return new GuzzleResponse($exception->getResponse(), $url, $exception->getCode(), '');
			} else {
				return new CurlResult($url, '', ['http_code' => $exception->getCode()], $exception->getCode(), '');
			}
		} catch (InvalidArgumentException $argumentException) {
			$this->logger->info('Invalid Argument for HTTP call.', ['url' => $url, 'method' => $method, 'exception' => $argumentException]);
			return new CurlResult($url, '', ['http_code' => $argumentException->getCode()], $argumentException->getCode(), $argumentException->getMessage());
		} finally {
			$this->logger->debug('Request stop.', ['url' => $url, 'method' => $method]);
			$this->profiler->stopRecording();
		}
	}

	/** {@inheritDoc}
	 */
	public function head(string $url, array $opts = []): IHTTPResult
	{
		return $this->request('head', $url, $opts);
	}

	/**
	 * {@inheritDoc}
	 */
	public function get(string $url, array $opts = []): IHTTPResult
	{
		return $this->request('get', $url, $opts);
	}

	/**
	 * {@inheritDoc}
	 */
	public function post(string $url, $params, array $headers = [], int $timeout = 0): IHTTPResult
	{
		$opts = [];

		$opts[HTTPClientOptions::BODY] = $params;

		if (!empty($headers)) {
			$opts[HTTPClientOptions::HEADERS] = $headers;
		}

		if (!empty($timeout)) {
			$opts[HTTPClientOptions::TIMEOUT] = $timeout;
		}

		return $this->request('post', $url, $opts);
	}

	/**
	 * {@inheritDoc}
	 */
	public function finalUrl(string $url)
	{
		$this->profiler->startRecording('network');

		if (Network::isLocalLink($url)) {
			$this->logger->debug('Local link', ['url' => $url, 'callstack' => System::callstack(20)]);
		}

		if (Network::isUrlBlocked($url)) {
			$this->logger->info('Domain is blocked.', ['url' => $url]);
			return $url;
		}

		if (Network::isRedirectBlocked($url)) {
			$this->logger->info('Domain should not be redirected.', ['url' => $url]);
			return $url;
		}

		$url = Network::stripTrackingQueryParams($url);

		$url = trim($url, "'");

		$urlResult = $this->resolver->resolveURL($url);

		if ($urlResult->didErrorOccur()) {
			throw new TransferException($urlResult->getErrorMessageString());
		}

		return $urlResult->getURL();
	}

	/**
	 * {@inheritDoc}
	 */
	public function fetch(string $url, int $timeout = 0, string $accept_content = '', string $cookiejar = '')
	{
		$ret = $this->fetchFull($url, $timeout, $accept_content, $cookiejar);

		return $ret->getBody();
	}

	/**
	 * {@inheritDoc}
	 */
	public function fetchFull(string $url, int $timeout = 0, string $accept_content = '', string $cookiejar = '')
	{
		return $this->get(
			$url,
			[
				'timeout'        => $timeout,
				'accept_content' => $accept_content,
				'cookiejar'      => $cookiejar
			]
		);
	}
}
