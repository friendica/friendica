<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Worker;

use Friendica\Core\Worker;
use Friendica\DI;
use Friendica\Model\Contact;

class ContactDiscoveryForUser
{
	/**
	 * Discover contact relations
	 */
	public static function execute(int $uid)
	{
		Contact\Relation::discoverByUser($uid);
	}

	/**
	 * @param array|int $run_parameters Priority constant or array of options described in Worker::add
	 * @param int       $uid
	 * @return int
	 */
	public static function add($run_parameters, int $uid): int
	{
		DI::logger()->debug('Contact discovery for user', ['uid' => $uid]);
		return Worker::add($run_parameters, 'ContactDiscoveryForUser', $uid);
	}
}
