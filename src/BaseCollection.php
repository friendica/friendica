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

/**
 * The Collection classes inheriting from this class are meant to represent a list of structured objects of a single type.
 *
 * Collections can be used with foreach(), accessed like an array and counted.
 */
class BaseCollection extends \ArrayIterator
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
	#[\ReturnTypeWillChange]
	public function offsetSet($key, $value): void
	{
		if (is_null($key)) {
			$this->totalCount++;
		}

		parent::offsetSet($key, $value);
	}

	/**
	 * @inheritDoc
	 */
	#[\ReturnTypeWillChange]
	public function offsetUnset($key): void
	{
		if ($this->offsetExists($key)) {
			$this->totalCount--;
		}

		parent::offsetUnset($key);
	}

	/**
	 * Getter for total count
	 *
	 * @return int Total count
	 */
	public function getTotalCount(): int
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
	public function column(string $column, $index_key = null): array
	{
		return array_column($this->getArrayCopy(true), $column, $index_key);
	}

	/**
	 * Apply a callback function on all elements in the collection and returns a new collection with the updated elements
	 *
	 * @param callable $callback
	 * @return BaseCollection
	 * @see array_map()
	 */
	public function map(callable $callback): BaseCollection
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
	public function filter(callable $callback = null, int $flag = 0): BaseCollection
	{
		return new static(array_filter($this->getArrayCopy(), $callback, $flag));
	}

	/**
	 * Reverse the orders of the elements in the collection
	 *
	 * @return $this
	 */
	public function reverse(): BaseCollection
	{
		return new static(array_reverse($this->getArrayCopy()), $this->getTotalCount());
	}

	/**
	 * @inheritDoc
	 *
	 * includes recursion for entity::toArray() function
	 * @see BaseEntity::toArray()
	 */
	public function getArrayCopy(bool $recursive = false): array
	{
		if (!$recursive) {
			return parent::getArrayCopy();
		}

		return array_map(function ($item) {
			return is_object($item) ? $item->toArray() : $item;
		}, parent::getArrayCopy());
	}
}
