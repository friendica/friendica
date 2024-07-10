<?php
/**
 * @copyright Copyright (C) 2010-2024, the Friendica project
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
use Friendica\Model\Attach;
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
			$attachments[] = $this->createFromMediaArray($attachment);
		}

		return $attachments;
	}

	/**
	 * @param int $id id of the media
	 * @return \Friendica\Object\Api\Mastodon\Attachment
	 * @throws HTTPException\InternalServerErrorException
	 */
	public function createFromId(int $id): \Friendica\Object\Api\Mastodon\Attachment
	{
		$attachment = Post\Media::getById($id);
		if (empty($attachment)) {
			return [];
		}
		return $this->createFromMediaArray($attachment);
	}

	/**
	 * @param array $attachment
	 * @return \Friendica\Object\Api\Mastodon\Attachment
	 * @throws HTTPException\InternalServerErrorException
	 */
	private function createFromMediaArray(array $attachment):  \Friendica\Object\Api\Mastodon\Attachment
	{
		$filetype = !empty($attachment['mimetype']) ? strtolower(substr($attachment['mimetype'], 0, strpos($attachment['mimetype'], '/'))) : '';

		if (($filetype == 'audio') || ($attachment['type'] == Post\Media::AUDIO)) {
			$type = 'audio';
		} elseif (($filetype == 'video') || ($attachment['type'] == Post\Media::VIDEO)) {
			$type = 'video';
		} elseif ($attachment['mimetype'] == image_type_to_mime_type(IMAGETYPE_GIF)) {
			$type = 'gifv';
		} elseif (($filetype == 'image') || ($attachment['type'] == Post\Media::IMAGE)) {
			$type = 'image';
		} else {
			$type = 'unknown';
		}

		$remote = $attachment['url'];
		if ($type == 'image') {
			$url     = Post\Media::getPreviewUrlForId($attachment['id']);
			$preview = Post\Media::getPreviewUrlForId($attachment['id'], Proxy::SIZE_MEDIUM);
		} else {
			$url = $attachment['url'];

			if (!empty($attachment['preview'])) {
				$preview = Post\Media::getPreviewUrlForId($attachment['id'], Proxy::SIZE_MEDIUM);
			} else {
				$preview = '';
			}
		}

		return new \Friendica\Object\Api\Mastodon\Attachment($attachment, $type, $url, $preview, $remote);
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

		$ext = Images::getExtensionByMimeType($photo['type']);

		$url = $this->baseUrl . '/photo/' . $photo['resource-id'] . '-0' . $ext;

		$preview = Photo::selectFirst(['scale'], ["`resource-id` = ? AND `uid` = ? AND `scale` > ?", $photo['resource-id'], $photo['uid'], 0], ['order' => ['scale']]);
		if (!empty($preview)) {
			$preview_url = $this->baseUrl . '/photo/' . $photo['resource-id'] . '-' . $preview['scale'] . $ext;
		} else {
			$preview_url = '';
		}

		$object = new \Friendica\Object\Api\Mastodon\Attachment($attachment, 'image', $url, $preview_url, '');
		return $object->toArray();
	}

	/**
	 * @param int $id id of the attachment
	 *
	 * @return array
	 * @throws HTTPException\InternalServerErrorException
	 */
	public function createFromAttach(int $id): array
	{
		$media = Attach::selectFirst(['id', 'filetype'], ['id' => $id]);
		if (empty($media)) {
			return [];
		}
		$attachment = [
			'id'          => 'attach:' . $media['id'],
			'description' => null,
			'blurhash'    => null,
		];

		$types = [Post\Media::AUDIO => 'audio', Post\Media::VIDEO => 'video', Post\Media::IMAGE => 'image'];

		$type = Post\Media::getType($media['filetype']);

		$url = $this->baseUrl . '/attach/' . $id;

		$object = new \Friendica\Object\Api\Mastodon\Attachment($attachment, $types[$type] ?? 'unknown', $url, '', '');
		return $object->toArray();
	}

	public function isAttach(string $id): bool
	{
		return substr($id, 0, 7) == 'attach:';
	}
}
