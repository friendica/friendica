<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Network\HTTPClient\Client;

use Friendica\App;
use Friendica\Core\System;
use Friendica\Network\HTTPClient\Response\CurlResult;
use Friendica\Network\HTTPClient\Response\GuzzleResponse;
use Friendica\Network\HTTPClient\Capability\ICanSendHttpRequests;
use Friendica\Network\HTTPClient\Capability\ICanHandleHttpResponses;
use Friendica\Util\Network;
use Friendica\Util\Profiler;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Psr7\Stream;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;
use GuzzleHttp\Psr7\Utils;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\InvalidArgumentException;
use Psr\Log\LoggerInterface;

/**
 * Performs HTTP requests to a given URL
 */
class HttpClient implements ICanSendHttpRequests
{
	/** Upper bound for redirects and og:url/canonical hops in finalUrl() */
	private const MAX_RESOLVER_HOPS = 10;

	/** Timeout in seconds for a single hop in finalUrl() */
	private const RESOLVER_TIMEOUT = 10;

	/** Status codes that finalUrl() follows */
	private const REDIRECT_STATUS = [301, 302, 303, 307, 308];

	public function __construct(private readonly LoggerInterface $logger, private readonly Profiler $profiler, private readonly Client $client, private readonly App\BaseURL $baseUrl, private readonly int $maxBodySize = 0, private readonly int $maxResolverBodySize = 1000000) {}

	/**
	 * {@inheritDoc}
	 */
	public function request(string $method, string $url, array $opts = []): ICanHandleHttpResponses
	{
		$method = strtoupper($method);
		$this->profiler->startRecording('network');
		$this->logger->debug('Request start.', ['url' => $url, 'method' => $method]);

		$host = parse_url($url, PHP_URL_HOST);
		if (empty($host)) {
			$this->logger->notice('Unable to retrieve the host in URL', ['url' => $url]);
			$this->profiler->stopRecording();
			return CurlResult::createErrorCurl($this->logger, $url);
		}

		if (!filter_var($host, FILTER_VALIDATE_IP) && !@dns_get_record($host . '.', DNS_A) && !@dns_get_record($host . '.', DNS_AAAA)) {
			$this->logger->info('URL cannot be resolved.', ['url' => $url]);
			$this->profiler->stopRecording();
			return CurlResult::createErrorCurl($this->logger, $url);
		}

		if ($this->baseUrl->isLocalUrl($url)) {
			$this->logger->info('Local link', ['url' => $url]);
		}

		if (strlen($url) > 1000) {
			$this->logger->info('URL is longer than 1000 characters.', ['url' => $url]);
			$this->profiler->stopRecording();
			return CurlResult::createErrorCurl($this->logger, substr($url, 0, 200));
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
		$url           = (string) Uri::fromParts((array) $parts);

		if (Network::isUriBlocked(new Uri($url))) {
			$this->logger->info('Domain is blocked.', ['url' => $url]);
			$this->profiler->stopRecording();
			return CurlResult::createErrorCurl($this->logger, $url);
		}

		if (!Network::isValidHttpUrl($url)) {
			$this->logger->info('Unsupported scheme.', ['url' => $url]);
			$this->profiler->stopRecording();
			return CurlResult::createErrorCurl($this->logger, $url);
		}

		// AddressPinningMiddleware checks the target address of the request and every redirect

		$conf = [];

		$jar = null;
		if (!empty($opts[HttpClientOptions::COOKIEJAR])) {
			$jar                           = new ExplicitFileCookieJar($opts[HttpClientOptions::COOKIEJAR]);
			$conf[RequestOptions::COOKIES] = $jar;
		}

		$headers = ['User-Agent' => $this->getUserAgent($opts[HttpClientOptions::REQUEST] ?? '')];

		if (!empty($opts[HttpClientOptions::ACCEPT_CONTENT])) {
			$headers['Accept'] = $opts[HttpClientOptions::ACCEPT_CONTENT];
		}

		if (!empty($opts[HttpClientOptions::LEGACY_HEADER])) {
			$this->logger->notice('Wrong option \'headers\' used.');
			$headers = array_merge($opts[HttpClientOptions::LEGACY_HEADER], $headers);
		}

		if (!empty($opts[HttpClientOptions::HEADERS])) {
			$headers = array_merge($opts[HttpClientOptions::HEADERS], $headers);
		}

		$conf[RequestOptions::HEADERS] = array_merge($this->client->getConfig(RequestOptions::HEADERS), $headers);

		if (!empty($opts[HttpClientOptions::TIMEOUT])) {
			$conf[RequestOptions::TIMEOUT] = $opts[HttpClientOptions::TIMEOUT];
		}

		if (isset($opts[HttpClientOptions::VERIFY])) {
			$conf[RequestOptions::VERIFY] = $opts[HttpClientOptions::VERIFY];
		}

		if (!empty($opts[HttpClientOptions::BODY])) {
			$conf[RequestOptions::BODY] = $opts[HttpClientOptions::BODY];
		}

		if (!empty($opts[HttpClientOptions::FORM_PARAMS])) {
			$conf[RequestOptions::FORM_PARAMS] = $opts[HttpClientOptions::FORM_PARAMS];
		}

		if (!empty($opts[HttpClientOptions::AUTH])) {
			$conf[RequestOptions::AUTH] = $opts[HttpClientOptions::AUTH];
		}

		$maxBodySize = (int) ($opts[HttpClientOptions::CONTENT_LENGTH] ?? 0);

		// Handle streaming requests - don't use sink for streaming
		$sink = null;
		if (empty($opts[HttpClientOptions::STREAM])) {
			$maxBodySize = $maxBodySize ?: $this->maxBodySize;

			$sink = $this->createSink();
			if ($sink !== null) {
				$stream = new Stream($sink);

				$conf[RequestOptions::SINK] = $maxBodySize > 0 ? new SizeLimitedStream($stream, $maxBodySize) : $stream;
			}
		} else {
			$conf[RequestOptions::STREAM] = true;
		}

		$conf[RequestOptions::ON_HEADERS] = function (ResponseInterface $response) use ($maxBodySize, $sink): void {
			if ($maxBodySize > 0 && (int) $response->getHeaderLine('Content-Length') > $maxBodySize) {
				throw new ResponseTooLargeException($maxBodySize);
			}

			// The sink is shared by all responses of a redirect chain, only the last body must remain
			if ($sink !== null) {
				ftruncate($sink, 0);
				rewind($sink);
			}
		};

		if (empty($conf[HttpClientOptions::HEADERS]['Accept']) && in_array($method, ['GET', 'HEAD'])) {
			$this->logger->info('Accept header was missing, using default.', ['url' => $url]);
			$conf[HttpClientOptions::HEADERS]['Accept'] = HttpClientAccept::DEFAULT;
		}

		try {
			$this->logger->debug('http request config.', ['url' => $url, 'method' => $method, 'options' => $conf]);

			$response = $this->client->request($method, $url, $conf);
			return new GuzzleResponse($response, $url);
		} catch (TransferException $exception) {
			// A previous exception aborted the transfer in a callback, the response is incomplete
			if (
				$exception instanceof RequestException
				&& $exception->hasResponse()
				&& $exception->getPrevious() === null
			) {
				return new GuzzleResponse($exception->getResponse(), $url, $exception->getCode(), '');
			}

			$tooLarge = self::findResponseTooLarge($exception);
			if ($tooLarge !== null) {
				$this->logger->notice('Response exceeds the size limit.', ['url' => $url, 'method' => $method, 'limit' => $tooLarge->limit]);
			} else {
				$this->logger->info('HTTP request failed.', ['url' => $url, 'method' => $method, 'exception' => $exception]);
			}

			return new CurlResult($this->logger, $url, '', ['http_code' => 500], $exception->getCode(), $exception->getMessage());
		} catch (InvalidArgumentException|\InvalidArgumentException $argumentException) {
			$this->logger->info('Invalid Argument for HTTP call.', ['url' => $url, 'method' => $method, 'exception' => $argumentException]);
			return new CurlResult($this->logger, $url, '', ['http_code' => 500], $argumentException->getCode(), $argumentException->getMessage());
		} finally {
			if ($jar !== null) {
				$this->saveCookieJar($jar, $opts[HttpClientOptions::COOKIEJAR], $url);
			}

			$this->logger->debug('Request stop.', ['url' => $url, 'method' => $method]);
			$this->profiler->stopRecording();
		}
	}

	/**
	 * A failed save must not fail the request, the response is already complete.
	 */
	private function saveCookieJar(ExplicitFileCookieJar $jar, string $filename, string $url): void
	{
		try {
			$jar->save($filename);
		} catch (\Exception $exception) {
			$this->logger->warning('Unable to save the cookie jar.', ['file' => $filename, 'url' => $url, 'exception' => $exception]);
		}
	}

	/**
	 * Creates the temp file that receives the response body.
	 *
	 * The file is deleted right after opening. On Linux/Unix, unlink() only removes the
	 * name from the directory: the open handle keeps working, Guzzle writes the body into
	 * it and reads it back as usual. The kernel frees the space when the last handle is
	 * closed - which also happens when the process dies without running any PHP cleanup
	 * (fatal error, exit(), killed worker). A named file would stay behind in these cases.
	 *
	 * @return resource|null File handle, php://temp when no temp file can be used, null when that fails as well
	 */
	private function createSink()
	{
		$filename = tempnam(System::getTempPath(), 'http-');
		if ($filename === false) {
			return $this->createMemorySink();
		}

		$sink = @fopen($filename, 'w+');
		if ($sink === false) {
			@unlink($filename);
			return $this->createMemorySink();
		}

		// Some systems (Windows) can't delete an open file, fall back to php://temp there
		if (!@unlink($filename)) {
			fclose($sink);
			@unlink($filename);
			return $this->createMemorySink();
		}

		return $sink;
	}

	/**
	 * @return resource|null
	 */
	private function createMemorySink()
	{
		return fopen('php://temp', 'w+') ?: null;
	}

	/**
	 * Guzzle wraps exceptions thrown in callbacks or by the sink, the size limit abort can be nested
	 */
	private static function findResponseTooLarge(\Throwable $exception): ?ResponseTooLargeException
	{
		for ($current = $exception; $current !== null; $current = $current->getPrevious()) {
			if ($current instanceof ResponseTooLargeException) {
				return $current;
			}
		}

		return null;
	}

	/** {@inheritDoc}
	 */
	public function head(string $url, array $opts = []): ICanHandleHttpResponses
	{
		return $this->request('HEAD', $url, $opts);
	}

	/**
	 * {@inheritDoc}
	 */
	public function get(string $url, string $accept_content = HttpClientAccept::DEFAULT, array $opts = []): ICanHandleHttpResponses
	{
		// In case there is no
		$opts[HttpClientOptions::ACCEPT_CONTENT] ??= $accept_content;

		return $this->request('GET', $url, $opts);
	}

	/**
	 * {@inheritDoc}
	 */
	public function post(string $url, $params, array $headers = [], int $timeout = 0, string $request = ''): ICanHandleHttpResponses
	{
		$opts = [];

		if (!is_array($params)) {
			$opts[HttpClientOptions::BODY] = $params;
		} else {
			$opts[HttpClientOptions::FORM_PARAMS] = $params;
		}

		if (!empty($headers)) {
			$opts[HttpClientOptions::HEADERS] = $headers;
		}

		if (!empty($timeout)) {
			$opts[HttpClientOptions::TIMEOUT] = $timeout;
		}

		if (!empty($request)) {
			$opts[HttpClientOptions::REQUEST] = $request;
		}

		return $this->request('POST', $url, $opts);
	}

	/**
	 * {@inheritDoc}
	 */
	public function finalUrl(string $url): string
	{
		if ($this->baseUrl->isLocalUrl($url)) {
			$this->logger->debug('Local link', ['url' => $url]);
		}

		if (Network::isUriBlocked(new Uri($url))) {
			$this->logger->info('Domain is blocked.', ['url' => $url]);
			return $url;
		}

		if (Network::isRedirectBlocked($url)) {
			$this->logger->info('Domain should not be redirected.', ['url' => $url]);
			return $url;
		}

		if (Network::isPrivateTarget(new Uri($url))) {
			$this->logger->notice('Target is not on the public internet.', ['url' => $url]);
			return $url;
		}

		$url = Network::stripTrackingQueryParams($url);
		$url = trim($url, "'");

		$this->profiler->startRecording('network');

		try {
			return $this->followHops($url);
		} finally {
			$this->profiler->stopRecording();
		}
	}

	/**
	 * Follows redirects and og:url/canonical links one hop at a time, so every target passes the outbound restrictions first.
	 *
	 * Picks the deepest URL in this order: og:url, canonical, success, failure, redirect.
	 *
	 * @throws TransferException when a hop can't be fetched
	 */
	private function followHops(string $url): string
	{
		$jar    = new CookieJar();
		$found  = [];
		$visits = [];
		$viaOg  = $viaCanonical = false;
		for ($hop = 0; $hop < self::MAX_RESOLVER_HOPS; $hop++) {
			$page        = $this->fetchHop($url, $jar);
			$isRedirect  = in_array($page['status'], self::REDIRECT_STATUS);
			$isOg        = $viaOg;
			$isCanonical = $viaCanonical;
			$next        = null;
			$nextOg      = $nextCanonical = false;

			if ($isRedirect) {
				if ($page['location'] === null) {
					throw new TransferException('Redirect without location: ' . $url, $page['status']);
				}
				$next = self::resolveLink($url, $page['location']);
			} elseif ($page['og'] !== null || $page['canonical'] !== null) {
				$nextOg        = $page['og'] !== null;
				$nextCanonical = $page['canonical'] !== null && ($page['og'] === null || $page['canonical'] === $page['og']);
				$target        = self::resolveLink($url, $page['og'] ?? $page['canonical']);
				if ($target === $url) {
					$isOg        = $isOg || $nextOg;
					$isCanonical = $isCanonical || $nextCanonical;
				} else {
					$next = $target;
				}
			}

			if ($page['status'] === 200) {
				$found[$isOg ? 'og' : ($isCanonical ? 'canonical' : 'ok')] = $url;
			} else {
				$found[$isRedirect ? 'redirect' : 'fail'] = $url;
			}

			$visits[$url] = ($visits[$url] ?? 0) + 1;

			if ($next === null || ($visits[$next] ?? 0) > 1 || (isset($visits[$next]) && !$isRedirect)) {
				break;
			}

			if (Network::isUriBlocked(new Uri($next)) || !Network::isValidHttpUrl($next) || Network::isPrivateTarget(new Uri($next))) {
				$this->logger->notice('Redirect target is not allowed.', ['url' => $url, 'to' => $next]);
				break;
			}

			// Same as for the initial URL: a domain on the no_redirect_list is returned as is, without fetching it.
			if (Network::isRedirectBlocked($next)) {
				$this->logger->info('Redirect target should not be redirected.', ['url' => $url, 'to' => $next]);
				return $next;
			}

			$viaOg        = $nextOg;
			$viaCanonical = $nextCanonical;
			$url          = $next;
		}

		return $found['og'] ?? $found['canonical'] ?? $found['ok'] ?? $found['fail'] ?? $found['redirect'] ?? $url;
	}

	/**
	 * Fetches a single hop without following redirects, only HTML bodies are read up to the size limit.
	 *
	 * @return array{status: int, location: ?string, og: ?string, canonical: ?string}
	 *
	 * @throws TransferException when no response headers were received
	 */
	private function fetchHop(string $url, CookieJar $jar): array
	{
		$sink    = new SizeLimitedStream(Utils::streamFor(fopen('php://temp', 'w+')), $this->maxResolverBodySize);
		$headers = null;

		try {
			$this->client->request('GET', $url, [
				RequestOptions::ALLOW_REDIRECTS => false,
				RequestOptions::CONNECT_TIMEOUT => self::RESOLVER_TIMEOUT,
				RequestOptions::TIMEOUT         => self::RESOLVER_TIMEOUT,
				RequestOptions::COOKIES         => $jar,
				RequestOptions::HEADERS         => [
					'User-Agent' => $this->getUserAgent(HttpClientRequest::URLRESOLVER),
					'Accept'     => HttpClientAccept::HTML,
				],
				RequestOptions::SINK       => $sink,
				RequestOptions::ON_HEADERS => function (ResponseInterface $response) use (&$headers): void {
					$headers = $response;
					if (!self::isHtmlPage($response)) {
						throw new \RuntimeException('The body is not needed.');
					}
				},
			]);
		} catch (TransferException $exception) {
			// An aborted or oversized body still leaves the status, the headers and the beginning of the page
			if ($headers === null) {
				throw $exception;
			}
		}

		$page = [
			'status'    => $headers->getStatusCode(),
			'location'  => $headers->getHeaderLine('Location') ?: null,
			'og'        => null,
			'canonical' => null,
		];

		if (self::isHtmlPage($headers)) {
			$sink->rewind();
			$doc = new \DOMDocument();
			if (@$doc->loadHTML($sink->getContents() ?: '<html></html>')) {
				$xpath             = new \DOMXPath($doc);
				$page['og']        = trim((string) $xpath->evaluate('string(//meta[@property="og:url"][1]/@content)')) ?: null;
				$page['canonical'] = trim((string) $xpath->evaluate('string(//link[@rel="canonical"][1]/@href)')) ?: null;
			}
		}

		return $page;
	}

	private static function isHtmlPage(ResponseInterface $response): bool
	{
		return $response->getStatusCode() === 200 && stripos($response->getHeaderLine('Content-Type'), 'html') !== false;
	}

	/** Resolves a Location header or an og:url/canonical link against the URL of the page. */
	private static function resolveLink(string $base, string $link): string
	{
		$link = trim($link);
		// Links like "www.example.com/page" are common in the wild, they lack the scheme
		if (stripos($link, 'www.') === 0) {
			$link = (new Uri($base))->getScheme() . '://' . $link;
		}

		$target = UriResolver::resolve(new Uri($base), new Uri($link));
		if ($target->getPath() === '' && $target->getHost() !== '') {
			$target = $target->withPath('/');
		}

		$fragment = (new Uri($base))->getFragment();
		if ($fragment !== '' && $target->getFragment() === '') {
			$target = $target->withFragment($fragment);
		}

		return (string) $target;
	}

	/**
	 * {@inheritDoc}
	 */
	public function fetch(string $url, string $accept_content = HttpClientAccept::DEFAULT, int $timeout = 0, string $cookiejar = '', string $request = ''): string
	{
		try {
			$ret = $this->get(
				$url,
				$accept_content,
				[
					HttpClientOptions::TIMEOUT   => $timeout,
					HttpClientOptions::COOKIEJAR => $cookiejar,
					HttpClientOptions::REQUEST   => $request,
				],
			);
			return $ret->getBodyString();
		} catch (\Throwable $th) {
			$this->logger->notice('Got exception', ['code' => $th->getCode(), 'message' => $th->getMessage()]);
			return '';
		}
	}

	private function getUserAgent(string $type = ''): string
	{
		// @see https://developers.whatismybrowser.com/learn/browser-detection/user-agents/user-agent-best-practices
		$userAgent = App::PLATFORM . '/' . App::VERSION . ' DatabaseVersion/' . DB_UPDATE_VERSION;
		if ($type != '') {
			$userAgent .= ' Request/' . $type;
		}
		$userAgent .= ' +' . $this->baseUrl;
		return $userAgent;
	}
}
