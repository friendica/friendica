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
	private const MAX_HOST_LENGTH = 253;
	private const MAX_HOSTS       = 1000;

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

		self::checkFormSecurityTokenForbiddenOnError('trustedhosts-add');

		$host = mb_strtolower(trim((string) ($request['hostname'] ?? '')));
		if ($host !== '' && function_exists('idn_to_ascii')) {
			$host = (string) idn_to_ascii($host, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
		}

		if ($host === '' || strlen($host) > self::MAX_HOST_LENGTH || !filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
			throw new HTTPException\BadRequestException();
		}

		$trusted = (array) $this->pConfig->get($uid, 'system', 'trusted_iframe_hosts', []);
		if (!in_array($host, $trusted, true)) {
			if (count($trusted) >= self::MAX_HOSTS) {
				throw new HTTPException\BadRequestException();
			}
			$trusted[] = $host;
			$this->pConfig->set($uid, 'system', 'trusted_iframe_hosts', $trusted);
		}

		System::exit();
	}
}
