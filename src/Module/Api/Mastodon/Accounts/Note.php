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
class Note extends BaseApi
{
	protected function post(array $request = [])
	{
		$this->checkAllowedScope(self::SCOPE_WRITE);
		$uid = self::getCurrentUserID();

		if (empty($this->parameters['id'])) {
			$this->logAndJsonError(422, $this->errorFactory->UnprocessableEntity());
		}

		$request = $this->getRequest([
			'comment' => '',
		], $request);

		$ucid = Contact::getUserContactId($this->parameters['id'], $uid);
		if (!$ucid) {
			$this->logAndJsonError(404, $this->errorFactory->RecordNotFound());
		}

		Contact::update(['info' => $request['comment']], ['id' => $ucid]);

		$this->jsonExit(DI::mstdnRelationship()->createFromContactId($this->parameters['id'], $uid)->toArray());
	}
}
