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

namespace Friendica\Domain;

/**
 * The Collection classes inheriting from this abstract class are meant to represent a list of database record.
 * The associated model class has to be provided in the child classes.
 *
 * Collections can be used with foreach(), accessed like an array and counted.
 */
abstract class BaseCollection extends \ArrayIterator
{
	/**
	 * This property is used with paginated results to hold the total number of items satisfying the paginated request.
	 * @var int
	 */
	protected $totalCount = 0;

	/**
	 * @param BaseEntity[] $entities
	 * @param int|null     $totalCount
	 */
	public function __construct(array $entities = [], int $totalCount = null)
	{
		parent::__construct($entities);

		$this->totalCount = $totalCount ?? count($entities);
	}

	/**
	 * @inheritDoc
	 */
	public function offsetSet($offset, $value)
	{
		if (is_null($offset)) {
			$this->totalCount++;
		}

		parent::offsetSet($offset, $value);
	}

	/**
	 * @inheritDoc
	 */
	public function offsetUnset($offset)
	{
		if ($this->offsetExists($offset)) {
			$this->totalCount--;
		}

		parent::offsetUnset($offset);
	}

	/**
	 * @return int
	 */
	public function getTotalCount()
	{
		return $this->totalCount;
	}

	/**
	 * Return the values from a single field in the collection
	 *
	 * @param string   $column
	 * @param int|null $index_key
	 * @return array
	 * @see array_column()
	 */
	public function column($column, $index_key = null)
	{
		return array_column($this->getArrayCopy(), $column, $index_key);
	}

	/**
	 * Apply a callback function on all elements in the collection and returns a new collection with the updated elements
	 *
	 * @param callable $callback
	 * @return BaseCollection
	 * @see array_map()
	 */
	public function map(callable $callback)
	{
		return new static(array_map($callback, $this->getArrayCopy()), $this->getTotalCount());
	}

	/**
	 * Filters the collection based on a callback that returns a boolean whether the current item should be kept.
	 *
	 * @param callable|null $callback
	 * @param int           $flag
	 * @return BaseCollection
	 * @see array_filter()
	 */
	public function filter(callable $callback = null, int $flag = 0)
	{
		return new static(array_filter($this->getArrayCopy(), $callback, $flag));
	}
}
