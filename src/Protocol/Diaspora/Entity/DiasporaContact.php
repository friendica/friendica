<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Protocol\Diaspora\Entity;

use Psr\Http\Message\UriInterface;

/**
 * @property-read int|null $uriId
 * @property-read UriInterface $url
 * @property-read string $guid
 * @property-read string $addr
 * @property-read UriInterface $alias
 * @property-read string $nick
 * @property-read string $name
 * @property-read string $givenName
 * @property-read string $familyName
 * @property-read UriInterface $photo
 * @property-read UriInterface $photoMedium
 * @property-read UriInterface $photoSmall
 * @property-read UriInterface $batch
 * @property-read UriInterface $notify
 * @property-read UriInterface $poll
 * @property-read string $subscribe
 * @property-read bool $searchable
 * @property-read string $pubKey
 * @property-read UriInterface $baseurl
 * @property-read int $gsid
 * @property-read \DateTime $created
 * @property-read \DateTime $updated
 * @property-read int $interacting_count
 * @property-read int $interacted_count
 * @property-read int $post_count
 */
class DiasporaContact extends \Friendica\BaseEntity
{
	/** @var int|null */
	protected $uriId;
	/** @var UriInterface */
	protected $url;
	/** @var string */
	protected $guid;
	/** @var string */
	protected $addr;
	/** @var UriInterface */
	protected $alias;
	/** @var string */
	protected $nick;
	/** @var string */
	protected $name;
	/** @var string */
	protected $givenName;
	/** @var string */
	protected $familyName;
	/** @var UriInterface */
	protected $photo;
	/** @var UriInterface */
	protected $photoMedium;
	/** @var UriInterface */
	protected $photoSmall;
	/** @var UriInterface */
	protected $batch;
	/** @var UriInterface */
	protected $notify;
	/** @var UriInterface */
	protected $poll;
	/** @var string URL pattern string including a placeholder "{uri}" that mustn't be URL-encoded */
	protected $subscribe;
	/** @var bool */
	protected $searchable;
	/** @var string */
	protected $pubKey;
	/** @var UriInterface */
	protected $baseurl;
	/** @var int */
	protected $gsid;
	/** @var \DateTime */
	protected $created;
	/** @var \DateTime */
	protected $updated;
	/** @var int */
	protected $interacting_count;
	/** @var int */
	protected $interacted_count;
	/** @var int */
	protected $post_count;

	public function __construct(
		UriInterface $url,
		\DateTime $created,
		?string $guid = null,
		?string $addr = null,
		?UriInterface $alias = null,
		?string $nick = null,
		?string $name = null,
		?string $givenName = null,
		?string $familyName = null,
		?UriInterface $photo = null,
		?UriInterface $photoMedium = null,
		?UriInterface $photoSmall = null,
		?UriInterface $batch = null,
		?UriInterface $notify = null,
		?UriInterface $poll = null,
		?string $subscribe = null,
		?bool $searchable = null,
		?string $pubKey = null,
		?UriInterface $baseurl = null,
		?int $gsid = null,
		?\DateTime $updated = null,
		int $interacting_count = 0,
		int $interacted_count = 0,
		int $post_count = 0,
		?int $uriId = null,
	) {
		$this->uriId             = $uriId;
		$this->url               = $url;
		$this->guid              = $guid;
		$this->addr              = $addr;
		$this->alias             = $alias;
		$this->nick              = $nick;
		$this->name              = $name;
		$this->givenName         = $givenName;
		$this->familyName        = $familyName;
		$this->photo             = $photo;
		$this->photoMedium       = $photoMedium;
		$this->photoSmall        = $photoSmall;
		$this->batch             = $batch;
		$this->notify            = $notify;
		$this->poll              = $poll;
		$this->subscribe         = $subscribe;
		$this->searchable        = $searchable;
		$this->pubKey            = $pubKey;
		$this->baseurl           = $baseurl;
		$this->gsid              = $gsid;
		$this->created           = $created;
		$this->updated           = $updated;
		$this->interacting_count = $interacting_count;
		$this->interacted_count  = $interacted_count;
		$this->post_count        = $post_count;
	}
}
