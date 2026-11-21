<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Network\HTTPClient\Client;

use Friendica\Util\Network;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\RequestInterface;
use Psr\Log\LoggerInterface;

/**
 * Connects every request, redirects included, only to the addresses that passed the private network check.
 *
 * Must sit below the redirect middleware in the handler stack, so it sees every hop.
 */
final readonly class AddressPinningMiddleware
{
	public function __construct(private LoggerInterface $logger) {}

	public function __invoke(callable $handler): callable
	{
		return function (RequestInterface $request, array $options) use ($handler): PromiseInterface {
			$uri       = $request->getUri();
			$addresses = Network::getPinnedAddresses($uri);

			if ($addresses === []) {
				$this->logger->notice('Target is not on the public internet or cannot be resolved.', ['url' => $uri]);
				return Create::rejectionFor(new RequestException('Target is not allowed: ' . $uri, $request));
			}

			// A proxy resolves the target itself, curl only connects to the proxy.
			// Streamed requests use Guzzle's stream handler without curl, only the check above applies to them.
			if ($addresses !== null && empty($options[RequestOptions::PROXY])) {
				$options['curl'][CURLOPT_RESOLVE] ??= [];
				$options['curl'][CURLOPT_RESOLVE][] = Network::getCurlResolveEntry($uri, $addresses);
			}

			return $handler($request, $options);
		};
	}
}
