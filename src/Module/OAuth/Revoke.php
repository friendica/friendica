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
		$token = DBA::selectFirst('application-view', ['id'], $condition);
		if (empty($token['id'])) {
			Logger::notice('Token not found', $condition);
			DI::mstdnError()->Unauthorized();
		}

		DBA::delete('application-token', ['application-id' => $token['id']]);
		System::jsonExit([]);
	}
}
