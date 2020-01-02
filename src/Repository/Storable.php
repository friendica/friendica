<?php

namespace Friendica\Repository;

use Friendica\Collection;
use Friendica\Model;
use Friendica\Repository;
use Friendica\Database\Database;
use Friendica\Database\DBA;
use Friendica\Network\HTTPException;
use Psr\Log\LoggerInterface;

/**
 * Storable repositories are linked to a main table with an id primary key and will instanciate Models and Collections
 * related to that table.
 */
abstract class Storable extends Repository
{
	const LIMIT = 30;

	/** @var Database */
	protected $dba;

	/** @var string */
	protected static $table_name;

	/** @var Model */
	protected static $model_class;

	/** @var Collection */
	protected static $collection_class;

	public function __construct(Database $dba, LoggerInterface $logger)
	{
		parent::__construct($logger);

		$this->dba = $dba;
		$this->logger = $logger;
	}

	/**
	 * Fetches a single model record. The condition array is expected to contain a unique index (primary or otherwise).
	 *
	 * Chainable.
	 *
	 * @param array $condition
	 * @return Model
	 * @throws HTTPException\NotFoundException
	 */
	public function selectFirst(array $condition)
	{
		$data = $this->dba->selectFirst(static::$table_name, [], $condition);

		if (!$data) {
			throw new HTTPException\NotFoundException(static::class . ' record not found.');
		}

		return new static::$model_class($data);
	}

	/**
	 * Populates a Collection according to the condition.
	 *
	 * Chainable.
	 *
	 * @param array $condition
	 * @param array $params
	 * @return Collection
	 * @throws \Exception
	 */
	public function select(array $condition = [], array $params = [])
	{
		$result = $this->dba->select(static::$table_name, [], $condition, $params);

		$models = [];

		while ($record = $this->dba->fetch($result)) {
			$models[] = new static::$model_class($this->dba, $this->logger, $record);
		}

		return new static::$collection_class($models);
	}

	/**
	 * Populates the collection according to the condition. Retrieves a limited subset of models depending on the boundaries
	 * and the limit. The total count of rows matching the condition is stored in the collection.
	 *
	 * Chainable.
	 *
	 * @param array $condition
	 * @param array $params
	 * @param int?  $max_id
	 * @param int?  $since_id
	 * @param int   $limit
	 * @return $this
	 * @throws \Exception
	 */
	public function selectByBoundaries(array $condition = [], array $params = [], int $max_id = null, int $since_id = null, int $limit = self::LIMIT)
	{
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

		$result = $this->dba->select(static::$table_name, [], $boundCondition, $params);

		$models = [];
		while ($record = $this->dba->fetch($result)) {
			$models[] = new static::$model_class($this->dba, $this->logger, $record);
		}

		$totalCount = DBA::count(static::$table_name, $condition);

		return new static::$collection_class($models, $totalCount);
	}

	/**
	 * Deletes the model record from the database.
	 *
	 * @param Model $model
	 * @return bool
	 * @throws \Exception
	 */
	public function delete(Model &$model)
	{
		if ($success = $this->dba->delete(static::$table_name, ['id' => $model->id])) {
			$model = null;
		}

		return $success;
	}
}
