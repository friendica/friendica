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

namespace Friendica\Factory\Api\Mastodon;

use Friendica\BaseFactory;
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
	 * @param int  $uriId  Uri-ID of the item
	 * @param int  $uid    Item user
	 * @param bool $reblog Check for reblogged post
	 *
	 * @return \Friendica\Object\Api\Mastodon\Status
	 * @throws HTTPException\InternalServerErrorException
	 * @throws ImagickException|HTTPException\NotFoundException
	 */
	public function createFromUriId(int $uriId, int $uid = 0, bool $reblog = true): \Friendica\Object\Api\Mastodon\Status
	{
		$fields = ['uri-id', 'uid', 'author-id', 'author-uri-id', 'author-link', 'causer-uri-id', 'post-reason', 'starred', 'app', 'title', 'body', 'raw-body', 'content-warning', 'question-id',
			'created', 'network', 'thr-parent-id', 'parent-author-id', 'language', 'uri', 'plink', 'private', 'vid', 'gravity', 'featured', 'has-media', 'quote-uri-id'];
		$item = Post::selectFirst($fields, ['uri-id' => $uriId, 'uid' => [0, $uid]], ['order' => ['uid' => true]]);
		if (!$item) {
			$mail = DBA::selectFirst('mail', ['id'], ['uri-id' => $uriId, 'uid' => $uid]);
			if ($mail) {
				return $this->createFromMailId($mail['id']);
			}
			throw new HTTPException\NotFoundException('Item with URI ID ' . $uriId . ' not found' . ($uid ? ' for user ' . $uid : '.'));
		}

		$is_reshare = $reblog && !is_null($item['causer-uri-id']) && ($item['post-reason'] == Item::PR_ANNOUNCEMENT);

		$account = $this->mstdnAccountFactory->createFromUriId($is_reshare ? $item['causer-uri-id']:$item['author-uri-id'], $uid);

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

		$counts = new \Friendica\Object\Api\Mastodon\Status\Counts(
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
		$userAttributes = new \Friendica\Object\Api\Mastodon\Status\UserAttributes(
			$origin_like,
			$origin_announce,
			Post\ThreadUser::getIgnored($uriId, $uid),
			(bool)($item['starred'] && ($item['gravity'] == Item::GRAVITY_PARENT)),
			$item['featured']
		);

		$sensitive   = $this->dba->exists('tag-view', ['uri-id' => $uriId, 'name' => 'nsfw', 'type' => TagModel::HASHTAG]);
		$application = new \Friendica\Object\Api\Mastodon\Application($item['app'] ?: ContactSelector::networkToName($item['network'], $item['author-link']));

		$mentions    = $this->mstdnMentionFactory->createFromUriId($uriId)->getArrayCopy();
		$tags        = $this->mstdnTagFactory->createFromUriId($uriId);
		if ($item['has-media']) {
			$card        = $this->mstdnCardFactory->createFromUriId($uriId);
			$attachments = $this->mstdnAttachementFactory->createFromUriId($uriId);
		} else {
			$card        = new \Friendica\Object\Api\Mastodon\Card([]);
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

			foreach ($this->mstdnMentionFactory->createFromUriId($shared_uri_id)->getArrayCopy() as $mention) {
				if (!in_array($mention, $mentions)) {
					$mentions[] = $mention;
				}
			}

			foreach ($this->mstdnTagFactory->createFromUriId($shared_uri_id) as $tag) {
				if (!in_array($tag, $tags)) {
					$tags[] = $tag;
				}
			}

			foreach ($this->mstdnAttachementFactory->createFromUriId($shared_uri_id) as $attachment) {
				if (!in_array($attachment, $attachments)) {
					$attachments[] = $attachment;
				}
			}

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
			$item['body']     = $this->contentItem->addSharedPost($item);
			$item['raw-body'] = $this->contentItem->addSharedPost($item, $item['raw-body']);
			$reshare = [];
		}

		if ($is_reshare) {
			$reshare = $this->createFromUriId($uriId, $uid, false)->toArray();
		}

		return new \Friendica\Object\Api\Mastodon\Status($item, $account, $counts, $userAttributes, $sensitive, $application, $mentions, $tags, $card, $attachments, $reshare, $poll);
	}

	/**
	 * @param int $uriId id of the mail
	 *
	 * @return \Friendica\Object\Api\Mastodon\Status
	 * @throws HTTPException\InternalServerErrorException
	 * @throws ImagickException|HTTPException\NotFoundException
	 */
	public function createFromMailId(int $id): \Friendica\Object\Api\Mastodon\Status
	{
		$item = ActivityPub\Transmitter::getItemArrayFromMail($id, true);
		if (empty($item)) {
			$this->mstdnErrorFactory->RecordNotFound();
		}

		$account = $this->mstdnAccountFactory->createFromContactId($item['author-id']);

		$replies = $this->dba->count('mail', ['thr-parent-id' => $item['uri-id'], 'reply' => true]);

		$counts = new \Friendica\Object\Api\Mastodon\Status\Counts($replies, 0, 0);

		$userAttributes = new \Friendica\Object\Api\Mastodon\Status\UserAttributes(false, false, false, false, false);

		$sensitive   = false;
		$application = new \Friendica\Object\Api\Mastodon\Application('');
		$mentions    = [];
		$tags        = [];
		$card        = new \Friendica\Object\Api\Mastodon\Card([]);
		$attachments = [];
		$reshare     = [];

		return new \Friendica\Object\Api\Mastodon\Status($item, $account, $counts, $userAttributes, $sensitive, $application, $mentions, $tags, $card, $attachments, $reshare);
	}
}
