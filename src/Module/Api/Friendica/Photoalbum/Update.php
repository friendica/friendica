<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Api\Friendica\Photoalbum;

use Friendica\Model\Photo;
use Friendica\Module\BaseApi;
use Friendica\Network\HTTPException\BadRequestException;
use Friendica\Network\HTTPException\InternalServerErrorException;

/**
 * API endpoint: /api/friendica/photoalbum/update
 */
class Update extends BaseApi
{
	protected function post(array $request = [])
	{
		$this->checkAllowedScope(self::SCOPE_WRITE);
		$uid = self::getCurrentUserID();

		$request = $this->getRequest([
			'album'     => '', // Current album name
			'album_new' => '', // New album name
		], $request);

		// we do not allow calls without album string
		if (empty($request['album'])) {
			throw new BadRequestException("no albumname specified");
		}
		if (empty($request['album_new'])) {
			throw new BadRequestException("no new albumname specified");
		}
		// check if album is existing
		if (!Photo::exists(['uid' => $uid, 'album' => $request['album']])) {
			throw new BadRequestException("album not available");
		}
		// now let's update all photos to the albumname
		$result = Photo::update(['album' => $request['album_new']], ['uid' => $uid, 'album' => $request['album']]);

		// return success of updating or error message
		if ($result) {
			Photo::clearAlbumCache($uid);
			$answer = ['result' => 'updated', 'message' => 'album `' . $request['album'] . '` with all containing photos has been renamed to `' . $request['album_new'] . '`.'];
			$this->response->addFormattedContent('photoalbum_update', ['$result' => $answer], $this->parameters['extension'] ?? null);
		} else {
			throw new InternalServerErrorException("unknown error - updating in database failed");
		}
	}
}
