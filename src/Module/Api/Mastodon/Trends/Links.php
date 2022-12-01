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

namespace Friendica\Module\Api\Mastodon\Trends;

use Friendica\Core\Protocol;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Post;
use Friendica\Module\BaseApi;
use Friendica\Util\DateTimeFormat;

/**
 * @see https://docs.joinmastodon.org/methods/trends/#links
 */
class Links extends BaseApi
{
	/**
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	protected function rawContent(array $request = [])
	{
		$request = $this->getRequest([
			'limit' => 10, // Maximum number of results to return. Defaults to 10.
		], $request);

		$condition = ["EXISTS(SELECT `id` FROM `post-media` WHERE `post-media`.`uri-id` = `post-thread-view`.`uri-id` AND `type` = ? AND NOT `name` IS NULL AND NOT `description` IS NULL) AND NOT `private` AND `commented` > ? AND `created` > ?",
			Post\Media::HTML, DateTimeFormat::utc('now -1 day'), DateTimeFormat::utc('now -1 week')];
		$condition = DBA::mergeConditions($condition, ['network' => Protocol::FEDERATED]);

		$trending = [];
		$statuses = Post::selectPostThread(['uri-id', 'total-comments', 'total-actors'], $condition, ['limit' => $request['limit'], 'order' => ['total-actors' => true]]);
		while ($status = Post::fetch($statuses)) {
			$history    = [['day' => (string)time(), 'uses' => (string)$status['total-comments'], 'accounts' => (string)$status['total-actors']]];
			$trending[] = DI::mstdnCard()->createFromUriId($status['uri-id'], $history)->toArray();
		}
		DBA::close($statuses);

		System::jsonExit($trending);
	}
}
