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

namespace Friendica\Factory\Api\Mastodon;

use Friendica\BaseFactory;
use Friendica\Content\Text\BBCode;
use Friendica\Core\Protocol;
use Friendica\Model\Post;
use Friendica\Network\HTTPException;

class StatusSource extends BaseFactory
{
	/**
	 * @param int $uriId Uri-ID of the item
	 *
	 * @return \Friendica\Object\Api\Mastodon\StatusSource
	 * @throws HTTPException\InternalServerErrorException
	 * @throws \ImagickException*@throws \Exception
	 */
	public function createFromUriId(int $uriId, int $uid): \Friendica\Object\Api\Mastodon\StatusSource
	{
		$post = Post::selectFirst(['uri-id', 'raw-body', 'body', 'title'], ['uri-id' => $uriId, 'uid' => [0, $uid]]);

		$spoiler_text = $post['title'] ?: BBCode::toPlaintext(BBCode::getAbstract($post['body'], Protocol::ACTIVITYPUB));
		$body         = BBCode::toMarkdown($post['body']);

		return new \Friendica\Object\Api\Mastodon\StatusSource($post['uri-id'], $body, $spoiler_text);
	}
}
