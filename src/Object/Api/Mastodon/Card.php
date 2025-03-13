<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Object\Api\Mastodon;

use Friendica\BaseDataTransferObject;
use Friendica\Util\DateTimeFormat;

/**
 * Class Card
 *
 * @see https://docs.joinmastodon.org/entities/card
 */
class Card extends BaseDataTransferObject
{
	/** @var string */
	protected $url;
	/** @var string */
	protected $title;
	/** @var string */
	protected $description;
	/** @var string */
	protected $language;
	/** @var string */
	protected $type;
	/** @var string */
	protected $author_name;
	/** @var string */
	protected $author_url;
	/** @var string */
	protected $provider_name;
	/** @var string */
	protected $provider_url;
	/** @var string */
	protected $html;
	/** @var int */
	protected $width;
	/** @var int */
	protected $height;
	/** @var string */
	protected $image;
	/** @var string */
	protected $image_description = '';
	/** @var string */
	protected $embed_url;
	/** @var string */
	protected $blurhash;
	/** @var string|null (Datetime) */
	protected $published_at;
	/** @var array */
	protected $authors = [];
	/** @var array */
	protected $history;

	/**
	 * Creates a card record from an attachment array.
	 *
	 * @param array   $attachment Attachment record
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public function __construct(array $attachment, array $history = [])
	{
		$this->url           = $attachment['url']           ?? '';
		$this->title         = $attachment['title']         ?? '';
		$this->description   = $attachment['description']   ?? '';
		$this->language      = $attachment['language']      ?? '';
		$this->type          = $attachment['type']          ?? '';
		$this->author_name   = $attachment['author_name']   ?? '';
		$this->author_url    = $attachment['author_url']    ?? '';
		$this->provider_name = $attachment['provider_name'] ?? '';
		$this->provider_url  = $attachment['provider_url']  ?? '';
		$this->html          = '';
		$this->width         = $attachment['width']  ?? 0;
		$this->height        = $attachment['height'] ?? 0;
		$this->image         = $attachment['image']  ?? '';
		$this->embed_url     = '';
		$this->blurhash      = $attachment['blurhash'] ?? '';
		$this->published_at  = !empty($attachment['published']) ? DateTimeFormat::utc($attachment['published'], DateTimeFormat::JSON) : null;
		$this->history       = $history;
	}

	/**
	 * Returns the current entity as an array
	 *
	 * @return array
	 */
	public function toArray(): array
	{
		if (empty($this->url)) {
			return [];
		}

		if (empty($this->history)) {
			unset($this->history);
		}

		return parent::toArray();
	}
}
