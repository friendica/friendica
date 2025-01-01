<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Api\Friendica\Statuses;

use Friendica\DI;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Module\BaseApi;
use Friendica\Protocol\Activity;

/**
 * @see https://docs.joinmastodon.org/methods/statuses/
 */
class DislikedBy extends BaseApi
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

		$id = $this->parameters['id'];
		if (!Post::exists(['uri-id' => $id, 'uid' => [0, $uid]])) {
			$this->logAndJsonError(404, $this->errorFactory->RecordNotFound());
		}

		$activities = Post::selectPosts(['author-id'], ['thr-parent-id' => $id, 'gravity' => Item::GRAVITY_ACTIVITY, 'verb' => Activity::DISLIKE, 'deleted' => false]);

		$accounts = [];

		while ($activity = Post::fetch($activities)) {
			$accounts[] = DI::mstdnAccount()->createFromContactId($activity['author-id'], $uid);
		}

		$this->jsonExit($accounts);
	}
}
