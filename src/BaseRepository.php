<?php

namespace Friendica;

use Friendica\Database\Database;
use Friendica\Database\DBA;
use Friendica\Network\HTTPException;
use Psr\Log\LoggerInterface;

/**
 * Repositories are Factories linked to one or more database tables.
 *
 * @see BaseModel
 * @see BaseCollection
 */
abstract class BaseRepository extends BaseFactory
{
	const LIMIT = 30;

	/** @var Database */
	protected $dba;

	/** @var string */
	protected static $table_name;

	/** @var BaseModel */
	protected static $model_class;

	/** @var BaseCollection */
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
	 * @return BaseModel
	 * @throws HTTPException\NotFoundException
	 */
	public function selectFirst(array $condition)
	{
		$data = $this->dba->selectFirst(static::$table_name, [], $condition);

		if (!$data) {
			throw new HTTPException\NotFoundException(static::class . ' record not found.');
		}

		return $this->create($data);
	}

	/**
	 * Populates a Collection according to the condition.
	 *
	 * Chainable.
	 *
	 * @param array $condition
	 * @param array $params
	 * @return BaseCollection
	 * @throws \Exception
	 */
	public function select(array $condition = [], array $params = [])
	{
		$models = $this->selectModels($condition, $params);

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
	 * @return BaseCollection
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

		$models = $this->selectModels($boundCondition, $params);

		$totalCount = DBA::count(static::$table_name, $condition);

		return new static::$collection_class($models, $totalCount);
	}

	/**
	 * This method updates the database row from the model.
	 *
	 * @param BaseModel $model
	 * @return bool
	 * @throws \Exception
	 */
	public function update(BaseModel $model)
	{
		return $this->dba->update(static::$table_name, $model->toArray(), ['id' => $model->id], $model->getOriginalData());
	}

	/**
	 * This method creates a new database row and returns a model if it was successful.
	 *
	 * @param array $fields
	 * @return BaseModel|bool
	 * @throws \Exception
	 */
	public function insert(array $fields)
	{
		$return = $this->dba->insert(static::$table_name, $fields);

		if (!$return) {
			throw new HTTPException\InternalServerErrorException('Unable to insert new row in table "' . static::$table_name . '"');
		}

		$fields['id'] = $this->dba->lastInsertId();
		$return = $this->create($fields);

		return $return;
	}

	/**
	 * Deletes the model record from the database.
	 *
	 * @param BaseModel $model
	 * @return bool
	 * @throws \Exception
	 */
	public function delete(BaseModel &$model)
	{
		if ($success = $this->dba->delete(static::$table_name, ['id' => $model->id])) {
			$model = null;
		}

		return $success;
	}

	/**
	 * Base instantiation method, can be overriden to add specific dependencies
	 *
	 * @param array $data
	 * @return BaseModel
	 */
	protected function create(array $data)
	{
		return new static::$model_class($this->dba, $this->logger, $data);
	}

	/**
	 * @param array $condition Query condition
	 * @param array $params    Additional query parameters
	 * @return BaseModel[]
	 * @throws \Exception
	 */
	protected function selectModels(array $condition, array $params = [])
	{
		$result = $this->dba->select(static::$table_name, [], $condition, $params);

		/** @var BaseModel $prototype */
		$prototype = null;

		$models = [];

		while ($record = $this->dba->fetch($result)) {
			if ($prototype === null) {
				$prototype = $this->create($record);
				$models[] = $prototype;
			} else {
				$models[] = static::$model_class::createFromPrototype($prototype, $record);
			}
		}

		return $models;
	}
}
