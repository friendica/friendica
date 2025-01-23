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

class MergeContact
{
	/**
	 * Replace all occurences of the given contact id and replace it
	 *
	 * @param integer $new_cid New contact id
	 * @param integer $old_cid Old contact id
	 * @param integer $uid User id
	 */
	public static function execute(int $new_cid, int $old_cid, int $uid)
	{
		if (empty($new_cid) || empty($old_cid) || ($new_cid == $old_cid)) {
			// Invalid request
			return;
		}

		DI::logger()->info('Handling duplicate', ['search' => $old_cid, 'replace' => $new_cid]);

		foreach (['item', 'thread', 'post-user', 'post-thread-user'] as $table) {
			if (DBStructure::existsTable($table)) {
				DBA::update($table, ['contact-id' => $new_cid], ['contact-id' => $old_cid]);
			}
		}
		DBA::update('mail', ['contact-id' => $new_cid], ['contact-id' => $old_cid]);
		DBA::update('photo', ['contact-id' => $new_cid], ['contact-id' => $old_cid]);
		DBA::update('event', ['cid' => $new_cid], ['cid' => $old_cid]);

		// These fields only contain public contact entries (uid = 0)
		if ($uid == 0) {
			DBA::update('post-tag', ['cid' => $new_cid], ['cid' => $old_cid]);
			DBA::delete('post-tag', ['cid' => $old_cid]);
			foreach (['item', 'post', 'post-thread', 'post-user', 'post-thread-user'] as $table) {
				if (DBStructure::existsTable($table)) {
					DBA::update($table, ['author-id' => $new_cid], ['author-id' => $old_cid]);
					DBA::update($table, ['owner-id' => $new_cid], ['owner-id' => $old_cid]);
					DBA::update($table, ['causer-id' => $new_cid], ['causer-id' => $old_cid]);
				}
			}
			if (DBStructure::existsTable('thread')) {
				DBA::update('thread', ['author-id' => $new_cid], ['author-id' => $old_cid]);
				DBA::update('thread', ['owner-id' => $new_cid], ['owner-id' => $old_cid]);
			}
		} else {
			self::mergePersonalContacts($new_cid, $old_cid);
		}

		// Remove the duplicate
		Contact::deleteById($old_cid);
	}

	/**
	 * Merge important fields between two contacts
	 *
	 * @param integer $first
	 * @param integer $duplicate
	 * @return void
	 */
	private static function mergePersonalContacts(int $first, int $duplicate)
	{
		$fields = ['self', 'remote_self', 'rel', 'prvkey', 'hub-verify', 'priority', 'writable', 'archive', 'pending',
			'rating', 'notify_new_posts', 'fetch_further_information', 'ffi_keyword_denylist', 'block_reason'];
		$c1 = Contact::getById($first, $fields);
		$c2 = Contact::getById($duplicate, $fields);

		$ctarget = $c1;

		if ($c1['self'] || $c2['self']) {
			return;
		}

		$ctarget['rel'] = $c1['rel'] | $c2['rel'];
		foreach (['prvkey', 'hub-verify', 'priority', 'rating', 'fetch_further_information', 'ffi_keyword_denylist', 'block_reason'] as $field) {
			$ctarget[$field] = $c1[$field] ?: $c2[$field];
		}

		foreach (['remote_self', 'writable', 'notify_new_posts'] as $field) {
			$ctarget[$field] = $c1[$field] || $c2[$field];
		}

		foreach (['archive', 'pending'] as $field) {
			$ctarget[$field] = $c1[$field] && $c2[$field];
		}

		$data = [];

		foreach ($fields as $field) {
			if ($ctarget[$field] != $c1[$field]) {
				$data[$field] = $ctarget[$field];
			}
		}

		if (empty($data)) {
			return;
		}
		Contact::update($data, ['id' => $first]);
	}
}
