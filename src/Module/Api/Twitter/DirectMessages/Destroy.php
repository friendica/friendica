<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Api\Twitter\DirectMessages;

use Friendica\App\Arguments;
use Friendica\App\BaseURL;
use Friendica\AppHelper;
use Friendica\Core\L10n;
use Friendica\Database\Database;
use Friendica\Database\DBA;
use Friendica\Module\Api\ApiResponse;
use Friendica\Module\BaseApi;
use Friendica\Network\HTTPException\BadRequestException;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

/**
 * delete a direct_message from mail table through api
 *
 * @see   https://developer.twitter.com/en/docs/direct-messages/sending-and-receiving/api-reference/delete-message
 */
class Destroy extends BaseApi
{
	public function __construct(private readonly Database $dba, \Friendica\Factory\Api\Mastodon\Error $errorFactory, AppHelper $appHelper, L10n $l10n, BaseURL $baseUrl, Arguments $args, LoggerInterface $logger, Profiler $profiler, ApiResponse $response, array $server, array $parameters = [])
	{
		parent::__construct($errorFactory, $appHelper, $l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);
	}

	protected function post(array $request = [])
	{
		$this->checkAllowedScope(BaseApi::SCOPE_WRITE);
		$uid = BaseApi::getCurrentUserID();

		$id = $this->getRequestValue($request, 'id', 0);
		$id = $this->getRequestValue($this->parameters, 'id', $id);

		$verbose   = $this->getRequestValue($request, 'friendica_verbose', false);
		$parenturi = $this->getRequestValue($request, 'friendica_parenturi', '');

		// error if no id or parenturi specified (for clients posting parent-uri as well)
		if ($verbose && $id == 0 && $parenturi == "") {
			$answer = ['result' => 'error', 'message' => 'message id or parenturi not specified'];
			$this->response->addFormattedContent('direct_messages_delete', ['direct_messages_delete' => $answer], $this->parameters['extension'] ?? null);
			return;
		}

		// add parent-uri to sql command if specified by calling app
		$sql_extra = ($parenturi != "" ? " AND `parent-uri` = '" . DBA::escape($parenturi) . "'" : "");

		// error message if specified id is not in database
		if (!$this->dba->exists('mail', ["`uid` = ? AND `id` = ? " . $sql_extra, $uid, $id])) {
			if ($verbose) {
				$answer = ['result' => 'error', 'message' => 'message id not in database'];
				$this->response->addFormattedContent('direct_messages_delete', ['direct_messages_delete' => $answer], $this->parameters['extension'] ?? null);
				return;
			}
			throw new BadRequestException('message id not in database');
		}

		// delete message
		$result = $this->dba->delete('mail', ["`uid` = ? AND `id` = ? " . $sql_extra, $uid, $id]);

		if ($verbose) {
			if ($result) {
				// return success
				$answer = ['result' => 'ok', 'message' => 'message deleted'];
				$this->response->addFormattedContent('direct_messages_delete', ['direct_messages_delete' => $answer], $this->parameters['extension'] ?? null);
			} else {
				$answer = ['result' => 'error', 'message' => 'unknown error'];
				$this->response->addFormattedContent('direct_messages_delete', ['direct_messages_delete' => $answer], $this->parameters['extension'] ?? null);
			}
		}
	}
}
