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

namespace Friendica\Database;

use Friendica\DI;
use Friendica\Util\Writer\ViewDefinitionSqlWriter;

class View
{
	/**
	 * Creates a view
	 *
	 * @param bool $verbose Whether to show SQL statements
	 * @param bool $action Whether to execute SQL statements
	 * @return void
	 */
	public static function create(bool $verbose, bool $action)
	{
		// Delete previously used views that aren't used anymore
		foreach (['post-view', 'post-thread-view'] as $view) {
			if (self::isView($view)) {
				$sql = sprintf("DROP VIEW IF EXISTS `%s`", DBA::escape($view));
				if (!empty($sql) && $verbose) {
					echo $sql . ";\n";
				}

				if (!empty($sql) && $action) {
					DBA::e($sql);
				}
			}
		}

		// just for Create purpose, reload the view definition with addons to explicit get the whole definition
		$definition = DI::viewDefinition()->load(true)->getAll();

		foreach ($definition as $name => $structure) {
			if (self::isView($name)) {
				DBA::e(sprintf("DROP VIEW IF EXISTS `%s`", DBA::escape($name)));
			} elseif (self::isTable($name)) {
				DBA::e(sprintf("DROP TABLE IF EXISTS `%s`", DBA::escape($name)));
			}
			DBA::e(ViewDefinitionSqlWriter::createView($name, $structure));
		}
	}

	/**
	 * Check if the given table/view is a view
	 *
	 * @param string $view
	 * @return boolean "true" if it's a view
	 */
	private static function isView(string $view): bool
	{
		$status = DBA::selectFirst('INFORMATION_SCHEMA.TABLES', ['TABLE_TYPE'],
			['TABLE_SCHEMA' => DBA::databaseName(), 'TABLE_NAME' => $view]);

		if (empty($status['TABLE_TYPE'])) {
			return false;
		}

		return $status['TABLE_TYPE'] == 'VIEW';
	}

	/**
	 * Check if the given table/view is a table
	 *
	 * @param string $table
	 * @return boolean "true" if it's a table
	 */
	private static function isTable(string $table): bool
	{
		$status = DBA::selectFirst('INFORMATION_SCHEMA.TABLES', ['TABLE_TYPE'],
			['TABLE_SCHEMA' => DBA::databaseName(), 'TABLE_NAME' => $table]);

		if (empty($status['TABLE_TYPE'])) {
			return false;
		}

		return $status['TABLE_TYPE'] == 'BASE TABLE';
	}
}
