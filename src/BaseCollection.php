<?php

namespace Friendica;

use Friendica\Database\Database;
use Friendica\Database\DBA;
use Psr\Log\LoggerInterface;

/**
 * Class BaseCollection
 *
 * The Collection classes inheriting from this abstract class are meant to represent a list of database record.
 * The associated model class has to be provided in the child classes.
 *
 * Collections can be used with foreach().
 *
 * @package Friendica
 */
abstract class BaseCollection implements \Iterator
{
	const LIMIT = 30;

	protected static $model_class;

	/** @var Database */
	protected $dba;
	/** @var LoggerInterface */
	protected $logger;

	/** @var array */
	protected $items = [];

	/** @var int */
	protected $totalCount = 0;

	public function __construct(Database $dba, LoggerInterface $logger)
	{
		$this->dba = $dba;
		$this->logger = $logger;
	}

	// Iterator interface

	/**
	 * @inheritDoc
	 */
	public function current()
	{
		return current($this->items);
	}

	/**
	 * @inheritDoc
	 */
	public function next()
	{
		return next($this->items);
	}

	/**
	 * @inheritDoc
	 */
	public function key()
	{
		return key($this->items);
	}

	/**
	 * @inheritDoc
	 */
	public function valid()
	{
		$key = key($this->items);
		return $key !== null && $key !== false;
	}

	/**
	 * @inheritDoc
	 */
	public function rewind()
	{
		reset($this->items);
	}

	// Custom methods

	/**
	 * @return int
	 */
	public function getTotalCount()
	{
		return $this->totalCount;
	}

	/**
	 * Populates the Collection according to the condition.
	 *
	 * Chainable.
	 *
	 * @param array $condition
	 * @param array $params
	 * @return $this
	 * @throws \Exception
	 */
	public function select(array $condition = [], array $params = [])
	{
		$model_class = static::$model_class;

		$result = $this->dba->select($model_class::$table_name, [], $condition, $params);

		while ($record = $this->dba->fetch($result)) {
			$this->items[] = new $model_class($this->dba, $this->logger, $record);
		}

		$this->totalCount = count($this->items);

		return $this;
	}

	/**
	 * Populates the collection according to the condition. Retrieves a limited subset of models depending on the boundaries
	 * and the limit. The total count of rows matching the condition is stored in the collection.
	 *
	 * Chainable.
	 *
	 * @param array $condition
	 * @param array $params
	 * @param null  $max_id
	 * @param null  $since_id
	 * @param int   $limit
	 * @return $this
	 * @throws \Exception
	 */
	public function selectByBoundaries(array $condition = [], array $params = [], $max_id = null, $since_id = null, int $limit = self::LIMIT)
	{
		$model_class = static::$model_class;

		$condition = DBA::collapseCondition($condition);

		$boundCondition = $condition;

		if (isset($max_id)) {
			$boundCondition[0] .= " AND `id` < ?";
			$boundCondition[] = $max_id;
		}

		if (isset($since_id)) {
			$boundCondition[0] .= " AND `id` > ?";
			$boundCondition[] = $since_id;
		}

		$params['limit'] = $limit;

		$result = $this->dba->select($model_class::$table_name, [], $boundCondition, $params);

		while ($record = $this->dba->fetch($result)) {
			$this->items[] = new $model_class($this->dba, $this->logger, $record);
		}

		$this->totalCount = DBA::count($model_class::$table_name, $condition);

		return $this;
	}
}
