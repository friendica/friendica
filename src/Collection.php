<?php

namespace Friendica;

use Friendica\Database\Database;
use Friendica\Database\DBA;
use Psr\Log\LoggerInterface;

/**
 * The Collection classes inheriting from this abstract class are meant to represent a list of database record.
 * The associated model class has to be provided in the child classes.
 *
 * Collections can be used with foreach().
 */
abstract class Collection implements \Iterator, \Countable, \ArrayAccess
{
	/** @var Model[] */
	protected $models = [];

	/** @var int */
	protected $totalCount = 0;

	/**
	 * @param Model[]  $models
	 * @param int|null $totalCount
	 */
	public function __construct(array $models = [], int $totalCount = null)
	{
		$this->models = $models;
		$this->totalCount = $totalCount ?? count($models);
	}

	// Iterator interface

	/**
	 * @inheritDoc
	 */
	public function current()
	{
		return current($this->models);
	}

	/**
	 * @inheritDoc
	 */
	public function next()
	{
		return next($this->models);
	}

	/**
	 * @inheritDoc
	 */
	public function key()
	{
		return key($this->models);
	}

	/**
	 * @inheritDoc
	 */
	public function valid()
	{
		$key = key($this->models);
		return $key !== null && $key !== false;
	}

	/**
	 * @inheritDoc
	 */
	public function rewind()
	{
		return reset($this->models);
	}

	// Countable interface

	/**
	 * @inheritDoc
	 */
	public function count()
	{
		return count($this->models);
	}

	// ArrayAccess interface

	/**
	 * @inheritDoc
	 */
	public function offsetExists($offset)
	{
		return array_key_exists($offset, $this->models);
	}

	/**
	 * @inheritDoc
	 */
	public function offsetGet($offset)
	{
		return $this->models[$offset];
	}

	/**
	 * @inheritDoc
	 */
	public function offsetSet($offset, $value)
	{
		if (!$this->offsetExists($offset)) {
			$this->totalCount++;
		}

		$this->models[$offset] = $value;
	}

	/**
	 * @inheritDoc
	 */
	public function offsetUnset($offset)
	{
		unset($this->models[$offset]);

		$this->totalCount--;
	}

	// Custom methods

	/**
	 * @return int
	 */
	public function getTotalCount()
	{
		return $this->totalCount;
	}
}
