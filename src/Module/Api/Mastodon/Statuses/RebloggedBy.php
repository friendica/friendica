<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Api\Mastodon\Statuses;

use Friendica\DI;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Module\BaseApi;
use Friendica\Protocol\Activity;

/**
 * @see https://docs.joinmastodon.org/methods/statuses/
 */
class RebloggedBy extends BaseApi
{
	/**
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	protected function rawContent(array $request = [])
	{
		$uid = self::getCurrentUserID();

		if (empty($this->parameters['id'])) {
			$this->logAndJsonError(422, $this->errorFactory->UnprocessableEntity());
		}

		if (!$post = Post::selectOriginal(['uri-id'], ['uri-id' => $this->parameters['id'], 'uid' => [0, $uid]])) {
			$this->logAndJsonError(404, $this->errorFactory->RecordNotFound());
		}

		$activities = Post::selectPosts(['author-id'], ['thr-parent-id' => $post['uri-id'], 'gravity' => Item::GRAVITY_ACTIVITY, 'verb' => Activity::ANNOUNCE]);

		$accounts = [];

		while ($activity = Post::fetch($activities)) {
			$accounts[] = DI::mstdnAccount()->createFromContactId($activity['author-id'], $uid);
		}

		$this->jsonExit($accounts);
	}
}
