<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Database\Repository;

use Friendica\Database\Database;

final class UserDeletedRepository
{
	private Database $database;

	public function __construct(Database $database)
	{
		$this->database = $database;
	}

	public function existsByUsername(string $username): bool
	{
		return $this->database->exists('userd', ['username' => $username]);
	}
}
