<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Api\Twitter\Lists;

use Friendica\App\Arguments;
use Friendica\App\BaseURL;
use Friendica\AppHelper;
use Friendica\Core\L10n;
use Friendica\Database\Database;
use Friendica\Factory\Api\Friendica\Circle as FriendicaCircle;
use Friendica\Module\BaseApi;
use Friendica\Model\Contact;
use Friendica\Model\Circle;
use Friendica\Module\Api\ApiResponse;
use Friendica\Network\HTTPException;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

/**
 * Update information about a circle.
 *
 * @see https://developer.twitter.com/en/docs/accounts-and-users/create-manage-lists/api-reference/post-lists-update
 */
class Update extends BaseApi
{
	public function __construct(private readonly Database $dba, private readonly FriendicaCircle $friendicaCircle, \Friendica\Factory\Api\Mastodon\Error $errorFactory, AppHelper $appHelper, L10n $l10n, BaseURL $baseUrl, Arguments $args, LoggerInterface $logger, Profiler $profiler, ApiResponse $response, array $server, array $parameters = [])
	{
		parent::__construct($errorFactory, $appHelper, $l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);
	}

	protected function post(array $request = [])
	{
		$this->checkAllowedScope(BaseApi::SCOPE_WRITE);
		$uid = BaseApi::getCurrentUserID();

		// params
		$gid  = $this->getRequestValue($request, 'list_id', 0);
		$name = $this->getRequestValue($request, 'name', '');

		// error if no gid specified
		if ($gid == 0) {
			throw new HTTPException\BadRequestException('gid not specified');
		}

		// get data of the specified circle id
		$circle = $this->dba->selectFirst('group', [], ['uid' => $uid, 'id' => $gid]);
		// error message if specified gid is not in database
		if (!$circle) {
			throw new HTTPException\BadRequestException('gid not available');
		}

		if (Circle::update($gid, $name)) {
			$list = $this->friendicaCircle->createFromId($gid);

			$this->response->addFormattedContent('statuses', ['lists' => ['lists' => $list]], $this->parameters['extension'] ?? null, Contact::getPublicIdByUserId($uid));
		}
	}
}
