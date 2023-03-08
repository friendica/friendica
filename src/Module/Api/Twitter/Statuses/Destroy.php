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

namespace Friendica\Module\Api\Twitter\Statuses;

use Friendica\Module\BaseApi;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Network\HTTPException\BadRequestException;

/**
 * Destroys a specific status.
 *
 * @see https://developer.twitter.com/en/docs/tweets/post-and-engage/api-reference/post-statuses-destroy-id
 */
class Destroy extends BaseApi
{
	protected function post(array $request = [])
	{
		BaseApi::checkAllowedScope(BaseApi::SCOPE_READ);
		$uid = BaseApi::getCurrentUserID();

		$id = $this->getRequestValue($request, 'id', 0);
		$id = $this->getRequestValue($this->parameters, 'id', $id);
		if (empty($id)) {
			throw new BadRequestException('An id is missing.');
		}

		$post = Post::selectFirst(['id'], ['uri-id' => $request['id'], 'uid' => [0, $uid]], ['order' => ['uid' => true]]);
		if (empty($post['id'])) {
			throw new BadRequestException('Item id not found');
		}

		$this->logger->notice('API: api_statuses_destroy: ' . $id);

		$include_entities = $this->getRequestValue($request, 'include_entities', false);

		$ret = DI::twitterStatus()->createFromUriId($id, $uid, $include_entities)->toArray();

		Item::deleteForUser(['id' => $post['id']], $uid);

		$this->response->exit('status', ['status' => $ret], $this->parameters['extension'] ?? null, Contact::getPublicIdByUserId($uid));
	}
}
