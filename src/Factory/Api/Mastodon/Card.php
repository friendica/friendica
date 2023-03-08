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
		$media = Post\Media::getByURIId($uriId, [Post\Media::HTML]);
		if (empty($media) || (empty($media[0]['description']) && empty($media[0]['image']) && empty($media[0]['preview']))) {
			return new \Friendica\Object\Api\Mastodon\Card([], $history);
		}

		$parts = parse_url($media[0]['url']);
		if (!empty($parts['scheme']) && !empty($parts['host'])) {
			if (empty($media[0]['publisher-name'])) {
				$media[0]['publisher-name'] = $parts['host'];
			}
			if (empty($media[0]['publisher-url']) || empty(parse_url($media[0]['publisher-url'], PHP_URL_SCHEME))) {
				$media[0]['publisher-url'] = $parts['scheme'] . '://' . $parts['host'];

				if (!empty($parts['port'])) {
					$media[0]['publisher-url'] .= ':' . $parts['port'];
				}
			}
		}

		$data = [];

		$data['url']           = $media[0]['url'];
		$data['title']         = $media[0]['name'];
		$data['description']   = $media[0]['description'];
		$data['type']          = 'link';
		$data['author_name']   = $media[0]['author-name'];
		$data['author_url']    = $media[0]['author-url'];
		$data['provider_name'] = $media[0]['publisher-name'];
		$data['provider_url']  = $media[0]['publisher-url'];
		$data['image']         = $media[0]['preview'];
		$data['width']         = $media[0]['preview-width'];
		$data['height']        = $media[0]['preview-height'];
		$data['blurhash']      = $media[0]['blurhash'];

		return new \Friendica\Object\Api\Mastodon\Card($data, $history);
	}
}
