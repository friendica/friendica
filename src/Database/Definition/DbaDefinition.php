<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Database\Definition;

use Exception;
use Friendica\DI;
use Friendica\Event\ArrayFilterEvent;

/**
 * Stores the whole database definition
 */
class DbaDefinition
{
	/** @var string The relative path of the db structure config file */
	public const DBSTRUCTURE_RELATIVE_PATH = '/static/dbstructure.config.php';

	/**
	 * Maximum length in bytes of the "text" and "blob" column types
	 *
	 * @var array<string, int>
	 */
	private const TEXT_LENGTHS = [
		'tinytext'   => 255,
		'text'       => 65535,
		'mediumtext' => 16777215,
		'longtext'   => 4294967295,
		'tinyblob'   => 255,
		'blob'       => 65535,
		'mediumblob' => 16777215,
		'longblob'   => 4294967295,
	];

	/**
	 * Value range of the integer column types
	 *
	 * "bigint" is capped at PHP_INT_MAX since PHP cannot represent larger integers.
	 *
	 * @var array<string, array{int, int}>
	 */
	private const INT_RANGES = [
		'tinyint'            => [-128, 127],
		'tinyint unsigned'   => [0, 255],
		'smallint'           => [-32768, 32767],
		'smallint unsigned'  => [0, 65535],
		'mediumint'          => [-8388608, 8388607],
		'mediumint unsigned' => [0, 16777215],
		'int'                => [-2147483648, 2147483647],
		'int unsigned'       => [0, 4294967295],
		'bigint'             => [PHP_INT_MIN, PHP_INT_MAX],
		'bigint unsigned'    => [0, PHP_INT_MAX],
	];

	/** @var array The complete DB definition as an array */
	protected $definition;

	/** @var string */
	protected $configFile;

	/**
	 * @param string $basePath The basepath of the dbstructure file (loads relative path in case of null)
	 *
	 * @throws Exception in case the config file isn't available/readable
	 */
	public function __construct(string $basePath)
	{
		$this->configFile = $basePath . static::DBSTRUCTURE_RELATIVE_PATH;

		if (!is_readable($this->configFile)) {
			throw new Exception('Missing database structure config file static/dbstructure.config.php at basePath=' . $basePath);
		}
	}

	/**
	 * @return array Returns the whole Definition as an array
	 */
	public function getAll(): array
	{
		return $this->definition;
	}

	/**
	 * Truncate field data for the given table
	 *
	 * @param string $table Name of the table to load field definitions for
	 * @param array  $data  data fields
	 *
	 * @return array fields for the given
	 */
	public function truncateFieldsForTable(string $table, array $data): array
	{
		$definition = $this->definition;
		if (empty($definition[$table])) {
			return [];
		}

		$fieldNames = array_keys($definition[$table]['fields']);

		$fields = [];

		$charset = DI::config()->get('database', 'charset') ?? '';

		// Assign all field that are present in the table
		foreach ($fieldNames as $field) {
			if (isset($data[$field]) || (!isset($definition[$table]['fields'][$field]['not null']) && array_key_exists($field, $data))) {
				$type = (string) $definition[$table]['fields'][$field]['type'];

				// Limit the length of varchar, varbinary, char and binary fields
				if (is_string($data[$field]) && preg_match("/char\((\d*)\)/", $type, $result)) {
					if ($charset == 'latin1') {
						$data[$field] = substr($data[$field], 0, (int) $result[1]);
					} else {
						$data[$field] = mb_substr($data[$field], 0, (int) $result[1]);
					}
				} elseif (is_string($data[$field]) && preg_match("/binary\((\d*)\)/", $type, $result)) {
					$data[$field] = substr($data[$field], 0, (int) $result[1]);
				} elseif (is_string($data[$field]) && isset(self::TEXT_LENGTHS[$type])) {
					// Unlike char fields, text and blob fields are limited in bytes, not in characters.
					// "mb_strcut" cuts at a byte offset without splitting a multi byte character.
					if (str_ends_with($type, 'blob')) {
						$data[$field] = substr($data[$field], 0, self::TEXT_LENGTHS[$type]);
					} else {
						$data[$field] = mb_strcut($data[$field], 0, self::TEXT_LENGTHS[$type]);
					}
				} elseif (is_numeric($data[$field]) && isset(self::INT_RANGES[$type])) {
					$data[$field] = min(max((int) $data[$field], self::INT_RANGES[$type][0]), self::INT_RANGES[$type][1]);
				}
				$fields[$field] = $data[$field];
			}
		}

		return $fields;
	}

	/**
	 * Loads the database structure definition from the static/dbstructure.config.php file.
	 * On first pass, defines DB_UPDATE_VERSION constant.
	 *
	 * @param bool  $withAddonStructure Whether to tack on addons additional tables
	 *
	 * @throws Exception in case the definition cannot be found
	 *
	 * @see static/dbstructure.config.php
	 *
	 * @return self The current instance
	 */
	public function load(bool $withAddonStructure = false): self
	{
		$definition = require $this->configFile;

		if (!is_array($definition)) {
			throw new Exception('Corrupted database structure config file static/dbstructure.config.php');
		}

		if ($withAddonStructure) {
			$eventDispatcher = DI::eventDispatcher();

			$definition = $eventDispatcher->dispatch(
				new ArrayFilterEvent(ArrayFilterEvent::DB_STRUCTURE_DEFINITION, $definition),
			)->getArray();
		}

		$this->definition = $definition;

		return $this;
	}
}
