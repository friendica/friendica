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

/**
 * Repository for deleted users
 */
final class UserDeletedRepository
{
	private Database $database;

	public function __construct(Database $database)
	{
		$this->database = $database;
	}

	/**
	 * Insert a deleted user by username.
	 *
	 * @throws \Exception If the username could not be inserted
	 */
	public function insertByUsername(string $username): void
	{
		$result = $this->database->insert('userd', ['username' => $username]);

		if ($result === false) {
			throw new Exception(sprintf('Error while inserting username `%s` as deleted user.', $username));
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
