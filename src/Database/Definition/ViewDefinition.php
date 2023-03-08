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
 * Stores the whole View definitions
 */
class ViewDefinition
{
	/** @var string the relative path to the database view config file */
	const DBSTRUCTURE_RELATIVE_PATH = '/static/dbview.config.php';

	/** @var array The complete view definition as an array */
	protected $definition;

	/** @var string */
	protected $configFile;

	/**
	 * @param string $basePath The basepath of the dbview file (loads relative path in case of null)
	 *
	 * @throws Exception in case the config file isn't available/readable
	 */
	public function __construct(string $basePath)
	{
		$this->configFile = $basePath . static::DBSTRUCTURE_RELATIVE_PATH;

		if (!is_readable($this->configFile)) {
			throw new Exception('Missing database structure config file static/dbview.config.php at basePath=' . $basePath);
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
	 * Loads the database structure definition from the static/dbview.config.php file.
	 * On first pass, defines DB_UPDATE_VERSION constant.
	 *
	 * @param bool  $withAddonStructure Whether to tack on addons additional tables
	 *
	 * @throws Exception in case the definition cannot be found
	 *
	 * @see static/dbview.config.php
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
			Hook::callAll('dbview_definition', $definition);
		}

		$this->definition = $definition;

		return $this;
	}
}
