<?php
/**
 * @copyright Copyright (C) 2010-2021, the Friendica project
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

/**
 * @see https://docs.joinmastodon.org/spec/oauth/
 */
class Revoke extends BaseApi
{
	protected function post(array $request = [], array $post = [])
	{
		$post = self::checkDefaults([
			'client_id'     => '', // Client ID, obtained during app registration
			'client_secret' => '', // Client secret, obtained during app registration
			'token'         => '', // The previously obtained token, to be invalidated
		], $post);

		$condition = ['client_id' => $post['client_id'], 'client_secret' => $post['client_secret'], 'access_token' => $post['token']];
		$token = DBA::selectFirst('application-view', ['id'], $condition);
		if (empty($token['id'])) {
			Logger::warning('Token not found', $condition);
			DI::mstdnError()->Unauthorized();
		}

		DBA::delete('application-token', ['application-id' => $token['id']]);
		System::jsonExit([]);
	}
}
