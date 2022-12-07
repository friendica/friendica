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
use Friendica\Protocol\Feed;
use Friendica\Util\DateTimeFormat;

/**
 * Poll contacts for unreceived messages
 */
class PollContacts
{
	public static function execute()
	{
		$abandon_days = intval(DI::config()->get('system', 'account_abandon_days'));
		if ($abandon_days < 1) {
			$abandon_days = 0;
		}

		$condition = ['network' => [Protocol::FEED, Protocol::MAIL, Protocol::OSTATUS], 'self' => false, 'blocked' => false];

		if (!empty($abandon_days)) {
			$condition = DBA::mergeConditions($condition,
				["`uid` != ? AND `uid` IN (SELECT `uid` FROM `user` WHERE NOT `account_expired` AND NOT `account_removed` AND `last-activity` > ?)", 0, DateTimeFormat::utc('now - ' . $abandon_days . ' days')]);
		} else 	{
			$condition = DBA::mergeConditions($condition,
				["`uid` != ? AND `uid` IN (SELECT `uid` FROM `user` WHERE NOT `account_expired` AND NOT `account_removed`)", 0]);
		}

		$contacts = DBA::select('contact', ['id', 'nick', 'name', 'network', 'archive', 'last-update', 'priority', 'rating'], $condition);
		if (!DBA::isResult($contacts)) {
			return;
		}

		while ($contact = DBA::fetch($contacts)) {
			$interval = Feed::getPollInterval($contact);
			if ($interval == 0) {
				continue;
			}

			$now = DateTimeFormat::utcNow();
			$next_update = DateTimeFormat::utc($contact['last-update'] . ' + ' . $interval . ' minute');

			if ($now < $next_update)  {
				Logger::debug('No update', ['cid' => $contact['id'], 'interval' => $interval, 'next' => $next_update, 'now' => $now]);
				continue;
			}

			if ((($contact['network'] == Protocol::FEED) && ($contact['priority'] <= 3)) || ($contact['network'] == Protocol::MAIL)) {
				$priority = Worker::PRIORITY_MEDIUM;
			} elseif ($contact['archive']) {
				$priority = Worker::PRIORITY_NEGLIGIBLE;
			} else {
				$priority = Worker::PRIORITY_LOW;
			}

			Logger::notice("Polling " . $contact["network"] . " " . $contact["id"] . " " . $contact['priority'] . " " . $contact["nick"] . " " . $contact["name"]);

			Worker::add(['priority' => $priority, 'dont_fork' => true, 'force_priority' => true], 'OnePoll', (int)$contact['id']);
			Worker::coolDown();
		}
		DBA::close($contacts);
	}
}
