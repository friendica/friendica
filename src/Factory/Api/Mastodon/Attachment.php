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

use Friendica\App\BaseURL;
use Friendica\BaseFactory;
use Friendica\Model\Photo;
use Friendica\Network\HTTPException;
use Friendica\Model\Post;
use Friendica\Util\Images;
use Friendica\Util\Proxy;
use Psr\Log\LoggerInterface;

class Attachment extends BaseFactory
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
	 * @return array
	 * @throws HTTPException\InternalServerErrorException
	 */
	public function createFromUriId(int $uriId): array
	{
		$attachments = [];
		foreach (Post\Media::getByURIId($uriId, [Post\Media::AUDIO, Post\Media::VIDEO, Post\Media::IMAGE]) as $attachment) {
			$filetype = !empty($attachment['mimetype']) ? strtolower(substr($attachment['mimetype'], 0, strpos($attachment['mimetype'], '/'))) : '';

			if (($filetype == 'audio') || ($attachment['type'] == Post\Media::AUDIO)) {
				$type = 'audio';
			} elseif (($filetype == 'video') || ($attachment['type'] == Post\Media::VIDEO)) {
				$type = 'video';
			} elseif ($attachment['mimetype'] == 'image/gif') {
				$type = 'gifv';
			} elseif (($filetype == 'image') || ($attachment['type'] == Post\Media::IMAGE)) {
				$type = 'image';
			} else {
				$type = 'unknown';
			}

			$remote = $attachment['url'];
			if ($type == 'image') {
				$url     = Post\Media::getPreviewUrlForId($attachment['id']);
				$preview = Post\Media::getPreviewUrlForId($attachment['id'], Proxy::SIZE_SMALL);
			} else {
				$url = $attachment['url'];

				if (!empty($attachment['preview'])) {
					$preview = Post\Media::getPreviewUrlForId($attachment['id'], Proxy::SIZE_SMALL);
				} else {
					$preview = '';
				}
			}

			$object        = new \Friendica\Object\Api\Mastodon\Attachment($attachment, $type, $url, $preview, $remote);
			$attachments[] = $object->toArray();
		}

		return $attachments;
	}

	/**
	 * @param int $id id of the photo
	 *
	 * @return array
	 * @throws HTTPException\InternalServerErrorException
	 */
	public function createFromPhoto(int $id): array
	{
		$photo = Photo::selectFirst(['resource-id', 'uid', 'id', 'title', 'type', 'width', 'height', 'blurhash'], ['id' => $id]);
		if (empty($photo)) {
			return [];
		}

		$attachment = [
			'id'          => $photo['id'],
			'description' => $photo['title'],
			'width'       => $photo['width'],
			'height'      => $photo['height'],
			'blurhash'    => $photo['blurhash'],
		];

		$photoTypes = Images::supportedTypes();
		$ext        = $photoTypes[$photo['type']];

		$url = $this->baseUrl . '/photo/' . $photo['resource-id'] . '-0.' . $ext;

		$preview = Photo::selectFirst(['scale'], ["`resource-id` = ? AND `uid` = ? AND `scale` > ?", $photo['resource-id'], $photo['uid'], 0], ['order' => ['scale']]);
		if (empty($scale)) {
			$preview_url = $this->baseUrl . '/photo/' . $photo['resource-id'] . '-' . $preview['scale'] . '.' . $ext;
		} else {
			$preview_url = '';
		}

		$object = new \Friendica\Object\Api\Mastodon\Attachment($attachment, 'image', $url, $preview_url, '');
		return $object->toArray();
	}
}
