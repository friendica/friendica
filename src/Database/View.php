<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

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
		// just for Create purpose, reload the view definition with addons to explicit get the whole definition
		$definition = DI::viewDefinition()->load(true)->getAll();

		// Delete previously used views that aren't used anymore
		foreach (['post-view', 'post-thread-view'] as $view) {
			if (!isset($definition[$view]) && self::isView($view)) {
				self::execute(sprintf("DROP VIEW IF EXISTS `%s`;\n", DBA::escape($view)), $verbose, $action);
			}
		}

		// Existing views are replaced in place, so concurrent queries never miss them
		foreach ($definition as $name => $structure) {
			if (self::isTable($name)) {
				self::execute(sprintf("DROP TABLE IF EXISTS `%s`;\n", DBA::escape($name)), $verbose, $action);
			}
			self::execute(ViewDefinitionSqlWriter::createView($name, $structure, true), $verbose, $action);
		}
	}

	/**
	 * Shows and/or executes an SQL statement
	 *
	 * @param string $sql     SQL statement
	 * @param bool   $verbose Whether to show the SQL statement
	 * @param bool   $action  Whether to execute the SQL statement
	 * @return void
	 */
	private static function execute(string $sql, bool $verbose, bool $action)
	{
		if ($verbose) {
			echo $sql;
		}

		if ($action) {
			DBA::e($sql);
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
		$status = DBA::selectFirst(
			'INFORMATION_SCHEMA.TABLES',
			['TABLE_TYPE'],
			['TABLE_SCHEMA' => DBA::databaseName(), 'TABLE_NAME' => $view],
		);

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
		$status = DBA::selectFirst(
			'INFORMATION_SCHEMA.TABLES',
			['TABLE_TYPE'],
			['TABLE_SCHEMA' => DBA::databaseName(), 'TABLE_NAME' => $table],
		);

		if (empty($status['TABLE_TYPE'])) {
			return false;
		}

		return $status['TABLE_TYPE'] == 'BASE TABLE';
	}
}
