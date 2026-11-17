<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Contact\Enum;

/**
 * Relationship between a user and a contact, stored in `contact.rel` and `user-contact.rel`.
 */
enum ContactRelation: int
{
	/** There is no relationship between the contact and the user */
	case Nothing = 0;
	/** The contact is following this user (the contact is the subscriber) */
	case Follower = 1;
	/** The contact shares their content with this user (the user is the subscriber) */
	case Sharing = 2;
	/** There is a mutual relationship between the contact and the user */
	case Friend = 3;
	/** This is the user themself */
	case Self = 4;

	/**
	 * Whether the contact follows the user.
	 */
	public function isFollower(): bool
	{
		return $this === self::Follower || $this === self::Friend;
	}

	/**
	 * Whether the user follows the contact.
	 */
	public function isSharing(): bool
	{
		return $this === self::Sharing || $this === self::Friend;
	}

	/**
	 * The relation after the contact started following the user.
	 */
	public function addFollower(): self
	{
		return $this->isSharing() ? self::Friend : self::Follower;
	}

	/**
	 * The relation after the contact stopped following the user.
	 */
	public function removeFollower(): self
	{
		return $this->isSharing() ? self::Sharing : self::Nothing;
	}

	/**
	 * The relation after the user started following the contact.
	 */
	public function addSharer(): self
	{
		return $this->isFollower() ? self::Friend : self::Sharing;
	}

	/**
	 * The relation after the user stopped following the contact.
	 */
	public function removeSharer(): self
	{
		return $this->isFollower() ? self::Follower : self::Nothing;
	}
}
