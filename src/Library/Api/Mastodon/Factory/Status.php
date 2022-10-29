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

namespace Friendica\Library\Api\Mastodon\Factory;

use Friendica\Library\BaseFactory;
use Friendica\Content\ContactSelector;
use Friendica\Content\Item as ContentItem;
use Friendica\Database\Database;
use Friendica\Database\DBA;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Model\Tag as TagModel;
use Friendica\Model\Verb;
use Friendica\Network\HTTPException;
use Friendica\Protocol\Activity;
use Friendica\Protocol\ActivityPub;
use ImagickException;
use Psr\Log\LoggerInterface;

class Status extends BaseFactory
{
	/** @var Database */
	private $dba;
	/** @var Account */
	private $mstdnAccountFactory;
	/** @var Mention */
	private $mstdnMentionFactory;
	/** @var Tag */
	private $mstdnTagFactory;
	/** @var Card */
	private $mstdnCardFactory;
	/** @var Attachment */
	private $mstdnAttachementFactory;
	/** @var Error */
	private $mstdnErrorFactory;
	/** @var Poll */
	private $mstdnPollFactory;
	/** @var ContentItem */
	private $contentItem;

	public function __construct(LoggerInterface $logger, Database $dba,
		Account $mstdnAccountFactory, Mention $mstdnMentionFactory,
		Tag $mstdnTagFactory, Card $mstdnCardFactory,
		Attachment $mstdnAttachementFactory, Error $mstdnErrorFactory,
		Poll $mstdnPollFactory, ContentItem $contentItem)
	{
		parent::__construct($logger);
		$this->dba                     = $dba;
		$this->mstdnAccountFactory     = $mstdnAccountFactory;
		$this->mstdnMentionFactory     = $mstdnMentionFactory;
		$this->mstdnTagFactory         = $mstdnTagFactory;
		$this->mstdnCardFactory        = $mstdnCardFactory;
		$this->mstdnAttachementFactory = $mstdnAttachementFactory;
		$this->mstdnErrorFactory       = $mstdnErrorFactory;
		$this->mstdnPollFactory        = $mstdnPollFactory;
		$this->contentItem             = $contentItem;
	}

	/**
	 * @param int $uriId Uri-ID of the item
	 * @param int $uid   Item user
	 *
	 * @return \Friendica\Library\Api\Mastodon\Object\Status
	 * @throws HTTPException\InternalServerErrorException
	 * @throws ImagickException|HTTPException\NotFoundException
	 */
	public function createFromUriId(int $uriId, int $uid = 0): \Friendica\Library\Api\Mastodon\Object\Status
	{
		$fields = ['uri-id', 'uid', 'author-id', 'author-uri-id', 'author-link', 'starred', 'app', 'title', 'body', 'raw-body', 'content-warning', 'question-id',
			'created', 'network', 'thr-parent-id', 'parent-author-id', 'language', 'uri', 'plink', 'private', 'vid', 'gravity', 'featured', 'has-media'];
		$item = Post::selectFirst($fields, ['uri-id' => $uriId, 'uid' => [0, $uid]], ['order' => ['uid' => true]]);
		if (!$item) {
			$mail = DBA::selectFirst('mail', ['id'], ['uri-id' => $uriId, 'uid' => $uid]);
			if ($mail) {
				return $this->createFromMailId($mail['id']);
			}
			throw new HTTPException\NotFoundException('Item with URI ID ' . $uriId . ' not found' . ($uid ? ' for user ' . $uid : '.'));
		}
		$account = $this->mstdnAccountFactory->createFromUriId($item['author-uri-id'], $uid);

		$count_announce = Post::countPosts([
			'thr-parent-id' => $uriId,
			'gravity'       => Item::GRAVITY_ACTIVITY,
			'vid'           => Verb::getID(Activity::ANNOUNCE),
			'deleted'       => false
		], []);
		$count_like = Post::countPosts([
			'thr-parent-id' => $uriId,
			'gravity'       => Item::GRAVITY_ACTIVITY,
			'vid'           => Verb::getID(Activity::LIKE),
			'deleted'       => false
		], []);

		$counts = new \Friendica\Library\Api\Mastodon\Status\Object\Counts(
			Post::countPosts(['thr-parent-id' => $uriId, 'gravity' => Item::GRAVITY_COMMENT, 'deleted' => false], []),
			$count_announce,
			$count_like
		);

		$origin_like = ($count_like == 0) ? false : Post::exists([
			'thr-parent-id' => $uriId,
			'uid'           => $uid,
			'origin'        => true,
			'gravity'       => Item::GRAVITY_ACTIVITY,
			'vid'           => Verb::getID(Activity::LIKE),
			'deleted'     => false
		]);
		$origin_announce = ($count_announce == 0) ? false : Post::exists([
			'thr-parent-id' => $uriId,
			'uid'           => $uid,
			'origin'        => true,
			'gravity'       => Item::GRAVITY_ACTIVITY,
			'vid'           => Verb::getID(Activity::ANNOUNCE),
			'deleted'       => false
		]);
		$userAttributes = new \Friendica\Library\Api\Mastodon\Status\Object\UserAttributes(
			$origin_like,
			$origin_announce,
			Post\ThreadUser::getIgnored($uriId, $uid),
			(bool)($item['starred'] && ($item['gravity'] == Item::GRAVITY_PARENT)),
			$item['featured']
		);

		$sensitive   = $this->dba->exists('tag-view', ['uri-id' => $uriId, 'name' => 'nsfw', 'type' => TagModel::HASHTAG]);
		$application = new \Friendica\Library\Api\Mastodon\Object\Application($item['app'] ?: ContactSelector::networkToName($item['network'], $item['author-link']));

		$mentions    = $this->mstdnMentionFactory->createFromUriId($uriId)->getArrayCopy();
		$tags        = $this->mstdnTagFactory->createFromUriId($uriId);
		if ($item['has-media']) {
			$card        = $this->mstdnCardFactory->createFromUriId($uriId);
			$attachments = $this->mstdnAttachementFactory->createFromUriId($uriId);
		} else {
			$card        = new \Friendica\Library\Api\Mastodon\Object\Card([]);
			$attachments = [];
		}

		if (!empty($item['question-id'])) {
			$poll = $this->mstdnPollFactory->createFromId($item['question-id'], $uid)->toArray();
		} else {
			$poll = null;
		}

		$shared = $this->contentItem->getSharedPost($item, ['uri-id']);
		if (!empty($shared)) {
			$shared_uri_id = $shared['post']['uri-id'];

			$mentions    = array_merge($mentions, $this->mstdnMentionFactory->createFromUriId($shared_uri_id)->getArrayCopy());
			$tags        = array_merge($tags, $this->mstdnTagFactory->createFromUriId($shared_uri_id));
			$attachments = array_merge($attachments, $this->mstdnAttachementFactory->createFromUriId($shared_uri_id));

			if (empty($card->toArray())) {
				$card = $this->mstdnCardFactory->createFromUriId($shared_uri_id);
			}
		}

		if ($item['vid'] == Verb::getID(Activity::ANNOUNCE)) {
			$reshare       = $this->createFromUriId($item['thr-parent-id'], $uid)->toArray();
			$reshared_item = Post::selectFirst(['title', 'body'], ['uri-id' => $item['thr-parent-id'],'uid' => [0, $uid]]);
			$item['title'] = $reshared_item['title'] ?? $item['title'];
			$item['body']  = $reshared_item['body'] ?? $item['body'];
		} else {
			$reshare = [];
		}

		return new \Friendica\Library\Api\Mastodon\Object\Status($item, $account, $counts, $userAttributes, $sensitive, $application, $mentions, $tags, $card, $attachments, $reshare, $poll);
	}

	/**
	 * @param int $uriId id of the mail
	 *
	 * @return \Friendica\Library\Api\Mastodon\Object\Status
	 * @throws HTTPException\InternalServerErrorException
	 * @throws ImagickException|HTTPException\NotFoundException
	 */
	public function createFromMailId(int $id): \Friendica\Library\Api\Mastodon\Object\Status
	{
		$item = ActivityPub\Transmitter::getItemArrayFromMail($id, true);
		if (empty($item)) {
			$this->mstdnErrorFactory->RecordNotFound();
		}

		$account = $this->mstdnAccountFactory->createFromContactId($item['author-id']);

		$replies = $this->dba->count('mail', ['thr-parent-id' => $item['uri-id'], 'reply' => true]);

		$counts = new \Friendica\Library\Api\Mastodon\Status\Object\Counts($replies, 0, 0);

		$userAttributes = new \Friendica\Library\Api\Mastodon\Status\Object\UserAttributes(false, false, false, false, false);

		$sensitive   = false;
		$application = new \Friendica\Library\Api\Mastodon\Object\Application('');
		$mentions    = [];
		$tags        = [];
		$card        = new \Friendica\Library\Api\Mastodon\Object\Card([]);
		$attachments = [];
		$reshare     = [];

		return new \Friendica\Library\Api\Mastodon\Object\Status($item, $account, $counts, $userAttributes, $sensitive, $application, $mentions, $tags, $card, $attachments, $reshare);
	}
}
