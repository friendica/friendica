<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Api\Mastodon;

use Friendica\Database\DBA;
use Friendica\Module\BaseApi;

/**
 * @see https://docs.joinmastodon.org/methods/tags/
 */
class Tags extends BaseApi
{
	/**
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	protected function rawContent(array $request = [])
	{
		$this->checkAllowedScope(self::SCOPE_READ);
		$uid = self::getCurrentUserID();

		if (empty($this->parameters['hashtag'])) {
			$this->logAndJsonError(422, $this->errorFactory->UnprocessableEntity());
		}

		$tag       = ltrim((string) $this->parameters['hashtag'], '#');
		$following = DBA::exists('search', ['uid' => $uid, 'term' => '#' . $tag]);

		$hashtag = new \Friendica\Object\Api\Mastodon\Tag($this->baseUrl, ['name' => $tag], [], $following);
		$this->jsonExit($hashtag->toArray());
	}
}
