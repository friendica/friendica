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

namespace Friendica\Model\Post;

use Friendica\Database\DBA;
use BadMethodCallException;
use Friendica\Database\Database;
use Friendica\DI;
use Friendica\Model\ItemURI;

class Delivery
{
	/**
	 * Add a post to an inbox
	 *
	 * @param integer $uri_id
	 * @param string  $inbox
	 * @param string  $created
	 * @param array   %receivers
	 */
	public static function add(int $uri_id, int $uid, string $inbox, string $created, string $command, array $receivers)
	{
		if (empty($uri_id)) {
			throw new BadMethodCallException('Empty URI_id');
		}

		$fields = ['uri-id' => $uri_id, 'uid' => $uid, 'inbox-id' => ItemURI::getIdByURI($inbox),
			'created' => $created, 'command' => $command, 'receivers' => json_encode($receivers)];

		DBA::insert('post-delivery', $fields, Database::INSERT_IGNORE);
	}

	/**
	 * Remove post from an inbox after delivery
	 *
	 * @param integer $uri_id
	 * @param string  $inbox
	 */
	public static function remove(int $uri_id, string $inbox)
	{
		DBA::delete('post-delivery', ['uri-id' => $uri_id, 'inbox-id' => ItemURI::getIdByURI($inbox)]);
	}

	/**
	 * Remove failed posts for an inbox
	 *
	 * @param string  $inbox
	 */
	public static function removeFailed(string $inbox)
	{
		DBA::delete('post-delivery', ["`inbox-id` = ? AND `failed` >= ?", ItemURI::getIdByURI($inbox), DI::config()->get('system', 'worker_defer_limit')]);
	}

	/**
	 * Increment "failed" counter for the given inbox and post
	 *
	 * @param integer $uri_id
	 * @param string  $inbox
	 */
	public static function incrementFailed(int $uri_id, string $inbox)
	{
		return DBA::e('UPDATE `post-delivery` SET `failed` = `failed` + 1 WHERE `uri-id` = ? AND `inbox-id` = ?', $uri_id, ItemURI::getIdByURI($inbox));
	}

	public static function selectForInbox(string $inbox)
	{
		$rows = DBA::select('post-delivery', [], ["`inbox-id` = ? AND `failed` < ?", ItemURI::getIdByURI($inbox), DI::config()->get('system', 'worker_defer_limit')], ['order' => ['created']]);
		$deliveries = [];
		while ($row = DBA::fetch($rows)) {
			if (!empty($row['receivers'])) {
				$row['receivers'] = json_decode($row['receivers'], true);
			} else {
				$row['receivers'] = [];
			}
			$deliveries[] = $row;
		}
		DBA::close($rows);

		return $deliveries;
	}
}
