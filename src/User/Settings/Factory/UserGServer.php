<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\User\Settings\Factory;

use Friendica\Capabilities\ICanCreateFromTableRow;
use Friendica\Federation\Entity\GServer;
use Friendica\User\Settings\Entity;

class UserGServer extends \Friendica\BaseFactory implements ICanCreateFromTableRow
{
	/**
	 * @param array        $row    `user-gserver` table row
	 * @param GServer|null $server Corresponding GServer entity
	 * @return Entity\UserGServer
	 */
	public function createFromTableRow(array $row, ?GServer $server = null): Entity\UserGServer
	{
		return new Entity\UserGServer(
			$row['uid'],
			$row['gsid'],
			$row['ignored'],
			$server,
		);
	}

	/**
	 * @param int          $uid
	 * @param int          $gsid
	 * @param GServer|null $gserver Corresponding GServer entity
	 * @return Entity\UserGServer
	 */
	public function createFromUserAndServer(int $uid, int $gsid, ?GServer $gserver = null): Entity\UserGServer
	{
		return new Entity\UserGServer(
			$uid,
			$gsid,
			false,
			$gserver,
		);
	}
}
