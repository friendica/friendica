<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Friendica\Module\OAuth;

use Friendica\Core\Logger;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\DI;
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
			Logger::warning('Incomplete request data', ['request' => $request]);
			DI::mstdnError()->Unauthorized('invalid_client', DI::l10n()->t('Incomplete request data'));
		}

		$application = OAuth::getApplication($request['client_id'], $request['client_secret'], $request['redirect_uri']);
		if (empty($application)) {
			DI::mstdnError()->Unauthorized('invalid_client', DI::l10n()->t('Invalid data or unknown client'));
		}

		if ($request['grant_type'] == 'client_credentials') {
			// the "client_credentials" are used as a token for the application itself.
			// see https://aaronparecki.com/oauth-2-simplified/#client-credentials
			$token = OAuth::createTokenForUser($application, 0, '');
			$me = null;
		} elseif ($request['grant_type'] == 'authorization_code') {
			// For security reasons only allow freshly created tokens
			$condition = ["`redirect_uri` = ? AND `id` = ? AND `code` = ? AND `created_at` > ?",
				$request['redirect_uri'], $application['id'], $request['code'], DateTimeFormat::utc('now - 5 minutes')];

			$token = DBA::selectFirst('application-view', ['access_token', 'created_at', 'uid'], $condition);
			if (!DBA::isResult($token)) {
				Logger::notice('Token not found or outdated', $condition);
				DI::mstdnError()->Unauthorized();
			}
			$owner = User::getOwnerDataById($token['uid']);
			$me = $owner['url'];
		} else {
			Logger::warning('Unsupported or missing grant type', ['request' => $_REQUEST]);
			DI::mstdnError()->UnprocessableEntity(DI::l10n()->t('Unsupported or missing grant type'));
		}

		$object = new \Friendica\Object\Api\Mastodon\Token($token['access_token'], 'Bearer', $application['scopes'], $token['created_at'], $me);

		System::jsonExit($object->toArray());
	}
}
