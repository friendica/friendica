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

namespace Friendica\Model\APContact;

use Friendica\Database\DBA;

class Endpoint
{
	// Mobilizon Endpoints
	const DISCUSSIONS = 10;
	const EVENTS      = 11;
	const MEMBERS     = 12;
	const POSTS       = 13;
	const RESOURCES   = 14;
	const TODOS       = 15;

	// Peertube Endpoints
	const PLAYLISTS = 20;

	// Mastodon Endpoints
	const DEVICES = 30;

	const ENDPOINT_NAMES = [
		self::PLAYLISTS   => 'pt:playlists',
		self::DISCUSSIONS => 'mobilizon:discussions',
		self::EVENTS      => 'mobilizon:events',
		self::MEMBERS     => 'mobilizon:members',
		self::POSTS       => 'mobilizon:posts',
		self::RESOURCES   => 'mobilizon:resources',
		self::TODOS       => 'mobilizon:todos',
		self::DEVICES     => 'toot:devices',
	];

	/**
	 * Update an apcontact endpoint
	 *
	 * @param int    $owner_uri_id
	 * @param int    $type
	 * @param string $url
	 * @return bool
	 */
	public static function update(int $owner_uri_id, int $type, string $url)
	{
		if (empty($url) || empty($owner_uri_id)) {
			return false;
		}

		$fields = ['owner-uri-id' => $owner_uri_id, 'type' => $type];

		return DBA::update('endpoint', $fields, ['url' => $url], true);
	}
}
