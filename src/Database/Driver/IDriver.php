<?php


namespace Friendica\Database\Driver;


interface IDriver
{
	/**
	 * Connecting to the current database
	 *
	 * @return bool True, if connection was successful
	 */
	function connect();

	/**
	 * Returns true if the database is connected
	 *
	 * @param bool $force Force a database in-deep check
	 *
	 * @return bool
	 */
	function isConnected($force = false);

	/**
	 * Disconnects the current database connection
	 */
	function disconnect();

	/**
	 * Perform a reconnect of an existing database connection
	 *
	 * @return bool Wsa the reconnect successful?
	 */
	function reconnect();

	/**
	 * Returns the server version string
	 *
	 * @return string
	 */
	function getServerInfo();

	/**
	 * Escapes a given existing statement based on the SQL connection
	 *
	 * @param string $sql
	 *
	 * @return string
	 */
	function escape($sql);

	/**
	 * Closes the current statement
	 *
	 * @param object $stmt
	 *
	 * @return bool
	 */
	function closeStatement($stmt);

	/**
	 * Commits the executed statements into the current connected database
	 *
	 * @return bool
	 */
	function performCommit();

	/**
	 * Starts a transaction for the current connected database
	 *
	 * @return bool
	 */
	function startTransaction();

	/**
	 * Does a rollback of the current transaction
	 *
	 * @return bool
	 */
	function rollbackTransaction();

	/**
	 * Fetches a row of the current cursor
	 *
	 * @param object $stmt
	 *
	 * @return array
	 */
	function fetch($stmt);

	/**
	 * Executes a given SQL in context of the current connected database
	 *
	 * @param string $sql
	 * @param array  $args
	 *
	 * @return object
	 *
	 * @throws DriverException In case the execution doesn't work
	 */
	function executePrepared($sql, array $args = []);

	/**
	 * Returns the number of affected rows for a given statement
	 *
	 * @param object $stmt
	 *
	 * @return int
	 *
	 * @throws DriverException In case the statement isn't valid
	 */
	function getNumRows($stmt);
}
