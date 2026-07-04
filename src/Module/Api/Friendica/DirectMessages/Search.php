<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Api\Friendica\DirectMessages;

use Friendica\App\Arguments;
use Friendica\App\BaseURL;
use Friendica\AppHelper;
use Friendica\Core\L10n;
use Friendica\Database\Database;
use Friendica\Database\DBA;
use Friendica\Factory\Api\Twitter\DirectMessage;
use Friendica\Module\Api\ApiResponse;
use Friendica\Module\BaseApi;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

/**
 * search for direct_messages containing a searchstring through api
 *
 * API endpoint: api/friendica/direct_messages_search
 */
class Search extends BaseApi
{
	public function __construct(private readonly DirectMessage $directMessage, private readonly Database $dba, \Friendica\Factory\Api\Mastodon\Error $errorFactory, AppHelper $appHelper, L10n $l10n, BaseURL $baseUrl, Arguments $args, LoggerInterface $logger, Profiler $profiler, ApiResponse $response, array $server, array $parameters = [])
	{
		parent::__construct($errorFactory, $appHelper, $l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);
	}

	protected function rawContent(array $request = [])
	{
		$this->checkAllowedScope(self::SCOPE_READ);
		$uid = self::getCurrentUserID();

		$request = $this->getRequest([
			'searchstring' => '',
		], $request);

		// error if no searchstring specified
		if ($request['searchstring'] == '') {
			$answer = ['result' => 'error', 'message' => 'searchstring not specified'];
			$this->response->addFormattedContent('direct_message_search', ['$result' => $answer], $this->parameters['extension'] ?? null);
			return;
		}

		// get data for the specified searchstring
		$mails = $this->dba->selectToArray('mail', ['id'], ["`uid` = ? AND `body` LIKE ?", $uid, '%' . $request['searchstring'] . '%'], ['order' => ['id' => true]]);

		// message if nothing was found
		if (!DBA::isResult($mails)) {
			$success = ['success' => false, 'search_results' => 'nothing found'];
		} else {
			$ret = [];
			foreach ($mails as $mail) {
				$ret[] = $this->directMessage->createFromMailId($mail['id'], $uid, $this->getRequestValue($request, 'getText', ''));
			}
			$success = ['success' => true, 'search_results' => $ret];
		}

		$this->response->addFormattedContent('direct_message_search', ['$result' => $success], $this->parameters['extension'] ?? null);
	}
}
