<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Api\Mastodon\Conversations;

use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Module\BaseApi;
use Friendica\Network\HTTPException\NotFoundException;

/**
 * @see https://docs.joinmastodon.org/methods/timelines/conversations/
 */
class Read extends BaseApi
{
	protected function post(array $request = [])
	{
		$this->checkAllowedScope(self::SCOPE_WRITE);
		$uid = self::getCurrentUserID();

		if (empty($this->parameters['id'])) {
			$this->logAndJsonError(422, $this->errorFactory->UnprocessableEntity());
		}

		$mail = DBA::selectFirst('mail', ['parent-uri'], ['id' => $this->parameters['id'], 'uid' => $uid]);
		if (!$mail) {
			$this->logAndJsonError(404, $this->errorFactory->RecordNotFound());
		}

		DBA::update('mail', ['seen' => true], ['parent-uri' => $mail['parent-uri'], 'uid' => $uid]);

		try {
			$this->earlyJsonExit(DI::mstdnConversation()->createFromMailId($uid, (int) $this->parameters['id'])->toArray());
		} catch (NotFoundException) {
			$this->logAndJsonError(404, $this->errorFactory->RecordNotFound());
		}
	}
}
