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
 * @see https://docs.joinmastodon.org/methods/statuses/#source
 */
class Source extends BaseApi
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

		if (!Post::exists(['uri-id' => $id, 'uid' => [0, $uid]])) {
			throw new HTTPException\NotFoundException('Item with URI ID ' . $id . ' not found' . ($uid ? ' for user ' . $uid : '.'));
		}

		$source = DI::mstdnStatusSource()->createFromUriId($id, $uid);

		$this->jsonExit($source->toArray());
	}
}
