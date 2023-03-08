<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

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
			'created' => DateTimeFormat::utcNow()
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
	 * @return string|boolean The meta enry or false if not found.
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
		$condition = ["`type` = ? AND `created` < ?", $type, DateTimeFormat::utcNow() . ' - INTERVAL ' . $interval];
		DBA::delete('openwebauth-token', $condition);
	}

}
