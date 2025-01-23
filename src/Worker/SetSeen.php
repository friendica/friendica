<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Worker;

use Friendica\DI;
use Friendica\Model\Item;

/**
 * Set posts seen for the given user.
 */
class SetSeen
{
	public static function execute(int $uid)
	{
		$ret = Item::update(['unseen' => false], ['unseen' => true, 'uid' => $uid]);
		DI::logger()->debug('Set seen', ['uid' => $uid, 'ret' => $ret]);
	}
}
