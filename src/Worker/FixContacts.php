<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Worker;

use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;

/**
 * add missing public contacts and account-user entries
 */
class FixContacts
{
	public static function execute()
	{
		$added = 0;
		DI::logger()->info('Add missing public contacts');
		$contacts = DBA::p("SELECT `contact`.`id` FROM `contact` LEFT JOIN `contact` AS `pcontact` ON `contact`.`uri-id` = `pcontact`.`uri-id` WHERE `pcontact`.`id` IS NULL");
		while ($contact = DBA::fetch($contacts)) {
			Contact::selectAccountUserById($contact['id'], ['id']);
			$added++;
		}
		DBA::close($contacts);

		if ($added == 0) {
			DI::logger()->info('No public contacts have been added');
		} else {
			DI::logger()->info('Missing public contacts have been added', ['added' => $added]);
		}

		$added = 0;
		DI::logger()->info('Add missing account-user entries');
		$contacts = DBA::p("SELECT `contact`.`id`, `contact`.`uid`, `contact`.`uri-id`, `contact`.`url` FROM `contact` LEFT JOIN `account-user` ON `contact`.`id` = `account-user`.`id` WHERE `contact`.`id` > ? AND `account-user`.`id` IS NULL", 0);
		while ($contact = DBA::fetch($contacts)) {
			Contact::setAccountUser($contact['id'], $contact['uid'], $contact['uri-id'], $contact['url']);
			$added++;
		}
		DBA::close($contacts);

		if ($added == 0) {
			DI::logger()->info('No account-user entries have been added');
		} else {
			DI::logger()->info('Missing account-user entries have been added', ['added' => $added]);
		}
	}
}
