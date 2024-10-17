<?php
/**
 * Copyright (C) 2010-2024, the Friendica project
 * SPDX-FileCopyrightText: 2010-2024 the Friendica project
 *
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See update_profile.php for documentation
 *
 */

use Friendica\App;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Post;
use Friendica\Model\Contact;

function update_contact_content(App $a)
{
	if (!empty(DI::args()->get(1)) && !empty($_GET['force'])) {
		$contact = DBA::selectFirst('account-user-view', ['pid', 'deleted'], ['id' => DI::args()->get(1)]);
		if (DBA::isResult($contact) && empty($contact['deleted'])) {
			DI::page()['aside'] = '';

			$item = Post::selectFirst(['parent'], ['id' => $_GET['item'] ?? 0]);

			$text = Contact::getThreadsFromId($contact['pid'], DI::userSession()->getLocalUserId(), true, $item['parent'] ?? 0, $_GET['last_received'] ?? '');
		}
	}

	System::htmlUpdateExit($text ?? '');
}
