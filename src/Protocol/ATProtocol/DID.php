<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Protocol\ATProtocol;

use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Network\HTTPException;

/**
 * This class handles DID related activities from the AT Protocol
 */
class DID
{
	/**
	 * Routes AT Protocol DID requests
	 *
	 * @param string $path
	 * @param array $server
	 * @return void
	 */
	public static function routeRequest(string $path, array $server)
	{
		$host = DI::baseUrl()->getHost();

		if (($host == $server['SERVER_NAME']) || !strpos((string) $server['SERVER_NAME'], '.' . $host)) {
			return;
		}

		if (!DI::config()->get('bluesky', 'friendica_handles')) {
			throw new HTTPException\NotFoundException();
		}

		if (!in_array($path, ['.well-known/atproto-did', ''])) {
			throw new HTTPException\NotFoundException();
		}

		$nick = str_replace('.' . $host, '', $server['SERVER_NAME']);

		$user = DBA::selectFirst('user', ['uid'], ['nickname' => $nick, 'verified' => true, 'blocked' => false, 'account_removed' => false, 'account_expired' => false]);
		if (empty($user['uid'])) {
			throw new HTTPException\NotFoundException();
		}

		if (!DI::pConfig()->get($user['uid'], 'bluesky', 'friendica_handle')) {
			throw new HTTPException\NotFoundException();
		}

		if ($path == '') {
			System::externalRedirect(DI::baseUrl() . '/profile/' . urlencode($nick), 0);
		}

		$did = DI::pConfig()->get($user['uid'], 'bluesky', 'did');
		if (empty($did)) {
			throw new HTTPException\NotFoundException();
		}

		header('Content-Type: text/plain');
		echo $did;
		System::exit();
	}
}
