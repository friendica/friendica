<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Protocol;

use Friendica\App;
use Friendica\Core\Addon;
use Friendica\DI;
use Friendica\Module;
use Friendica\Module\Register;

/**
 * ZOT Protocol class
 *
 * This class contains functionality that is needed for OpenWebAuth, which is part of ZOT.
 * Friendica doesn't support the ZOT protocol itself.
 */
class ZOT
{
	/**
	 * Checks if the web request is done for the AP protocol
	 *
	 * @return bool is it ZOT?
	 */
	public static function isRequest(): bool
	{
		if (stristr($_SERVER['HTTP_ACCEPT'] ?? '', 'application/x-zot+json')) {
			DI::logger()->debug('Is ZOT request', ['accept' => $_SERVER['HTTP_ACCEPT'], 'agent' => $_SERVER['HTTP_USER_AGENT'] ?? '']);
			return true;
		}

		return false;
	}

	/**
	 * Get information about this site
	 *
	 * @return array
	 */
	public static function getSiteInfo(): array
	{
		$policies = [
			Module\Register::OPEN    => 'open',
			Module\Register::APPROVE => 'approve',
			Module\Register::CLOSED  => 'closed',
		];

		return [
			'url'             => (string)DI::baseUrl(),
			'openWebAuth'     => (string)DI::baseUrl() . '/owa',
			'authRedirect'    => (string)DI::baseUrl() . '/magic',
			'register_policy' => $policies[Register::getPolicy()],
			'accounts'        => DI::keyValue()->get('nodeinfo_total_users'),
			'plugins'         => Addon::getVisibleList(),
			'sitename'        => DI::config()->get('config', 'sitename'),
			'about'           => DI::config()->get('config', 'info'),
			'project'         => App::PLATFORM,
			'version'         => App::VERSION,
		];
	}
}
