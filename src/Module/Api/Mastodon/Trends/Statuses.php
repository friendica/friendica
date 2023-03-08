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

namespace Friendica\Module\Api\Mastodon\Trends;

use Friendica\Core\Logger;
use Friendica\Core\Protocol;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Post;
use Friendica\Module\BaseApi;
use Friendica\Util\DateTimeFormat;

/**
 * @see https://docs.joinmastodon.org/methods/trends/#statuses
 */
class Statuses extends BaseApi
{
	/**
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	protected function rawContent(array $request = [])
	{
		$uid = self::getCurrentUserID();

		$request = $this->getRequest([
			'limit' => 10, // Maximum number of results to return. Defaults to 10.
			'offset' => 0, // Offset in set, Defaults to 0.
		], $request);

		$condition = ["NOT `private` AND `commented` > ? AND `created` > ?", DateTimeFormat::utc('now -1 day'), DateTimeFormat::utc('now -1 week')];
		$condition = DBA::mergeConditions($condition, ['network' => Protocol::FEDERATED]);

		$display_quotes = self::appSupportsQuotes();

		$trending = [];
		$statuses = Post::selectPostThread(['uri-id'], $condition, ['limit' => [$request['offset'], $request['limit']],  'order' => ['total-actors' => true]]);
		while ($status = Post::fetch($statuses)) {
			try {
				$trending[] = DI::mstdnStatus()->createFromUriId($status['uri-id'], $uid, $display_quotes);
			} catch (\Exception $exception) {
				Logger::info('Post not fetchable', ['uri-id' => $status['uri-id'], 'uid' => $uid, 'exception' => $exception]);
			}
		}
		DBA::close($statuses);

		if (!empty($trending)) {
			self::setLinkHeaderByOffsetLimit($request['offset'], $request['limit']);
		}

		System::jsonExit($trending);
	}
}
