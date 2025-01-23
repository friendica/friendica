<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Worker\Contact;

use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;

/**
 * Removes a contact and all its related content
 */
class Remove extends RemoveContent
{
	public static function execute(int $id): bool
	{
		// Only delete if the contact is to be deleted
		$contact = DBA::selectFirst('contact', ['id', 'uid', 'url', 'nick', 'name'], ['deleted' => true, 'id' => $id]);
		if (!DBA::isResult($contact)) {
			return false;
		}

		if (!parent::execute($id)) {
			return false;
		}

		$ret = Contact::deleteById($id);
		DI::logger()->info('Deleted contact', ['id' => $id, 'result' => $ret]);

		return true;
	}
}
