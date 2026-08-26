<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Repository;

use Friendica\Database\DatabaseException;

/**
 * Interface for a repository for deleted users
 */
interface DeletedUserRepository
{
	/**
	 * Insert a deleted user by username.
	 *
	 * @throws DatabaseException If the username could not be inserted
	 */
	public function insertByUsername(string $username): void;

	/**
	 * Check if a deleted username exists.
	 *
	 * @throws \Exception
	 */
	public function existsByUsername(string $username): bool;
}
