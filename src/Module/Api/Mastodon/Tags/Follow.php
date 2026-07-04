<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Api\Mastodon\Tags;

use Friendica\Database\DBA;
use Friendica\Module\BaseApi;

/**
 * @see https://docs.joinmastodon.org/methods/tags/#follow
 */
class Follow extends BaseApi
{
	protected function post(array $request = [])
	{
		$this->checkAllowedScope(self::SCOPE_WRITE);
		$uid = self::getCurrentUserID();

		if (empty($this->parameters['hashtag'])) {
			$this->logAndJsonError(422, $this->errorFactory->UnprocessableEntity());
		}

		$fields = ['uid' => $uid, 'term' => '#' . ltrim((string) $this->parameters['hashtag'], '#')];
		if (!DBA::exists('search', $fields)) {
			DBA::insert('search', $fields);
		}

		$hashtag = new \Friendica\Object\Api\Mastodon\Tag($this->baseUrl, ['name' => ltrim((string) $this->parameters['hashtag'])], [], true);
		$this->jsonExit($hashtag->toArray());
	}
}
