<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Security;

use Friendica\App;
use Friendica\BaseModule;
use Friendica\Core\Cache\Capability\ICanCache;
use Friendica\Core\Hook;
use Friendica\Core\L10n;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Core\System;
use Friendica\Model\User\Cookie;
use Friendica\Module\Response;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

/**
 * Logout module
 */
class Logout extends BaseModule
{
	/** @var ICanCache */
	protected $cache;
	/** @var Cookie */
	protected $cookie;
	/** @var IHandleUserSessions */
	protected $session;

	public function __construct(L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, ICanCache $cache, Cookie $cookie, IHandleUserSessions $session, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->cache   = $cache;
		$this->cookie  = $cookie;
		$this->session = $session;
	}

	protected function post(array $request = [])
	{
		// @todo check if POST is really used here
		$this->rawContent($request);
	}

	/**
	 * Process logout requests
	 */
	protected function rawContent(array $request = [])
	{
		$visitor_home = null;
		if ($this->session->getRemoteUserId()) {
			$visitor_home = $this->session->getMyUrl();
			$this->cache->delete('zrlInit:' . $visitor_home);
		}

		Hook::callAll("logging_out");

		// If this is a trusted browser, redirect to the 2fa signout page
		if ($this->cookie->get('2fa_cookie_hash')) {
			$this->baseUrl->redirect('2fa/signout');
		}

		$this->cookie->clear();
		$this->session->clear();

		if ($visitor_home) {
			System::externalRedirect($visitor_home);
		} else {
			$this->baseUrl->redirect();
		}
	}
}
