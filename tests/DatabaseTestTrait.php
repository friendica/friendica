<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test;

use Friendica\Database\Database;
use Friendica\Test\Util\Database\StaticDatabase;

/**
 * Abstract class used by tests that need a database.
 */
trait DatabaseTestTrait
{
	protected function setUpDb()
	{
		StaticDatabase::statConnect($_SERVER);
		// Rollbacks every DB usage (in case the test couldn't call tearDown)
		StaticDatabase::statRollback();
		// Rollback the first, outer transaction just 2 be sure
		StaticDatabase::getGlobConnection()->rollback();
		// Start the first, outer transaction
		StaticDatabase::getGlobConnection()->beginTransaction();
	}

	protected function tearDownDb()
	{
		try {
			// Rollbacks every DB usage so we don't commit anything into the DB
			StaticDatabase::statRollback();
		} catch (\PDOException) {
			print_r("Found already rolled back transaction");
		}
	}

	/**
	 * Loads a given DB fixture for this DB test
	 *
	 * @param string[][] $fixture The fixture array
	 * @param Database $dba     The DB connection
	 *
	 * @throws \Exception
	 */
	protected function loadDirectFixture(array $fixture, Database $dba)
	{
		foreach ($fixture as $tableName => $rows) {
			if (is_numeric($tableName)) {
				continue;
			}

			if (!is_array($rows)) {
				$dba->e('TRUNCATE TABLE `' . $tableName . '``');
				continue;
			}

			foreach ($rows as $row) {
				if (is_array($row)) {
					$dba->insert($tableName, $row, true);
				} else {
					throw new \Exception('row isn\'t an array');
				}
			}
		}
	}

	/**
	 * Loads a given DB fixture-file for this DB test
	 *
	 * @param string   $fixture The path to the fixture
	 * @param Database $dba     The DB connection
	 *
	 * @throws \Exception
	 */
	protected function loadFixture(string $fixture, Database $dba)
	{
		$data = include $fixture;

		$this->loadDirectFixture($data, $dba);
	}
}
