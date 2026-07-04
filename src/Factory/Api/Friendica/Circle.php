<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Factory\Api\Friendica;

use Friendica\BaseFactory;
use Friendica\Database\Database;
use Friendica\Network\HTTPException;
use Psr\Log\LoggerInterface;
use Friendica\Factory\Api\Twitter\User as TwitterUser;

class Circle extends BaseFactory
{
	public function __construct(LoggerInterface $logger, /** @var twitterUser entity */
		private readonly TwitterUser $twitterUser, private readonly Database $dba)
	{
		parent::__construct($logger);
	}

	/**
	 * @param int $id id of the circle
	 * @return array
	 * @throws HTTPException\InternalServerErrorException
	 */
	public function createFromId(int $id): array
	{
		$circle = $this->dba->selectFirst('group', [], ['id' => $id, 'deleted' => false]);
		if (empty($circle)) {
			return [];
		}

		$user   = $this->twitterUser->createFromUserId($circle['uid'])->toArray();
		$object = new \Friendica\Object\Api\Friendica\Circle($circle, $user);

		return $object->toArray();
	}
}
