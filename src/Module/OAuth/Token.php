<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\OAuth;

use Friendica\Database\DBA;
use Friendica\Model\User;
use Friendica\Module\BaseApi;
use Friendica\Module\Special\HTTPException;
use Friendica\Security\OAuth;
use Friendica\Util\DateTimeFormat;
use Psr\Http\Message\ResponseInterface;

/**
 * @see https://docs.joinmastodon.org/methods/oauth/#token
 * @see https://aaronparecki.com/oauth-2-simplified/
 */
class Token extends BaseApi
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
			'redirect_uri'  => '', // Set a URI to redirect the user to. If this parameter is set to "urn:ietf:wg:oauth:2.0:oob" then the token will be shown instead. Must match one of the redirect URIs declared during app registration.
			'scope'         => 'read', // List of requested OAuth scopes, separated by spaces. Must be a subset of scopes declared during app registration. If not provided, defaults to "read".
			'code'          => '', // A user authorization code, obtained via /oauth/authorize
			'grant_type'    => '', // Set equal to "authorization_code" if code is provided in order to gain user-level access. Otherwise, set equal to "client_credentials" to obtain app-level access only.
		], $request);

		// AndStatus transmits the client data in the AUTHORIZATION header field, see https://github.com/andstatus/andstatus/issues/530
		$authorization = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
		if (empty($authorization)) {
			// workaround for HTTP-auth in CGI mode
			$authorization = $_SERVER['REDIRECT_REMOTE_USER'] ?? '';
		}

		if ((empty($request['client_id']) || empty($request['client_secret'])) && substr($authorization, 0, 6) == 'Basic ') {
			// Per RFC2617, usernames can't contain a colon but password can,
			// so we cut on the first colon to obtain the username and the password
			// @see https://www.rfc-editor.org/rfc/rfc2617#section-2
			$datapair = explode(':', base64_decode(trim(substr($authorization, 6))), 2);
			if (count($datapair) == 2) {
				$request['client_id']     = $datapair[0];
				$request['client_secret'] = $datapair[1];
			}
		}

		if (empty($request['client_id']) || empty($request['client_secret'])) {
			$this->logger->warning('Incomplete request data', ['request' => $request]);
			$this->logAndJsonError(401, $this->errorFactory->Unauthorized('invalid_client', $this->t('Incomplete request data')));
			;
		}

		$application = OAuth::getApplication($request['client_id'], $request['client_secret'], $request['redirect_uri']);
		if (empty($application)) {
			$this->logAndJsonError(401, $this->errorFactory->Unauthorized('invalid_client', $this->t('Invalid data or unknown client')));
		}

		$grant_type = (string) $request['grant_type'];

		if (!in_array($grant_type, ['client_credentials', 'authorization_code'])) {
			$this->logger->warning('Unsupported or missing grant type', ['request' => $_REQUEST]);
			$this->logAndJsonError(422, $this->errorFactory->UnprocessableEntity($this->t('Unsupported or missing grant type')));
		}

		if ($grant_type === 'client_credentials') {
			// the "client_credentials" are used as a token for the application itself.
			// see https://aaronparecki.com/oauth-2-simplified/#client-credentials
			$token = OAuth::createTokenForUser($application, 0, '');

			$object = new \Friendica\Object\Api\Mastodon\Token(
				$token['access_token'],
				'Bearer',
				$application['scopes'],
				$token['created_at'],
				null
			);

			$this->jsonExit($object->toArray());
		}

		// now check for $grant_type === 'authorization_code'
		// For security reasons only allow freshly created tokens
		$redirect_uri = strtok($request['redirect_uri'], '?');
		$condition    = [
			"`redirect_uri` LIKE ? AND `id` = ? AND `code` = ? AND `created_at` > ?",
			$redirect_uri, $application['id'], $request['code'], DateTimeFormat::utc('now - 5 minutes')
		];

		$token = DBA::selectFirst('application-view', ['access_token', 'created_at', 'uid'], $condition);
		if (!DBA::isResult($token)) {
			$this->logger->notice('Token not found or outdated', $condition);
			$this->logAndJsonError(401, $this->errorFactory->Unauthorized());
		}

		$owner = User::getOwnerDataById($token['uid']);

		$object = new \Friendica\Object\Api\Mastodon\Token(
			$token['access_token'],
			'Bearer',
			$application['scopes'],
			$token['created_at'],
			$owner['url']
		);

		$this->jsonExit($object->toArray());
	}
}
