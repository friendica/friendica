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

namespace Friendica\Content\Post\Repository;

use Friendica\BaseCollection;
use Friendica\BaseRepository;
use Friendica\Content\Post\Collection;
use Friendica\Content\Post\Entity;
use Friendica\Content\Post\Factory;
use Friendica\Database\Database;
use Friendica\Util\Network;
use Friendica\Util\Strings;
use Psr\Log\LoggerInterface;

class PostMedia extends BaseRepository
{
	protected static $table_name = 'post-media';

	public function __construct(Database $database, LoggerInterface $logger, Factory\PostMedia $factory)
	{
		parent::__construct($database, $logger, $factory);
	}

	protected function _select(array $condition, array $params = []): BaseCollection
	{
		$rows = $this->db->selectToArray(static::$table_name, [], $condition, $params);

		$Entities = new Collection\PostMedias();
		foreach ($rows as $fields) {
			if (!Network::createUriFromString($fields['url'])) {
				continue;
			}
			try {
				$Entities[] = $this->factory->createFromTableRow($fields);
			} catch (\Exception $e) {
				$this->logger->warning('Invalid media row', ['code' => $e->getCode(), 'message' => $e->getMessage(), 'fields' => $fields]);
			}
		}

		return $Entities;
	}

	public function selectOneById(int $postMediaId): Entity\PostMedia
	{
		return $this->_selectOne(['id' => $postMediaId]);
	}

	public function selectByUriId(int $uriId): Collection\PostMedias
	{
		return $this->_select(['uri-id' => $uriId]);
	}

	public function save(Entity\PostMedia $PostMedia): Entity\PostMedia
	{
		$fields = [
			'uri-id'          => $PostMedia->uriId,
			'url'             => $PostMedia->url->__toString(),
			'type'            => $PostMedia->type,
			'mimetype'        => $PostMedia->mimetype->__toString(),
			'height'          => $PostMedia->height,
			'width'           => $PostMedia->width,
			'size'            => $PostMedia->size,
			'preview'         => $PostMedia->preview ? $PostMedia->preview->__toString() : null,
			'preview-height'  => $PostMedia->previewHeight,
			'preview-width'   => $PostMedia->previewWidth,
			'description'     => $PostMedia->description,
			'name'            => $PostMedia->name,
			'author-url'      => $PostMedia->authorUrl ? $PostMedia->authorUrl->__toString() : null,
			'author-name'     => $PostMedia->authorName,
			'author-image'    => $PostMedia->authorImage ? $PostMedia->authorImage->__toString() : null,
			'publisher-url'   => $PostMedia->publisherUrl ? $PostMedia->publisherUrl->__toString() : null,
			'publisher-name'  => $PostMedia->publisherName,
			'publisher-image' => $PostMedia->publisherImage ? $PostMedia->publisherImage->__toString() : null,
			'media-uri-id'    => $PostMedia->activityUriId,
			'blurhash'        => $PostMedia->blurhash,
		];

		if ($PostMedia->id) {
			$this->db->update(self::$table_name, $fields, ['id' => $PostMedia->id]);
		} else {
			$this->db->insert(self::$table_name, $fields, Database::INSERT_IGNORE);

			$newPostMediaId = $this->db->lastInsertId();

			$PostMedia = $this->selectOneById($newPostMediaId);
		}

		return $PostMedia;
	}


	/**
	 * Split the attachment media in the three segments "visual", "link" and "additional"
	 *
	 * @param int    $uri_id URI id
	 * @param array  $links list of links that shouldn't be added
	 * @param bool   $has_media
	 * @return Collection\PostMedias[] Three collections in "visual", "link" and "additional" keys
	 */
	public function splitAttachments(int $uri_id, array $links = [], bool $has_media = true): array
	{
		$attachments = [
			'visual'     => new Collection\PostMedias(),
			'link'       => new Collection\PostMedias(),
			'additional' => new Collection\PostMedias(),
		];

		if (!$has_media) {
			return $attachments;
		}

		$PostMedias = $this->selectByUriId($uri_id);
		if (!count($PostMedias)) {
			return $attachments;
		}

		$heights = [];
		$selected = '';
		$previews = [];

		foreach ($PostMedias as $PostMedia) {
			foreach ($links as $link) {
				if (Strings::compareLink($link, $PostMedia->url)) {
					continue 2;
				}
			}

			// Avoid adding separate media entries for previews
			foreach ($previews as $preview) {
				if (Strings::compareLink($preview, $PostMedia->url)) {
					continue 2;
				}
			}

			// Currently these two types are ignored here.
			// Posts are added differently and contacts are not displayed as attachments.
			if (in_array($PostMedia->type, [Entity\PostMedia::TYPE_ACCOUNT, Entity\PostMedia::TYPE_ACTIVITY])) {
				continue;
			}

			if (!empty($PostMedia->preview)) {
				$previews[] = $PostMedia->preview;
			}

			//$PostMedia->filetype = $filetype;
			//$PostMedia->subtype = $subtype;

			if ($PostMedia->type == Entity\PostMedia::TYPE_HTML || ($PostMedia->mimetype->type == 'text' && $PostMedia->mimetype->subtype == 'html')) {
				$attachments['link'][] = $PostMedia;
				continue;
			}

			if (
				in_array($PostMedia->type, [Entity\PostMedia::TYPE_AUDIO, Entity\PostMedia::TYPE_IMAGE]) ||
				in_array($PostMedia->mimetype->type, ['audio', 'image'])
			) {
				$attachments['visual'][] = $PostMedia;
			} elseif (($PostMedia->type == Entity\PostMedia::TYPE_VIDEO) || ($PostMedia->mimetype->type == 'video')) {
				if (!empty($PostMedia->height)) {
					// Peertube videos are delivered in many different resolutions. We pick a moderate one.
					// Since only Peertube provides a "height" parameter, this wouldn't be executed
					// when someone for example on Mastodon was sharing multiple videos in a single post.
					$heights[$PostMedia->height] = (string)$PostMedia->url;
					$video[(string) $PostMedia->url] = $PostMedia;
				} else {
					$attachments['visual'][] = $PostMedia;
				}
			} else {
				$attachments['additional'][] = $PostMedia;
			}
		}

		if (!empty($heights)) {
			ksort($heights);
			foreach ($heights as $height => $url) {
				if (empty($selected) || $height <= 480) {
					$selected = $url;
				}
			}

			if (!empty($selected)) {
				$attachments['visual'][] = $video[$selected];
				unset($video[$selected]);
				foreach ($video as $element) {
					$attachments['additional'][] = $element;
				}
			}
		}

		return $attachments;
	}

}
