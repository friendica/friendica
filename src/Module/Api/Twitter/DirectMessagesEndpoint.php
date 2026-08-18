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
	/** @var Database */
	private $dba;

	/** @var DirectMessage */
	private $directMessage;

	public function __construct(DirectMessage $directMessage, Database $dba, \Friendica\Factory\Api\Mastodon\Error $errorFactory, AppHelper $appHelper, L10n $l10n, BaseURL $baseUrl, Arguments $args, LoggerInterface $logger, Profiler $profiler, ApiResponse $response, array $server, array $parameters = [])
	{
		parent::__construct($errorFactory, $appHelper, $l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->dba           = $dba;
		$this->directMessage = $directMessage;
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
			// Bug fix: when the caller has no established contact relationship with the
			// resolved account, $ucid is falsy and this used to skip adding a contact-id
			// filter entirely -- silently widening the query from "messages with this
			// person" to "all of the caller's messages with everyone". -1 is an
			// unmatchable sentinel so a non-contact search correctly yields zero results
			// instead of the caller's whole mailbox.
			$ucid      = Contact::getUserContactId($cid, $uid);
			$condition = DBA::mergeConditions($condition, ["`contact-id` = ?", $ucid ?: -1]);
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
