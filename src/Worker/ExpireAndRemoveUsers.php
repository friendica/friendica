<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Worker;

use Friendica\Database\DBA;
use Friendica\Database\DBStructure;
use Friendica\DI;
use Friendica\Model\Contact;
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
		// expire any expired regular accounts. Don't expire groups.
		$condition = ["NOT `account_expired` AND `account_expires_on` > ? AND `account_expires_on` < ? AND `page-flags` = ? AND `uid` != ?",
			DBA::NULL_DATETIME, DateTimeFormat::utcNow(), User::PAGE_FLAGS_NORMAL, 0];
		DBA::update('user', ['account_expired' => true], $condition);

		// Ensure to never remove the user with uid=0
		DBA::update('user', ['verified' => true, 'blocked' => false, 'account_removed' => false, 'account_expired' => false,
			'account_expires_on'           => DBA::NULL_DATETIME], ['uid' => 0]);

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
			$pcid = Contact::getPublicIdByUserId($user['uid']);

			DI::logger()->info('Removing user - start', ['uid' => $user['uid'], 'pcid' => $pcid]);
			// We have to delete photo entries by hand because otherwise the photo data won't be deleted
			$result = Photo::delete(['uid' => $user['uid']]);
			if ($result) {
				DI::logger()->debug('Deleted user photos', ['result' => $result, 'rows' => DBA::affectedRows()]);
			} else {
				DI::logger()->warning('Error deleting user photos', ['errno' => DBA::errorNo(), 'errmsg' => DBA::errorMessage()]);
			}

			if (!empty($pcid)) {
				$result = DBA::delete('post-tag', ['cid' => $pcid]);
				if ($result) {
					DI::logger()->debug('Deleted post-tag entries', ['result' => $result, 'rows' => DBA::affectedRows()]);
				} else {
					DI::logger()->warning('Error deleting post-tag entries', ['errno' => DBA::errorNo(), 'errmsg' => DBA::errorMessage()]);
				}

				$tables = ['post', 'post-user', 'post-thread', 'post-thread-user'];

				if (DBStructure::existsTable('item')) {
					$tables[] = 'item';
				}

				// Delete all entries with the public contact in post related tables
				foreach ($tables as $table) {
					foreach (['owner-id', 'author-id', 'causer-id'] as $field) {
						$result = DBA::delete($table, [$field => $pcid]);
						if ($result) {
							DI::logger()->debug('Deleted entries', ['table' => $table, 'field' => $field, 'result' => $result, 'rows' => DBA::affectedRows()]);
						} else {
							DI::logger()->warning('Error deleting entries', ['table' => $table, 'field' => $field, 'errno' => DBA::errorNo(), 'errmsg' => DBA::errorMessage()]);
						}
					}
				}
			}

			// Delete the contacts of this user
			$self = DBA::selectFirst('contact', ['nurl'], ['self' => true, 'uid' => $user['uid']]);
			if (DBA::isResult($self)) {
				$result = DBA::delete('contact', ['nurl' => $self['nurl'], 'self' => false]);
				if ($result) {
					DI::logger()->debug('Deleted the user contact for other users', ['result' => $result, 'rows' => DBA::affectedRows()]);
				} else {
					DI::logger()->warning('Error deleting the user contact for other users', ['errno' => DBA::errorNo(), 'errmsg' => DBA::errorMessage()]);
				}
			}

			// Delete all contacts of this user
			$result = DBA::delete('contact', ['uid' => $user['uid']]);
			if ($result) {
				DI::logger()->debug('Deleted user contacts', ['result' => $result, 'rows' => DBA::affectedRows()]);
			} else {
				DI::logger()->warning('Error deleting user contacts', ['errno' => DBA::errorNo(), 'errmsg' => DBA::errorMessage()]);
			}

			// These tables contain the permissionset which will also be deleted when a user is deleted.
			// It seems that sometimes the system wants to delete the records in the wrong order.
			// So when the permissionset is deleted and these tables are still filled then an error is thrown.
			// So we now delete them before all other user related entries are deleted.
			if (DBStructure::existsTable('item')) {
				$result = DBA::delete('item', ['uid' => $user['uid']]);
				if ($result) {
					DI::logger()->debug('Deleted user items', ['result' => $result, 'rows' => DBA::affectedRows()]);
				} else {
					DI::logger()->warning('Error deleting user items', ['errno' => DBA::errorNo(), 'errmsg' => DBA::errorMessage()]);
				}
			}
			$result = DBA::delete('post-user', ['uid' => $user['uid']]);
			if ($result) {
				DI::logger()->debug('Deleted post-user entries', ['result' => $result, 'rows' => DBA::affectedRows()]);
			} else {
				DI::logger()->warning('Error deleting post-user entries', ['errno' => DBA::errorNo(), 'errmsg' => DBA::errorMessage()]);
			}

			$result = DBA::delete('profile_field', ['uid' => $user['uid']]);
			if ($result) {
				DI::logger()->debug('Deleted profile_field entries', ['result' => $result, 'rows' => DBA::affectedRows()]);
			} else {
				DI::logger()->warning('Error deleting profile_field entries', ['errno' => DBA::errorNo(), 'errmsg' => DBA::errorMessage()]);
			}

			$result = DBA::delete('user', ['uid' => $user['uid']]);
			if ($result) {
				DI::logger()->debug('Deleted user record', ['result' => $result, 'rows' => DBA::affectedRows()]);
			} else {
				DI::logger()->warning('Error deleting user record', ['errno' => DBA::errorNo(), 'errmsg' => DBA::errorMessage()]);
			}

			DI::logger()->info('Removing user - done', ['uid' => $user['uid']]);
		}
		DBA::close($users);
	}
}
