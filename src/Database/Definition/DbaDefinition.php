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

namespace Friendica\Database\Definition;

use Exception;
use Friendica\Core\Hook;

/**
 * Stores the whole database definition
 */
class DbaDefinition
{
	/** @var string The relative path of the db structure config file */
	const DBSTRUCTURE_RELATIVE_PATH = '/static/dbstructure.config.php';

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

		// Assign all field that are present in the table
		foreach ($fieldNames as $field) {
			if (isset($data[$field])) {
				// Limit the length of varchar, varbinary, char and binrary fields
				if (is_string($data[$field]) && preg_match("/char\((\d*)\)/", $definition[$table]['fields'][$field]['type'], $result)) {
					$data[$field] = mb_substr($data[$field], 0, $result[1]);
				} elseif (is_string($data[$field]) && preg_match("/binary\((\d*)\)/", $definition[$table]['fields'][$field]['type'], $result)) {
					$data[$field] = substr($data[$field], 0, $result[1]);
				} elseif (is_numeric($data[$field]) && $definition[$table]['fields'][$field]['type'] === 'int') {
					$data[$field] = min(max((int)$data[$field], -2147483648), 2147483647);
				} elseif (is_numeric($data[$field]) && $definition[$table]['fields'][$field]['type'] === 'int unsigned') {
					$data[$field] = min(max((int)$data[$field], 0), 4294967295);
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

		if (!$definition) {
			throw new Exception('Corrupted database structure config file static/dbstructure.config.php');
		}

		if ($withAddonStructure) {
			Hook::callAll('dbstructure_definition', $definition);
		}

		$this->definition = $definition;

		return $this;
	}
}
