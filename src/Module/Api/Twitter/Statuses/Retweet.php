<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Api\Twitter\Statuses;

use Friendica\Core\Protocol;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Module\BaseApi;
use Friendica\Network\HTTPException\BadRequestException;
use Friendica\Network\HTTPException\ForbiddenException;
use Friendica\Network\HTTPException\InternalServerErrorException;
use Friendica\Protocol\Diaspora;

/**
 * Repeats a status.
 *
 * @see https://developer.twitter.com/en/docs/tweets/post-and-engage/api-reference/post-statuses-retweet-id
 */
class Retweet extends BaseApi
{
	protected function post(array $request = [])
	{
		$this->checkAllowedScope(self::SCOPE_WRITE);
		$uid = self::getCurrentUserID();

		$id = $this->getRequestValue($request, 'id', 0);
		$id = $this->getRequestValue($this->parameters, 'id', $id);
		if (empty($id)) {
			throw new BadRequestException('An id is missing.');
		}

		$fields = ['id', 'uri-id', 'network', 'body', 'title', 'author-name', 'author-link', 'author-avatar', 'guid', 'created', 'plink'];
		$item   = Post::selectFirst($fields, ['uri-id' => $id, 'uid' => [0, $uid], 'private' => [Item::PUBLIC, Item::UNLISTED]], ['order' => ['uid' => true]]);

		if (DBA::isResult($item) && !empty($item['body'])) {
			if (in_array($item['network'], [Protocol::ACTIVITYPUB, Protocol::DFRN, Protocol::ATPROTO, Protocol::TUMBLR, Protocol::TWITTER])) {
				if (!Item::performActivity($id, 'announce', $uid)) {
					throw new InternalServerErrorException();
				}

				$item_id = $item['id'];
			} else {
				$item_id = Diaspora::performReshare($item['uri-id'], $uid);
			}
		} else {
			throw new ForbiddenException();
		}

		$status_info = DI::twitterStatus()->createFromItemId($item_id, $uid)->toArray();

		DI::apiResponse()->addFormattedContent('statuses', ['status' => $status_info], $this->parameters['extension'] ?? null);
	}
}
