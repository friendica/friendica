<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Api\Friendica\Photo;

use Friendica\App\Arguments;
use Friendica\App\BaseURL;
use Friendica\AppHelper;
use Friendica\Core\ACL;
use Friendica\Core\L10n;
use Friendica\Factory\Api\Friendica\Photo as FriendicaPhoto;
use Friendica\Module\BaseApi;
use Friendica\Model\Photo;
use Friendica\Module\Api\ApiResponse;
use Friendica\Network\HTTPException;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

/**
 * API endpoint: /api/friendica/photo/create
 */
class Create extends BaseApi
{
	public function __construct(private readonly FriendicaPhoto $friendicaPhoto, \Friendica\Factory\Api\Mastodon\Error $errorFactory, AppHelper $appHelper, L10n $l10n, BaseURL $baseUrl, Arguments $args, LoggerInterface $logger, Profiler $profiler, ApiResponse $response, array $server, array $parameters = [])
	{
		parent::__construct($errorFactory, $appHelper, $l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);
	}

	protected function post(array $request = [])
	{
		$this->checkAllowedScope(BaseApi::SCOPE_WRITE);
		$uid  = BaseApi::getCurrentUserID();
		$type = $this->getRequestValue($this->parameters, 'extension', 'json');

		// input params
		$desc      = $this->getRequestValue($request, 'desc', '');
		$album     = $this->getRequestValue($request, 'album');
		$allow_cid = $this->getRequestValue($request, 'allow_cid');
		$deny_cid  = $this->getRequestValue($request, 'deny_cid');
		$allow_gid = $this->getRequestValue($request, 'allow_gid');
		$deny_gid  = $this->getRequestValue($request, 'deny_gid');

		// do several checks on input parameters
		// we do not allow calls without album string
		if ($album === null) {
			throw new HTTPException\BadRequestException('no album name specified');
		}

		// error if no media posted in create-mode
		if (empty($_FILES['media'])) {
			// Output error
			throw new HTTPException\BadRequestException('no media data submitted');
		}

		// checks on acl strings provided by clients
		$acl_input_error = false;
		$acl_input_error |= !ACL::isValidContact($allow_cid, $uid);
		$acl_input_error |= !ACL::isValidContact($deny_cid, $uid);
		$acl_input_error |= !ACL::isValidCircle($allow_gid, $uid);
		$acl_input_error |= !ACL::isValidCircle($deny_gid, $uid);
		if ($acl_input_error) {
			throw new HTTPException\BadRequestException('acl data invalid');
		}
		// now let's upload the new media in create-mode
		$photo = Photo::upload($uid, $_FILES['media'], $album, trim((string) $allow_cid), trim((string) $allow_gid), trim((string) $deny_cid), trim((string) $deny_gid), $desc);

		// return success of updating or error message
		if (!empty($photo)) {
			Photo::clearAlbumCache($uid);
			$data = ['photo' => $this->friendicaPhoto->createFromId($photo['resource_id'], null, $uid, $type)];
			$this->response->addFormattedContent('photo_create', $data, $this->parameters['extension'] ?? null);
		} else {
			throw new HTTPException\InternalServerErrorException('unknown error - uploading photo failed, see Friendica log for more information');
		}
	}
}
