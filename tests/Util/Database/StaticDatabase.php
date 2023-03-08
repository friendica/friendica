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

use Friendica\Database\Database;
use Friendica\Database\DatabaseException;
use PDO;
use PDOException;

/**
 * Overrides the Friendica database class for re-using the connection
 * for different tests
 *
 * Overrides functionality to enforce one transaction per call (for nested transactions)
 */
class StaticDatabase extends Database
{
	/**
	 * @var ExtendedPDO
	 */
	private static $staticConnection;

	/** @var bool  */
	private $_locked = false;

	/**
	 * Override the behaviour of connect, due there is just one, static connection at all
	 *
	 * @return bool Success
	 */
	public function connect(): bool
	{
		if (!is_null($this->connection) && $this->connected()) {
			return true;
		}

		if (!isset(self::$staticConnection)) {
			self::statConnect($_SERVER);
		}

		$this->driver = 'pdo';
		$this->connection = self::$staticConnection;
		$this->connected = true;
		$this->emulate_prepares = false;

		return $this->connected;
	}

	/**
	 * Override the transaction since there are now hierachical transactions possible
	 *
	 * @return bool
	 */
	public function transaction(): bool
	{
		if (!$this->in_transaction && !$this->connection->beginTransaction()) {
			return false;
		}

		$this->in_transaction = true;
		return true;
	}

	/** Mock for locking tables */
	public function lock($table): bool
	{
		if ($this->_locked) {
			return false;
		}

		$this->in_transaction = true;
		$this->_locked = true;

		return true;
	}

	/** Mock for unlocking tables */
	public function unlock(): bool
	{
		// See here: https://dev.mysql.com/doc/refman/5.7/en/lock-tables-and-transactions.html
		$this->performCommit();

		$this->in_transaction = false;
		$this->_locked = false;

		return true;
	}

	/**
	 * Does a commit
	 *
	 * @return bool Was the command executed successfully?
	 */
	public function commit(): bool
	{
		if (!$this->performCommit()) {
			return false;
		}
		$this->in_transaction = false;
		return true;
	}

	/**
	 * Setup of the global, static connection
	 * Either through explicit calling or through implicit using the Database
	 *
	 * @param array $server $_SERVER variables
	 *
	 * @throws \Exception
	 */
	public static function statConnect(array $server)
	{
		// Init variables
		$db_host = $db_user = $db_data = $db_pw = '';

		// Use environment variables for mysql if they are set beforehand
		if (!empty($server['MYSQL_HOST'])
		    && (!empty($server['MYSQL_USERNAME']) || !empty($server['MYSQL_USER']))
		    && $server['MYSQL_PASSWORD'] !== false
		    && !empty($server['MYSQL_DATABASE']))
		{
			$db_host = $server['MYSQL_HOST'];
			if (!empty($server['MYSQL_PORT'])) {
				$db_host .= ':' . $server['MYSQL_PORT'];
			}

			if (!empty($server['MYSQL_USERNAME'])) {
				$db_user = $server['MYSQL_USERNAME'];
			} else {
				$db_user = $server['MYSQL_USER'];
			}
			$db_pw = (string) $server['MYSQL_PASSWORD'];
			$db_data = $server['MYSQL_DATABASE'];
		}

		if (empty($db_host) || empty($db_user) || empty($db_data)) {
			throw new DatabaseException('Either one of the following settings are missing: Host, User or Database', 999, 'CONNECT');
		}

		$port       = 0;
		$serveraddr = trim($db_host);
		$serverdata = explode(':', $serveraddr);
		$server     = $serverdata[0];
		if (count($serverdata) > 1) {
			$port = (int) trim($serverdata[1]);
		}
		$server  = trim($server);
		$user    = trim($db_user);
		$pass    = trim($db_pw);
		$db      = trim($db_data);

		if (!(strlen($server) && strlen($user) && strlen($db))) {
			return;
		}

		$connect = "mysql:host=" . $server . ";dbname=" . $db;

		if ($port > 0) {
			$connect .= ";port=" . $port;
		}

		try {
			self::$staticConnection = @new ExtendedPDO($connect, $user, $pass);
			self::$staticConnection->setAttribute(PDO::ATTR_AUTOCOMMIT,0);
		} catch (PDOException $e) {
			/*
			 * @TODO Try to find a way to log this exception as it contains valueable information
			 * @nupplaphil@github.com comment:
			 *
			 * There is no easy possibility to add a logger here, that's why
			 * there isn't any yet and instead a placeholder.. This execution
			 * point is a critical state during a testrun, and tbh I'd like to
			 * leave here no further logic (yet) because I spent hours debugging
			 * cases, where transactions weren't fully closed and
			 * strange/unpredictable errors occur (sometimes -mainly during
			 * debugging other errors :) ...)
			 */
		}
	}

	/**
	 * @return ExtendedPDO The global, static connection
	 */
	public static function getGlobConnection()
	{
		return self::$staticConnection;
	}

	/**
	 * Perform a global rollback for every nested transaction of the static connection
	 */
	public static function statRollback()
	{
		if (isset(self::$staticConnection)) {
			while (self::$staticConnection->getTransactionDepth() > 0) {
				self::$staticConnection->rollBack();
			}
		}
	}
}
