<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Object\Api\Mastodon;

use Friendica\BaseDataTransferObject;
use Friendica\Content\Text\BBCode;
use Friendica\Model\Item;
use Friendica\Object\Api\Mastodon\Status\Counts;
use Friendica\Object\Api\Mastodon\Status\FriendicaExtension;
use Friendica\Object\Api\Mastodon\Status\UserAttributes;
use Friendica\Util\DateTimeFormat;

/**
 * Class Status
 *
 * @see https://docs.joinmastodon.org/entities/status
 */
class Status extends BaseDataTransferObject
{
	/** @var string */
	protected $id;
	/** @var string|null (Datetime) */
	protected $created_at;
	/** @var string|null (Datetime) */
	protected $edited_at;
	/** @var string|null */
	protected $in_reply_to_id = null;
	/** @var Status[]|null - Fedilab extension, see issue https://github.com/friendica/friendica/issues/12672 */
	protected $in_reply_to_status = null;
	/** @var string|null */
	protected $in_reply_to_account_id = null;
	/** @var bool */
	protected $sensitive = false;
	/** @var string */
	protected $spoiler_text = "";
	/** @var string (Enum of public, unlisted, private, direct)*/
	protected $visibility;
	/** @var string|null */
	protected $language = null;
	/** @var string */
	protected $uri;
	/** @var string|null (URL)*/
	protected $url = null;
	/** @var int */
	protected $replies_count = 0;
	/** @var int */
	protected $reblogs_count = 0;
	/** @var int */
	protected $favourites_count = 0;
	/** @var bool */
	protected $favourited = false;
	/** @var bool */
	protected $reblogged = false;
	/** @var bool */
	protected $muted = false;
	/** @var bool */
	protected $bookmarked = false;
	/** @var bool */
	protected $pinned = false;
	/** @var string */
	protected $content;
	/** @var array */
	protected $filtered = [];
	/** @var Status[]|null */
	protected $reblog = null;
	/** @var Status[]|null - Akkoma extension, see issue https://github.com/friendica/friendica/issues/12603 */
	protected $quote = null;
	/** @var array */
	protected $application = null;
	/** @var array */
	protected $account;
	/** @var Attachment[] */
	protected $media_attachments = [];
	/** @var Mention[] */
	protected $mentions = [];
	/** @var Tag[] */
	protected $tags = [];
	/** @var Emoji[] */
	protected $emojis = [];
	/** @var array|null */
	protected $card = null;
	/** @var array|null */
	protected $poll = null;
	/** @var FriendicaExtension */
	protected $friendica;

	/**
	 * Creates a status record from an item record.
	 *
	 * @param array   $item
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public function __construct(array $item, Account $account, Counts $counts, UserAttributes $userAttributes, bool $sensitive, Application $application, array $mentions, array $tags, Card $card, array $attachments, array $in_reply, array $reblog, FriendicaExtension $friendica, array $quote = null, array $poll = null, array $emojis = null)
	{
		$reblogged        = !empty($reblog);
		$this->id         = (string) $item['uri-id'];
		$this->created_at = DateTimeFormat::utc($item['created'], DateTimeFormat::JSON);
		$this->edited_at  = DateTimeFormat::utc($item['edited'], DateTimeFormat::JSON);

		if ($item['gravity'] == Item::GRAVITY_COMMENT) {
			$this->in_reply_to_id         = (string) $item['thr-parent-id'];
			$this->in_reply_to_status     = $in_reply;
			$this->in_reply_to_account_id = (string) $item['parent-author-id'];
		}

		$this->sensitive    = $sensitive;
		$this->spoiler_text = $item['title'] ?: $item['content-warning'] ?: '';

		$visibility       = ['public', 'private', 'unlisted'];
		$this->visibility = $visibility[$item['private']];

		$languages = json_decode($item['language'] ?? '', true);
		if (is_array($languages)) {
			$this->language = array_key_first($languages);
		} else {
			$this->language = null;
		}

		$this->uri               = $item['uri'];
		$this->url               = $item['plink'] ?? null;
		$this->replies_count     = $reblogged ? 0 : $counts->replies;
		$this->reblogs_count     = $reblogged ? 0 : $counts->reblogs;
		$this->favourites_count  = $reblogged ? 0 : $counts->favourites;
		$this->favourited        = $userAttributes->favourited;
		$this->reblogged         = $userAttributes->reblogged;
		$this->muted             = $userAttributes->muted;
		$this->bookmarked        = $userAttributes->bookmarked;
		$this->pinned            = $userAttributes->pinned;
		$this->content           = $reblogged ? '' : BBCode::convertForUriId($item['uri-id'], BBCode::setMentionsToNicknames($item['raw-body'] ?? $item['body']), BBCode::MASTODON_API);
		$this->reblog            = $reblog;
		$this->quote             = $quote;
		$this->application       = $application->toArray();
		$this->account           = $account->toArray();
		$this->media_attachments = $reblogged ? [] : $attachments;
		$this->mentions          = $reblogged ? [] : $mentions;
		$this->tags              = $reblogged ? [] : $tags;
		$this->emojis            = $reblogged ? [] : ($emojis ?: []);
		$this->card              = $reblogged ? null : ($card->toArray() ?: null);
		$this->poll              = $reblogged ? null : $poll;
		$this->friendica         = $reblogged ? null : $friendica;
	}

	/**
	 * Returns the current created_at string or null if not set
	 * @return ?string
	 */
	public function createdAt(): ?string
	{
		return $this->created_at;
	}

	/**
	 * Returns the current edited_at string or null if not set
	 * @return ?string
	 */
	public function editedAt(): ?string
	{
		return $this->edited_at;
	}

	/**
	 * Returns the Friendica Extension properties
	 * @return FriendicaExtension
	 */
	public function friendicaExtension(): FriendicaExtension
	{
		return $this->friendica;
	}

	/**
	 * Returns the current entity as an array
	 *
	 * @return array
	 */
	public function toArray(): array
	{
		$status = parent::toArray();

		if (!$status['pinned']) {
			unset($status['pinned']);
		}

		if (empty($status['application']['name'])) {
			unset($status['application']);
		}

		if (empty($status['reblog'])) {
			$status['reblog'] = null;
		}

		if (empty($status['quote'])) {
			$status['quote'] = null;
		}

		if (empty($status['in_reply_to_status'])) {
			$status['in_reply_to_status'] = null;
		}

		if ($status['created_at'] == $status['edited_at']) {
			$status['edited_at'] = null;
		}

		return $status;
	}
}
