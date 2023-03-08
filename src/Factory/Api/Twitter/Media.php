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

namespace Friendica\Factory\Api\Twitter;

use Friendica\App\BaseURL;
use Friendica\BaseFactory;
use Friendica\Network\HTTPException;
use Friendica\Model\Post;
use Psr\Log\LoggerInterface;

class Media extends BaseFactory
{
	/** @var BaseURL */
	private $baseUrl;

	public function __construct(LoggerInterface $logger, BaseURL $baseURL)
	{
		parent::__construct($logger);

		$this->baseUrl = $baseURL;
	}

	/**
	 * @param int $uriId Uri-ID of the attachments
	 * @param string $text
	 *
	 * @return array
	 * @throws HTTPException\InternalServerErrorException
	 */
	public function createFromUriId(int $uriId, string $text): array
	{
		$attachments = [];
		foreach (Post\Media::getByURIId($uriId, [Post\Media::AUDIO, Post\Media::IMAGE, Post\Media::VIDEO]) as $attachment) {
			if ($attachment['type'] == Post\Media::IMAGE) {
				$url = Post\Media::getUrlForId($attachment['id']);
			} elseif (!empty($attachment['preview'])) {
				$url = Post\Media::getPreviewUrlForId($attachment['id']);
			} else {
				$url = $attachment['url'];
			}

			$indices = [];

			$object        = new \Friendica\Object\Api\Twitter\Media($attachment, $url, $indices);
			$attachments[] = $object->toArray();
		}

		return $attachments;
	}
}
