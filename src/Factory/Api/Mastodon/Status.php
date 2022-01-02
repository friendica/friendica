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
use Friendica\Content\Text\BBCode;
use Friendica\Database\Database;
use Friendica\Database\DBA;
use Friendica\Model\Post;
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

	public function __construct(LoggerInterface $logger, Database $dba,
		Account $mstdnAccountFactory, Mention $mstdnMentionFactory,
		Tag $mstdnTagFactory, Card $mstdnCardFactory,
		Attachment $mstdnAttachementFactory, Error $mstdnErrorFactory)
	{
		parent::__construct($logger);
		$this->dba                     = $dba;
		$this->mstdnAccountFactory     = $mstdnAccountFactory;
		$this->mstdnMentionFactory     = $mstdnMentionFactory;
		$this->mstdnTagFactory         = $mstdnTagFactory;
		$this->mstdnCardFactory        = $mstdnCardFactory;
		$this->mstdnAttachementFactory = $mstdnAttachementFactory;
		$this->mstdnErrorFactory       = $mstdnErrorFactory;
	}

	/**
	 * @param int $uriId Uri-ID of the item
	 * @param int $uid   Item user
	 *
	 * @return \Friendica\Object\Api\Mastodon\Status
	 * @throws HTTPException\InternalServerErrorException
	 * @throws ImagickException|HTTPException\NotFoundException
	 */
	public function createFromUriId(int $uriId, $uid = 0): \Friendica\Object\Api\Mastodon\Status
	{
		$fields = ['uri-id', 'uid', 'author-id', 'author-link', 'starred', 'app', 'title', 'body', 'raw-body', 'created', 'network',
			'thr-parent-id', 'parent-author-id', 'language', 'uri', 'plink', 'private', 'vid', 'gravity'];
		$item = Post::selectFirst($fields, ['uri-id' => $uriId, 'uid' => [0, $uid]], ['order' => ['uid' => true]]);
		if (!$item) {
			$mail = DBA::selectFirst('mail', ['id'], ['uri-id' => $uriId, 'uid' => $uid]);
			if ($mail) {
				return $this->createFromMailId($mail['id']);
			}
			throw new HTTPException\NotFoundException('Item with URI ID ' . $uriId . ' not found' . ($uid ? ' for user ' . $uid : '.'));
		}

		$account = $this->mstdnAccountFactory->createFromContactId($item['author-id']);

		$counts = new \Friendica\Object\Api\Mastodon\Status\Counts(
			Post::countPosts(['thr-parent-id' => $uriId, 'gravity' => GRAVITY_COMMENT, 'deleted' => false], []),
			Post::countPosts([
				'thr-parent-id' => $uriId,
				'gravity'       => GRAVITY_ACTIVITY,
				'vid'           => Verb::getID(Activity::ANNOUNCE),
				'deleted'       => false
			], []),
			Post::countPosts([
				'thr-parent-id' => $uriId,
				'gravity'       => GRAVITY_ACTIVITY,
				'vid'           => Verb::getID(Activity::LIKE),
				'deleted'       => false
			], [])
		);

		$userAttributes = new \Friendica\Object\Api\Mastodon\Status\UserAttributes(
			Post::exists([
				'thr-parent-id' => $uriId,
				'uid'           => $uid,
				'origin'        => true,
				'gravity'       => GRAVITY_ACTIVITY,
				'vid'           => Verb::getID(Activity::LIKE)
				, 'deleted'     => false
			]),
			Post::exists([
				'thr-parent-id' => $uriId,
				'uid'           => $uid,
				'origin'        => true,
				'gravity'       => GRAVITY_ACTIVITY,
				'vid'           => Verb::getID(Activity::ANNOUNCE),
				'deleted'       => false
			]),
			Post\ThreadUser::getIgnored($uriId, $uid),
			(bool)($item['starred'] && ($item['gravity'] == GRAVITY_PARENT)),
			Post\ThreadUser::getPinned($uriId, $uid)
		);

		$sensitive   = $this->dba->exists('tag-view', ['uri-id' => $uriId, 'name' => 'nsfw']);
		$application = new \Friendica\Object\Api\Mastodon\Application($item['app'] ?: ContactSelector::networkToName($item['network'], $item['author-link']));

		$mentions    = $this->mstdnMentionFactory->createFromUriId($uriId)->getArrayCopy();
		$tags        = $this->mstdnTagFactory->createFromUriId($uriId);
		$card        = $this->mstdnCardFactory->createFromUriId($uriId);
		$attachments = $this->mstdnAttachementFactory->createFromUriId($uriId);

		$shared = BBCode::fetchShareAttributes($item['body']);
		if (!empty($shared['guid'])) {
			$shared_item = Post::selectFirst(['uri-id', 'plink'], ['guid' => $shared['guid']]);

			$shared_uri_id = $shared_item['uri-id'] ?? 0;

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

		return new \Friendica\Object\Api\Mastodon\Status($item, $account, $counts, $userAttributes, $sensitive, $application, $mentions, $tags, $card, $attachments, $reshare);
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
		$item = ActivityPub\Transmitter::ItemArrayFromMail($id, true);
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
