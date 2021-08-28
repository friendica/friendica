<?php

namespace Friendica\Factory;

use Friendica\App;
use Friendica\BaseFactory;
use Friendica\Core\Config\IConfig;
use Friendica\Core\System;
use Friendica\Network\HTTPClient;
use Friendica\Network\IHTTPClient;
use Friendica\Util\Crypto;
use Friendica\Util\Profiler;
use Friendica\Util\Strings;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\RequestOptions;
use mattwright\URLResolver;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use Psr\Log\LoggerInterface;

class HTTPClientFactory extends BaseFactory
{
	/** @var IConfig */
	private $config;
	/** @var Profiler */
	private $profiler;
	/** @var App\BaseURL */
	private $baseUrl;

	public function __construct(LoggerInterface $logger, IConfig $config, Profiler $profiler, App\BaseURL $baseUrl)
	{
		parent::__construct($logger);
		$this->config   = $config;
		$this->profiler = $profiler;
		$this->baseUrl  = $baseUrl;
	}

	/**
	 * Creates a IHTTPClient for communications with HTTP endpoints
	 *
	 * @param HandlerStack|null $handlerStack (optional) A handler replacement (just usefull at test environments)
	 *
	 * @return IHTTPClient
	 */
	public function createClient(HandlerStack $handlerStack = null): IHTTPClient
	{
		$proxy = $this->config->get('system', 'proxy');

		if (!empty($proxy)) {
			$proxyuser = $this->config->get('system', 'proxyuser');

			if (!empty($proxyuser)) {
				$proxy = $proxyuser . '@' . $proxy;
			}
		}

		$logger = $this->logger;

		$onRedirect = function (
			RequestInterface $request,
			ResponseInterface $response,
			UriInterface $uri
		) use ($logger) {
			$logger->notice('Curl redirect.', ['url' => $request->getUri(), 'to' => $uri, 'method' => $request->getMethod()]);
		};

		$userAgent = FRIENDICA_PLATFORM . " '" .
					 FRIENDICA_CODENAME . "' " .
					 FRIENDICA_VERSION . '-' .
					 DB_UPDATE_VERSION . '; ' .
					 $this->baseUrl->get();

		$guzzle = new Client([
			RequestOptions::ALLOW_REDIRECTS => [
				'max'            => 8,
				'on_redirect'    => $onRedirect,
				'track_redirect' => true,
				'strict'         => true,
				'referer'        => true,
			],
			RequestOptions::HTTP_ERRORS => false,
			// Without this setting it seems as if some webservers send compressed content
			// This seems to confuse curl so that it shows this uncompressed.
			/// @todo  We could possibly set this value to "gzip" or something similar
			RequestOptions::DECODE_CONTENT   => '',
			RequestOptions::FORCE_IP_RESOLVE => ($this->config->get('system', 'ipv4_resolve') ? 'v4' : null),
			RequestOptions::CONNECT_TIMEOUT  => 10,
			RequestOptions::TIMEOUT          => $this->config->get('system', 'curl_timeout', 60),
			// by default we will allow self-signed certs
			// but it can be overridden
			RequestOptions::VERIFY  => (bool)$this->config->get('system', 'verifyssl'),
			RequestOptions::PROXY   => $proxy,
			RequestOptions::HEADERS => [
				'User-Agent' => $userAgent,
			],
			'handler' => $handlerStack ?? HandlerStack::create(),
		]);

		$resolver = new URLResolver();
		$resolver->setUserAgent($userAgent);
		$resolver->setMaxRedirects(10);
		$resolver->setRequestTimeout(10);
		// if the file is too large then exit
		$resolver->setMaxResponseDataSize(1000000);
		// Designate a temporary file that will store cookies during the session.
		// Some websites test the browser for cookie support, so this enhances results.
		$resolver->setCookieJar(get_temppath() .'/resolver-cookie-' . Strings::getRandomName(10));

		return new HTTPClient($logger, $this->profiler, $guzzle, $resolver);
	}
}
