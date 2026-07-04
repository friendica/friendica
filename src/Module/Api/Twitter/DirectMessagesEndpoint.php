<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Api\Twitter;

use Friendica\App\Arguments;
use Friendica\App\BaseURL;
use Friendica\AppHelper;
use Friendica\Core\L10n;
use Friendica\Database\Database;
use Friendica\Database\DBA;
use Friendica\Factory\Api\Twitter\DirectMessage;
use Friendica\Model\Contact;
use Friendica\Module\Api\ApiResponse;
use Friendica\Module\BaseApi;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

abstract class DirectMessagesEndpoint extends BaseApi
{
	public function __construct(private readonly DirectMessage $directMessage, private readonly Database $dba, \Friendica\Factory\Api\Mastodon\Error $errorFactory, AppHelper $appHelper, L10n $l10n, BaseURL $baseUrl, Arguments $args, LoggerInterface $logger, Profiler $profiler, ApiResponse $response, array $server, array $parameters = [])
	{
		parent::__construct($errorFactory, $appHelper, $l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);
	}

	/**
	 * Handles a direct messages endpoint with the given condition
	 *
	 * @param array $request
	 * @param int   $uid
	 * @param array $condition
	 *
	 * @return void
	 */
	protected function getMessages(array $request, int $uid, array $condition)
	{
		// params
		$count    = $this->getRequestValue($request, 'count', 20, 1, 100);
		$page     = $this->getRequestValue($request, 'page', 1, 1);
		$since_id = $this->getRequestValue($request, 'since_id', 0, 0);
		$max_id   = $this->getRequestValue($request, 'max_id', 0, 0);
		$min_id   = $this->getRequestValue($request, 'min_id', 0, 0);
		$verbose  = $this->getRequestValue($request, 'friendica_verbose', false);

		// pagination
		$start = max(0, ($page - 1) * $count);

		$params = ['order' => ['id' => true], 'limit' => [$start, $count]];

		if (!empty($max_id)) {
			$condition = DBA::mergeConditions($condition, ["`id` < ?", $max_id]);
		}

		if (!empty($since_id)) {
			$condition = DBA::mergeConditions($condition, ["`id` > ?", $since_id]);
		}

		if (!empty($min_id)) {
			$condition = DBA::mergeConditions($condition, ["`id` > ?", $min_id]);

			$params['order'] = ['id'];
		}

		$cid = BaseApi::getContactIDForSearchterm($this->getRequestValue($request, 'screen_name', ''), $this->getRequestValue($request, 'profileurl', ''), $this->getRequestValue($request, 'user_id', 0), 0);
		if (!empty($cid)) {
			$ucid = Contact::getUserContactId($cid, $uid);
			if ($ucid) {
				$condition = DBA::mergeConditions($condition, ["`contact-id` = ?", $ucid]);
			}
		}

		$condition = DBA::mergeConditions($condition, ["`uid` = ?", $uid]);

		$mails = $this->dba->selectToArray('mail', ['id'], $condition, $params);
		if ($verbose && !DBA::isResult($mails)) {
			$answer = ['result' => 'error', 'message' => 'no mails available'];
			$this->response->addFormattedContent('direct-messages', ['direct_message' => $answer], $this->parameters['extension'] ?? null);
			return;
		}

		$ids = array_column($mails, 'id');

		if (!empty($min_id)) {
			$ids = array_reverse($ids);
		}

		$ret = [];
		foreach ($ids as $id) {
			$ret[] = $this->directMessage->createFromMailId($id, $uid, $this->getRequestValue($request, 'getText', ''));
		}

		self::setLinkHeader();

		$this->response->addFormattedContent('direct-messages', ['direct_message' => $ret], $this->parameters['extension'] ?? null, Contact::getPublicIdByUserId($uid));
	}
}
