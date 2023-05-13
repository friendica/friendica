<?php

namespace Friendica\Database;

use Friendica\Database\Capabilities\IDatabase;
use Friendica\Database\Capabilities\IDatabaseDriver;
use Friendica\Database\Capabilities\IDatabaseResult;
use Friendica\Database\Definition\DbaDefinition;
use Friendica\Database\Definition\ViewDefinition;
use Friendica\Database\PDO;

class Database_ implements IDatabase
{
	/** @var IDatabaseDriver */
	protected $driver;
	/** @var DbaDefinition */
	protected $dbaDefinition;
	/** @var ViewDefinition */
	protected $viewDefinition;

	public function __construct(IDatabaseDriver $driver)
	{
		$this->driver         = $driver;
		$this->dbaDefinition  = $dbaDefinition;
		$this->viewDefinition = $viewDefinition;
	}

	/**
	 * PDO only - Cast field types according to the table definition
	 *
	 * @param string $table
	 * @param array  $fields
	 *
	 * @return array casted fields
	 */
	protected function castFields(string $table, array $fields): array
	{

	}

	/** {@inheritDoc} */
	public function selectToArray(string $table, array $fields = [], array $condition = [], array $params = []): array
	{
		$result = $this->select($table, $fields, $condition, $params);
		if ($result instanceof IDatabaseResult) {
			return $result->toArray();
		}

		return [];
	}

	/** {@inheritDoc} */
	public function selectFirst(string $table, array $fields = [], array $condition = [], array $params = [])
	{
		$params['limit'] = 1;
		$result          = $this->select($table, $fields, $condition, $params);

		if (is_bool($result)) {
			return $result;
		} elseif ($result instanceof IDatabaseResult) {
			$row = $result->fetch();
			$result->close();
			return $row;
		} else {
			return false;
		}
	}

	/** {@inheritDoc} */
	public function select(string $table, array $fields = [], array $condition = [], array $params = [])
	{
		if (empty($table)) {
			return false;
		}

		if (count($fields) > 0) {
			$fields        = DatabaseUtils::escapeFields($fields, $params);
			$select_string = implode(', ', $fields);
		} else {
			$select_string = '*';
		}

		$table_string = DatabaseUtils::buildTableString([$table]);

		$condition_string = DatabaseUtils::buildCondition($condition);

		$param_string = DatabaseUtils::buildParameter($params);

		$sql = "SELECT " . $select_string . " FROM " . $table_string . $condition_string . $param_string;

		return $this->driver->read($sql, ...$condition);
	}

	/** {@inheritDoc} */
	public function insert(string $table, array $param, int $duplicate_mode = self::INSERT_DEFAULT)
	{
		if (empty($table) || empty($param)) {
			return false;
		}

		$param = $this->castFields($table, $param);

		$table_string = DatabaseUtils::buildTableString([$table]);

		$fields_string = implode(', ', array_map([DatabaseUtils::class, 'quoteIdentifier'], array_keys($param)));

		$values_string = substr(str_repeat("?, ", count($param)), 0, -2);

		$sql = "INSERT ";

		if ($duplicate_mode == static::INSERT_IGNORE) {
			$sql .= "IGNORE ";
		}

		$sql .= "INTO " . $table_string . " (" . $fields_string . ") VALUES (" . $values_string . ")";

		if ($duplicate_mode == static::INSERT_UPDATE) {
			$fields_string = implode(' = ?, ', array_map([DatabaseUtils::class, 'quoteIdentifier'], array_keys($param)));

			$sql .= " ON DUPLICATE KEY UPDATE " . $fields_string . " = ?";

			$values = array_values($param);
			$param  = array_merge_recursive($values, $values);
		}

		$result = $this->driver->write($sql, $param);
		if (!$result || ($duplicate_mode !== static::INSERT_IGNORE)) {
			return $result;
		} else {
			return $this->driver->lastInsertId();
		}
	}

	/** {@inheritDoc} */
	public function exists(string $table, array $condition = []): bool
	{
		if (empty($table)) {
			return false;
		}

		$fields = [];

		// if no conditions set, check if the table exists
		if (empty($condition)) {
			return $this->exists('information_schema.tables', [
				'table_schema' => $this->driver->databaseName() ,
				'table_name' => $table]);
		}

		reset($condition);
		$first_key = key($condition);
		if (!is_int($first_key)) {
			$fields = [$first_key];
		}

		$stmt = $this->select($table, $fields, $condition, ['limit' => 1]);

		if (is_bool($stmt)) {
			$retval = $stmt;
		} elseif ($stmt instanceof IDatabaseResult) {
			$retval = ($stmt->getAffectedRows() > 0);
			$stmt->close();
		} else  {
			$retval = false;
		}

		return $retval;
	}


	/** {@inheritDoc} */
	public function delete(string $table, array $conditions): bool
	{
		if (empty($table) || empty($conditions)) {
			return false;
		}

		$table_string = DatabaseUtils::buildTableString([$table]);

		$condition_string = DatabaseUtils::buildCondition($conditions);

		$sql = "DELETE FROM " . $table_string . " " . $condition_string;
		return $this->driver->write($sql, $conditions);
	}

	/** {@inheritDoc} */
	public function replace(string $table, array $param): bool
	{
		if (empty($table) || empty($param)) {
			return false;
		}

		$param = $this->castFields($table, $param);

		$table_string = DatabaseUtils::buildTableString([$table]);

		$fields_string = implode(', ', array_map([DatabaseUtils::class, 'quoteIdentifier'], array_keys($param)));

		$values_string = substr(str_repeat("?, ", count($param)), 0, -2);

		$sql = "REPLACE " . $table_string . " (" . $fields_string . ") VALUES (" . $values_string . ")";

		return $this->driver->write($sql, $param);
	}

	/** {@inheritDoc} */
	public function count(string $table, array $condition = [], array $params = []): int
	{
		if (empty($table)) {
			throw new \InvalidArgumentException('Parameter "table" cannot be empty.');
		}

		$table_string = DatabaseUtils::buildTableString([$table]);

		$condition_string = DatabaseUtils::buildCondition($condition);

		if (empty($params['expression'])) {
			$expression = '*';
		} elseif (!empty($params['distinct'])) {
			$expression = "DISTINCT " . DatabaseUtils::quoteIdentifier($params['expression']);
		} else {
			$expression = DatabaseUtils::quoteIdentifier($params['expression']);
		}

		$sql = "SELECT COUNT(" . $expression . ") AS `count` FROM " . $table_string . $condition_string;

		$row = $this->driver->fetchFirst($sql, ...$condition);

		if (!isset($row['count'])) {
			return 0;
		} else {
			return (int)$row['count'];
		}
	}

	/** {@inheritdoc} */
	public function update(string $table, array $fields, array $condition, $old_fields = [], array $params = []): bool
	{
		if (empty($table) || empty($fields) || empty($condition)) {
			return false;
		}

		if (is_bool($old_fields)) {
			$do_insert = $old_fields;

			$old_fields = $this->selectFirst($table, [], $condition);

			if (is_bool($old_fields)) {
				if ($do_insert) {
					$values = array_merge($condition, $fields);
					return $this->replace($table, $values);
				}
				$old_fields = [];
			}
		}

		foreach ($old_fields as $fieldname => $content) {
			if (isset($fields[$fieldname]) && !is_null($content) && ($fields[$fieldname] == $content)) {
				unset($fields[$fieldname]);
			}
		}

		if (count($fields) == 0) {
			return true;
		}

		$params = $this->castFields($table, $params);

		$table_string = DatabaseUtils::buildTableString([$table]);

		$condition_string = DatabaseUtils::buildCondition($condition);

		if (!empty($params['ignore'])) {
			$ignore = 'IGNORE ';
		} else {
			$ignore = '';
		}

		$sql = "UPDATE " . $ignore . $table_string . " SET "
			   . implode(" = ?, ", array_map([DatabaseUtils::class, 'quoteIdentifier'], array_keys($fields))) . " = ?"
			   . $condition_string;

		// Combines the updated fields parameter values with the condition parameter values
		$params = array_merge(array_values($fields), $condition);

		return $this->driver->write($sql, $params);
	}
}
