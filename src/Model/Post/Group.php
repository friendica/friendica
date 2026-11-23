<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Model\Post;

use Friendica\Model\APContact;
use Friendica\Model\Tag;

class Group
{
	/**
	 * Returns the URLs of the groups a post is addressed to.
	 * A group is addressed either via the audience or via an exclusive mention.
	 * Tags that don't belong to an ActivityPub "Group" actor are ignored.
	 *
	 * @param int $uriId URI id of the post
	 * @return array List of group URLs
	 */
	public static function getAudience(int $uriId): array
	{
		$groups = [];
		foreach (Tag::getByURIId($uriId, [Tag::AUDIENCE, Tag::EXCLUSIVE_MENTION]) as $tag) {
			$apcontact = APContact::getByURL($tag['url'], false);
			if (($apcontact['type'] ?? '') == 'Group') {
				$groups[] = $tag['url'];
			}
		}
		return array_values(array_unique($groups));
	}

	/**
	 * Checks if a post is a group post, which means that it is addressed to a group
	 *
	 * @param int $uriId URI id of the post
	 * @return bool
	 */
	public static function isGroupPost(int $uriId): bool
	{
		return !empty(self::getAudience($uriId));
	}
}
