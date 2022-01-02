<?php
/**
 * @copyright Copyright (C) 2010-2022, the Friendica project
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

namespace Friendica\Worker;

use Friendica\Core\Logger;
use Friendica\Core\Protocol;
use Friendica\Core\Worker;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Util\DateTimeFormat;

/**
 * Update federated contacts
 */
class UpdateContacts
{
	public static function execute()
	{
		$base_condition = ['network' => array_merge(Protocol::FEDERATED, [Protocol::ZOT, Protocol::PHANTOM]), 'self' => false];

		$update_limit = DI::config()->get('system', 'contact_update_limit');
		if (empty($update_limit)) {
			return;
		}

		$updating = Worker::countWorkersByCommand('UpdateContact');
		$limit = $update_limit - $updating;
		if ($limit <= 0) {
			Logger::info('The number of currently running jobs exceed the limit');
			return;
		}

		$condition = DBA::mergeConditions($base_condition,
			["`uid` != ? AND (`last-update` < ? OR (NOT `failed` AND `last-update` < ?))",
			0, DateTimeFormat::utc('now - 1 month'), DateTimeFormat::utc('now - 1 week')]);
		$ids = self::getContactsToUpdate($condition, [], $limit);
		Logger::info('Fetched federated user contacts', ['count' => count($ids)]);

		$conditions = ["`id` IN (SELECT `author-id` FROM `post` WHERE `author-id` = `contact`.`id`)",
			"`id` IN (SELECT `owner-id` FROM `post` WHERE `owner-id` = `contact`.`id`)",
			"`id` IN (SELECT `causer-id` FROM `post` WHERE `causer-id` = `contact`.`id`)",
			"`id` IN (SELECT `cid` FROM `post-tag` WHERE `cid` = `contact`.`id`)",
			"`id` IN (SELECT `cid` FROM `user-contact` WHERE `cid` = `contact`.`id`)"];

		foreach ($conditions as $contact_condition) {
			$condition = DBA::mergeConditions($base_condition,
				[$contact_condition . " AND (`last-update` < ? OR (NOT `failed` AND `last-update` < ?))",
				DateTimeFormat::utc('now - 1 month'), DateTimeFormat::utc('now - 1 week')]);
			$ids = self::getContactsToUpdate($condition, $ids, $limit);
			Logger::info('Fetched interacting federated contacts', ['count' => count($ids), 'condition' => $contact_condition]);
		}

		if (count($ids) > $limit) {
			$ids = array_slice($ids, 0, $limit, true);
		}

		if (!DI::config()->get('system', 'update_active_contacts')) {
			// Add every contact (mostly failed ones) that hadn't been updated for six months
			// and every non failed contact that hadn't been updated for a month
			$condition = DBA::mergeConditions($base_condition,
				["(`last-update` < ? OR (NOT `failed` AND `last-update` < ?))",
					DateTimeFormat::utc('now - 6 month'), DateTimeFormat::utc('now - 1 month')]);
			$previous = count($ids);
			$ids = self::getContactsToUpdate($condition, $ids, $limit - $previous);
			Logger::info('Fetched federated contacts', ['count' => count($ids) - $previous]);
		}

		$count = 0;
		foreach ($ids as $id) {
			if (Worker::add(PRIORITY_LOW, "UpdateContact", $id)) {
				++$count;
			}
		}

		Logger::info('Initiated update for federated contacts', ['count' => $count]);
	}

	/**
	 * Returns contact ids based on a given condition
	 *
	 * @param array $condition
	 * @param array $ids
	 * @return array contact ids
	 */
	private static function getContactsToUpdate(array $condition, array $ids = [], int $limit)
	{
		$contacts = DBA::select('contact', ['id'], $condition, ['limit' => $limit]);
		while ($contact = DBA::fetch($contacts)) {
			$ids[$contact['id']] = $contact['id'];
		}
		DBA::close($contacts);
		return $ids;
	}
}
