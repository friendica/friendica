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

namespace Friendica\Module\Api\Mastodon;

use Friendica\Content\PageInfo;
use Friendica\Content\Text\BBCode;
use Friendica\Content\Text\Markdown;
use Friendica\Core\Protocol;
use Friendica\Core\Worker;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Attach;
use Friendica\Model\Contact;
use Friendica\Model\Circle;
use Friendica\Model\Item;
use Friendica\Model\Photo;
use Friendica\Model\Post;
use Friendica\Model\Tag;
use Friendica\Model\User;
use Friendica\Module\BaseApi;
use Friendica\Network\HTTPException;
use Friendica\Protocol\Activity;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Images;

/**
 * @see https://docs.joinmastodon.org/methods/statuses/
 */
class Statuses extends BaseApi
{
	public function put(array $request = [])
	{
		$this->checkAllowedScope(self::SCOPE_WRITE);
		$uid = self::getCurrentUserID();

		$request = $this->getRequest([
			'status'           => '',    // Text content of the status. If media_ids is provided, this becomes optional. Attaching a poll is optional while status is provided.
			'media_ids'        => [],    // Array of Attachment ids to be attached as media. If provided, status becomes optional, and poll cannot be used.
			'in_reply_to_id'   => 0,     // ID of the status being replied to, if status is a reply
			'sensitive'        => false, // Mark status and attached media as sensitive? Defaults to false.
			'spoiler_text'     => '',    // Text to be shown as a warning or subject before the actual content. Statuses are generally collapsed behind this field.
			'language'         => '',    // ISO 639 language code for this status.
			'media_attributes' => [],
			'friendica'        => [],
		], $request);

		$owner = User::getOwnerDataById($uid);

		$condition = [
			'uid'        => $uid,
			'uri-id'     => $this->parameters['id'],
			'contact-id' => $owner['id'],
			'author-id'  => Contact::getPublicIdByUserId($uid),
			'origin'     => true,
		];

		$post = Post::selectFirst(['uri-id', 'id', 'gravity', 'verb', 'uid', 'allow_cid', 'allow_gid', 'deny_cid', 'deny_gid', 'network'], $condition);
		if (empty($post['id'])) {
			throw new HTTPException\NotFoundException('Item with URI ID ' . $this->parameters['id'] . ' not found for user ' . $uid . '.');
		}

		$item['title']      = '';
		$item['uid']        = $post['uid'];
		$item['body']       = $this->formatStatus($request['status'], $uid);
		$item['network']    = $post['network'];
		$item['gravity']    = $post['gravity'];
		$item['verb']       = $post['verb'];
		$item['allow_cid']  = $post['allow_cid'];
		$item['allow_gid']  = $post['allow_gid'];
		$item['deny_cid']   = $post['deny_cid'];
		$item['deny_gid']   = $post['deny_gid'];
		$item['app']        = $this->getApp();
		$item['sensitive']  = $request['sensitive'];

		if (!empty($request['language'])) {
			$item['language'] = json_encode([$request['language'] => 1]);
		}

		if ($post['gravity'] == Item::GRAVITY_PARENT) {
			$item['title'] = $request['friendica']['title'] ?? '';
		}

		$spoiler_text = $request['spoiler_text'];

		if (!empty($spoiler_text)) {
			if (!isset($request['friendica']['title']) && $post['gravity'] == Item::GRAVITY_PARENT && DI::pConfig()->get($uid, 'system', 'api_spoiler_title', true)) {
				$item['title'] = $spoiler_text;
			} else {
				$item['body'] = '[abstract=' . Protocol::ACTIVITYPUB . ']' . $spoiler_text . "[/abstract]\n" . $item['body'];
				$item['content-warning'] = BBCode::toPlaintext($spoiler_text);
			}
		}

		$item = DI::contentItem()->expandTags($item);

		/*
		The provided ids in the request value consists of these two sources:
		- The id in the "photo" table for newly uploaded media
		- The id in the "post-media" table for already attached media

		Because of this we have to add all media that isn't already attached.
		Also we have to delete all media that isn't provided anymore.

		There is a possible situation where the newly uploaded media
		could have the same id as an existing, but deleted media.

		We can't do anything about this, but the probability for this is extremely low.
		*/
		$media_ids      = [];
		$existing_media = array_column(Post\Media::getByURIId($post['uri-id'], [Post\Media::AUDIO, Post\Media::VIDEO, Post\Media::IMAGE]), 'id');

		foreach ($request['media_attributes'] as $attributes) {
			if (!empty($attributes['id']) && in_array($attributes['id'], $existing_media)) {
				Post\Media::updateById(['description' => $attributes['description'] ?? null], $attributes['id']);
			}
		}

		foreach ($request['media_ids'] as $media) {
			if (!in_array($media, $existing_media)) {
				$media_ids[] = $media;
			}
		}

		foreach ($existing_media as $media) {
			if (!in_array($media, $request['media_ids'])) {
				Post\Media::deleteById($media);
			}
		}

		$item = $this->storeMediaIds($media_ids, array_merge($post, $item));

		foreach ($item['attachments'] as $attachment) {
			$attachment['uri-id'] = $post['uri-id'];
			Post\Media::insert($attachment);
		}
		unset($item['attachments']);

		if (!Item::isValid($item)) {
			throw new \Exception('Missing parameters in definition');
		}

		// Link Preview Attachment Processing
		Post\Media::deleteByURIId($post['uri-id'], [Post\Media::HTML]);

		Item::update($item, ['id' => $post['id']]);

		foreach (Tag::getByURIId($post['uri-id']) as $tagToRemove) {
			Tag::remove($post['uri-id'], $tagToRemove['type'], $tagToRemove['name'], $tagToRemove['url']);
		}
		// Store tags from the body if this hadn't been handled previously in the protocol classes

		Tag::storeFromBody($post['uri-id'], Item::setHashtags($item['body']));

		Item::updateDisplayCache($post['uri-id']);

		$this->jsonExit(DI::mstdnStatus()->createFromUriId($post['uri-id'], $uid, self::appSupportsQuotes()));
	}

	protected function post(array $request = [])
	{
		$this->checkAllowedScope(self::SCOPE_WRITE);
		$uid = self::getCurrentUserID();

		$request = $this->getRequest([
			'status'         => '',    // Text content of the status. If media_ids is provided, this becomes optional. Attaching a poll is optional while status is provided.
			'media_ids'      => [],    // Array of Attachment ids to be attached as media. If provided, status becomes optional, and poll cannot be used.
			'poll'           => [],    // Poll data. If provided, media_ids cannot be used, and poll[expires_in] must be provided.
			'in_reply_to_id' => 0,     // ID of the status being replied to, if status is a reply
			'quote_id'       => 0,     // ID of the message to quote
			'sensitive'      => false, // Mark status and attached media as sensitive? Defaults to false.
			'spoiler_text'   => '',    // Text to be shown as a warning or subject before the actual content. Statuses are generally collapsed behind this field.
			'visibility'     => '',    // Visibility of the posted status. One of: "public", "unlisted", "private" or "direct".
			'scheduled_at'   => '',    // ISO 8601 Datetime at which to schedule a status. Providing this parameter will cause ScheduledStatus to be returned instead of Status. Must be at least 5 minutes in the future.
			'language'       => '',    // ISO 639 language code for this status.
			'friendica'      => [],	   // Friendica extensions to the standard Mastodon API spec
		], $request);

		$owner = User::getOwnerDataById($uid);

		$item               = [];
		$item['network']    = Protocol::DFRN;
		$item['uid']        = $uid;
		$item['verb']       = Activity::POST;
		$item['contact-id'] = $owner['id'];
		$item['author-id']  = $item['owner-id'] = Contact::getPublicIdByUserId($uid);
		$item['title']      = '';
		$item['body']       = $this->formatStatus($request['status'], $uid);
		$item['app']        = $this->getApp();
		$item['sensitive']  = $request['sensitive'];
		$item['visibility'] = $request['visibility'];

		switch ($request['visibility']) {
			case 'public':
				$item['allow_cid'] = '';
				$item['allow_gid'] = '';
				$item['deny_cid']  = '';
				$item['deny_gid']  = '';
				$item['private']   = Item::PUBLIC;
				break;
			case 'unlisted':
				$item['allow_cid'] = '';
				$item['allow_gid'] = '';
				$item['deny_cid']  = '';
				$item['deny_gid']  = '';
				$item['private']   = Item::UNLISTED;
				break;
			case 'private':
				if ($request['in_reply_to_id']) {
					$parent_item = Post::selectFirst(Item::ITEM_FIELDLIST, ['uri-id' => $request['in_reply_to_id'], 'uid' => $uid, 'private' => Item::PRIVATE]);
					if (!empty($parent_item)) {
						$item['allow_cid'] = $parent_item['allow_cid'];
						$item['allow_gid'] = $parent_item['allow_gid'];
						$item['deny_cid']  = $parent_item['deny_cid'];
						$item['deny_gid']  = $parent_item['deny_gid'];
						$item['private']   = $parent_item['private'];
						break;
					}
				}

				if (!empty($owner['allow_cid'] . $owner['allow_gid'] . $owner['deny_cid'] . $owner['deny_gid'])) {
					$item['allow_cid'] = $owner['allow_cid'];
					$item['allow_gid'] = $owner['allow_gid'];
					$item['deny_cid']  = $owner['deny_cid'];
					$item['deny_gid']  = $owner['deny_gid'];
				} else {
					$item['allow_cid'] = '';
					$item['allow_gid'] = '<' . Circle::FOLLOWERS . '>';
					$item['deny_cid']  = '';
					$item['deny_gid']  = '';
				}
				$item['private'] = Item::PRIVATE;
				break;
			case 'direct':
				$item['private'] = Item::PRIVATE;
				// The permissions are assigned in "expandTags"
				break;
			default:
				if (is_numeric($request['visibility']) && Circle::exists($request['visibility'], $uid)) {
					$item['allow_cid'] = '';
					$item['allow_gid'] = '<' . $request['visibility'] . '>';
					$item['deny_cid']  = '';
					$item['deny_gid']  = '';
				} else {
					$item['allow_cid'] = $owner['allow_cid'];
					$item['allow_gid'] = $owner['allow_gid'];
					$item['deny_cid']  = $owner['deny_cid'];
					$item['deny_gid']  = $owner['deny_gid'];
				}

				if (!empty($item['allow_cid'] . $item['allow_gid'] . $item['deny_cid'] . $item['deny_gid'])) {
					$item['private'] = Item::PRIVATE;
				} elseif (DI::pConfig()->get($uid, 'system', 'unlisted')) {
					$item['private'] = Item::UNLISTED;
				} else {
					$item['private'] = Item::PUBLIC;
				}
				break;
		}

		if (!empty($request['language'])) {
			$item['language'] = json_encode([$request['language'] => 1]);
		}

		if ($request['in_reply_to_id']) {
			$parent = Post::selectOriginal(['uri'], ['uri-id' => $request['in_reply_to_id'], 'uid' => [0, $uid]]);
			if (empty($parent)) {
				throw new HTTPException\NotFoundException('Item with URI ID ' . $request['in_reply_to_id'] . ' not found for user ' . $uid . '.');
			}

			$item['thr-parent']  = $parent['uri'];
			$item['gravity']     = Item::GRAVITY_COMMENT;
			$item['object-type'] = Activity\ObjectType::COMMENT;
		} else {
			$this->checkThrottleLimit();

			$item['gravity']     = Item::GRAVITY_PARENT;
			$item['object-type'] = Activity\ObjectType::NOTE;
		}

		if ($request['quote_id']) {
			if (!Post::exists(['uri-id' => $request['quote_id'], 'uid' => [0, $uid]])) {
				throw new HTTPException\NotFoundException('Item with URI ID ' . $request['quote_id'] . ' not found for user ' . $uid . '.');
			}
			$item['quote-uri-id'] = $request['quote_id'];
		}

		$item['title'] = $request['friendica']['title'] ?? '';

		if (!empty($request['spoiler_text'])) {
			if (!isset($request['friendica']['title']) && !$request['in_reply_to_id'] && DI::pConfig()->get($uid, 'system', 'api_spoiler_title', true)) {
				$item['title'] = $request['spoiler_text'];
			} else {
				$item['body'] = '[abstract=' . Protocol::ACTIVITYPUB . ']' . $request['spoiler_text'] . "[/abstract]\n" . $item['body'];
			}
		}

		$item = DI::contentItem()->expandTags($item, $request['visibility'] == 'direct');

		if (!empty($request['media_ids'])) {
			$item = $this->storeMediaIds($request['media_ids'], $item);
		}

		if (!empty($request['scheduled_at'])) {
			$item['guid'] = Item::guid($item, true);
			$item['uri'] = Item::newURI($item['guid']);
			$id = Post\Delayed::add($item['uri'], $item, Worker::PRIORITY_HIGH, Post\Delayed::PREPARED, DateTimeFormat::utc($request['scheduled_at']));
			if (empty($id)) {
				$this->logAndJsonError(500, $this->errorFactory->InternalError());
			}
			$this->jsonExit(DI::mstdnScheduledStatus()->createFromDelayedPostId($id, $uid)->toArray());
		}

		$id = Item::insert($item, true);
		if (!empty($id)) {
			$item = Post::selectFirst(['uri-id'], ['id' => $id]);
			if (!empty($item['uri-id'])) {
				$this->jsonExit(DI::mstdnStatus()->createFromUriId($item['uri-id'], $uid, self::appSupportsQuotes()));
			}
		}

		$this->logAndJsonError(500, $this->errorFactory->InternalError());
	}

	protected function delete(array $request = [])
	{
		$this->checkAllowedScope(self::SCOPE_READ);
		$uid = self::getCurrentUserID();

		if (empty($this->parameters['id'])) {
			$this->logAndJsonError(422, $this->errorFactory->UnprocessableEntity());
		}

		$item = Post::selectFirstForUser($uid, ['id'], ['uri-id' => $this->parameters['id'], 'uid' => $uid]);
		if (empty($item['id'])) {
			$this->logAndJsonError(404, $this->errorFactory->RecordNotFound());
		}

		if (!Item::markForDeletionById($item['id'])) {
			$this->logAndJsonError(404, $this->errorFactory->RecordNotFound());
		}

		$this->jsonExit([]);
	}

	/**
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	protected function rawContent(array $request = [])
	{
		$uid = self::getCurrentUserID();

		if (empty($this->parameters['id'])) {
			$this->logAndJsonError(422, $this->errorFactory->UnprocessableEntity());
		}

		$this->jsonExit(DI::mstdnStatus()->createFromUriId($this->parameters['id'], $uid, self::appSupportsQuotes(), false));
	}

	private function getApp(): string
	{
		if (!empty(self::getCurrentApplication()['name'])) {
			return self::getCurrentApplication()['name'];
		} else {
			return 'API';
		}
	}

	/**
	 * Store provided media ids in the item array and adjust permissions
	 *
	 * @param array $media_ids
	 * @param array $item
	 * @return array
	 */
	private function storeMediaIds(array $media_ids, array $item): array
	{
		$item['object-type'] = Activity\ObjectType::IMAGE;
		$item['post-type']   = Item::PT_IMAGE;
		$item['attachments'] = [];

		foreach ($media_ids as $id) {
			if (DI::mstdnAttachment()->isAttach($id) && Attach::exists(['id' => substr($id, 7)])) {
				$attach = Attach::selectFirst([], ['id' => substr($id, 7)]);
				$attachment = [
					'type'     => Post\Media::getType($attach['filetype']),
					'mimetype' => $attach['filetype'],
					'url'      => DI::baseUrl() . '/attach/' . substr($id, 7),
					'size'     => $attach['filetype'],
					'name'     => $attach['filename']
				];
				$item['attachments'][] = $attachment;
				Attach::setPermissionForId(substr($id, 7), $item['uid'], $item['allow_cid'], $item['allow_gid'], $item['deny_cid'], $item['deny_gid']);
				continue;
			}

			$media = DBA::toArray(DBA::p("SELECT `resource-id`, `scale`, `type`, `desc`, `filename`, `datasize`, `width`, `height` FROM `photo`
					WHERE `resource-id` IN (SELECT `resource-id` FROM `photo` WHERE `id` = ?) AND `photo`.`uid` = ?
					ORDER BY `photo`.`width` DESC LIMIT 2", $id, $item['uid']));

			if (empty($media)) {
				continue;
			}

			Photo::setPermissionForResource($media[0]['resource-id'], $item['uid'], $item['allow_cid'], $item['allow_gid'], $item['deny_cid'], $item['deny_gid']);

			$ext = Images::getExtensionByMimeType($media[0]['type']);

			$attachment = [
				'type'        => Post\Media::IMAGE,
				'mimetype'    => $media[0]['type'],
				'url'         => DI::baseUrl() . '/photo/' . $media[0]['resource-id'] . '-' . $media[0]['scale'] . $ext,
				'size'        => $media[0]['datasize'],
				'name'        => $media[0]['filename'] ?: $media[0]['resource-id'],
				'description' => $media[0]['desc'] ?? '',
				'width'       => $media[0]['width'],
				'height'      => $media[0]['height']
			];

			if (count($media) > 1) {
				$attachment['preview'] = DI::baseUrl() . '/photo/' . $media[1]['resource-id'] . '-' . $media[1]['scale'] . $ext;
				$attachment['preview-width'] = $media[1]['width'];
				$attachment['preview-height'] = $media[1]['height'];
			}
			$item['attachments'][] = $attachment;
		}
		return $item;
	}

	/**
	 * Format the status via Markdown and a link description if enabled for this user
	 *
	 * @param string $status
	 * @param integer $uid
	 * @return string
	 */
	private function formatStatus(string $status, int $uid): string
	{
		// The input is defined as text. So we can use Markdown for some enhancements
		$status = Markdown::toBBCode($status);

		if (!DI::pConfig()->get($uid, 'system', 'api_auto_attach', false)) {
			return $status;
		}

		$status = BBCode::expandVideoLinks($status);
		if (preg_match("/\[url=[^\[\]]*\](.*)\[\/url\]\z/ism", $status, $matches)) {
			$status = preg_replace("/\[url=[^\[\]]*\].*\[\/url\]\z/ism", PageInfo::getFooterFromUrl($matches[1]), $status);
		}

		return $status;
	}
}
