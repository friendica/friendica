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

namespace Friendica\Module\Api\Twitter\Statuses;

use Friendica\Core\Logger;
use Friendica\Database\DBA;
use Friendica\Module\BaseApi;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Post;
use Friendica\Network\HTTPException\BadRequestException;

/**
 * Returns a single status.
 *
 * @see https://developer.twitter.com/en/docs/tweets/post-and-engage/api-reference/get-statuses-show-id
 */
class Show extends BaseApi
{
	protected function rawContent(array $request = [])
	{
		BaseApi::checkAllowedScope(BaseApi::SCOPE_READ);
		$uid = BaseApi::getCurrentUserID();

		if (empty($this->parameters['id'])) {
			$id = intval($request['id'] ?? 0);
		} else {
			$id = (int)$this->parameters['id'];
		}

		Logger::notice('API: api_statuses_show: ' . $id);

		$conversation = !empty($request['conversation']);

		// try to fetch the item for the local user - or the public item, if there is no local one
		$uri_item = Post::selectFirst(['uri-id'], ['id' => $id]);
		if (!DBA::isResult($uri_item)) {
			throw new BadRequestException(sprintf("There is no status with the id %d", $id));
		}

		$item = Post::selectFirst(['id'], ['uri-id' => $uri_item['uri-id'], 'uid' => [0, $uid]], ['order' => ['uid' => true]]);
		if (!DBA::isResult($item)) {
			throw new BadRequestException(sprintf("There is no status with the uri-id %d for the given user.", $uri_item['uri-id']));
		}

		$id = $item['id'];

		if ($conversation) {
			$condition = ['parent' => $id, 'gravity' => [GRAVITY_PARENT, GRAVITY_COMMENT]];
			$params    = ['order' => ['id' => true]];
		} else {
			$condition = ['id' => $id, 'gravity' => [GRAVITY_PARENT, GRAVITY_COMMENT]];
			$params    = [];
		}

		$statuses = Post::selectForUser($uid, [], $condition, $params);

		/// @TODO How about copying this to above methods which don't check $r ?
		if (!DBA::isResult($statuses)) {
			throw new BadRequestException(sprintf("There is no status or conversation with the id %d.", $id));
		}

		$include_entities = strtolower(($request['include_entities'] ?? 'false') == 'true');

		$ret = [];
		while ($status = DBA::fetch($statuses)) {
			$ret[] = DI::twitterStatus()->createFromUriId($status['uri-id'], $status['uid'], $include_entities)->toArray();
		}
		DBA::close($statuses);

		if ($conversation) {
			$data = ['status' => $ret];
			$this->response->exit('statuses', $data, $this->parameters['extension'] ?? null, Contact::getPublicIdByUserId($uid));
		} else {
			$data = ['status' => $ret[0]];
			$this->response->exit('status', $data, $this->parameters['extension'] ?? null, Contact::getPublicIdByUserId($uid));
		}
	}
}
