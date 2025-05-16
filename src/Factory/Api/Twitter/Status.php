<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Factory\Api\Twitter;

use Friendica\BaseFactory;
use Friendica\Content\Item as ContentItem;
use Friendica\Content\Text\BBCode;
use Friendica\Content\Text\HTML;
use Friendica\Database\Database;
use Friendica\Factory\Api\Friendica\Activities;
use Friendica\Factory\Api\Twitter\User as TwitterUser;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Model\Verb;
use Friendica\Network\HTTPException\InternalServerErrorException;
use Friendica\Network\HTTPException\NotFoundException;
use Friendica\Protocol\Activity;
use ImagickException;
use Psr\Log\LoggerInterface;

class Status extends BaseFactory
{
	/** @var Database */
	private $dba;
	/** @var twitterUser entity */
	private $twitterUser;
	/** @var Hashtag entity */
	private $hashtag;
	/** @var Media entity */
	private $media;
	/** @var Url entity */
	private $url;
	/** @var Mention entity */
	private $mention;
	/** @var Activities entity */
	private $activities;
	/** @var Attachment entity */
	private $attachment;
	/** @var ContentItem */
	private $contentItem;

	public function __construct(LoggerInterface $logger, Database $dba, TwitterUser $twitteruser, Hashtag $hashtag, Media $media, Url $url, Mention $mention, Activities $activities, Attachment $attachment, ContentItem $contentItem)
	{
		parent::__construct($logger);
		$this->dba         = $dba;
		$this->twitterUser = $twitteruser;
		$this->hashtag     = $hashtag;
		$this->media       = $media;
		$this->url         = $url;
		$this->mention     = $mention;
		$this->activities  = $activities;
		$this->attachment  = $attachment;
		$this->contentItem = $contentItem;
	}

	/**
	 * @param int $id    Uri-ID of the item
	 * @param int $uid   Item user
	 * @param bool $include_entities Whether to include entities
	 *
	 * @return \Friendica\Object\Api\Twitter\Status
	 * @throws InternalServerErrorException
	 * @throws ImagickException|NotFoundException
	 */
	public function createFromItemId(int $id, int $uid, bool $include_entities = false): \Friendica\Object\Api\Twitter\Status
	{
		$fields = ['parent-uri-id', 'uri-id', 'uid', 'author-id', 'author-link', 'author-network', 'author-gsid', 'owner-id', 'causer-id',
			'starred', 'app', 'title', 'body', 'raw-body', 'created', 'network','post-reason', 'language', 'gravity',
			'thr-parent-id', 'parent-author-id', 'parent-author-nick', 'uri', 'plink', 'private', 'vid', 'coord', 'quote-uri-id'];
		$item = Post::selectFirst($fields, ['id' => $id], ['order' => ['uid' => true]]);
		if (!$item) {
			throw new NotFoundException('Item with ID ' . $id . ' not found.');
		}
		return $this->createFromArray($item, $uid, $include_entities);
	}

	/**
	 * @param int $uriId Uri-ID of the item
	 * @param int $uid   Item user
	 * @param bool $include_entities Whether to include entities
	 *
	 * @return \Friendica\Object\Api\Twitter\Status
	 * @throws InternalServerErrorException
	 * @throws ImagickException|NotFoundException
	 */
	public function createFromUriId(int $uriId, int $uid = 0, bool $include_entities = false): \Friendica\Object\Api\Twitter\Status
	{
		$fields = ['parent-uri-id', 'uri-id', 'uid', 'author-id', 'author-link', 'author-network', 'author-gsid', 'owner-id', 'causer-id',
			'starred', 'app', 'title', 'body', 'raw-body', 'created', 'network','post-reason', 'language', 'gravity',
			'thr-parent-id', 'parent-author-id', 'parent-author-nick', 'uri', 'plink', 'private', 'vid', 'coord'];
		$item = Post::selectFirst($fields, ['uri-id' => $uriId, 'uid' => [0, $uid]], ['order' => ['uid' => true]]);
		if (!$item) {
			throw new NotFoundException('Item with URI ID ' . $uriId . ' not found' . ($uid ? ' for user ' . $uid : '.'));
		}
		return $this->createFromArray($item, $uid, $include_entities);
	}

	/**
	 * @param array $item item array
	 * @param int   $uid  Item user
	 * @param bool $include_entities Whether to include entities
	 *
	 * @return \Friendica\Object\Api\Twitter\Status
	 * @throws InternalServerErrorException
	 * @throws ImagickException|NotFoundException
	 */
	private function createFromArray(array $item, int $uid, bool $include_entities): \Friendica\Object\Api\Twitter\Status
	{
		$item   = Post\Media::addHTMLAttachmentToItem($item);
		$author = $this->twitterUser->createFromContactId($item['author-id'], $uid, true);

		if (!empty($item['causer-id']) && ($item['post-reason'] == Item::PR_ANNOUNCEMENT)) {
			$owner = $this->twitterUser->createFromContactId($item['causer-id'], $uid, true);
		} else {
			$owner = $this->twitterUser->createFromContactId($item['owner-id'], $uid, true);
		}

		$friendica_comments = Post::countPosts(['thr-parent-id' => $item['uri-id'], 'deleted' => false, 'gravity' => Item::GRAVITY_COMMENT]);

		$text  = '';
		$title = '';

		// Add the title to text / html if set
		if (!empty($item['title'])) {
			$text .= $item['title'] . ' ';
			$title = sprintf("[h4]%s[/h4]", $item['title']);
		}

		$statusnetHtml = BBCode::convertForUriId($item['uri-id'], BBCode::setMentionsToNicknames($title . ($item['raw-body'] ?? $item['body'])), BBCode::TWITTER_API);
		$friendicaHtml = BBCode::convertForUriId($item['uri-id'], $title . $item['body'], BBCode::EXTERNAL);

		$text .= Post\Media::addAttachmentsToBody($item['uri-id'], $this->contentItem->addSharedPost($item));

		$text = trim(HTML::toPlaintext(BBCode::convertForUriId($item['uri-id'], $text, BBCode::TWITTER_API), 0));

		$geo = [];

		if ($item['coord'] != '') {
			$coords = explode(' ', $item["coord"]);
			if (count($coords) == 2) {
				$geo = [
					'type'        => 'Point',
					'coordinates' => [(float) $coords[0], (float) $coords[1]]
				];
			}
		}

		$liked = Post::exists([
			'thr-parent-id' => $item['uri-id'],
			'uid'           => $uid,
			'origin'        => true,
			'gravity'       => Item::GRAVITY_ACTIVITY,
			'vid'           => Verb::getID(Activity::LIKE),
			'deleted'       => false
		]);

		if ($include_entities) {
			$hashtags = $this->hashtag->createFromUriId($item['uri-id'], $text);
			$medias   = $this->media->createFromUriId($item['uri-id'], $text);
			$urls     = $this->url->createFromUriId($item['uri-id']);
			$mentions = $this->mention->createFromUriId($item['uri-id']);
		} else {
			$attachments = $this->attachment->createFromUriId($item['uri-id']);
		}

		$friendica_activities = $this->activities->createFromUriId($item['uri-id'], $uid);

		$shared = $this->contentItem->getSharedPost($item, ['uri-id']);
		if (!empty($shared)) {
			$shared_uri_id = $shared['post']['uri-id'];

			if ($include_entities) {
				$hashtags = array_merge($hashtags, $this->hashtag->createFromUriId($shared_uri_id, $text));
				$medias   = array_merge($medias, $this->media->createFromUriId($shared_uri_id, $text));
				$urls     = array_merge($urls, $this->url->createFromUriId($shared_uri_id));
				$mentions = array_merge($mentions, $this->mention->createFromUriId($shared_uri_id));
			} else {
				$attachments = array_merge($attachments, $this->attachment->createFromUriId($shared_uri_id));
			}
		}

		if ($item['vid'] == Verb::getID(Activity::ANNOUNCE)) {
			$retweeted      = $this->createFromUriId($item['thr-parent-id'], $uid)->toArray();
			$retweeted_item = Post::selectFirst(['title', 'body', 'author-id'], ['uri-id' => $item['thr-parent-id'], 'uid' => [0, $uid]]);
			$item['title']  = $retweeted_item['title'] ?? $item['title'];
			$item['body']   = $retweeted_item['body']  ?? $item['body'];
			$author         = $this->twitterUser->createFromContactId($retweeted_item['author-id'], $uid, true);
		} else {
			$retweeted = [];
		}

		$quoted = []; // @todo

		if ($include_entities) {
			$entities    = ['hashtags' => $hashtags, 'media' => $medias, 'urls' => $urls, 'user_mentions' => $mentions];
			$attachments = [];
		} else {
			$entities = [];
		}

		return new \Friendica\Object\Api\Twitter\Status($text, $statusnetHtml, $friendicaHtml, $item, $author, $owner, $retweeted, $quoted, $geo, $friendica_activities, $entities, $attachments, $friendica_comments, $liked);
	}
}
