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

use Friendica\Core\System;
use Friendica\DI;
use Friendica\Model\Tag;
use Friendica\Module\BaseApi;

/**
 * @see https://docs.joinmastodon.org/methods/instance/trends/
 */
class Tags extends BaseApi
{
	/**
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	protected function rawContent(array $request = [])
	{
		$request = $this->getRequest([
			'limit' => 20, // Maximum number of results to return. Defaults to 20.
			'offset' => 0, // Offset in set. Defaults to 0.
			'friendica_local' => false, // Whether to return local tag trends instead of global, defaults to false
		], $request);

		$trending = [];
		if ($request['friendica_local']) {
			$tags = Tag::getLocalTrendingHashtags(24, $request['limit'], $request['offset']);
		} else {
			$tags = Tag::getGlobalTrendingHashtags(24, $request['limit'], $request['offset']);
		}

		foreach ($tags as $tag) {
			$tag['name'] = $tag['term'];
			$history = [['day' => (string)time(), 'uses' => (string)$tag['score'], 'accounts' => (string)$tag['authors']]];
			$hashtag = new \Friendica\Object\Api\Mastodon\Tag(DI::baseUrl(), $tag, $history);
			$trending[] = $hashtag->toArray();
		}

		if (!empty($trending)) {
			self::setLinkHeaderByOffsetLimit($request['offset'], $request['limit']);
		}

		System::jsonExit($trending);
	}
}
