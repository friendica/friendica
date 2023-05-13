<?php

namespace Friendica\Database\Mysqli;

use Friendica\Database\AbstractDBResult;
use Friendica\Database\Capabilities\IDatabaseResult;
use mysqli;
use mysqli_result;
use mysqli_stmt;

class Result extends AbstractDBResult implements IDatabaseResult
{
	/** @var mysqli_result|mysqli_stmt */
	protected $result;
	/** @var int */
	protected $affectedRows;

	/**
	 * @param mysqli_result|mysqli_stmt $result
	 * @param mysqli                    $connection
	 */
	public function __construct($result, mysqli $connection)
	{
		$this->result       = $result;
		$this->affectedRows = $result->num_rows ?: $connection->affected_rows;
	}

	public function getAffectedRows(): int
	{
		return $this->affectedRows;
	}

	public function getColumnCount(): int
	{
		return $this->result->field_count;
	}

	public function close(): bool
	{
		if ($this->result instanceof mysqli_stmt) {
			$this->result->free_result();
			return $this->result->close();
		} else {
			$this->result->free();
			return true;
		}
	}

	public function fetch()
	{
		if ($this->result instanceof mysqli_result) {
			return $this->result->fetch_assoc() ?? false;
		} else {
			// This code works, but is slow

			// Bind the result to a result array
			$cols = [];

			$cols_num = [];
			for ($x = 0; $x < $this->result->field_count; $x++) {
				$cols[] = &$cols_num[$x];
			}

			call_user_func_array([$this->result, 'bind_result'], $cols);

			if (!$this->result->fetch()) {
				return false;
			}

			// The slow part:
			// We need to get the field names for the array keys
			// It seems that there is no better way to do this.
			$result = $this->result->result_metadata();
			$fields = $result->fetch_fields();

			$columns = [];

			foreach ($cols_num as $param => $col) {
				$columns[$fields[$param]->name] = $col;
			}

			return $columns;
		}
	}
}
