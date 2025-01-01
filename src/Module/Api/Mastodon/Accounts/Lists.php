<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Api\Mastodon\Accounts;

use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Module\BaseApi;

/**
 * @see https://docs.joinmastodon.org/methods/accounts/
 */
class Lists extends BaseApi
{
	/**
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	protected function rawContent(array $request = [])
	{
		$this->checkAllowedScope(self::SCOPE_READ);
		$uid = self::getCurrentUserID();

		if (empty($this->parameters['id'])) {
			$this->logAndJsonError(422, $this->errorFactory->UnprocessableEntity());
		}

		$id = $this->parameters['id'];
		if (!DBA::exists('contact', ['id' => $id, 'uid' => 0])) {
			$this->logAndJsonError(404, $this->errorFactory->RecordNotFound());
		}

		$lists = [];

		$ucid = Contact::getUserContactId($id, $uid);
		if ($ucid) {
			$circles = DBA::select('group_member', ['gid'], ['contact-id' => $ucid]);
			while ($circle = DBA::fetch($circles)) {
				$lists[] = DI::mstdnList()->createFromCircleId($circle['gid']);
			}
			DBA::close($circles);
		}

		$this->jsonExit($lists);
	}
}
