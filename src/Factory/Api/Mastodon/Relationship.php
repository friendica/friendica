<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Factory\Api\Mastodon;

use Exception;
use Friendica\Network\HTTPException;
use Friendica\Object\Api\Mastodon\Relationship as RelationshipEntity;
use Friendica\BaseFactory;
use Friendica\Model\Contact;

class Relationship extends BaseFactory
{
	/**
	 * @param int $contactId Contact ID (public or user contact)
	 * @param int $uid User ID
	 *
	 * @return RelationshipEntity
	 * @throws Exception
	 */
	public function createFromContactId(int $contactId, int $uid): RelationshipEntity
	{
		$cdata = Contact::getPublicAndUserContactID($contactId, $uid);
		$pcid  = !empty($cdata['public']) ? $cdata['public'] : $contactId;
		$cid   = !empty($cdata['user']) ? $cdata['user'] : $contactId;

		$contact = Contact::getById($cid);
		if (!$contact) {
			$this->logger->warning('Target contact not found', ['contactId' => $contactId, 'uid' => $uid, 'pcid' => $pcid, 'cid' => $cid]);
			throw new HTTPException\NotFoundException('Contact not found.');
		}

		return new RelationshipEntity(
			$pcid,
			$contact,
			Contact\User::isBlocked($cid, $uid),
			Contact\User::isIgnored($cid, $uid),
			Contact\User::isIsBlocked($cid, $uid),
		);
	}
}
