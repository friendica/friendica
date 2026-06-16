<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Content\Conversation\Factory;

use Friendica\Capabilities\ICanCreateFromTableRow;
use Friendica\Content\Conversation\Entity;

final class UserDefinedChannel extends Timeline implements ICanCreateFromTableRow
{
	public function isTimeline(string $selectedTab, int $uid): bool
	{
		return is_numeric($selectedTab) && $uid && $this->channelRepository->existsById($selectedTab, $uid);
	}

	public function createFromTableRow(array $row): Entity\UserDefinedChannel
	{
		if (is_string($row['languages'])) {
			$row['languages'] = unserialize($row['languages']);
		}

		if (!is_array($row['languages'])) {
			$row['languages'] = [];
		}

		return new Entity\UserDefinedChannel(
			$row['id'] ?? null,
			$row['label'],
			$row['description'] ?? null,
			$row['access-key'] ?? null,
			null,
			$row['uid'],
			$row['include-tags'] ?? null,
			$row['exclude-tags'] ?? null,
			$row['full-text-search'] ?? null,
			$row['media-type'] ?? null,
			$row['circle'] ?? null,
			$row['languages'],
			$row['publish'] ?? null,
			$row['valid'] ?? null,
			$row['min-size'] ?? null,
			$row['max-size'] ?? null,
		);
	}
}
