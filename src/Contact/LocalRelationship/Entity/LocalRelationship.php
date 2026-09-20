<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Contact\LocalRelationship\Entity;

use Friendica\Core\Protocol;
use Friendica\Model\Contact;

/**
 * @property-read int    $userId
 * @property-read int    $contactId
 * @property-read int    $uriId
 * @property-read bool   $blocked
 * @property-read bool   $ignored
 * @property-read bool   $collapsed
 * @property-read bool   $hidden
 * @property-read bool   $pending
 * @property-read int    $rel
 * @property-read string $info
 * @property-read int    $notifyNewPosts
 * @property-read int    $remoteSelf
 * @property-read int    $fetchFurtherInformation
 * @property-read string $ffiKeywordDenylist
 * @property-read string $hubVerify
 * @property-read string $protocol
 * @property-read int    $rating
 * @property-read int    $priority
 */
class LocalRelationship extends \Friendica\BaseEntity
{
	// Fetch Further Information options, not a binary flag
	public const FFI_NONE        = 0;
	public const FFI_INFORMATION = 1;
	public const FFI_KEYWORD     = 3;
	public const FFI_BOTH        = 2;

	public const MIRROR_DEACTIVATED    = 0;
	public const MIRROR_OWN_POST       = 2;
	public const MIRROR_NATIVE_RESHARE = 3;

	// Notify New Posts options, not a binary flag
	public const NOTIFY_NEW_POSTS_NONE   = 0;
	public const NOTIFY_NEW_POSTS_ALL    = 1;
	public const NOTIFY_NEW_POSTS_POSTS  = 2;
	public const NOTIFY_NEW_POSTS_SHARES = 3;

	/** @var int */
	protected $userId;
	/** @var int */
	protected $contactId;
	/** @var bool */
	protected $blocked;
	/** @var bool */
	protected $ignored;
	/** @var bool */
	protected $collapsed;
	/** @var bool */
	protected $hidden;
	/** @var bool */
	protected $pending;
	/** @var int */
	protected $rel;
	/** @var string */
	protected $info;
	/** @var int One of NOTIFY_NEW_POSTS_* */
	protected $notifyNewPosts;
	/** @var int One of MIRROR_* */
	protected $remoteSelf;
	/** @var int One of FFI_* */
	protected $fetchFurtherInformation;
	/** @var string */
	protected $ffiKeywordDenylist;
	/** @var string */
	protected $hubVerify;
	/** @var string */
	protected $protocol;
	/** @var int */
	protected $rating;
	/** @var int */
	protected $priority;

	public function __construct(int $userId, int $contactId, bool $blocked = false, bool $ignored = false, bool $collapsed = false, bool $hidden = false, bool $pending = false, int $rel = Contact::NOTHING, string $info = '', int $notifyNewPosts = self::NOTIFY_NEW_POSTS_NONE, int $remoteSelf = self::MIRROR_DEACTIVATED, int $fetchFurtherInformation = self::FFI_NONE, string $ffiKeywordDenylist = '', string $hubVerify = '', string $protocol = Protocol::PHANTOM, ?int $rating = null, ?int $priority = null)
	{
		$this->userId                  = $userId;
		$this->contactId               = $contactId;
		$this->blocked                 = $blocked;
		$this->ignored                 = $ignored;
		$this->collapsed               = $collapsed;
		$this->hidden                  = $hidden;
		$this->pending                 = $pending;
		$this->rel                     = $rel;
		$this->info                    = $info;
		$this->notifyNewPosts          = $notifyNewPosts;
		$this->remoteSelf              = $remoteSelf;
		$this->fetchFurtherInformation = $fetchFurtherInformation;
		$this->ffiKeywordDenylist      = $ffiKeywordDenylist;
		$this->hubVerify               = $hubVerify;
		$this->protocol                = $protocol;
		$this->rating                  = $rating;
		$this->priority                = $priority;
	}

	public function addFollow()
	{
		$this->rel = in_array($this->rel, [Contact::FOLLOWER, Contact::FRIEND]) ? Contact::FRIEND : Contact::SHARING;
	}

	public function removeFollow()
	{
		$this->rel = in_array($this->rel, [Contact::FOLLOWER, Contact::FRIEND]) ? Contact::FOLLOWER : Contact::NOTHING;
	}

	public function addFollower()
	{
		$this->rel = in_array($this->rel, [Contact::SHARING, Contact::FRIEND]) ? Contact::FRIEND : Contact::FOLLOWER;
	}

	public function removeFollower()
	{
		$this->rel = in_array($this->rel, [Contact::SHARING, Contact::FRIEND]) ? Contact::SHARING : Contact::NOTHING;
	}
}
