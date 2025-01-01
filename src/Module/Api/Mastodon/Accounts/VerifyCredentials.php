<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Api\Mastodon\Accounts;

use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\User;
use Friendica\Module\BaseApi;

/**
 * @see https://docs.joinmastodon.org/methods/accounts/
 */
class VerifyCredentials extends BaseApi
{
	/**
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	protected function rawContent(array $request = [])
	{
		$this->checkAllowedScope(self::SCOPE_READ);
		$uid = self::getCurrentUserID();

		$self = User::getOwnerDataById($uid);
		if (empty($self)) {
			DI::mstdnError()->InternalError();
		}

		$ucid = Contact::getUserContactId($self['id'], $uid);
		if (!$ucid) {
			DI::mstdnError()->InternalError();
		}

		// @todo Support the source property,
		$account = DI::mstdnAccount()->createFromContactId($ucid, $uid);
		$this->response->addJsonContent($account->toArray());
	}
}
