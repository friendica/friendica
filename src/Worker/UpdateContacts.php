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

namespace Friendica\Worker;

use Friendica\Core\Logger;
use Friendica\Core\Worker;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\GServer;
use Friendica\Util\DateTimeFormat;

/**
 * Update federated contacts
 */
class UpdateContacts
{
	public static function execute()
	{
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

		Logger::info('Updating contact', ['count' => $limit]);

		$condition = ['self' => false];

		if (DI::config()->get('system', 'update_active_contacts')) {
			$condition = array_merge(['local-data' => true], $condition);
		}

		$condition = DBA::mergeConditions(["`next-update` < ?", DateTimeFormat::utcNow()], $condition);
		$contacts = DBA::select('contact', ['id', 'url', 'gsid', 'baseurl'], $condition, ['order' => ['next-update'], 'limit' => $limit]);
		$count = 0;
		while ($contact = DBA::fetch($contacts)) {
			if (Contact::isLocal($contact['url'])) {
				continue;
			}

			try {
				if ((!empty($contact['gsid']) || !empty($contact['baseurl'])) && GServer::reachable($contact)) {
					$stamp = (float)microtime(true);
					$success = Contact::updateFromProbe($contact['id']);
					Logger::debug('Direct update', ['id' => $contact['id'], 'count' => $count, 'duration' => round((float)microtime(true) - $stamp, 3), 'success' => $success]);
					++$count;
				} elseif (UpdateContact::add(['priority' => Worker::PRIORITY_LOW, 'dont_fork' => true], $contact['id'])) {
					Logger::debug('Update by worker', ['id' => $contact['id'], 'count' => $count]);
					++$count;
				}
			} catch (\InvalidArgumentException $e) {
				Logger::notice($e->getMessage(), ['contact' => $contact]);
			}

			Worker::coolDown();
		}
		DBA::close($contacts);

		Logger::info('Initiated update for federated contacts', ['count' => $count]);
	}
}
