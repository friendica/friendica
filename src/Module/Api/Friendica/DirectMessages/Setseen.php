<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Api\Friendica\DirectMessages;

use Friendica\Database\DBA;
use Friendica\Module\BaseApi;

/**
 * API endpoint: /api/friendica/direct_messages_setseen
 */
class Setseen extends BaseApi
{
	protected function post(array $request = [])
	{
		$this->checkAllowedScope(self::SCOPE_WRITE);
		$uid = self::getCurrentUserID();

		$request = $this->getRequest([
			'id' => 0, // Id of the direct message
		], $request);

		// return error if id is zero
		if (empty($request['id'])) {
			$answer = ['result' => 'error', 'message' => 'message id not specified'];
			$this->response->addFormattedContent('direct_messages_setseen', ['$result' => $answer], $this->parameters['extension'] ?? null);
			return;
		}

		// error message if specified id is not in database
		if (!DBA::exists('mail', ['id' => $request['id'], 'uid' => $uid])) {
			$answer = ['result' => 'error', 'message' => 'message id not in database'];
			$this->response->addFormattedContent('direct_messages_setseen', ['$result' => $answer], $this->parameters['extension'] ?? null);
			return;
		}

		// update seen indicator
		if (DBA::update('mail', ['seen' => true], ['id' => $request['id']])) {
			$answer = ['result' => 'ok', 'message' => 'message set to seen'];
		} else {
			$answer = ['result' => 'error', 'message' => 'unknown error'];
		}

		$this->response->addFormattedContent('direct_messages_setseen', ['$result' => $answer], $this->parameters['extension'] ?? null);
	}
}
