<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Util;

use Friendica\Network\HTTPClient\Capability\ICanHandleHttpResponses;
use Friendica\Network\HTTPClient\Capability\ICanSendHttpRequests;
use Friendica\Network\HTTPClient\Client\HttpClientAccept;
use Friendica\Network\HTTPClient\Response\CurlResult;
use Psr\Log\LoggerInterface;

/**
 * HTTP client for fixture tests that answers every request like an unresolvable host, without DNS or network access.
 */
class OfflineHttpClient implements ICanSendHttpRequests
{
	public function __construct(private readonly LoggerInterface $logger) {}

	public function fetch(string $url, string $accept_content = HttpClientAccept::DEFAULT, int $timeout = 0, string $cookiejar = '', string $request = ''): string
	{
		return '';
	}

	public function get(string $url, string $accept_content = HttpClientAccept::DEFAULT, array $opts = []): ICanHandleHttpResponses
	{
		return CurlResult::createErrorCurl($this->logger, $url);
	}

	public function head(string $url, array $opts = []): ICanHandleHttpResponses
	{
		return CurlResult::createErrorCurl($this->logger, $url);
	}

	public function post(string $url, $params, array $headers = [], int $timeout = 0, string $request = ''): ICanHandleHttpResponses
	{
		return CurlResult::createErrorCurl($this->logger, $url);
	}

	public function request(string $method, string $url, array $opts = []): ICanHandleHttpResponses
	{
		return CurlResult::createErrorCurl($this->logger, $url);
	}

	public function finalUrl(string $url): string
	{
		return $url;
	}
}
