<?php

namespace Friendica\Database;

class DatabaseUtils
{
	/**
	 * beautifies the query - useful for "SHOW PROCESSLIST"
	 *
	 * This is safe when we bind the parameters later.
	 * The parameter values aren't part of the SQL.
	 *
	 * @param string $sql An SQL string without the values
	 * @return string The input SQL string modified if necessary.
	 */
	public static function cleanQuery(string $sql): string
	{
		$search = ["\t", "\n", "\r", "  "];
		$replace = [' ', ' ', ' ', ' '];
		do {
			$oldsql = $sql;
			$sql = str_replace($search, $replace, $sql);
		} while ($oldsql != $sql);

		return $sql;
	}

	/**
	 * Build the table query substring from one or more tables, with or without a schema.
	 *
	 * Expected formats:
	 * - [table]
	 * - [table1, table2, ...]
	 * - [schema1 => table1, schema2 => table2, table3, ...]
	 *
	 * @param array $tables Table names
	 * @return string
	 */
	public static function buildTableString(array $tables): string
	{
		// Quote each entry
		return implode(',', array_map([static::class, 'quoteIdentifier'], $tables));
	}

	/**
	 * Escape an identifier (table or field name) optional with a schema like ((schema.)table.)field
	 *
	 * @param string $identifier Table, field name
	 * @return string Quotes table or field name
	 */
	public static function quoteIdentifier(string $identifier): string
	{
		return implode(
			'.',
			array_map(
				function (string $identifier) { return '`' . str_replace('`', '``', $identifier) . '`'; },
				explode('.', $identifier)
			)
		);
	}

	/**
	 * Escape fields, adding special treatment for "group by" handling
	 *
	 * @param array $fields
	 * @param array $options
	 * @return array Escaped fields
	 */
	public static function escapeFields(array $fields, array $options): array
	{
		// In the case of a "GROUP BY" we have to add all the ORDER fields to the fieldlist.
		// This needs to done to apply the "ANY_VALUE(...)" treatment from below to them.
		// Otherwise MySQL would report errors.
		if (!empty($options['group_by']) && !empty($options['order'])) {
			foreach ($options['order'] as $key => $field) {
				if (!is_int($key)) {
					if (!in_array($key, $fields)) {
						$fields[] = $key;
					}
				} else {
					if (!in_array($field, $fields)) {
						$fields[] = $field;
					}
				}
			}
		}

		array_walk($fields, function (&$value, $key) use ($options) {
			$field = $value;
			$value = static::quoteIdentifier($field);

			if (!empty($options['group_by']) && !in_array($field, $options['group_by'])) {
				$value = 'ANY_VALUE(' . $value . ') AS ' . $value;
			}
		});

		return $fields;
	}

	/**
	 * Returns the SQL condition string built from the provided condition array
	 *
	 * This function operates with two modes.
	 * - Supplied with a field/value associative array, it builds simple strict
	 *   equality conditions linked by AND.
	 * - Supplied with a flat list, the first element is the condition string and
	 *   the following arguments are the values to be interpolated
	 *
	 * $condition = ["uid" => 1, "network" => 'dspr'];
	 * or:
	 * $condition = ["`uid` = ? AND `network` IN (?, ?)", 1, 'dfrn', 'dspr'];
	 *
	 * In either case, the provided array is left with the parameters only
	 *
	 * @param array $condition
	 * @return string
	 */
	public static function buildCondition(array &$condition = []): string
	{
		$condition = static::collapseCondition($condition);

		$condition_string = '';
		if (count($condition) > 0) {
			$condition_string = " WHERE (" . array_shift($condition) . ")";
		}

		return $condition_string;
	}

	/**
	 * Collapse an associative array condition into a SQL string + parameters condition array.
	 *
	 * ['uid' => 1, 'network' => ['dspr', 'apub']]
	 *
	 * gets transformed into
	 *
	 * ["`uid` = ? AND `network` IN (?, ?)", 1, 'dspr', 'apub']
	 *
	 * @param array $condition
	 * @return array
	 */
	public static function collapseCondition(array $condition): array
	{
		// Ensures an always true condition is returned
		if (count($condition) < 1) {
			return ['1'];
		}

		reset($condition);
		$first_key = key($condition);

		if (is_int($first_key)) {
			// Already collapsed
			return $condition;
		}

		$values = [];
		$condition_string = "";
		foreach ($condition as $field => $value) {
			if ($condition_string != "") {
				$condition_string .= " AND ";
			}

			if (is_array($value)) {
				if (count($value)) {
					/* Workaround for MySQL Bug #64791.
					 * Never mix data types inside any IN() condition.
					 * In case of mixed types, cast all as string.
					 * Logic needs to be consistent with DBA::p() data types.
					 */
					$is_int = false;
					$is_alpha = false;
					foreach ($value as $single_value) {
						if (is_int($single_value)) {
							$is_int = true;
						} else {
							$is_alpha = true;
						}
					}

					if ($is_int && $is_alpha) {
						foreach ($value as &$ref) {
							if (is_int($ref)) {
								$ref = (string)$ref;
							}
						}
						unset($ref); //Prevent accidental re-use.
					}

					$values = array_merge($values, array_values($value));
					$placeholders = substr(str_repeat("?, ", count($value)), 0, -2);
					$condition_string .= static::quoteIdentifier($field) . " IN (" . $placeholders . ")";
				} else {
					// Empty value array isn't supported by IN and is logically equivalent to no match
					$condition_string .= "FALSE";
				}
			} elseif (is_null($value)) {
				$condition_string .= static::quoteIdentifier($field) . " IS NULL";
			} else {
				$values[$field] = $value;
				$condition_string .= static::quoteIdentifier($field) . " = ?";
			}
		}

		$condition = array_merge([$condition_string], array_values($values));

		return $condition;
	}

	/**
	 * Merges the provided conditions into a single collapsed one
	 *
	 * @param array ...$conditions One or more condition arrays
	 * @return array A collapsed condition
	 * @see DBA::collapseCondition() for the condition array formats
	 */
	public static function mergeConditions(array ...$conditions): array
	{
		if (count($conditions) == 1) {
			return current($conditions);
		}

		$conditionStrings = [];
		$result = [];

		foreach ($conditions as $key => $condition) {
			if (!$condition) {
				continue;
			}

			$condition = static::collapseCondition($condition);

			$conditionStrings[] = array_shift($condition);
			// The result array holds the eventual parameter values
			$result = array_merge($result, $condition);
		}

		if (count($conditionStrings)) {
			// We prepend the condition string at the end to form a collapsed condition array again
			array_unshift($result, implode(' AND ', $conditionStrings));
		}

		return $result;
	}

	/**
	 * Returns the SQL parameter string built from the provided parameter array
	 *
	 * Expected format for each key:
	 *
	 * group_by:
	 *  - list of column names
	 *
	 * order:
	 *  - numeric keyed column name => ASC
	 *  - associative element with boolean value => DESC (true), ASC (false)
	 *  - associative element with string value => 'ASC' or 'DESC' literally
	 *
	 * limit:
	 *  - single numeric value => count
	 *  - list with two numeric values => offset, count
	 *
	 * @param array $params
	 * @return string
	 */
	public static function buildParameter(array $params = []): string
	{
		$groupby_string = '';
		if (!empty($params['group_by'])) {
			$groupby_string = " GROUP BY " . implode(', ', array_map([static::class, 'quoteIdentifier'], $params['group_by']));
		}

		$order_string = '';
		if (isset($params['order'])) {
			$order_string = " ORDER BY ";
			foreach ($params['order'] as $fields => $order) {
				if ($order === 'RAND()') {
					$order_string .= "RAND(), ";
				} elseif (!is_int($fields)) {
					if ($order !== 'DESC' && $order !== 'ASC') {
						$order = $order ? 'DESC' : 'ASC';
					}

					$order_string .= static::quoteIdentifier($fields) . " " . $order . ", ";
				} else {
					$order_string .= static::quoteIdentifier($order) . ", ";
				}
			}
			$order_string = substr($order_string, 0, -2);
		}

		$limit_string = '';
		if (isset($params['limit']) && is_numeric($params['limit'])) {
			$limit_string = " LIMIT " . intval($params['limit']);
		}

		if (isset($params['limit']) && is_array($params['limit'])) {
			$limit_string = " LIMIT " . intval($params['limit'][0]) . ", " . intval($params['limit'][1]);
		}

		return $groupby_string . $order_string . $limit_string;
	}
}
