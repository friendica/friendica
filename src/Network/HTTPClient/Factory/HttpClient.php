<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Network\HTTPClient\Factory;

use Friendica\App;
use Friendica\BaseFactory;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Network\HTTPClient\Client;
use Friendica\Network\HTTPClient\Capability\ICanSendHttpRequests;
use Friendica\Util\Network;
use Friendica\Util\Profiler;
use GuzzleHttp;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use Psr\Log\LoggerInterface;

require_once __DIR__ . '/../../../../static/dbstructure.config.php';

class HttpClient extends BaseFactory
{
	public function __construct(
		LoggerInterface $logger,
		private readonly IManageConfigValues $config,
		private readonly Profiler $profiler,
		private readonly App\BaseURL $baseUrl,
	) {
		parent::__construct($logger);
	}

	/**
	 * Creates a IHTTPClient for communications with HTTP endpoints
	 *
	 * @param HandlerStack|null $handlerStack (optional) A handler replacement (just useful at test environments)
	 *
	 * @return ICanSendHttpRequests
	 */
	public function createClient(?HandlerStack $handlerStack = null): ICanSendHttpRequests
	{
		$proxy = $this->config->get('system', 'proxy');

		if (!empty($proxy)) {
			$proxyUser = $this->config->get('system', 'proxyuser');

			if (!empty($proxyUser)) {
				$proxy = $proxyUser . '@' . $proxy;
			}
		}

		$logger = $this->logger;

		$onRedirect = function (
			RequestInterface $request,
			ResponseInterface $response,
			UriInterface $uri,
		) use ($logger): void {
			$logger->info('Curl redirect.', ['url' => $request->getUri(), 'to' => $uri, 'method' => $request->getMethod()]);

			// Apply the outbound restrictions to every redirect target, AddressPinningMiddleware checks the address.
			if (Network::isUriBlocked($uri) || !Network::isValidHttpUrl((string) $uri)) {
				$logger->notice('Redirect target is not allowed.', ['url' => $request->getUri(), 'to' => $uri]);
				throw new TransferException('Redirect to a disallowed target: ' . $uri);
			}
		};

		$handlerStack ??= HandlerStack::create();
		// Pushed last, so it runs below the redirect middleware and checks every hop
		$handlerStack->remove('address_pinning');
		$handlerStack->push(new Client\AddressPinningMiddleware($logger), 'address_pinning');

		$guzzle = new GuzzleHttp\Client([
			RequestOptions::ALLOW_REDIRECTS => [
				'max'             => 8,
				'on_redirect'     => $onRedirect,
				'track_redirects' => true,
				'strict'          => true,
				'referer'         => true,
			],
			RequestOptions::HTTP_ERRORS => false,
			// Without this setting it seems as if some webservers send compressed content
			// This seems to confuse curl so that it shows this uncompressed.
			/// @todo  We could possibly set this value to "gzip" or something similar
			//RequestOptions::DECODE_CONTENT   => '',
			// Fixes Issue 14451 - [Bluesky] Unexpected GZIP response from getTimeline endpoint
			RequestOptions::DECODE_CONTENT   => true,
			RequestOptions::FORCE_IP_RESOLVE => ($this->config->get('system', 'ipv4_resolve') ? 'v4' : null),
			RequestOptions::CONNECT_TIMEOUT  => 10,
			RequestOptions::TIMEOUT          => $this->config->get('system', 'curl_timeout', 60),
			// by default, we will allow self-signed certs,
			// but it can be overridden
			RequestOptions::VERIFY  => (bool) $this->config->get('system', 'verifyssl'),
			RequestOptions::VERSION => (curl_version()['features'] & CURL_VERSION_HTTP2) ? 2.0 : 1.1,
			RequestOptions::PROXY   => $proxy,
			RequestOptions::HEADERS => [],
			'handler'               => $handlerStack,
		]);

		return new Client\HttpClient(
			$logger,
			$this->profiler,
			$guzzle,
			$this->baseUrl,
			(int) $this->config->get('performance', 'max_download_size', 0),
			(int) $this->config->get('performance', 'max_response_data_size', 1000000),
		);
	}
}
