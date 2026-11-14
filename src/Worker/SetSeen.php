<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Worker;

use Friendica\DI;
use Friendica\Model\Item;
use Friendica\Model\Post;

/**
 * Set posts seen for the given user.
 */
class SetSeen
{
	public static function execute(int $uid)
	{
		$ret = Item::update(['unseen' => false], Post::getUnseenCondition($uid));
		DI::logger()->debug('Set seen', ['uid' => $uid, 'ret' => $ret]);
	}
}
