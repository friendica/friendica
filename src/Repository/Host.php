<?php
/**
 * @copyright Copyright (C) 2020, Friendica
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

namespace Friendica\Repository;

use Friendica\BaseRepository;
use Friendica\Collection;
use Friendica\Model;
use Friendica\Network\HTTPException\InternalServerErrorException;

class Host extends BaseRepository
{
	/**
	 * Defines the environment variable, which includes the current node name instead of the detected hostname
	 *
	 * @var string
	 *
	 * @notice This is used for cluster environments, where the each node defines it own hostname.
	 */
	const ENV_VARIABLE = 'NODE_NAME';

	protected static $table_name = 'host';

	protected static $model_class = Model\Host::class;

	protected static $collection_class = Collection\Hosts::class;

	/**
	 * @param array $data
	 * @return Model\Host
	 */
	protected function create(array $data)
	{
		return new Model\Host($this->dba, $this->logger, $data);
	}

	/**
	 * @param array $server The $_SERVER variable
	 *
	 * @return Model\Host
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \Exception
	 */
	public function selectCurrentHost(array $server = [])
	{
		$hostname = $server[self::ENV_VARIABLE] ?? null;

		if (empty($hostname)) {
			$hostname = gethostname();
		}

		// Trim whitespaces first to avoid getting an empty hostname
		// For linux the hostname is read from file /proc/sys/kernel/hostname directly
		$hostname = trim($hostname);
		if (empty($hostname)) {
			throw new InternalServerErrorException('Empty hostname is invalid.');
		}

		$hostname = strtolower($hostname);

		$data = $this->dba->selectFirst(self::$table_name, ['id', 'name'], ['name' => $hostname]);
		if (!empty($host['id'])) {
			return new Model\Host($this->dba, $this->logger, $data);
		} else {
			$this->dba->replace(self::$table_name, ['name' => $hostname]);

			return parent::selectFirst(['name' => $hostname]);
		}
	}

	/**
	 * @param array $condition
	 * @return Model\Host
	 * @throws \Friendica\Network\HTTPException\NotFoundException
	 */
	public function selectFirst(array $condition)
	{
		return parent::selectFirst($condition);
	}

	/**
	 * @param array $condition
	 * @param array $params
	 * @return Collection\Hosts
	 * @throws \Exception
	 */
	public function select(array $condition = [], array $params = [])
	{
		return parent::select($condition, $params);
	}

	/**
	 * @param array $condition
	 * @param array $params
	 * @param int|null $max_id
	 * @param int|null $since_id
	 * @param int $limit
	 * @return Collection\Hosts
	 * @throws \Exception
	 */
	public function selectByBoundaries(array $condition = [], array $params = [], int $max_id = null, int $since_id = null, int $limit = self::LIMIT)
	{
		return parent::selectByBoundaries($condition, $params, $max_id, $since_id, $limit);
	}
}
