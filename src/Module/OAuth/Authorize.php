<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\OAuth;

use Friendica\DI;
use Friendica\Module\BaseApi;
use Friendica\Security\OAuth;

/**
 * @see https://docs.joinmastodon.org/spec/oauth/
 * @see https://aaronparecki.com/oauth-2-simplified/
 */
class Authorize extends BaseApi
{
	private static $oauth_code = '';

	/**
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	protected function rawContent(array $request = [])
	{
		$request = $this->getRequest([
			'force_login'   => '', // Forces the user to re-login, which is necessary for authorizing with multiple accounts from the same instance.
			'response_type' => '', // Should be set equal to "code".
			'client_id'     => '', // Client ID, obtained during app registration.
			'client_secret' => '', // Isn't normally provided. We will use it if present.
			'redirect_uri'  => '', // Set a URI to redirect the user to. If this parameter is set to "urn:ietf:wg:oauth:2.0:oob" then the authorization code will be shown instead. Must match one of the redirect URIs declared during app registration.
			'scope'         => 'read', // List of requested OAuth scopes, separated by spaces (or by pluses, if using query parameters). Must be a subset of scopes declared during app registration. If not provided, defaults to "read".
			'state'         => '',
		], $request);

		if ($request['response_type'] != 'code') {
			$this->logger->warning('Unsupported or missing response type', ['request' => $request]);
			$this->logAndJsonError(422, $this->errorFactory->UnprocessableEntity($this->t('Unsupported or missing response type')));
		}

		if (empty($request['client_id']) || empty($request['redirect_uri'])) {
			$this->logger->warning('Incomplete request data', ['request' => $request]);
			$this->logAndJsonError(422, $this->errorFactory->UnprocessableEntity($this->t('Incomplete request data')));
		}

		$application = OAuth::getApplication($request['client_id'], $request['client_secret'], $request['redirect_uri']);
		if (empty($application)) {
			$this->logger->warning('An application could not be fetched.', ['request' => $request]);
			$this->logAndJsonError(422, $this->errorFactory->UnprocessableEntity());
		}

		// @todo Compare the application scope and requested scope

		$redirect_request = $_REQUEST;
		unset($redirect_request['pagename']);
		$redirect = http_build_query($redirect_request);

		$uid = DI::userSession()->getLocalUserId();
		if (empty($uid)) {
			$this->logger->info('Redirect to login');
			DI::appHelper()->redirect('login?' . http_build_query(['return_authorize' => $redirect]));
		} else {
			$this->logger->info('Already logged in user', ['uid' => $uid]);
		}

		if (!OAuth::existsTokenForUser($application, $uid) && !DI::session()->get('oauth_acknowledge')) {
			$this->logger->info('Redirect to acknowledge');
			DI::appHelper()->redirect('oauth/acknowledge?' . http_build_query(['return_authorize' => $redirect, 'application' => $application['name']]));
		}

		DI::session()->remove('oauth_acknowledge');

		$token = OAuth::createTokenForUser($application, $uid, $request['scope']);
		if (!$token) {
			$this->logAndJsonError(422, $this->errorFactory->UnprocessableEntity());
		}

		if ($application['redirect_uri'] != 'urn:ietf:wg:oauth:2.0:oob') {
			DI::appHelper()->redirect($request['redirect_uri'] . (strpos($request['redirect_uri'], '?') ? '&' : '?') . http_build_query(['code' => $token['code'], 'state' => $request['state']]));
		}

		self::$oauth_code = $token['code'];
	}

	protected function content(array $request = []): string
	{
		if (empty(self::$oauth_code)) {
			return '';
		}

		return DI::l10n()->t('Please copy the following authentication code into your application and close this window: %s', self::$oauth_code);
	}
}
