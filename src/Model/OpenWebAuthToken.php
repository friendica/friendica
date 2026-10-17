<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Model;

use Friendica\Database\DBA;
use Friendica\Util\DateTimeFormat;

/**
 * Methods to deal with entries of the 'openwebauth-token' table.
 */
class OpenWebAuthToken
{
	/**
	 * Create an entry in the 'openwebauth-token' table.
	 *
	 * @param string $type Verify type.
	 * @param int    $uid  The user ID.
	 * @param string $token
	 * @param string $meta
	 * @return boolean
	 * @throws \Exception
	 */
	public static function create(string $type, int $uid, string $token, string $meta)
	{
		$fields = [
			'type'    => $type,
			'uid'     => $uid,
			'token'   => $token,
			'meta'    => $meta,
			'created' => DateTimeFormat::utcNow(),
		];
		return DBA::insert('openwebauth-token', $fields);
	}

	/**
	 * Get the "meta" field of an entry in the openwebauth-token table.
	 *
	 * @param string $type Verify type.
	 * @param int    $uid  The user ID.
	 * @param string $token
	 *
	 * @return string|boolean The meta entry or false if not found.
	 * @throws \Exception
	 */
	public static function getMeta(string $type, int $uid, string $token)
	{
		$condition = ['type' => $type, 'uid' => $uid, 'token' => $token];

		$entry = DBA::selectFirst('openwebauth-token', ['id', 'meta'], $condition);
		if (DBA::isResult($entry)) {
			DBA::delete('openwebauth-token', ['id' => $entry['id']]);

			return $entry['meta'];
		}
		return false;
	}

	/**
	 * Purge entries of a verify-type older than interval.
	 *
	 * @param string $type     Verify type.
	 * @param string $interval SQL compatible time interval
	 * @return void
	 * @throws \Exception
	 */
	public static function purge(string $type, string $interval)
	{
		$condition = ["`type` = ? AND `created` < ?", $type, DateTimeFormat::utc('now - ' . $interval)];
		DBA::delete('openwebauth-token', $condition);
	}

}
