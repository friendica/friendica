<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Api\Mastodon\Accounts;

use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Module\BaseApi;

/**
 * @see https://docs.joinmastodon.org/methods/accounts/
 */
class Unfollow extends BaseApi
{
	protected function post(array $request = [])
	{
		$this->checkAllowedScope(self::SCOPE_FOLLOW);
		$uid = self::getCurrentUserID();

		if (empty($this->parameters['id'])) {
			$this->logAndJsonError(422, $this->errorFactory->UnprocessableEntity());
		}

		$ucid = Contact::getUserContactId($this->parameters['id'], $uid);
		if (!$ucid) {
			$this->logAndJsonError(404, $this->errorFactory->RecordNotFound());
		}

		$contact = Contact::getById($ucid);

		Contact::unfollow($contact);

		$this->jsonExit(DI::mstdnRelationship()->createFromContactId($this->parameters['id'], $uid)->toArray());
	}
}
