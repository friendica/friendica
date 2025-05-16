<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Navigation\Notifications\Entity;

use DateTime;
use Friendica\BaseEntity;
use Friendica\Content\Text\BBCode;
use Friendica\Core\Renderer;
use Psr\Http\Message\UriInterface;

/**
 * @property-read int $type
 * @property-read string $name
 * @property-read UriInterface $url
 * @property-read UriInterface $photo
 * @property-read DateTime $date
 * @property-read string|null $msg
 * @property-read int $uid
 * @property-read UriInterface $link
 * @property-read int|null $itemId
 * @property-read int|null $parent
 * @property-read bool $seen
 * @property-read string $verb
 * @property-read string|null $otype
 * @property-read string|null $name_cache
 * @property-read string|null $msg_cache
 * @property-read int|null $uriId
 * @property-read int|null $parentUriId
 * @property-read int|null $id
 *
 * @deprecated 2022.05 Use \Friendica\Navigation\Notifications\Entity\Notification instead
 * @see \Friendica\Navigation\Notifications\Entity\Notification
 */
class Notify extends BaseEntity
{
	/** @var int */
	protected $type;
	/** @var string */
	protected $name;
	/** @var UriInterface */
	protected $url;
	/** @var UriInterface */
	protected $photo;
	/** @var DateTime */
	protected $date;
	/** @var string|null */
	protected $msg;
	/** @var int */
	protected $uid;
	/** @var UriInterface */
	protected $link;
	/** @var int|null */
	protected $itemId;
	/** @var int|null */
	protected $parent;
	/** @var bool */
	protected $seen;
	/** @var string */
	protected $verb;
	/** @var string */
	protected $otype;
	/** @var string|null */
	protected $name_cache;
	/** @var string|null */
	protected $msg_cache;
	/** @var int|null */
	protected $uriId;
	/** @var int|null */
	protected $parentUriId;
	/** @var int|null */
	protected $id;

	public function __construct(int $type, string $name, UriInterface $url, UriInterface $photo, DateTime $date, int $uid, UriInterface $link, bool $seen, string $verb, string $otype, string $name_cache = null, string $msg = null, string $msg_cache = null, int $itemId = null, int $uriId = null, int $parent = null, ?int $parentUriId = null, ?int $id = null)
	{
		$this->type        = $type;
		$this->name        = $name;
		$this->url         = $url;
		$this->photo       = $photo;
		$this->date        = $date;
		$this->msg         = $msg;
		$this->uid         = $uid;
		$this->link        = $link;
		$this->itemId      = $itemId;
		$this->parent      = $parent;
		$this->seen        = $seen;
		$this->verb        = $verb;
		$this->otype       = $otype;
		$this->name_cache  = $name_cache;
		$this->msg_cache   = $msg_cache;
		$this->uriId       = $uriId;
		$this->parentUriId = $parentUriId;
		$this->id          = $id;
	}

	public function setSeen()
	{
		$this->seen = true;
	}

	public function updateMsgFromPreamble($epreamble)
	{
		$this->msg       = Renderer::replaceMacros($epreamble, ['$itemlink' => $this->link->__toString()]);
		$this->msg_cache = self::formatMessage($this->name_cache ?? $this->name, BBCode::toPlaintext($this->msg, false));
	}

	/**
	 * Formats a notification message with the notification author
	 *
	 * Replace the name with {0} but ensure to make that only once. The {0} is used
	 * later and prints the name in bold.
	 *
	 * @param string $name
	 * @param string $message
	 *
	 * @return string Formatted message
	 */
	public static function formatMessage(string $name, string $message): string
	{
		return str_replace('{0}', '<span class="contactname">' . htmlspecialchars(BBCode::toPlaintext($name, false)) . '</span>', htmlspecialchars($message));
	}
}
