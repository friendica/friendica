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
		return sprintf("CREATE VIEW `%s` AS SELECT \n\t", static::escape($viewName)) .
			   implode(",\n\t", $sql_rows) . "\n\t" . $viewStructure['query'] . ";\n\n";
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
