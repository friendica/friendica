<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\OAuth;

use Friendica\Database\DBA;
use Friendica\Module\BaseApi;
use Friendica\Module\Special\HTTPException;
use Psr\Http\Message\ResponseInterface;

/**
 * @see https://docs.joinmastodon.org/spec/oauth/
 */
class Revoke extends BaseApi
{
	public function run(HTTPException $httpException, array $request = [], bool $scopecheck = true): ResponseInterface
	{
		return parent::run($httpException, $request, false);
	}

	protected function post(array $request = [])
	{
		$request = $this->getRequest([
			'client_id'     => '', // Client ID, obtained during app registration
			'client_secret' => '', // Client secret, obtained during app registration
			'token'         => '', // The previously obtained token, to be invalidated
		], $request);

		$condition = ['client_id' => $request['client_id'], 'client_secret' => $request['client_secret'], 'access_token' => $request['token']];
		$token     = DBA::selectFirst('application-view', ['id'], $condition);
		if (empty($token['id'])) {
			$this->logger->notice('Token not found', $condition);
			$this->logAndJsonError(401, $this->errorFactory->Unauthorized());
		}

		DBA::delete('application-token', ['application-id' => $token['id']]);
		$this->jsonExit([]);
	}
}
