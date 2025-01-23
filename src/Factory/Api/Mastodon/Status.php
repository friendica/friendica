<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Factory\Api\Mastodon;

use Friendica\BaseFactory;
use Friendica\Content\ContactSelector;
use Friendica\Content\Item as ContentItem;
use Friendica\Content\Smilies;
use Friendica\Content\Text\BBCode;
use Friendica\Core\Protocol;
use Friendica\Database\Database;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Model\Verb;
use Friendica\Network\HTTPException\InternalServerErrorException;
use Friendica\Network\HTTPException\NotFoundException;
use Friendica\Object\Api\Mastodon\Status\FriendicaDeliveryData;
use Friendica\Object\Api\Mastodon\Status\FriendicaExtension;
use Friendica\Object\Api\Mastodon\Status\FriendicaVisibility;
use Friendica\Protocol\Activity;
use Friendica\Protocol\ActivityPub;
use Friendica\Util\ACLFormatter;
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
	private $mstdnAttachmentFactory;
	/** @var Emoji */
	private $mstdnEmojiFactory;
	/** @var Poll */
	private $mstdnPollFactory;
	/** @var ContentItem */
	private $contentItem;
	/** @var ACLFormatter */
	private $aclFormatter;

	public function __construct(
		LoggerInterface $logger,
		Database $dba,
		Account $mstdnAccountFactory,
		Mention $mstdnMentionFactory,
		Tag $mstdnTagFactory,
		Card $mstdnCardFactory,
		Attachment $mstdnAttachmentFactory,
		Emoji $mstdnEmojiFactory,
		Poll $mstdnPollFactory,
		ContentItem $contentItem,
		ACLFormatter $aclFormatter
	) {
		parent::__construct($logger);
		$this->dba                    = $dba;
		$this->mstdnAccountFactory    = $mstdnAccountFactory;
		$this->mstdnMentionFactory    = $mstdnMentionFactory;
		$this->mstdnTagFactory        = $mstdnTagFactory;
		$this->mstdnCardFactory       = $mstdnCardFactory;
		$this->mstdnAttachmentFactory = $mstdnAttachmentFactory;
		$this->mstdnEmojiFactory      = $mstdnEmojiFactory;
		$this->mstdnPollFactory       = $mstdnPollFactory;
		$this->contentItem            = $contentItem;
		$this->aclFormatter           = $aclFormatter;
	}

	/**
	 * @param int  $uriId           Uri-ID of the item
	 * @param int  $uid             Item user
	 * @param bool $display_quote   Display quoted posts
	 * @param bool $reblog          Check for reblogged post
	 * @param bool $in_reply_status Add an "in_reply_status" element
	 *
	 * @return \Friendica\Object\Api\Mastodon\Status
	 * @throws InternalServerErrorException
	 * @throws ImagickException|NotFoundException
	 */
	public function createFromUriId(int $uriId, int $uid = 0, bool $display_quote = false, bool $reblog = true, bool $in_reply_status = true): \Friendica\Object\Api\Mastodon\Status
	{
		$fields = ['uri-id', 'uid', 'author-id', 'causer-id', 'author-uri-id', 'author-link', 'author-gsid', 'causer-uri-id', 'post-reason', 'starred', 'app', 'title', 'body', 'raw-body', 'content-warning', 'question-id',
			'created', 'edited', 'commented', 'received', 'changed', 'network', 'thr-parent-id', 'parent-author-id', 'language', 'uri', 'plink', 'private', 'vid', 'gravity', 'featured', 'has-media', 'quote-uri-id',
			'delivery_queue_count', 'delivery_queue_done','delivery_queue_failed', 'allow_cid', 'deny_cid', 'allow_gid', 'deny_gid', 'sensitive'];
		$item = Post::selectFirst($fields, ['uri-id' => $uriId, 'uid' => [0, $uid]], ['order' => ['uid' => true]]);
		if (!$item) {
			$mail = DBA::selectFirst('mail', ['id'], ['uri-id' => $uriId, 'uid' => $uid]);
			if ($mail) {
				return $this->createFromMailId($mail['id']);
			}
			throw new NotFoundException('Item with URI ID ' . $uriId . ' not found' . ($uid ? ' for user ' . $uid : '.'));
		}

		$activity_fields = ['uri-id', 'thr-parent-id', 'uri', 'author-id', 'author-uri-id', 'author-link', 'app', 'created', 'network', 'parent-author-id', 'private'];

		if (($item['gravity'] == Item::GRAVITY_ACTIVITY) && ($item['vid'] == Verb::getID(Activity::ANNOUNCE))) {
			$is_reshare = true;
			$account    = $this->mstdnAccountFactory->createFromUriId($item['author-uri-id'], $uid);
			$uriId      = $item['thr-parent-id'];
			$activity   = $item;
			$item       = Post::selectFirst($fields, ['uri-id' => $uriId, 'uid' => [0, $uid]], ['order' => ['uid' => true]]);
			if (!$item) {
				throw new NotFoundException('Item with URI ID ' . $uriId . ' not found' . ($uid ? ' for user ' . $uid : '.'));
			}
			foreach ($activity_fields as $field) {
				$item[$field] = $activity[$field];
			}
		} else {
			$is_reshare = $reblog && !is_null($item['causer-uri-id']) && ($item['causer-id'] != $item['author-id']) && ($item['post-reason'] == Item::PR_ANNOUNCEMENT);
			$account    = $this->mstdnAccountFactory->createFromUriId($is_reshare ? $item['causer-uri-id'] : $item['author-uri-id'], $uid);
			if ($is_reshare) {
				$activity = Post::selectFirstPost($activity_fields, ['thr-parent-id' => $item['uri-id'], 'author-id' => $item['causer-id'], 'verb' => Activity::ANNOUNCE]);
				if ($activity) {
					$item = array_merge($item, $activity);
				}
			}
		}

		$count_announce = Post::countPosts([
			'thr-parent-id' => $uriId,
			'gravity'       => Item::GRAVITY_ACTIVITY,
			'vid'           => Verb::getID(Activity::ANNOUNCE),
			'deleted'       => false
		]) + Post::countPosts([
			'quote-uri-id' => $uriId,
			'body'         => '',
			'deleted'      => false
		]);

		$count_like = Post::countPosts([
			'thr-parent-id' => $uriId,
			'gravity'       => Item::GRAVITY_ACTIVITY,
			'vid'           => Verb::getID(Activity::LIKE),
			'deleted'       => false
		]);

		$count_dislike = Post::countPosts([
			'thr-parent-id' => $uriId,
			'gravity'       => Item::GRAVITY_ACTIVITY,
			'vid'           => Verb::getID(Activity::DISLIKE),
			'deleted'       => false
		]);

		$counts = new \Friendica\Object\Api\Mastodon\Status\Counts(
			Post::countPosts(['thr-parent-id' => $uriId, 'gravity' => Item::GRAVITY_COMMENT, 'deleted' => false], []),
			$count_announce,
			$count_like,
			$count_dislike
		);

		$origin_like = $count_like > 0 && Post::exists([
			'thr-parent-id' => $uriId,
			'uid'           => $uid,
			'origin'        => true,
			'gravity'       => Item::GRAVITY_ACTIVITY,
			'vid'           => Verb::getID(Activity::LIKE),
			'deleted'       => false
		]);
		$origin_dislike = $count_dislike > 0 && Post::exists([
			'thr-parent-id' => $uriId,
			'uid'           => $uid,
			'origin'        => true,
			'gravity'       => Item::GRAVITY_ACTIVITY,
			'vid'           => Verb::getID(Activity::DISLIKE),
			'deleted'       => false
		]);
		$origin_announce = $count_announce > 0 && (Post::exists([
			'thr-parent-id' => $uriId,
			'uid'           => $uid,
			'origin'        => true,
			'gravity'       => Item::GRAVITY_ACTIVITY,
			'vid'           => Verb::getID(Activity::ANNOUNCE),
			'deleted'       => false
		]) || Post::exists([
			'quote-uri-id' => $uriId,
			'uid'          => $uid,
			'origin'       => true,
			'body'         => '',
			'deleted'      => false
		]));
		$userAttributes = new \Friendica\Object\Api\Mastodon\Status\UserAttributes(
			$origin_like,
			$origin_announce,
			Post\ThreadUser::getIgnored($uriId, $uid),
			$item['starred'] && $item['gravity'] == Item::GRAVITY_PARENT,
			$item['featured']
		);

		$sensitive = (bool)$item['sensitive'];

		$network  = ContactSelector::networkToName($item['network']);
		$sitename = '';
		$platform = '';
		$version  = '';

		if (in_array($item['network'], Protocol::FEDERATED)) {
			$gserver = $this->dba->selectFirst('gserver', ['site_name', 'platform', 'version'], ['id' => $item['author-gsid']]);
			if (!empty($gserver)) {
				$platform = ucfirst($gserver['platform']);
				$version  = $gserver['version'];
				$sitename = $gserver['site_name'];
			}
		}

		if ($platform == '') {
			$platform = ContactSelector::networkToName($item['network'], $item['network'], $item['author-gsid']);
		}

		$application = new \Friendica\Object\Api\Mastodon\Application($item['app'] ?: $platform);

		$mentions = $this->mstdnMentionFactory->createFromUriId($uriId)->getArrayCopy();
		$tags     = $this->mstdnTagFactory->createFromUriId($uriId);
		if ($item['has-media']) {
			$card        = $this->mstdnCardFactory->createFromUriId($uriId);
			$attachments = $this->mstdnAttachmentFactory->createFromUriId($uriId);
		} else {
			$card        = new \Friendica\Object\Api\Mastodon\Card([]);
			$attachments = [];
		}

		if (!empty($item['question-id'])) {
			$poll = $this->mstdnPollFactory->createFromId($item['question-id'], $uid)->toArray();
		} else {
			$poll = null;
		}

		if ($display_quote) {
			$quote = self::createQuote($item, $uid);

			$item['body'] = BBCode::removeSharedData($item['body']);

			if (!is_null($item['raw-body'])) {
				$item['raw-body'] = BBCode::removeSharedData($item['raw-body']);
			}
		} else {
			// We can always safely add attached activities. Real quotes are added to the body via "addSharedPost".
			if (empty($item['quote-uri-id'])) {
				$quote = self::createQuote($item, $uid);
			} else {
				$quote = [];
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

				foreach ($this->mstdnAttachmentFactory->createFromUriId($shared_uri_id) as $attachment) {
					if (!in_array($attachment, $attachments)) {
						$attachments[] = $attachment;
					}
				}

				if (empty($card->toArray())) {
					$card = $this->mstdnCardFactory->createFromUriId($shared_uri_id);
				}
			}

			if (!is_null($item['raw-body'])) {
				$item['raw-body'] = $this->contentItem->addSharedPost($item, $item['raw-body']);
				$item['raw-body'] = Post\Media::addHTMLLinkToBody($uriId, $item['raw-body']);
			} else {
				$item['body'] = $this->contentItem->addSharedPost($item);
				$item['body'] = Post\Media::addHTMLLinkToBody($uriId, $item['body']);
			}
		}

		$emojis = null;
		if (DI::baseUrl()->isLocalUrl($item['uri'])) {
			$used_smilies = Smilies::extractUsedSmilies($item['raw-body'] ?: $item['body'], $normalized);
			if ($item['raw-body']) {
				$item['raw-body'] = $normalized;
			} elseif ($item['body']) {
				$item['body'] = $normalized;
			}
			$emojis = $this->mstdnEmojiFactory->createCollectionFromArray($used_smilies)->getArrayCopy(true);
		} else {
			if (preg_match_all("(\[emoji=(.*?)](.*?)\[/emoji])ism", $item['body'] ?: $item['raw-body'], $matches)) {
				$emojis = $this->mstdnEmojiFactory->createCollectionFromArray(array_combine($matches[2], $matches[1]))->getArrayCopy(true);
			}
		}

		if ($is_reshare) {
			try {
				$reshare = $this->createFromUriId($uriId, $uid, $display_quote, false, false)->toArray();
			} catch (\Exception $exception) {
				DI::logger()->info('Reshare not fetchable', ['uri-id' => $item['uri-id'], 'uid' => $uid, 'exception' => $exception]);
				$reshare = [];
			}
		} else {
			$reshare = [];
		}

		if ($in_reply_status && ($item['gravity'] == Item::GRAVITY_COMMENT)) {
			try {
				$in_reply = $this->createFromUriId($item['thr-parent-id'], $uid, $display_quote, false, false)->toArray();
			} catch (\Exception $exception) {
				DI::logger()->info('Reply post not fetchable', ['uri-id' => $item['uri-id'], 'uid' => $uid, 'exception' => $exception]);
				$in_reply = [];
			}
		} else {
			$in_reply = [];
		}

		$delivery_data   = $uid != $item['uid'] ? null : new FriendicaDeliveryData($item['delivery_queue_count'], $item['delivery_queue_done'], $item['delivery_queue_failed']);
		$visibility_data = $uid != $item['uid'] ? null : new FriendicaVisibility($this->aclFormatter->expand($item['allow_cid']), $this->aclFormatter->expand($item['deny_cid']), $this->aclFormatter->expand($item['allow_gid']), $this->aclFormatter->expand($item['deny_gid']));
		$friendica       = new FriendicaExtension($item['title'] ?? '', $item['changed'], $item['commented'], $item['received'], $counts->dislikes, $origin_dislike, $network, $platform, $version, $sitename, $delivery_data, $visibility_data, BBCode::convertForUriId($item['uri-id'], $item['body'], BBCode::EXTERNAL));

		return new \Friendica\Object\Api\Mastodon\Status($item, $account, $counts, $userAttributes, $sensitive, $application, $mentions, $tags, $card, $attachments, $in_reply, $reshare, $friendica, $quote, $poll, $emojis);
	}

	/**
	 * Create a quote status object
	 *
	 * @param array $item
	 * @param integer $uid
	 * @return array
	 */
	private function createQuote(array $item, int $uid): array
	{
		if (empty($item['quote-uri-id'])) {
			$media = Post\Media::getByURIId($item['uri-id'], [Post\Media::ACTIVITY]);
			if (!empty($media)) {
				if (!empty($media['media-uri-id'])) {
					$quote_id = $media['media-uri-id'];
				} else {
					$shared_item = Post::selectFirst(['uri-id'], ['plink' => $media[0]['url'], 'uid' => [$uid, 0]]);
					$quote_id    = $shared_item['uri-id'] ?? 0;
				}
			}
		} else {
			$quote_id = $item['quote-uri-id'];
		}

		if (!empty($quote_id) && ($quote_id != $item['uri-id'])) {
			try {
				$quote = $this->createFromUriId($quote_id, $uid, false, false, false)->toArray();
			} catch (\Exception $exception) {
				DI::logger()->info('Quote not fetchable', ['uri-id' => $item['uri-id'], 'uid' => $uid, 'exception' => $exception]);
				$quote = [];
			}
		} else {
			$quote = [];
		}
		return $quote;
	}

	/**
	 * @param int $id id of the mail
	 *
	 * @return \Friendica\Object\Api\Mastodon\Status
	 * @throws InternalServerErrorException
	 * @throws ImagickException|NotFoundException
	 */
	public function createFromMailId(int $id): \Friendica\Object\Api\Mastodon\Status
	{
		$item = ActivityPub\Transmitter::getItemArrayFromMail($id, true);
		if (empty($item)) {
			throw new NotFoundException('Mail record not found with id: ' . $id);
		}

		$account = $this->mstdnAccountFactory->createFromContactId($item['author-id']);

		$replies = $this->dba->count('mail', ['thr-parent-id' => $item['uri-id'], 'reply' => true]);

		$counts = new \Friendica\Object\Api\Mastodon\Status\Counts($replies, 0, 0, 0);

		$userAttributes = new \Friendica\Object\Api\Mastodon\Status\UserAttributes(false, false, false, false, false);

		$sensitive   = false;
		$application = new \Friendica\Object\Api\Mastodon\Application('');
		$mentions    = [];
		$tags        = [];
		$card        = new \Friendica\Object\Api\Mastodon\Card([]);
		$attachments = [];
		$in_reply    = [];
		$reshare     = [];
		$friendica   = new FriendicaExtension('', null, null, null, 0, false, null, null, null, null, null, null, BBCode::convertForUriId($item['uri-id'], $item['body'], BBCode::EXTERNAL));

		return new \Friendica\Object\Api\Mastodon\Status($item, $account, $counts, $userAttributes, $sensitive, $application, $mentions, $tags, $card, $attachments, $in_reply, $reshare, $friendica);
	}
}
