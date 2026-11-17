<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Api\Friendica\Photoalbum;

use Friendica\App\Arguments;
use Friendica\App\BaseURL;
use Friendica\AppHelper;
use Friendica\Core\L10n;
use Friendica\Database\DBA;
use Friendica\Model\Item;
use Friendica\Model\Photo;
use Friendica\Module\Api\ApiResponse;
use Friendica\Module\BaseApi;
use Friendica\Network\HTTPException\BadRequestException;
use Friendica\Network\HTTPException\InternalServerErrorException;
use Friendica\Post\Deletion;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

/**
 * API endpoint: /api/friendica/photoalbum/delete
 */
class Delete extends BaseApi
{
	public function __construct(private readonly Deletion $deletion, \Friendica\Factory\Api\Mastodon\Error $errorFactory, AppHelper $appHelper, L10n $l10n, BaseURL $baseUrl, Arguments $args, LoggerInterface $logger, Profiler $profiler, ApiResponse $response, array $server, array $parameters = [])
	{
		parent::__construct($errorFactory, $appHelper, $l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);
	}

	protected function post(array $request = [])
	{
		$this->checkAllowedScope(self::SCOPE_WRITE);
		$uid = self::getCurrentUserID();

		$request = $this->getRequest([
			'album' => '', // Album name
		], $request);

		// we do not allow calls without album string
		if (empty($request['album'])) {
			throw new BadRequestException("no albumname specified");
		}
		// check if album is existing

		$photos = DBA::selectToArray('photo', ['resource-id'], ['uid' => $uid, 'album' => $request['album']], ['group_by' => ['resource-id']]);
		if (!DBA::isResult($photos)) {
			throw new BadRequestException("album not available");
		}

		$resourceIds = array_column($photos, 'resource-id');

		// function for setting the items to "deleted = 1" which ensures that comments, likes etc. are not shown anymore
		// to the user and the contacts of the users (drop_items() performs the federation of the deletion to other networks
		$condition = ['uid' => $uid, 'resource-id' => $resourceIds, 'post-type' => Item::PT_IMAGE, 'origin' => true];
		$this->deletion->deleteForUser($condition, $uid);

		// now let's delete all photos from the album
		$result = Photo::delete(['uid' => $uid, 'album' => $request['album']]);

		// return success of deletion or error message
		if ($result) {
			Photo::clearAlbumCache($uid);
			$answer = ['result' => 'deleted', 'message' => 'album `' . $request['album'] . '` with all containing photos has been deleted.'];
			$this->response->addFormattedContent('photoalbum_delete', ['$result' => $answer], $this->parameters['extension'] ?? null);
		} else {
			throw new InternalServerErrorException("unknown error - deleting from database failed");
		}
	}
}
