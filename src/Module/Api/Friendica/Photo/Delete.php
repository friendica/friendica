<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Api\Friendica\Photo;

use Friendica\App\Arguments;
use Friendica\App\BaseURL;
use Friendica\AppHelper;
use Friendica\Core\L10n;
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
 * API endpoint: /api/friendica/photo/delete
 */
class Delete extends BaseApi
{
	public function __construct(private readonly Deletion $deletion, \Friendica\Factory\Api\Mastodon\Error $errorFactory, AppHelper $appHelper, L10n $l10n, BaseURL $baseUrl, Arguments $args, LoggerInterface $logger, Profiler $profiler, ApiResponse $response, array $server, array $parameters = [])
	{
		parent::__construct($errorFactory, $appHelper, $l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);
	}

	protected function post(array $request = [])
	{
		$uid = self::getCurrentUserID();

		$request = $this->getRequest([
			'photo_id' => '', // Photo id
		], $request);

		// do several checks on input parameters
		// we do not allow calls without photo id
		if (empty($request['photo_id'])) {
			throw new BadRequestException("no photo_id specified");
		}

		// check if photo is existing in database
		if (!Photo::exists(['resource-id' => $request['photo_id'], 'uid' => $uid])) {
			throw new BadRequestException("photo not available");
		}

		// now we can perform on the deletion of the photo
		$result = Photo::delete(['uid' => $uid, 'resource-id' => $request['photo_id']]);

		// return success of deletion or error message
		if ($result) {
			// function for setting the items to "deleted = 1" which ensures that comments, likes etc. are not shown anymore
			// to the user and the contacts of the users (drop_items() do all the necessary magic to avoid orphans in database and federate deletion)
			$condition = ['uid' => $uid, 'resource-id' => $request['photo_id'], 'post-type' => Item::PT_IMAGE, 'origin' => true];
			$this->deletion->deleteForUser($condition, $uid);
			Photo::clearAlbumCache($uid);
			$result = ['result' => 'deleted', 'message' => 'photo with id `' . $request['photo_id'] . '` has been deleted from server.'];
			$this->response->addFormattedContent('photo_delete', ['$result' => $result], $this->parameters['extension'] ?? null);
		} else {
			throw new InternalServerErrorException("unknown error on deleting photo from database table");
		}
	}
}
