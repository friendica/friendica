<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Worker\Contact;

use Friendica\Core\Worker;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;

/**
 * Block a contact by URL
 */
class BlockByUrl
{
	/**
	 * Block a contact by URL
	 *
	 * @param string $url Contact URL or handle
	 * @param string $reason Block reason
	 * @param bool $purge Whether to also purge content
	 */
	public static function execute(string $url, string $reason = '', bool $purge = false)
	{
		$contact = Contact::getByURL($url, null, ['id', 'nurl']);
		if (empty($contact)) {
			DI::logger()->notice('Contact not found for blocking', ['url' => $url]);
			return;
		}

		if (DI::baseUrl()->isLocalUrl($contact['nurl'])) {
			DI::logger()->notice('Skipped blocking local contact', ['url' => $url]);
			return;
		}

		$existing = DBA::selectFirst('contact', ['blocked'], ['id' => $contact['id'], 'uid' => 0]);
		if (!empty($existing) && $existing['blocked']) {
			DI::logger()->debug('Contact already blocked', ['url' => $url]);
			return;
		}

		Contact::block($contact['id'], $reason);
		DI::logger()->info('Contact blocked', ['url' => $url, 'id' => $contact['id']]);

		if ($purge) {
			foreach (Contact::selectToArray(['id'], ['nurl' => $contact['nurl']]) as $contact_entry) {
				Worker::add(Worker::PRIORITY_LOW, 'Contact\RemoveContent', $contact_entry['id']);
			}
		}
	}
}
