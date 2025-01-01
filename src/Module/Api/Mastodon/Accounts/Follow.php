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
class Follow extends BaseApi
{
	protected function post(array $request = [])
	{
		$this->checkAllowedScope(self::SCOPE_FOLLOW);
		$uid = self::getCurrentUserID();

		if (empty($this->parameters['id'])) {
			$this->logAndJsonError(422, $this->errorFactory->UnprocessableEntity());
		}

		$request = $this->getRequest([
			'notify' => false, // Notify on new posts.
		], $request);

		$contact = Contact::getById($this->parameters['id'], ['url']);

		$result = Contact::createFromProbeForUser($uid, $contact['url']);

		if (!$result['success']) {
			DI::mstdnError()->UnprocessableEntity($result['message']);
		}

		Contact::update(['notify_new_posts' => $request['notify']], ['id' => $result['cid']]);

		$this->jsonExit(DI::mstdnRelationship()->createFromContactId($result['cid'], $uid)->toArray());
	}
}
