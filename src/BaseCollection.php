<?php

namespace Friendica;

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
	 * @param BaseModel[] $models
	 * @param int|null    $totalCount
	 */
	public function __construct(array $models = [], int $totalCount = null)
	{
		parent::__construct($models);

		$this->totalCount = $totalCount ?? count($models);
	}

	/**
	 * @return int
	 */
	public function getTotalCount()
	{
		return $this->totalCount;
	}
}
