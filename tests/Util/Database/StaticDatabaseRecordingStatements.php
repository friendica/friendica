<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\Util\Database;

/**
 * Test database that records the statements passed to e() instead of executing them
 *
 * DDL statements commit the open test transaction implicitly, so they must not reach the server.
 */
class StaticDatabaseRecordingStatements extends StaticDatabase
{
	/** @var string[] */
	public static array $statements = [];

	/**
	 * @inheritDoc
	 */
	public function e(string $sql): bool
	{
		self::$statements[] = $sql;

		return true;
	}
}
