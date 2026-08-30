<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Network\HTTPClient\Factory;

use Friendica\App;
use Friendica\BaseFactory;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\System;
use Friendica\Network\HTTPClient\Client;
use Friendica\Network\HTTPClient\Capability\ICanSendHttpRequests;
use Friendica\Util\Network;
use Friendica\Util\Profiler;
use Friendica\Util\Strings;
use GuzzleHttp;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\RequestOptions;
use mattwright\URLResolver;
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

			// Apply the outbound restrictions to every redirect target.
			if (Network::isUriBlocked($uri) || !Network::isValidHttpUrl((string) $uri) || Network::isPrivateTarget($uri)) {
				$logger->notice('Redirect target is not allowed.', ['url' => $request->getUri(), 'to' => $uri]);
				throw new TransferException('Redirect to a disallowed target: ' . $uri);
			}
		};

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
			RequestOptions::VERSION => 2.0,
			RequestOptions::PROXY   => $proxy,
			RequestOptions::HEADERS => [],
			'handler'               => $handlerStack ?? HandlerStack::create(),
		]);

		$resolver = new URLResolver();
		$resolver->setMaxRedirects(10);
		$resolver->setRequestTimeout(10);
		// if the file is too large then exit
		$resolver->setMaxResponseDataSize($this->config->get('performance', 'max_response_data_size', 1000000));
		// Designate a temporary file that will store cookies during the session.
		// Some websites test the browser for cookie support, so this enhances results.
		$resolver->setCookieJar(System::getTempPath() . '/resolver-cookie-' . Strings::getRandomName(10));

		return new Client\HttpClient($logger, $this->profiler, $guzzle, $resolver, $this->baseUrl);
	}
}
