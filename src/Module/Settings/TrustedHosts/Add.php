<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Settings\TrustedHosts;

use Friendica\App;
use Friendica\BaseModule;
use Friendica\Core\L10n;
use Friendica\Core\PConfig\Capability\IManagePersonalConfigValues;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Core\System;
use Friendica\Module\Response;
use Friendica\Network\HTTPException;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

class Add extends BaseModule
{
	public function __construct(
		private readonly IHandleUserSessions $session,
		private readonly IManagePersonalConfigValues $pConfig,
		L10n $l10n,
		App\BaseURL $baseUrl,
		App\Arguments $args,
		LoggerInterface $logger,
		Profiler $profiler,
		Response $response,
		array $server,
		array $parameters = [],
	) {
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);
	}

	protected function post(array $request = [])
	{
		$uid = $this->session->getLocalUserId();
		if (!$uid) {
			throw new HTTPException\ForbiddenException();
		}

		$host = mb_strtolower(trim((string) ($request['hostname'] ?? '')));
		if ($host === '') {
			throw new HTTPException\BadRequestException();
		}

		$trusted = $this->pConfig->get($uid, 'system', 'trusted_iframe_hosts', []);
		if (!in_array($host, $trusted, true)) {
			$trusted[] = $host;
			$this->pConfig->set($uid, 'system', 'trusted_iframe_hosts', $trusted);
		}

		System::exit();
	}
}
