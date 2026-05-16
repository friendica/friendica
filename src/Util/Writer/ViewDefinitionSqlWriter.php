<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Util\Writer;

use Friendica\Database\Definition\ViewDefinition;

/**
 * SQL writer utility for the db view definition
 */
class ViewDefinitionSqlWriter
{
	/**
	 * Creates a complete SQL definition bases on a give View Definition class
	 *
	 * @param ViewDefinition $definition The View definition class
	 *
	 * @return string The SQL definition as a string
	 */
	public static function create(ViewDefinition $definition): string
	{
		$sqlString = '';

		foreach ($definition->getAll() as $viewName => $viewStructure) {
			$sqlString .= "--\n";
			$sqlString .= "-- VIEW $viewName\n";
			$sqlString .= "--\n";
			$sqlString .= static::dropView($viewName);
			$sqlString .= static::createView($viewName, $viewStructure);
		}

		return $sqlString;
	}

	/**
	 * Creates the SQL definition to drop a view
	 *
	 * @param string $viewName the view name
	 *
	 * @return string The SQL definition
	 */
	public static function dropView(string $viewName): string
	{
		return sprintf("DROP VIEW IF EXISTS `%s`", static::escape($viewName)) . ";\n";
	}

	/**
	 * Creates the SQL definition to create a new view
	 *
	 * @param string $viewName      The view name
	 * @param array  $viewStructure The structure information of the view
	 *
	 * @return string The SQL definition
	 */
	public static function createView(string $viewName, array $viewStructure): string
	{
		$sql_rows = [];
		foreach ($viewStructure['fields'] as $fieldname => $origin) {
			if (is_string($origin)) {
				$sql_rows[] = $origin . " AS `" . static::escape($fieldname) . "`";
			} elseif (is_array($origin) && (sizeof($origin) == 2)) {
				$sql_rows[] = "`" . static::escape($origin[0]) . "`.`" . static::escape($origin[1]) . "` AS `" . static::escape($fieldname) . "`";
			}
		}
		return sprintf("CREATE VIEW `%s` AS SELECT\n\t", static::escape($viewName))
			   . implode(",\n\t", $sql_rows) . "\n\t" . $viewStructure['query'] . ";\n\n";
	}

	/**
	 * Standard escaping for SQL definitions
	 *
	 * @param string $sqlString the SQL string to escape
	 *
	 * @return string escaped SQL string
	 */
	public static function escape(string $sqlString): string
	{
		return str_replace("'", "\\'", $sqlString);
	}
}
