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

use Friendica\Content\Text\BBCode;
use Friendica\Core\Protocol;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Module\BaseApi;
use Friendica\Network\HTTPException\BadRequestException;
use Friendica\Network\HTTPException\ForbiddenException;
use Friendica\Network\HTTPException\InternalServerErrorException;
use Friendica\Protocol\Diaspora;

/**
 * Repeats a status.
 *
 * @see https://developer.twitter.com/en/docs/tweets/post-and-engage/api-reference/post-statuses-retweet-id
 */
class Retweet extends BaseApi
{
	protected function post(array $request = [])
	{
		self::checkAllowedScope(self::SCOPE_WRITE);
		$uid = self::getCurrentUserID();

		$id = $request['id'] ?? 0;

		if (empty($id)) {
			throw new BadRequestException('Item id not specified');
		}

		$fields = ['uri-id', 'network', 'body', 'title', 'author-name', 'author-link', 'author-avatar', 'guid', 'created', 'plink'];
		$item   = Post::selectFirst($fields, ['id' => $id, 'private' => [Item::PUBLIC, Item::UNLISTED]]);

		if (DBA::isResult($item) && !empty($item['body'])) {
			if (in_array($item['network'], [Protocol::ACTIVITYPUB, Protocol::DFRN, Protocol::TWITTER])) {
				if (!Item::performActivity($id, 'announce', $uid)) {
					throw new InternalServerErrorException();
				}

				$item_id = $id;
			} else {
				$item_id = Diaspora::performReshare($item['uri-id'], $uid);
			}
		} else {
			throw new ForbiddenException();
		}

		$status_info = DI::twitterStatus()->createFromItemId($item_id, $uid)->toArray();

		DI::apiResponse()->exit('statuses', ['status' => $status_info], $this->parameters['extension'] ?? null);
	}
}
