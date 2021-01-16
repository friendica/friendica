<?php
/**
 * @copyright Copyright (C) 2020, Friendica
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
use Friendica\Content\Text\BBCode;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Model\Verb;
use Friendica\Network\HTTPException;
use Friendica\Protocol\Activity;
use Friendica\Repository\ProfileField;
use Psr\Log\LoggerInterface;

class Status extends BaseFactory
{
	/** @var BaseURL */
	protected $baseUrl;
	/** @var ProfileField */
	protected $profileField;
	/** @var Field */
	protected $mstdnField;

	public function __construct(LoggerInterface $logger, BaseURL $baseURL, ProfileField $profileField, Field $mstdnField)
	{
		parent::__construct($logger);

		$this->baseUrl = $baseURL;
		$this->profileField = $profileField;
		$this->mstdnField = $mstdnField;
	}

	/**
	 * @param int $uriId Uri-ID of the item
	 * @param int $uid   Item user
	 * @return \Friendica\Object\Api\Mastodon\Status
	 * @throws HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public function createFromUriId(int $uriId, $uid = 0)
	{
		$item = Post::selectFirst([], ['uri-id' => $uriId, 'uid' => $uid]);
		if (!$item) {
			throw new HTTPException\NotFoundException('Item with URI ID ' . $uriId . 'not found' . ($uid ? ' for user ' . $uid : '.'));
		}

		$account = DI::mstdnAccount()->createFromContactId($item['author-id']);

		$counts = new \Friendica\Object\Api\Mastodon\Status\Counts(
			DBA::count('item', ['thr-parent-id' => $uriId, 'uid' => $uid, 'gravity' => GRAVITY_COMMENT]),
			DBA::count('item', ['thr-parent-id' => $uriId, 'uid' => $uid, 'gravity' => GRAVITY_ACTIVITY, 'vid' => Verb::getID(Activity::ANNOUNCE)]),
			DBA::count('item', ['thr-parent-id' => $uriId, 'uid' => $uid, 'gravity' => GRAVITY_ACTIVITY, 'vid' => Verb::getID(Activity::LIKE)])
		);

		$userAttributes = new \Friendica\Object\Api\Mastodon\Status\UserAttributes(
			DBA::exists('item', ['thr-parent-id' => $uriId, 'uid' => $uid, 'origin' => true, 'gravity' => GRAVITY_ACTIVITY, 'vid' => Verb::getID(Activity::LIKE)]),
			DBA::exists('item', ['thr-parent-id' => $uriId, 'uid' => $uid, 'origin' => true, 'gravity' => GRAVITY_ACTIVITY, 'vid' => Verb::getID(Activity::ANNOUNCE)]),
			DBA::exists('thread', ['iid' => $item['id'], 'uid' => $item['uid'], 'ignored' => true]),
			(bool)$item['starred'],
			DBA::exists('user-item', ['iid' => $item['id'], 'uid' => $item['uid'], 'pinned' => true])
		);

		$sensitive = DBA::exists('tag-view', ['uri-id' => $uriId, 'name' => 'nsfw']);
		$application = new \Friendica\Object\Api\Mastodon\Application($item['app'] ?? '');
		$mentions = DI::mstdnMention()->createFromUriId($uriId);
		$tags = DI::mstdnTag()->createFromUriId($uriId);

		$data = BBCode::getAttachmentData($item['body']);
		$card = new \Friendica\Object\Api\Mastodon\Card($data);

		$attachments = DI::mstdnAttachment()->createFromUriId($uriId);

		if ($item['vid'] == Verb::getID(Activity::ANNOUNCE)) {
			$reshare = $this->createFromUriId($item['thr-parent-id'], $uid)->toArray();
			$reshared_item = Post::selectFirst(['title', 'body'], ['uri-id' => $item['thr-parent-id'], 'uid' => $uid]);
			$item['title'] = $reshared_item['title'] ?? $item['title'];
			$item['body'] = $reshared_item['body'] ?? $item['body'];
		} else {
			$reshare = [];
		}

		return new \Friendica\Object\Api\Mastodon\Status($item, $account, $counts, $userAttributes, $sensitive, $application, $mentions, $tags, $card, $attachments, $reshare);
	}
}
