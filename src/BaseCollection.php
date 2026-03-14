<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

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
	public function __construct(array $entities = [], ?int $totalCount = null)
	{
		parent::__construct($entities);

		$this->totalCount = $totalCount ?? count($entities);
	}

	/**
	 * @param mixed $key
	 * @param mixed $value
	 */
	public function offsetSet($key, $value): void
	{
		if (is_null($key)) {
			$this->totalCount++;
		}

		parent::offsetSet($key, $value);
	}

	/**
	 * @param mixed $key
	 */
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
		$class = get_class($this);

		return new $class(array_map($callback, $this->getArrayCopy()), $this->getTotalCount());
	}

	/**
	 * Filters the collection based on a callback that returns a boolean whether the current item should be kept.
	 *
	 * @param callable|null $callback
	 * @param int           $flag
	 * @return BaseCollection
	 * @see array_filter()
	 */
	public function filter(?callable $callback = null, int $flag = 0): BaseCollection
	{
		$class = get_class($this);

		return new $class(array_filter($this->getArrayCopy(), $callback, $flag));
	}

	/**
	 * Reverse the orders of the elements in the collection
	 */
	public function reverse(): BaseCollection
	{
		$class = get_class($this);

		return new $class(array_reverse($this->getArrayCopy()), $this->getTotalCount());
	}

	/**
	 * Split the collection in smaller collections no bigger than the provided length
	 *
	 * @param int $length
	 */
	public function chunk(int $length): array
	{
		if ($length < 1) {
			throw new \RangeException('BaseCollection->chunk(): Size parameter expected to be greater than 0');
		}

		return array_map(
			function ($array) {
				$class = get_class($this);

				return new $class($array);
			},
			array_chunk($this->getArrayCopy(), $length),
		);
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
