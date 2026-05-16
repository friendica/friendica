<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Database;

use Friendica\Database\Repository\CacheTableRepository;
use Friendica\Database\Repository\UserdTableRepository;
use Friendica\Repository\CacheRepository;
use Friendica\Repository\DeletedUserRepository;

final class DatabaseService
{
	private Database $database;

	public function __construct(Database $database)
	{
		$this->database = $database;
	}

	public function getDeletedUserRepository(): DeletedUserRepository
	{
		return new UserdTableRepository($this->database);
	}

	public function getCacheRepository(): CacheRepository
	{
		return new CacheTableRepository($this->database);
	}
}
