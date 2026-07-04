<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Api\Mastodon\Tags;

use Friendica\Database\DBA;
use Friendica\Module\BaseApi;

/**
 * @see https://docs.joinmastodon.org/methods/tags/#unfollow
 */
class Unfollow extends BaseApi
{
	protected function post(array $request = [])
	{
		$this->checkAllowedScope(self::SCOPE_WRITE);
		$uid = self::getCurrentUserID();

		if (empty($this->parameters['hashtag'])) {
			$this->logAndJsonError(422, $this->errorFactory->UnprocessableEntity());
		}

		$term = ['uid' => $uid, 'term' => '#' . ltrim((string) $this->parameters['hashtag'], '#')];

		DBA::delete('search', $term);

		$hashtag = new \Friendica\Object\Api\Mastodon\Tag($this->baseUrl, ['name' => ltrim((string) $this->parameters['hashtag'])], [], false);
		$this->jsonExit($hashtag->toArray());
	}
}
