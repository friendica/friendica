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
use Friendica\Module\Api\ApiResponse;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

/**
 * Returns all circles the user owns.
 *
 * @see https://developer.twitter.com/en/docs/accounts-and-users/create-manage-lists/api-reference/get-lists-ownerships
 */
class Ownership extends BaseApi
{
	/** @var FriendicaCircle */
	private $friendicaCircle;

	/** @var Database */
	private $dba;

	public function __construct(Database $dba, FriendicaCircle $friendicaCircle, \Friendica\Factory\Api\Mastodon\Error $errorFactory, AppHelper $appHelper, L10n $l10n, BaseURL $baseUrl, Arguments $args, LoggerInterface $logger, Profiler $profiler, ApiResponse $response, array $server, array $parameters = [])
	{
		parent::__construct($errorFactory, $appHelper, $l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->dba             = $dba;
		$this->friendicaCircle = $friendicaCircle;
	}
	protected function rawContent(array $request = [])
	{
		$this->checkAllowedScope(BaseApi::SCOPE_READ);
		$uid = BaseApi::getCurrentUserID();

		$circles = $this->dba->selectToArray('group', [], ['deleted' => false, 'uid' => $uid, 'cid' => null]);

		// loop through all circles
		$lists = [];

		foreach ($circles as $circle) {
			$lists[] = $this->friendicaCircle->createFromId($circle['id']);
		}

		$this->response->addFormattedContent('statuses', ['lists' => ['lists' => $lists]], $this->parameters['extension'] ?? null, Contact::getPublicIdByUserId($uid));
	}
}
