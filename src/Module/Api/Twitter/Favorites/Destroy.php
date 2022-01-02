<?php
/**
 * @copyright Copyright (C) 2010-2022, the Friendica project
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

namespace Friendica\Module\Api\Twitter\Favorites;

use Friendica\DI;
use Friendica\Model\Item;
use Friendica\Module\BaseApi;
use Friendica\Network\HTTPException\BadRequestException;

/**
 * @see https://developer.twitter.com/en/docs/twitter-api/v1/tweets/post-and-engage/api-reference/post-favorites-destroy
 */
class Destroy extends BaseApi
{
	protected function post(array $request = [])
	{
		self::checkAllowedScope(self::SCOPE_WRITE);
		$uid = self::getCurrentUserID();

		$id = $request['id'] ?? 0;

		if (empty($id)) {
			throw new BadRequestException('Item id not specified');
		}

		Item::performActivity($id, 'unlike', $uid);

		$status_info = DI::twitterStatus()->createFromItemId($id, $uid)->toArray();

		$this->response->exit('status', ['status' => $status_info], $this->parameters['extension'] ?? null);
	}
}
