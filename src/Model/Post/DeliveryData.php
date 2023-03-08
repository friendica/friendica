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
use \BadMethodCallException;

class DeliveryData
{
	const LEGACY_FIELD_LIST = [
		// Legacy fields moved from item table
		'postopts',
		'inform',
	];

	const FIELD_LIST = [
		// New delivery fields with virtual field name in item fields
		'queue_count' => 'delivery_queue_count',
		'queue_done'  => 'delivery_queue_done',
		'queue_failed'  => 'delivery_queue_failed',
	];

	const ACTIVITYPUB = 1;
	const DFRN = 2;
	const LEGACY_DFRN = 3; // @deprecated since version 2021.09
	const DIASPORA = 4;
	const OSTATUS = 5;
	const MAIL = 6;

	/**
	 * Extract delivery data from the provided item fields
	 *
	 * @param array $fields
	 * @return array
	 */
	public static function extractFields(array &$fields)
	{
		$delivery_data = [];
		foreach (array_merge(self::FIELD_LIST, self::LEGACY_FIELD_LIST) as $key => $field) {
			if (is_int($key) && isset($fields[$field])) {
				// Legacy field moved from item table
				$delivery_data[$field] = $fields[$field];
				$fields[$field] = null;
			} elseif (isset($fields[$field])) {
				// New delivery field with virtual field name in item fields
				$delivery_data[$key] = $fields[$field];
				unset($fields[$field]);
			}
		}

		return $delivery_data;
	}

	/**
	 * Increments the queue_done for the given URI ID.
	 *
	 * Avoids racing condition between multiple delivery threads.
	 *
	 * @param integer $uri_id
	 * @param integer $protocol
	 * @return bool
	 * @throws \Exception
	 */
	public static function incrementQueueDone(int $uri_id, int $protocol = 0)
	{
		$sql = '';

		switch ($protocol) {
			case self::ACTIVITYPUB:
				$sql = ", `activitypub` = `activitypub` + 1";
				break;
			case self::DFRN:
				$sql = ", `dfrn` = `dfrn` + 1";
				break;
			case self::LEGACY_DFRN:
				$sql = ", `legacy_dfrn` = `legacy_dfrn` + 1";
				break;
			case self::DIASPORA:
				$sql = ", `diaspora` = `diaspora` + 1";
				break;
			case self::OSTATUS:
				$sql = ", `ostatus` = `ostatus` + 1";
				break;
		}

		return DBA::e('UPDATE `post-delivery-data` SET `queue_done` = `queue_done` + 1' . $sql . ' WHERE `uri-id` = ?', $uri_id);
	}

	/**
	 * Increments the queue_failed for the given URI ID.
	 *
	 * Avoids racing condition between multiple delivery threads.
	 *
	 * @param integer $uri_id
	 * @return bool
	 * @throws \Exception
	 */
	public static function incrementQueueFailed(int $uri_id)
	{
		return DBA::e('UPDATE `post-delivery-data` SET `queue_failed` = `queue_failed` + 1 WHERE `uri-id` = ?', $uri_id);
	}

	/**
	 * Increments the queue_count for the given URI ID.
	 *
	 * @param integer $uri_id
	 * @param integer $increment
	 * @return bool
	 * @throws \Exception
	 */
	public static function incrementQueueCount(int $uri_id, int $increment = 1)
	{
		return DBA::e('UPDATE `post-delivery-data` SET `queue_count` = `queue_count` + ? WHERE `uri-id` = ?', $increment, $uri_id);
	}

	/**
	 * Insert a new URI delivery data entry
	 *
	 * @param integer $uri_id
	 * @param array   $fields
	 * @return bool
	 * @throws \Exception
	 */
	public static function insert(int $uri_id, array $fields)
	{
		if (empty($uri_id)) {
			throw new BadMethodCallException('Empty URI_id');
		}

		$fields['uri-id'] = $uri_id;

		return DBA::replace('post-delivery-data', $fields);
	}

	/**
	 * Update/Insert URI delivery data
	 *
	 * If you want to update queue_done, please use incrementQueueDone instead.
	 *
	 * @param integer $uri_id
	 * @param array   $fields
	 * @return bool
	 * @throws \Exception
	 */
	public static function update(int $uri_id, array $fields)
	{
		if (empty($uri_id)) {
			throw new BadMethodCallException('Empty URI_id');
		}

		if (empty($fields)) {
			// Nothing to do, update successful
			return true;
		}

		return DBA::update('post-delivery-data', $fields, ['uri-id' => $uri_id], true);
	}

	/**
	 * Delete URI delivery data
	 *
	 * @param integer $uri_id
	 * @return bool
	 * @throws \Exception
	 */
	public static function delete(int $uri_id)
	{
		if (empty($uri_id)) {
			throw new BadMethodCallException('Empty URI_id');
		}

		return DBA::delete('post-delivery-data', ['uri-id' => $uri_id]);
	}
}
