<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Api\Mastodon\Statuses;

use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Module\BaseApi;

/**
 * @see https://docs.joinmastodon.org/methods/statuses/
 */
class Unfavourite extends BaseApi
{
	protected function post(array $request = [])
	{
		$this->checkAllowedScope(self::SCOPE_WRITE);
		$uid = self::getCurrentUserID();

		if (empty($this->parameters['id'])) {
			$this->logAndJsonError(422, $this->errorFactory->UnprocessableEntity());
		}

		$item = Post::selectOriginalForUser($uid, ['id', 'uri-id'], ['uri-id' => $this->parameters['id'], 'uid' => [$uid, 0]]);
		if (!DBA::isResult($item)) {
			$this->logAndJsonError(404, $this->errorFactory->RecordNotFound());
		}

		Item::performActivity($item['id'], 'unlike', $uid);

		// @TODO Remove once mstdnStatus()->createFromUriId is fixed so that it returns posts not reshared posts if given an ID to an original post that has been reshared
		// Introduced in this PR: https://github.com/friendica/friendica/pull/13175
		// Issue tracking the behavior of createFromUriId: https://github.com/friendica/friendica/issues/13350
		$isReblog = $item['uri-id'] != $this->parameters['id'];

		$this->jsonExit(DI::mstdnStatus()->createFromUriId($this->parameters['id'], $uid, $isReblog)->toArray());
	}
}
