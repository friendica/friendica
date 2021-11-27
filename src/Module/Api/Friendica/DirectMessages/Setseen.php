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

namespace Friendica\Module\Api\Friendica\DirectMessages;

use Friendica\Database\DBA;
use Friendica\Module\BaseApi;

/**
 * API endpoint: /api/friendica/direct_messages_setseen
 */
class Setseen extends BaseApi
{
	protected function post(array $request = [], array $post = [])
	{
		self::checkAllowedScope(self::SCOPE_WRITE);
		$uid = self::getCurrentUserID();

		$post = $this->checkDefaults([
			'id' => 0, // Id of the direct message
		], $post);

		// return error if id is zero
		if (empty($post['id'])) {
			$answer = ['result' => 'error', 'message' => 'message id not specified'];
			$this->response->exit('direct_messages_setseen', ['$result' => $answer], $this->parameters['extension'] ?? null);
			return;
		}

		// error message if specified id is not in database
		if (!DBA::exists('mail', ['id' => $post['id'], 'uid' => $uid])) {
			$answer = ['result' => 'error', 'message' => 'message id not in database'];
			$this->response->exit('direct_messages_setseen', ['$result' => $answer], $this->parameters['extension'] ?? null);
			return;
		}

		// update seen indicator
		if (DBA::update('mail', ['seen' => true], ['id' => $post['id']])) {
			$answer = ['result' => 'ok', 'message' => 'message set to seen'];
		} else {
			$answer = ['result' => 'error', 'message' => 'unknown error'];
		}

		$this->response->exit('direct_messages_setseen', ['$result' => $answer], $this->parameters['extension'] ?? null);
	}
}
