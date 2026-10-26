<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Item;

use Friendica\BaseModule;
use Friendica\DI;
use Friendica\Model\Post;
use Friendica\Network\HTTPException;

/**
 * Vote on a poll
 */
class Vote extends BaseModule
{
	protected function post(array $request = [])
	{
		if (!DI::userSession()->isAuthenticated()) {
			throw new HTTPException\ForbiddenException(DI::l10n()->t('Access denied.'));
		}

		$uid  = DI::userSession()->getLocalUserId();
		$item = Post::selectFirstForUser($uid, ['uri-id'], ['uid' => [0, $uid], 'id' => $this->parameters['id'] ?? 0]);
		if (empty($item)) {
			throw new HTTPException\NotFoundException();
		}

		Post\QuestionVote::vote($item['uri-id'], $uid, (array) ($request['choices'] ?? []));

		$this->earlyJsonExit(['status' => 'ok']);
	}
}
