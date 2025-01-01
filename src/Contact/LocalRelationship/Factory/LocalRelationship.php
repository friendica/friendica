<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Contact\LocalRelationship\Factory;

use Friendica\BaseFactory;
use Friendica\Capabilities\ICanCreateFromTableRow;
use Friendica\Contact\LocalRelationship\Entity;
use Friendica\Core\Protocol;
use Friendica\Model\Contact;

class LocalRelationship extends BaseFactory implements ICanCreateFromTableRow
{
	/**
	 * @inheritDoc
	 */
	public function createFromTableRow(array $row): Entity\LocalRelationship
	{
		return new Entity\LocalRelationship(
			$row['uid'],
			$row['cid'],
			$row['blocked']                   ?? false,
			$row['ignored']                   ?? false,
			$row['collapsed']                 ?? false,
			$row['hidden']                    ?? false,
			$row['pending']                   ?? false,
			$row['rel']                       ?? Contact::NOTHING,
			$row['info']                      ?? '',
			$row['notify_new_posts']          ?? false,
			$row['remote_self']               ?? Entity\LocalRelationship::MIRROR_DEACTIVATED,
			$row['fetch_further_information'] ?? Entity\LocalRelationship::FFI_NONE,
			$row['ffi_keyword_denylist']      ?? '',
			$row['hub-verify']                ?? '',
			$row['protocol']                  ?? Protocol::PHANTOM,
			$row['rating']                    ?? null,
			$row['priority']                  ?? 0
		);
	}
}
