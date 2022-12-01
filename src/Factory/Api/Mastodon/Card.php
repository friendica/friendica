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

namespace Friendica\Factory\Api\Mastodon;

use Friendica\BaseFactory;
use Friendica\Content\Text\BBCode;
use Friendica\Model\Post;
use Friendica\Network\HTTPException;
use Friendica\Util\Strings;

class Card extends BaseFactory
{
	/**
	 * @param int   $uriId   Uri-ID of the item
	 * @param array $history Link request history
	 *
	 * @return \Friendica\Object\Api\Mastodon\Card
	 * @throws HTTPException\InternalServerErrorException
	 * @throws \ImagickException*@throws \Exception
	 */
	public function createFromUriId(int $uriId, array $history = []): \Friendica\Object\Api\Mastodon\Card
	{
		$item = Post::selectFirst(['body'], ['uri-id' => $uriId]);
		if (!empty($item['body'])) {
			$data = BBCode::getAttachmentData($item['body']);
		} else {
			$data = [];
		}

		foreach (Post\Media::getByURIId($uriId, [Post\Media::HTML]) as $attached) {
			if ((empty($data['url']) || Strings::compareLink($data['url'], $attached['url'])) &&
				(!empty($attached['description']) || !empty($attached['image']) || !empty($attached['preview']))) {
				$parts = parse_url($attached['url']);
				if (!empty($parts['scheme']) && !empty($parts['host'])) {
					if (empty($attached['publisher-name'])) {
						$attached['publisher-name'] = $parts['host'];
					}
					if (empty($attached['publisher-url']) || empty(parse_url($attached['publisher-url'], PHP_URL_SCHEME))) {
						$attached['publisher-url'] = $parts['scheme'] . '://' . $parts['host'];

						if (!empty($parts['port'])) {
							$attached['publisher-url'] .= ':' . $parts['port'];
						}
					}
				}

				$data['url']           = $attached['url'];
				$data['title']         = $attached['name'];
				$data['description']   = $attached['description'];
				$data['type']          = 'link';
				$data['author_name']   = $attached['author-name'];
				$data['author_url']    = $attached['author-url'];
				$data['provider_name'] = $attached['publisher-name'];
				$data['provider_url']  = $attached['publisher-url'];
				$data['image']         = $attached['preview'];
				$data['width']         = $attached['preview-width'];
				$data['height']        = $attached['preview-height'];
			}
		}

		return new \Friendica\Object\Api\Mastodon\Card($data, $history);
	}
}
