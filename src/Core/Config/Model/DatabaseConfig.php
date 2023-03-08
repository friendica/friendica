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
use Friendica\Core\Config\Util\SerializeUtil;
use Friendica\Core\Config\ValueObject\Cache;
use Friendica\Database\Database;

/**
 * Complete system configuration model, bound with the database
 */
class DatabaseConfig implements IManageConfigValues
{
	/** @var Database */
	protected $database;
	/** @var Cache */
	protected $cache;

	public function __construct(Database $database, Cache $cache)
	{
		$this->database = $database;
		$this->cache    = $cache;

		$this->reload();
	}

	/** {@inheritDoc} */
	public function reload()
	{
		$config = $this->database->selectToArray('config');

		foreach ($config as $entry) {
			$this->cache->set($entry['cat'], $entry['k'], SerializeUtil::maybeUnserialize($entry['v']), Cache::SOURCE_DATA);
		}
	}

	public function setAndSave(Cache $setCache, Cache $delCache): bool
	{
		$this->database->transaction();

		foreach ($setCache->getAll() as $category => $data) {
			foreach ($data as $key => $value) {
				$this->set($category, $key, $value);
			}
		}

		foreach ($delCache->getAll() as $category => $keys) {
			foreach ($keys as $key => $value) {
				$this->delete($category, $key);
			}
		}

		return $this->database->commit();
	}

	/** {@inheritDoc} */
	public function get(string $cat, string $key = null, $default_value = null)
	{
		return $this->cache->get($cat, $key) ?? $default_value;
	}

	/** {@inheritDoc} */
	public function set(string $cat, string $key, $value): bool
	{
		// In case someone or something already serialized a config entry, unserialize it first
		// We serialize values just once
		$value = SerializeUtil::maybeUnserialize($value);

		$this->cache->set($cat, $key, $value, Cache::SOURCE_DATA);
		return $this->database->insert('config', ['cat' => $cat, 'k' => $key, 'v' => serialize($value)], Database::INSERT_UPDATE);
	}

	/** {@inheritDoc} */
	public function beginTransaction(): ISetConfigValuesTransactionally
	{
		return new ConfigTransaction($this);
	}

	/** {@inheritDoc} */
	public function delete(string $cat, string $key): bool
	{
		$this->cache->delete($cat, $key);
		return $this->database->delete('config', ['cat' => $cat, 'k' => $key]);
	}

	/** {@inheritDoc} */
	public function getCache(): Cache
	{
		return $this->cache;
	}
}
