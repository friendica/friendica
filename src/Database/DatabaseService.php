<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Database;

use Friendica\Database\Database;
use Friendica\Database\Repository\DeletedUserRepository;
use Friendica\Repository\UserdRepository;

final class DatabaseService
{
	private Database $database;

	public function __construct(Database $database)
	{
		$this->database = $database;
	}

	public function getDeletedUserRepository(): DeletedUserRepository
	{
		return new UserdRepository($this->database);
	}
}
