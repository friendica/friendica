<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Api\Friendica\Photoalbum;

use Friendica\Model\Photo;
use Friendica\Module\BaseApi;

/**
 * api/friendica/photoalbum
 *
 * @package  Friendica\Module\Api\Friendica\Photoalbum
 */
class Index extends BaseApi
{
	protected function rawContent(array $request = [])
	{
		$this->checkAllowedScope(self::SCOPE_READ);
		$uid = self::getCurrentUserID();

		$albums = Photo::getAlbumsForAPI($uid);

		$items = [];
		foreach ($albums as $album) {
			$items[] = [
				'name'    => $album['album'],
				'created' => $album['created'],
				'count'   => $album['total'],
			];
		}

		$this->response->addFormattedContent('albums', ['albums' => $items], $this->parameters['extension'] ?? null);
	}
}
