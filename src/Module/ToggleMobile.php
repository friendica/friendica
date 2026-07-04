<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module;

use Friendica\App;
use Friendica\BaseModule;
use Friendica\Core\L10n;
use Friendica\Core\Session\Capability\IHandleSessions;
use Friendica\Core\System;
use Friendica\Network\HTTPException\BadRequestException;
use Friendica\Util;
use GuzzleHttp\Psr7\Uri;
use Psr\Log\LoggerInterface;

/**
 * Toggles the mobile view (on/off)
 */
class ToggleMobile extends BaseModule
{
	public function __construct(private readonly IHandleSessions $session, L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Util\Profiler $profiler, Response $response, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);
	}

	protected function rawContent(array $request = [])
	{
		$address = $request['address'] ?? '' ?: $this->baseUrl;

		$uri = new Uri($address);

		if (!$this->baseUrl->isLocalUri($uri)) {
			throw new BadRequestException();
		}

		$this->session->set('show-mobile', !isset($request['off']));

		System::externalRedirect((string) $uri);
	}
}
