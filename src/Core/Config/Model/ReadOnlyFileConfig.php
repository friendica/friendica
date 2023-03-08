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

namespace Friendica\Core\Config\Model;

use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\Config\Capability\ISetConfigValuesTransactionally;
use Friendica\Core\Config\Exception\ConfigPersistenceException;
use Friendica\Core\Config\ValueObject\Cache;

/**
 * Creates a basic, readonly model for the file-based configuration
 */
class ReadOnlyFileConfig implements IManageConfigValues
{
	/** @var Cache */
	protected $configCache;

	/**
	 * @param Cache $configCache The configuration cache (based on the config-files)
	 */
	public function __construct(Cache $configCache)
	{
		$this->configCache = $configCache;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getCache(): Cache
	{
		return $this->configCache;
	}

	/**    {@inheritDoc} */
	public function beginTransaction(): ISetConfigValuesTransactionally
	{
		throw new ConfigPersistenceException('beginTransaction not allowed.');
	}

	/** {@inheritDoc} */
	public function reload()
	{
		throw new ConfigPersistenceException('reload not allowed.');
	}

	/** {@inheritDoc} */
	public function get(string $cat, string $key = null, $default_value = null)
	{
		return $this->configCache->get($cat, $key) ?? $default_value;
	}

	/** {@inheritDoc} */
	public function set(string $cat, string $key, $value): bool
	{
		throw new ConfigPersistenceException('set not allowed.');
	}

	/** {@inheritDoc} */
	public function delete(string $cat, string $key): bool
	{
		throw new ConfigPersistenceException('Save not allowed');
	}
}
