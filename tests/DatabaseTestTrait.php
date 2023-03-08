<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

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
		StaticDatabase::getGlobConnection()->rollBack();
		// Start the first, outer transaction
		StaticDatabase::getGlobConnection()->beginTransaction();
	}

	protected function tearDownDb()
	{
		try {
			// Rollbacks every DB usage so we don't commit anything into the DB
			StaticDatabase::statRollback();
		} catch (\PDOException $exception) {
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
