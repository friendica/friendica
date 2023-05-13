<?php

namespace Friendica\Database\Capabilities;

use Friendica\Database\DatabaseException;

interface IDatabaseDriver
{
	/**
	 * Executes a prepared statement that returns data
	 *
	 * @usage Example: $r = execute("SELECT * FROM `post` WHERE `guid` = ?", $guid);
	 *
	 * Please only use it with complicated queries.
	 * For all regular queries please use Database::select or Database::exists
	 *
	 * @param string $sql           SQL statement
	 * @param array  $parameters optional parameters for the SQL statement
	 *
	 * @return IDatabaseError|IDatabaseResult
	 *
	 * @throws DatabaseException in case of unexpected connection loss or unexpected database failures
	 */
	public function read(string $sql, ...$parameters );

	/**
	 * Executes a prepared statement like UPDATE or INSERT that doesn't return data
	 *
	 * Please use DBA::delete, DBA::insert, DBA::update, ... instead
	 *
	 * @param string $sql           SQL statement
	 * @param mixed  ...$parameters optional parameters for the SQL statement
	 *
	 * @return bool Was the query successful? False is returned only if an error occurred
	 *
	 * @throws DatabaseException
	 */
	public function write(string $sql, ...$parameters): bool;

	/**
	 * Replaces the ? placeholders with the parameters in the $args array
	 *
	 * @param string $sql        SQL query
	 * @param array  $parameters The parameters that are to replace the ? placeholders
	 *
	 * @return string The replaced SQL query
	 */
	public function replaceParameters(string $sql, array $parameters): string;

	public function escape(string $parameter): string;

	public function reconnect(): bool;

	/**
	 * Tries to connect to database
	 *
	 * @return bool Success
	 */
	public function connect(): bool;

	public function disconnect();

	public function lastInsertId(): int;

	public function getDriver(): string;

	public function databaseName(): string;

	public function serverInfo(): string;

	public function isConnected(): bool;

	public function connected(): bool;

	public function inTransaction(): bool;

	public function transaction(): bool;

	public function commit(): bool;

	public function rollback(): bool;

	/**
	 * Fetches the first row
	 *
	 * Please use DBA::selectFirst or DBA::exists whenever this is possible.
	 *
	 * Fetches the first row
	 *
	 * @param string $sql SQL statement
	 * @param mixed  ...$parameters The parameters that are to replace the ? placeholders
	 *
	 * @return array|bool first row of query or false on failure
	 * @throws DatabaseException
	 */
	public function fetchFirst(string $sql, ...$parameters);

	/**
	 * Fetch a database variable
	 *
	 * @param string $name
	 * @return string|null content or null if inexistent
	 *
	 * @throws DatabaseException
	 */
	public function getVariable(string $name): ?string;
}
