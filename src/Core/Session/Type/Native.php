<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Core\Session\Type;

use Friendica\App\BaseURL;
use Friendica\Core\Session\Capability\IHandleSessions;
use Friendica\Model\User\Cookie;
use SessionHandlerInterface;

/**
 * The native Session class which uses the PHP internal Session functions
 */
class Native extends AbstractSession implements IHandleSessions
{
	public function __construct(BaseURL $baseURL, ?SessionHandlerInterface $handler = null)
	{
		ini_set('session.gc_probability', 50);
		ini_set('session.use_only_cookies', 1);
		ini_set('session.use_strict_mode', 1);
		ini_set('session.cookie_httponly', (int) Cookie::HTTPONLY);
		ini_set('session.cookie_samesite', 'Lax');

		if ($baseURL->getScheme() === 'https') {
			ini_set('session.cookie_secure', 1);
		}

		if (isset($handler)) {
			session_set_save_handler($handler);
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function start(): IHandleSessions
	{
		session_start();
		return $this;
	}

	/**
	 * {@inheritDoc}
	 */
	public function regenerateId(): IHandleSessions
	{
		if (session_status() === PHP_SESSION_ACTIVE) {
			session_regenerate_id(true);
		}

		return $this;
	}
}
