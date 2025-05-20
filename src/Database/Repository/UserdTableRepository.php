<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Database\Repository;

use Exception;
use Friendica\Database\Database;
use Friendica\Database\DatabaseException;
use Friendica\Repository\DeletedUserRepository;

/**
 * Repository for userd table
 */
final class UserdTableRepository implements DeletedUserRepository
{
	private Database $database;

	public function __construct(Database $database)
	{
		$this->database = $database;
	}

	/**
	 * Insert a deleted user by username.
	 *
	 * @throws DatabaseException If the username could not be inserted
	 */
	public function insertByUsername(string $username): void
	{
		$throw = $this->database->throwExceptionsOnErrors(true);

		try {
			$this->database->insert('userd', ['username' => $username]);
		} finally {
			$this->database->throwExceptionsOnErrors($throw);
		}
	}

	/**
	 * Check if a deleted username exists.
	 *
	 * @throws \Exception
	 */
	public function existsByUsername(string $username): bool
	{
		return $this->database->exists('userd', ['username' => $username]);
	}
}
