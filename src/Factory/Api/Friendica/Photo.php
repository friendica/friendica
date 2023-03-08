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

namespace Friendica\Factory\Api\Friendica;

use Friendica\App\BaseURL;
use Friendica\BaseFactory;
use Friendica\Database\DBA;
use Friendica\Factory\Api\Twitter\Status;
use Friendica\Model\Item;
use Friendica\Model\Photo as ModelPhoto;
use Friendica\Model\Post;
use Friendica\Network\HTTPException;
use Psr\Log\LoggerInterface;
use Friendica\Util\Images;

class Photo extends BaseFactory
{
	/** @var BaseURL */
	private $baseUrl;
	/** @var Status */
	private $status;
	/** @var Activities */
	private $activities;

	public function __construct(LoggerInterface $logger, BaseURL $baseURL, Status $status, Activities $activities)
	{
		parent::__construct($logger);

		$this->activities = $activities;
		$this->status     = $status;
		$this->baseUrl    = $baseURL;
	}

	/**
	 * @param string $photo_id
	 * @param int    $scale
	 * @param int    $uid
	 * @param string $type
	 * @return Array
	 */
	public function createFromId(string $photo_id, int $scale = null, int $uid, string $type = 'json', bool $with_posts = true): array
	{
		$fields = ['resource-id', 'created', 'edited', 'title', 'desc', 'album', 'filename','type',
			'height', 'width', 'datasize', 'profile', 'allow_cid', 'deny_cid', 'allow_gid', 'deny_gid',
			'backend-class', 'backend-ref', 'id', 'scale'];

		$condition = ['uid' => $uid, 'resource-id' => $photo_id];
		if (is_int($scale)) {
			$fields = array_merge(['data'], $fields);

			$condition['scale'] = $scale;
		}

		$photos = ModelPhoto::selectToArray($fields, $condition);
		if (empty($photos)) {
			throw new HTTPException\NotFoundException();
		}
		$data = $photos[0];

		$data['media-id'] = $data['id'];
		$data['id']       = $data['resource-id'];

		if (is_int($scale)) {
			$data['data'] = base64_encode(ModelPhoto::getImageDataForPhoto($data));
		}

		if ($type == 'xml') {
			$data['links'] = [];
		} else {
			$data['link'] = [];
		}

		foreach ($photos as $id => $photo) {
			$link = $this->baseUrl . '/photo/' . $data['resource-id'] . '-' . $photo['scale'] . Images::getExtensionByMimeType($data['type']);
			if ($type == 'xml') {
				$data['links'][$photo['scale'] . ':link']['@attributes'] = [
					'type'  => $data['type'],
					'scale' => $photo['scale'],
					'href'  => $link
				];
			} else {
				$data['link'][$id] = $link;
			}
			if (is_null($scale)) {
				$data['scales'][] = [
					'id'     => $photo['id'],
					'scale'  => $photo['scale'],
					'link'   => $link,
					'width'  => $photo['width'],
					'height' => $photo['height'],
					'size'   => $photo['datasize'],
				];
			}
		}

		unset($data['backend-class']);
		unset($data['backend-ref']);
		unset($data['resource-id']);

		if ($with_posts) {
			// retrieve item element for getting activities (like, dislike etc.) related to photo
			$condition = ['uid' => $uid, 'resource-id' => $photo_id];

			$item = Post::selectFirst(['id', 'uid', 'uri', 'uri-id', 'parent', 'allow_cid', 'deny_cid', 'allow_gid', 'deny_gid'], $condition);
		}
		if (!empty($item)) {
			$data['friendica_activities'] = $this->activities->createFromUriId($item['uri-id'], $item['uid'], $type);

			// retrieve comments on photo
			$condition = ["`parent` = ? AND `uid` = ? AND `gravity` IN (?, ?)",
				$item['parent'], $uid, Item::GRAVITY_PARENT, Item::GRAVITY_COMMENT];

			$statuses = Post::selectForUser($uid, [], $condition);

			// prepare output of comments
			$commentData = [];
			while ($status = DBA::fetch($statuses)) {
				$commentData[] = $this->status->createFromUriId($status['uri-id'], $status['uid'])->toArray();
			}
			DBA::close($statuses);

			$comments = [];
			if ($type == 'xml') {
				$k = 0;
				foreach ($commentData as $comment) {
					$comments[$k++ . ':comment'] = $comment;
				}
			} else {
				foreach ($commentData as $comment) {
					$comments[] = $comment;
				}
			}
			$data['friendica_comments'] = $comments;

			// include info if rights on photo and rights on item are mismatching
			$data['rights_mismatch'] = $data['allow_cid'] != $item['allow_cid'] ||
				$data['deny_cid'] != $item['deny_cid'] ||
				$data['allow_gid'] != $item['allow_gid'] ||
				$data['deny_gid'] != $item['deny_gid'];
		} elseif ($with_posts) {
			$data['friendica_activities'] = [];
			$data['friendica_comments']   = [];
			$data['rights_mismatch']      = false;
		}

		return $data;
	}
}
