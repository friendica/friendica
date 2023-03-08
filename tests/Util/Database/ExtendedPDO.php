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

namespace Friendica\Test\Util\Database;

use PDO;
use PDOException;

/**
 * This class extends native PDO one but allow nested transactions
 * by using the SQL statements `SAVEPOINT', 'RELEASE SAVEPOINT' AND 'ROLLBACK SAVEPOINT'
 */
class ExtendedPDO extends PDO
{
	/**
	 * @var array Database drivers that support SAVEPOINT * statements.
	 */
	protected static $_supportedDrivers = ['pgsql', 'mysql'];

	/**
	 * @var int the current transaction depth
	 */
	protected $_transactionDepth = 0;

	/**
	 * @return int
	 */
	public function getTransactionDepth()
	{
		return $this->_transactionDepth;
	}

	/**
	 * Test if database driver support savepoints
	 *
	 * @return bool
	 */
	protected function hasSavepoint()
	{
		return in_array($this->getAttribute(PDO::ATTR_DRIVER_NAME),
			self::$_supportedDrivers);
	}


	/**
	 * Start transaction
	 *
	 * @return bool|void
	 */
	public function beginTransaction()
	{
		if($this->_transactionDepth <= 0 || !$this->hasSavepoint()) {
			parent::beginTransaction();
			$this->_transactionDepth = 0;
		} else {
			$this->exec("SAVEPOINT LEVEL{$this->_transactionDepth}");
		}

		$this->_transactionDepth++;
	}

	/**
	 * Commit current transaction
	 *
	 * @return bool
	 */
	public function commit(): bool
	{
		// We don't want to "really" commit something, so skip the most outer hierarchy
		if ($this->_transactionDepth <= 1 && $this->hasSavepoint()) {
			$this->_transactionDepth = $this->_transactionDepth <= 0 ? 0 : 1;
			return true;
		}

		$this->_transactionDepth--;

		return $this->exec("RELEASE SAVEPOINT LEVEL{$this->_transactionDepth}");
	}

	/**
	 * Rollback current transaction,
	 *
	 * @throws PDOException if there is no transaction started
	 * @return bool Whether rollback was successful
	 */
	public function rollback(): bool
	{
		$this->_transactionDepth--;

		if ($this->_transactionDepth <= 0 || !$this->hasSavepoint()) {
			$this->_transactionDepth = 0;
			try {
				return parent::rollBack();
			} catch (PDOException $e) {
				// this shouldn't happen, but it does ...
			}
		} else {
			return $this->exec("ROLLBACK TO SAVEPOINT LEVEL{$this->_transactionDepth}");
		}
		return false;
	}
}
