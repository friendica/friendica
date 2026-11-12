<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Api\Mastodon\Statuses;

use Friendica\Content\Conversation\PostInteractions;
use Friendica\DI;
use Friendica\Model\Post;
use Friendica\Module\BaseApi;

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

		$request = $this->getRequest([
			'limit' => 40, // Maximum number of results to return. Defaults to 40, the maximum is 80.
		], $request);

		if (!$post = Post::selectOriginal(['uri-id'], ['uri-id' => $this->parameters['id'], 'uid' => [0, $uid]])) {
			$this->logAndJsonError(404, $this->errorFactory->RecordNotFound());
		}

		// The actors are selected like in the interaction lists of the web interface, so that visibility and blocked contacts are handled in the same way
		$actors = DI::postInteractions()->getActorIds(PostInteractions::RESHARES, $post['uri-id'], $uid, 0, min(max((int) $request['limit'], 1), 80));

		$accounts = [];

		foreach (array_keys($actors) as $authorId) {
			$accounts[] = DI::mstdnAccount()->createFromContactId($authorId, $uid);
		}

		$this->earlyJsonExit($accounts);
	}
}
