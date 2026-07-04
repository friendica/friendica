<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

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
		return in_array(
			$this->getAttribute(PDO::ATTR_DRIVER_NAME),
			self::$_supportedDrivers,
		);
	}


	/**
	 * Start transaction
	 */
	public function beginTransaction(): bool
	{
		if ($this->_transactionDepth <= 0 || !$this->hasSavepoint()) {
			parent::beginTransaction();
			$this->_transactionDepth = 0;
		} else {
			$this->exec("SAVEPOINT LEVEL{$this->_transactionDepth}");
		}

		$this->_transactionDepth++;

		return true;
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
			} catch (PDOException) {
				// this shouldn't happen, but it does ...
			}
		} else {
			return $this->exec("ROLLBACK TO SAVEPOINT LEVEL{$this->_transactionDepth}");
		}
		return false;
	}
}
