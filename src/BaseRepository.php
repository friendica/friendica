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

namespace Friendica;

use Exception;
use Friendica\Capabilities\ICanCreateFromTableRow;
use Friendica\Database\Database;
use Friendica\Database\DBA;
use Friendica\Network\HTTPException\NotFoundException;
use Psr\Log\LoggerInterface;

/**
 * Repositories are meant to store and retrieve Entities from the database.
 *
 * The reason why there are methods prefixed with an underscore is because PHP doesn't support generic polymorphism
 * which means we can't directly overload base methods and make parameters more strict (from a parent class to a child
 * class for example)
 *
 * Similarly, we can't make an overloaded method return type more strict until we only support PHP version 7.4 but this
 * is less pressing.
 */
abstract class BaseRepository
{
	const LIMIT = 30;

	/**
	 * @var string This should be set to the main database table name the depository is using
	 */
	protected static $table_name;

	/** @var Database */
	protected $db;

	/** @var LoggerInterface */
	protected $logger;

	/** @var ICanCreateFromTableRow */
	protected $factory;

	public function __construct(Database $database, LoggerInterface $logger, ICanCreateFromTableRow $factory)
	{
		$this->db      = $database;
		$this->logger  = $logger;
		$this->factory = $factory;
	}

	/**
	 * Populates the collection according to the condition. Retrieves a limited subset of entities depending on the
	 * boundaries and the limit. The total count of rows matching the condition is stored in the collection.
	 *
	 * Depends on the corresponding table featuring a numerical auto incremented column called `id`.
	 *
	 * max_id and min_id are susceptible to the query order:
	 * - min_id alone only reliably works with ASC order
	 * - max_id alone only reliably works with DESC order
	 * If the wrong order is detected in either case, we reverse the query order and the entity list order after the query
	 *
	 * Chainable.
	 *
	 * @param array    $condition
	 * @param array    $params
	 * @param int|null $min_id Retrieve models with an id no fewer than this, as close to it as possible
	 * @param int|null $max_id Retrieve models with an id no greater than this, as close to it as possible
	 * @param int      $limit
	 * @return BaseCollection
	 * @throws \Exception
	 */
	protected function _selectByBoundaries(
		array $condition = [],
		array $params = [],
		int $min_id = null,
		int $max_id = null,
		int $limit = self::LIMIT
	): BaseCollection {
		$totalCount = $this->count($condition);

		$boundCondition = $condition;

		$reverseOrder = false;

		if (isset($min_id)) {
			$boundCondition = DBA::mergeConditions($boundCondition, ['`id` > ?', $min_id]);
			if (!isset($max_id) && isset($params['order']['id']) && ($params['order']['id'] === true || $params['order']['id'] === 'DESC')) {
				$reverseOrder = true;

				$params['order']['id'] = 'ASC';
			}
		}

		if (isset($max_id) && $max_id > 0) {
			$boundCondition = DBA::mergeConditions($boundCondition, ['`id` < ?', $max_id]);
			if (!isset($min_id) && (!isset($params['order']['id']) || $params['order']['id'] === false || $params['order']['id'] === 'ASC')) {
				$reverseOrder = true;

				$params['order']['id'] = 'DESC';
			}
		}

		$params['limit'] = $limit;

		$Entities = $this->_select($boundCondition, $params);
		if ($reverseOrder) {
			$Entities->reverse();
		}

		return new BaseCollection($Entities->getArrayCopy(), $totalCount);
	}

	/**
	 * @param array $condition
	 * @param array $params
	 * @return BaseCollection
	 * @throws Exception
	 */
	protected function _select(array $condition, array $params = []): BaseCollection
	{
		$rows = $this->db->selectToArray(static::$table_name, [], $condition, $params);

		$Entities = new BaseCollection();
		foreach ($rows as $fields) {
			$Entities[] = $this->factory->createFromTableRow($fields);
		}

		return $Entities;
	}

	/**
	 * @param array $condition
	 * @param array $params
	 * @return BaseEntity
	 * @throws NotFoundException
	 */
	protected function _selectOne(array $condition, array $params = []): BaseEntity
	{
		$fields = $this->db->selectFirst(static::$table_name, [], $condition, $params);
		if (!$this->db->isResult($fields)) {
			throw new NotFoundException();
		}

		return $this->factory->createFromTableRow($fields);
	}

	/**
	 * @param array $condition
	 * @param array $params
	 * @return int
	 * @throws Exception
	 */
	public function count(array $condition, array $params = []): int
	{
		return $this->db->count(static::$table_name, $condition, $params);
	}

	/**
	 * @param array $condition
	 * @return bool
	 * @throws Exception
	 */
	public function exists(array $condition): bool
	{
		return $this->db->exists(static::$table_name, $condition);
	}
}
