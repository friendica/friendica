<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Factory\Api\Mastodon;

use Friendica\BaseFactory;
use Friendica\Content\Conversation\Entity\Timeline;
use Friendica\Database\Database;
use Friendica\Network\HTTPException\InternalServerErrorException;
use Psr\Log\LoggerInterface;

class ListEntity extends BaseFactory
{
	public function __construct(LoggerInterface $logger, private readonly Database $dba)
	{
		parent::__construct($logger);
	}

	/**
	 * @throws InternalServerErrorException
	 */
	public function createFromCircleId(int $id): \Friendica\Object\Api\Mastodon\ListEntity
	{
		$circle = $this->dba->selectFirst('group', ['name'], ['id' => $id, 'deleted' => false]);
		return new \Friendica\Object\Api\Mastodon\ListEntity($id, $circle['name'] ?? '', 'list');
	}

	public function createFromChannel(Timeline $channel): \Friendica\Object\Api\Mastodon\ListEntity
	{
		return new \Friendica\Object\Api\Mastodon\ListEntity('channel:' . $channel->code, $channel->label, 'followed');
	}

	public function createFromGroup(array $group): \Friendica\Object\Api\Mastodon\ListEntity
	{
		return new \Friendica\Object\Api\Mastodon\ListEntity('group:' . $group['id'], $group['name'], 'followed');
	}
}
