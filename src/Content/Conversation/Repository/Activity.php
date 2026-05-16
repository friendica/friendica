<?php

declare(strict_types=1);

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Content\Conversation\Repository;

use Friendica\Content\Conversation\Entity\Activity as ActivityEntity;
use Friendica\Database\Database;
use Friendica\Util\DateTimeFormat;

/**
 * Activity repository for handling activity table operations.
 */
final class Activity
{
	private Database $dba;

	/**
	 * ActivityRepository constructor.
	 *
	 * @param Database $dba
	 */
	public function __construct(Database $dba)
	{
		$this->dba = $dba;
	}

	/**
	 * Find an activity by uid and network.
	 *
	 * @param int $uid
	 * @param string $network
	 * @return ActivityEntity|null
	 */
	public function findByUidAndNetwork(int $uid, string $network): ?ActivityEntity
	{
		$activity = $this->dba->selectFirst('activity', [], ["`uid` = ? AND `network` = ? AND `expires` > ?", $uid, $network, DateTimeFormat::utcNow()]);
		if ($activity) {
			return ActivityEntity::fromArray($activity);
		}
		return null;
	}

	/**
	 * Save an activity (insert or update).
	 *
	 * @param ActivityEntity $activity
	 * @return bool
	 */
	public function save(ActivityEntity $activity): bool
	{
		$data              = $activity->toArray();
		$data['languages'] = json_encode($activity->languages);

		return $this->dba->insert('activity', $data, Database::INSERT_UPDATE);
	}
}
