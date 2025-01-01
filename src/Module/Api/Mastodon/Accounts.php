<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Api\Mastodon;

use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Module\BaseApi;

/**
 * @see https://docs.joinmastodon.org/methods/accounts/
 */
class Accounts extends BaseApi
{
	/**
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	protected function rawContent(array $request = [])
	{
		$uid = self::getCurrentUserID();

		if (empty($this->parameters['id']) && empty($this->parameters['name'])) {
			$this->logAndJsonError(422, $this->errorFactory->UnprocessableEntity());
		}

		if (!empty($this->parameters['id'])) {
			$id = $this->parameters['id'];
			if (!DBA::exists('contact', ['id' => $id, 'uid' => 0])) {
				$this->logAndJsonError(404, $this->errorFactory->RecordNotFound());
			}
		} else {
			$contact = Contact::selectFirst(['id'], ['nick' => $this->parameters['name'], 'uid' => 0]);
			if (!empty($contact['id'])) {
				$id = $contact['id'];
			} elseif (!($id = Contact::getIdForURL($this->parameters['name'], 0, false))) {
				$this->logAndJsonError(404, $this->errorFactory->RecordNotFound());
			}
		}

		$account = DI::mstdnAccount()->createFromContactId($id, $uid);
		$this->jsonExit($account);
	}
}
