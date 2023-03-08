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

namespace Friendica\Module\Api\Mastodon;

use Friendica\Content\Text\Markdown;
use Friendica\Core\Protocol;
use Friendica\Core\System;
use Friendica\Core\Worker;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Group;
use Friendica\Model\Item;
use Friendica\Model\Photo;
use Friendica\Model\Post;
use Friendica\Model\User;
use Friendica\Module\BaseApi;
use Friendica\Network\HTTPException;
use Friendica\Protocol\Activity;
use Friendica\Util\Images;

/**
 * @see https://docs.joinmastodon.org/methods/statuses/
 */
class Statuses extends BaseApi
{
	public function put(array $request = [])
	{
		self::checkAllowedScope(self::SCOPE_WRITE);
		$uid = self::getCurrentUserID();

		$request = $this->getRequest([
			'status'         => '',    // Text content of the status. If media_ids is provided, this becomes optional. Attaching a poll is optional while status is provided.
			'in_reply_to_id' => 0,     // ID of the status being replied to, if status is a reply
			'spoiler_text'   => '',    // Text to be shown as a warning or subject before the actual content. Statuses are generally collapsed behind this field.
			'language'       => '',    // ISO 639 language code for this status.
		], $request);

		$owner = User::getOwnerDataById($uid);

		$condition = [
			'uid'        => $uid,
			'uri-id'     => $this->parameters['id'],
			'contact-id' => $owner['id'],
			'author-id'  => Contact::getPublicIdByUserId($uid),
			'origin'     => true,
		];

		$post = Post::selectFirst(['uri-id', 'id'], $condition);
		if (empty($post['id'])) {
			throw new HTTPException\NotFoundException('Item with URI ID ' . $this->parameters['id'] . ' not found for user ' . $uid . '.');
		}

		// The imput is defined as text. So we can use Markdown for some enhancements
		$item = ['body' => Markdown::toBBCode($request['status']), 'app' => $this->getApp(), 'title' => ''];

		if (!empty($request['language'])) {
			$item['language'] = json_encode([$request['language'] => 1]);
		}

		if (!empty($request['spoiler_text'])) {
			if (($request['in_reply_to_id'] == $post['uri-id']) && DI::pConfig()->get($uid, 'system', 'api_spoiler_title', true)) {
				$item['title'] = $request['spoiler_text'];
			} else {
				$item['body'] = '[abstract=' . Protocol::ACTIVITYPUB . ']' . $request['spoiler_text'] . "[/abstract]\n" . $item['body'];
			}
		}

		Item::update($item, ['id' => $post['id']]);
		Item::updateDisplayCache($post['uri-id']);

		System::jsonExit(DI::mstdnStatus()->createFromUriId($post['uri-id'], $uid, self::appSupportsQuotes()));
	}

	protected function post(array $request = [])
	{
		self::checkAllowedScope(self::SCOPE_WRITE);
		$uid = self::getCurrentUserID();

		$request = $this->getRequest([
			'status'         => '',    // Text content of the status. If media_ids is provided, this becomes optional. Attaching a poll is optional while status is provided.
			'media_ids'      => [],    // Array of Attachment ids to be attached as media. If provided, status becomes optional, and poll cannot be used.
			'poll'           => [],    // Poll data. If provided, media_ids cannot be used, and poll[expires_in] must be provided.
			'in_reply_to_id' => 0,     // ID of the status being replied to, if status is a reply
			'quote_id'       => 0,     // ID of the message to quote
			'sensitive'      => false, // Mark status and attached media as sensitive?
			'spoiler_text'   => '',    // Text to be shown as a warning or subject before the actual content. Statuses are generally collapsed behind this field.
			'visibility'     => '',    // Visibility of the posted status. One of: "public", "unlisted", "private" or "direct".
			'scheduled_at'   => '',    // ISO 8601 Datetime at which to schedule a status. Providing this paramter will cause ScheduledStatus to be returned instead of Status. Must be at least 5 minutes in the future.
			'language'       => '',    // ISO 639 language code for this status.
			'friendica'      => [],	   // Friendica extensions to the standard Mastodon API spec
		], $request);

		$owner = User::getOwnerDataById($uid);

		// The imput is defined as text. So we can use Markdown for some enhancements
		$body = Markdown::toBBCode($request['status']);

		$item               = [];
		$item['network']    = Protocol::DFRN;
		$item['uid']        = $uid;
		$item['verb']       = Activity::POST;
		$item['contact-id'] = $owner['id'];
		$item['author-id']  = $item['owner-id'] = Contact::getPublicIdByUserId($uid);
		$item['title']      = '';
		$item['body']       = $body;
		$item['app']        = $this->getApp();

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
				if (!empty($owner['allow_cid'] . $owner['allow_gid'] . $owner['deny_cid'] . $owner['deny_gid'])) {
					$item['allow_cid'] = $owner['allow_cid'];
					$item['allow_gid'] = $owner['allow_gid'];
					$item['deny_cid']  = $owner['deny_cid'];
					$item['deny_gid']  = $owner['deny_gid'];
				} else {
					$item['allow_cid'] = '';
					$item['allow_gid'] = '<' . Group::FOLLOWERS . '>';
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
				if (is_numeric($request['visibility']) && Group::exists($request['visibility'], $uid)) {
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
			$parent = Post::selectFirst(['uri', 'private'], ['uri-id' => $request['in_reply_to_id'], 'uid' => [0, $uid]]);

			$item['thr-parent']  = $parent['uri'];
			$item['gravity']     = Item::GRAVITY_COMMENT;
			$item['object-type'] = Activity\ObjectType::COMMENT;

			if (in_array($parent['private'], [Item::UNLISTED, Item::PUBLIC]) && ($item['private'] == Item::PRIVATE)) {
				throw new HTTPException\NotImplementedException('Private replies for public posts are not implemented.');
			}
		} else {
			self::checkThrottleLimit();

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
			$item['object-type'] = Activity\ObjectType::IMAGE;
			$item['post-type']   = Item::PT_IMAGE;
			$item['attachments'] = [];

			foreach ($request['media_ids'] as $id) {
				$media = DBA::toArray(DBA::p("SELECT `resource-id`, `scale`, `type`, `desc`, `filename`, `datasize`, `width`, `height` FROM `photo`
						WHERE `resource-id` IN (SELECT `resource-id` FROM `photo` WHERE `id` = ?) AND `photo`.`uid` = ?
						ORDER BY `photo`.`width` DESC LIMIT 2", $id, $uid));

				if (empty($media)) {
					continue;
				}

				Photo::setPermissionForRessource($media[0]['resource-id'], $uid, $item['allow_cid'], $item['allow_gid'], $item['deny_cid'], $item['deny_gid']);

				$ressources[] = $media[0]['resource-id'];
				$phototypes = Images::supportedTypes();
				$ext = $phototypes[$media[0]['type']];

				$attachment = ['type' => Post\Media::IMAGE, 'mimetype' => $media[0]['type'],
					'url' => DI::baseUrl() . '/photo/' . $media[0]['resource-id'] . '-' . $media[0]['scale'] . '.' . $ext,
					'size' => $media[0]['datasize'],
					'name' => $media[0]['filename'] ?: $media[0]['resource-id'],
					'description' => $media[0]['desc'] ?? '',
					'width' => $media[0]['width'],
					'height' => $media[0]['height']];

				if (count($media) > 1) {
					$attachment['preview'] = DI::baseUrl() . '/photo/' . $media[1]['resource-id'] . '-' . $media[1]['scale'] . '.' . $ext;
					$attachment['preview-width'] = $media[1]['width'];
					$attachment['preview-height'] = $media[1]['height'];
				}
				$item['attachments'][] = $attachment;
			}
		}

		if (!empty($request['scheduled_at'])) {
			$item['guid'] = Item::guid($item, true);
			$item['uri'] = Item::newURI($item['guid']);
			$id = Post\Delayed::add($item['uri'], $item, Worker::PRIORITY_HIGH, Post\Delayed::PREPARED, $request['scheduled_at']);
			if (empty($id)) {
				DI::mstdnError()->InternalError();
			}
			System::jsonExit(DI::mstdnScheduledStatus()->createFromDelayedPostId($id, $uid)->toArray());
		}

		$id = Item::insert($item, true);
		if (!empty($id)) {
			$item = Post::selectFirst(['uri-id'], ['id' => $id]);
			if (!empty($item['uri-id'])) {
				System::jsonExit(DI::mstdnStatus()->createFromUriId($item['uri-id'], $uid, self::appSupportsQuotes()));
			}
		}

		DI::mstdnError()->InternalError();
	}

	protected function delete(array $request = [])
	{
		self::checkAllowedScope(self::SCOPE_READ);
		$uid = self::getCurrentUserID();

		if (empty($this->parameters['id'])) {
			DI::mstdnError()->UnprocessableEntity();
		}

		$item = Post::selectFirstForUser($uid, ['id'], ['uri-id' => $this->parameters['id'], 'uid' => $uid]);
		if (empty($item['id'])) {
			DI::mstdnError()->RecordNotFound();
		}

		if (!Item::markForDeletionById($item['id'])) {
			DI::mstdnError()->RecordNotFound();
		}

		System::jsonExit([]);
	}

	/**
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	protected function rawContent(array $request = [])
	{
		$uid = self::getCurrentUserID();

		if (empty($this->parameters['id'])) {
			DI::mstdnError()->UnprocessableEntity();
		}

		System::jsonExit(DI::mstdnStatus()->createFromUriId($this->parameters['id'], $uid, self::appSupportsQuotes(), false));
	}

	private function getApp(): string
	{
		if (!empty(self::getCurrentApplication()['name'])) {
			return self::getCurrentApplication()['name'];
		} else {
			return 'API';
		}
	}
}
