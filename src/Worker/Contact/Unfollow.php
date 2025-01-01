<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Worker\Contact;

use Friendica\Core\Protocol;
use Friendica\Core\Worker;
use Friendica\Model\Contact;
use Friendica\Model\User;

class Unfollow
{
	const WORKER_DEFER_LIMIT = 5;

	/**
	 * Issue asynchronous unfollow message to remote servers.
	 * The local relationship has already been updated, so we can't use the user-specific contact.
	 *
	 * @param int $cid Target public contact (uid = 0) id
	 * @param int $uid Source local user id
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function execute(int $cid, int $uid)
	{
		$ucid = Contact::getUserContactId($cid, $uid);
		if (!$ucid) {
			return;
		}

		$contact = Contact::getById($ucid);
		if (empty($contact)) {
			return;
		}

		$owner = User::getOwnerDataById($uid, false);
		if (empty($owner)) {
			return;
		}

		$result = Protocol::unfollow($contact, $owner);
		if ($result === false) {
			if (!Worker::defer(self::WORKER_DEFER_LIMIT)) {
				Contact::removeSharer($contact);
			}
		} else {
			Contact::removeSharer($contact);
		}
	}
}
