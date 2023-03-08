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
use Friendica\Model\Contact;
use Friendica\Model\Photo;

/**
 * Removes cached avatars from public contacts that aren't in use
 */
class RemoveUnusedAvatars
{
	public static function execute()
	{
		$sql = "FROM `contact` INNER JOIN `photo` ON `contact`.`id` = `contact-id`
			WHERE `contact`.`uid` = ? AND NOT `self` AND (`photo` != ? OR `thumb` != ? OR `micro` != ?)
				AND NOT `nurl` IN (SELECT `nurl` FROM `contact` WHERE `uid` != ?)
				AND NOT `contact`.`id` IN (SELECT `author-id` FROM `post-user` WHERE `author-id` = `contact`.`id`)
				AND NOT `contact`.`id` IN (SELECT `owner-id` FROM `post-user` WHERE `owner-id` = `contact`.`id`)
				AND NOT `contact`.`id` IN (SELECT `causer-id` FROM `post-user` WHERE `causer-id` IS NOT NULL AND `causer-id` = `contact`.`id`)
				AND NOT `contact`.`id` IN (SELECT `cid` FROM `post-tag` WHERE `cid` = `contact`.`id`)
				AND NOT `contact`.`id` IN (SELECT `contact-id` FROM `post-user` WHERE `contact-id` = `contact`.`id`);";

		$ret = DBA::fetchFirst("SELECT COUNT(*) AS `total` " . $sql, 0, '', '', '', 0);
		$total = $ret['total'] ?? 0;
		Logger::notice('Starting removal', ['total' => $total]);
		$count = 0;
		$contacts = DBA::p("SELECT `contact`.`id` " . $sql, 0, '', '', '', 0);
		while ($contact = DBA::fetch($contacts)) {
			Contact::update(['photo' => '', 'thumb' => '', 'micro' => ''], ['id' => $contact['id']]);
			Photo::delete(['contact-id' => $contact['id'], 'photo-type' => [Photo::CONTACT_AVATAR, Photo::CONTACT_BANNER]]);
			if ((++$count % 1000) == 0) {
				if (!Worker::isInMaintenanceWindow()) {
					Logger::notice('We are outside of the maintenance window, quitting');
					return;
				}
				Logger::info('In removal', ['count' => $count, 'total' => $total]);
			}
		}
		DBA::close($contacts);
		Logger::notice('Removal done', ['count' => $count, 'total' => $total]);

		self::fixPhotoContacts();
		self::deleteDuplicates();
	}

	private static function fixPhotoContacts()
	{
		$total = 0;
		$deleted = 0;
		$updated1 = 0;
		$updated2 = 0;
		Logger::notice('Starting contact fix');
		$photos = DBA::select('photo', [], ["`uid` = ? AND `contact-id` IN (SELECT `id` FROM `contact` WHERE `uid` != ?) AND `contact-id` != ? AND `scale` IN (?, ?, ?)", 0, 0, 0, 4, 5, 6]);
		while ($photo = DBA::fetch($photos)) {
			$total++;
			$photo_contact = Contact::getById($photo['contact-id']);
			$resource = Photo::ridFromURI($photo_contact['photo']);
			if ($photo['resource-id'] == $resource) {
				$contact = DBA::selectFirst('contact', [], ['nurl' => $photo_contact['nurl'], 'uid' => 0]);
				if (!empty($contact['photo']) && ($contact['photo'] == $photo_contact['photo'])) {
					Logger::notice('Photo updated to public user', ['id' => $photo['id'], 'contact-id' => $contact['id']]);
					DBA::update('photo', ['contact-id' => $contact['id']], ['id' => $photo['id']]);
					$updated1++;
				}
			} else {
				$updated = false;
				$contacts = DBA::select('contact', [], ['nurl' => $photo_contact['nurl']]);
				while ($contact = DBA::fetch($contacts)) {
					if ($photo['resource-id'] == Photo::ridFromURI($contact['photo'])) {
						Logger::notice('Photo updated to given user', ['id' => $photo['id'], 'contact-id' => $contact['id'], 'uid' => $contact['uid']]);
						DBA::update('photo', ['contact-id' => $contact['id'], 'uid' => $contact['uid']], ['id' => $photo['id']]);
						$updated = true;
						$updated2++;
					}
				}
				DBA::close($contacts);
				if (!$updated) {
					Logger::notice('Photo deleted', ['id' => $photo['id']]);
					Photo::delete(['id' => $photo['id']]);
					$deleted++;
				}
			}
		}
		DBA::close($photos);
		Logger::notice('Contact fix done', ['total' => $total, 'updated1' => $updated1, 'updated2' => $updated2, 'deleted' => $deleted]);
	}

	private static function deleteDuplicates()
	{
		$size = [4 => 'photo', 5 => 'thumb', 6 => 'micro'];

		$total = 0;
		$deleted = 0;
		Logger::notice('Starting duplicate removal');
		$photos = DBA::p("SELECT `photo`.`id`, `photo`.`uid`, `photo`.`scale`, `photo`.`album`, `photo`.`contact-id`, `photo`.`resource-id`, `contact`.`photo`, `contact`.`thumb`, `contact`.`micro` FROM `photo` INNER JOIN `contact` ON `contact`.`id` = `photo`.`contact-id` and `photo`.`contact-id` != ? AND `photo`.`scale` IN (?, ?, ?)", 0, 4, 5, 6);
		while ($photo = DBA::fetch($photos)) {
			$resource = Photo::ridFromURI($photo[$size[$photo['scale']]]);
			if ($resource != $photo['resource-id'] && !empty($resource)) {
				$total++;
				if (DBA::exists('photo', ['resource-id' => $resource, 'scale' => $photo['scale']])) {
					Logger::notice('Photo deleted', ['id' => $photo['id']]);
					Photo::delete(['id' => $photo['id']]);
					$deleted++;
				}
			}
		}
		DBA::close($photos);
		Logger::notice('Duplicate removal done', ['total' => $total, 'deleted' => $deleted]);
	}
}
