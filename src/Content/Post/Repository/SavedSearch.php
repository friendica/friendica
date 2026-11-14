<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Content\Post\Repository;

use Friendica\Database\Database;

/**
 * Repository for the saved searches of a user
 */
class SavedSearch
{
	public function __construct(
		private readonly Database $database,
	) {}

	/**
	 * @param int $uid
	 * @return string[] The saved search terms, ordered alphabetically
	 * @throws \Exception
	 */
	public function selectTermsByUid(int $uid): array
	{
		return array_column($this->database->selectToArray('search', ['term'], ['uid' => $uid], ['order' => ['term']]), 'term');
	}
}
