<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Api\Mastodon\Statuses;

use Friendica\DI;
use Friendica\Model\Post;
use Friendica\Module\BaseApi;
use Friendica\Network\HTTPException;

/**
 * @see https://docs.joinmastodon.org/methods/statuses/
 */
class Card extends BaseApi
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
			throw new HTTPException\NotFoundException('Item with URI ID ' . $this->parameters['id'] . ' not found' . ($uid ? ' for user ' . $uid : '.'));
		}

		$card = DI::mstdnCard()->createFromUriId($post['uri-id']);

		$this->jsonExit($card->toArray());
	}
}
