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

use Friendica\Contact\Avatar;
use Friendica\Core\Logger;
use Friendica\Core\Protocol;
use Friendica\Database\DBA;
use Friendica\Database\DBStructure;
use Friendica\Model\Contact;
use Friendica\Model\Photo;
use Friendica\Util\DateTimeFormat;

/**
 * Removes public contacts that aren't in use
 */
class RemoveUnusedContacts
{
	public static function execute()
	{
		$condition = ["`id` != ? AND `uid` = ? AND NOT `self` AND NOT `nurl` IN (SELECT `nurl` FROM `contact` WHERE `uid` != ?)
			AND (NOT `network` IN (?, ?, ?, ?, ?, ?) OR (`archive` AND `success_update` < ?))
			AND NOT `id` IN (SELECT `author-id` FROM `post-user` WHERE `author-id` = `contact`.`id`)
			AND NOT `id` IN (SELECT `owner-id` FROM `post-user` WHERE `owner-id` = `contact`.`id`)
			AND NOT `id` IN (SELECT `causer-id` FROM `post-user` WHERE `causer-id` IS NOT NULL AND `causer-id` = `contact`.`id`)
			AND NOT `id` IN (SELECT `cid` FROM `post-tag` WHERE `cid` = `contact`.`id`)
			AND NOT `id` IN (SELECT `contact-id` FROM `post-user` WHERE `contact-id` = `contact`.`id`)
			AND NOT `id` IN (SELECT `cid` FROM `user-contact` WHERE `cid` = `contact`.`id`)
			AND NOT `id` IN (SELECT `cid` FROM `event` WHERE `cid` = `contact`.`id`)
			AND NOT `id` IN (SELECT `contact-id` FROM `group_member` WHERE `contact-id` = `contact`.`id`)
			AND `created` < ?",
			0, 0, 0, Protocol::DFRN, Protocol::DIASPORA, Protocol::OSTATUS, Protocol::FEED, Protocol::MAIL, Protocol::ACTIVITYPUB, DateTimeFormat::utc('now - 365 days'), DateTimeFormat::utc('now - 30 days')];

		$total = DBA::count('contact', $condition);
		Logger::notice('Starting removal', ['total' => $total]);
		$count = 0;
		$contacts = DBA::select('contact', ['id', 'uid', 'photo', 'thumb', 'micro'], $condition);
		while ($contact = DBA::fetch($contacts)) {
			Photo::delete(['uid' => $contact['uid'], 'contact-id' => $contact['id']]);
			Avatar::deleteCache($contact);

			if (DBStructure::existsTable('thread')) {
				DBA::delete('thread', ['owner-id' => $contact['id']]);
				DBA::delete('thread', ['author-id' => $contact['id']]);
			}
			if (DBStructure::existsTable('item')) {
				DBA::delete('item', ['owner-id' => $contact['id']]);
				DBA::delete('item', ['author-id' => $contact['id']]);
				DBA::delete('item', ['causer-id' => $contact['id']]);
			}

			// There should be none entry for the contact in these tables when none was found in "post-user".
			// But we want to be sure since the foreign key prohibits deletion otherwise.
			DBA::delete('post', ['owner-id' => $contact['id']]);
			DBA::delete('post', ['author-id' => $contact['id']]);
			DBA::delete('post', ['causer-id' => $contact['id']]);

			DBA::delete('post-thread', ['owner-id' => $contact['id']]);
			DBA::delete('post-thread', ['author-id' => $contact['id']]);
			DBA::delete('post-thread', ['causer-id' => $contact['id']]);

			DBA::delete('post-thread-user', ['owner-id' => $contact['id']]);
			DBA::delete('post-thread-user', ['author-id' => $contact['id']]);
			DBA::delete('post-thread-user', ['causer-id' => $contact['id']]);

			Contact::deleteById($contact['id']);
			if ((++$count % 1000) == 0) {
				Logger::info('In removal', ['count' => $count, 'total' => $total]);
			}
		}
		DBA::close($contacts);
		Logger::notice('Removal done', ['count' => $count, 'total' => $total]);
	}
}
