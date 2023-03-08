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

use Friendica\Database\DBA;
use Friendica\Database\DBStructure;
use Friendica\Model\Photo;
use Friendica\Model\User;
use Friendica\Util\DateTimeFormat;

/**
 * Expire and remove user entries
 */
class ExpireAndRemoveUsers
{
	public static function execute()
	{
		// expire any expired regular accounts. Don't expire forums.
		$condition = ["NOT `account_expired` AND `account_expires_on` > ? AND `account_expires_on` < ? AND `page-flags` = ? AND `uid` != ?",
			DBA::NULL_DATETIME, DateTimeFormat::utcNow(), User::PAGE_FLAGS_NORMAL, 0];
		DBA::update('user', ['account_expired' => true], $condition);

		// Ensure to never remove the user with uid=0
		DBA::update('user', ['account_expired' => false, 'account_removed' => false,
			'account_expires_on' => DBA::NULL_DATETIME], ['uid' => 0]);

		// Remove any freshly expired account
		$users = DBA::select('user', ['uid'], ['account_expired' => true, 'account_removed' => false]);
		while ($user = DBA::fetch($users)) {
			if ($user['uid'] != 0) {
				User::remove($user['uid']);
			}
		}
		DBA::close($users);

		// delete user records for recently removed accounts
		$users = DBA::select('user', ['uid'], ["`account_removed` AND `account_expires_on` < ? AND `uid` != ?", DateTimeFormat::utcNow(), 0]);
		while ($user = DBA::fetch($users)) {
			// We have to delete photo entries by hand because otherwise the photo data won't be deleted
			Photo::delete(['uid' => $user['uid']]);

			// Delete the contacts of this user
			$self = DBA::selectFirst('contact', ['nurl'], ['self' => true, 'uid' => $user['uid']]);
			if (DBA::isResult($self)) {
				DBA::delete('contact', ['nurl' => $self['nurl'], 'self' => false]);
			}

			// Delete all contacts of this user
			DBA::delete('contact', ['uid' => $user['uid']]);

			// These tables contain the permissionset which will also be deleted when a user is deleted.
			// It seems that sometimes the system wants to delete the records in the wrong order.
			// So when the permissionset is deleted and these tables are still filled then an error is thrown.
			// So we now delete them before all other user related entries are deleted.
			if (DBStructure::existsTable('item')) {
				DBA::delete('item', ['uid' => $user['uid']]);
			}
			DBA::delete('post-user', ['uid' => $user['uid']]);
			DBA::delete('profile_field', ['uid' => $user['uid']]);

			DBA::delete('user', ['uid' => $user['uid']]);
		}
		DBA::close($users);
	}
}
