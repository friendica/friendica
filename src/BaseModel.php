<?php

namespace Friendica;

use Friendica\Database\Database;
use Friendica\Model\Entity\BaseEntity;
use Friendica\Network\HTTPException;
use Psr\Log\LoggerInterface;

/**
 * Class BaseModel
 *
 * A model implements the business logic between the persistance layer (=db) and the used entitys
 * There's only one model/service per call possible, so the table-name and main-entity-class stays as a static variable
 */
abstract class BaseModel
{
	/** @var string */
	protected static $table_name;
	/** @var BaseEntity */
	protected static $entity_class;

	/** @var Database */
	protected $dba;
	/** @var LoggerInterface */
	protected $logger;

	public function __construct(Database $dba, LoggerInterface $logger)
	{
		$this->dba    = $dba;
		$this->logger = $logger;
	}

	/**
	 * Fetches a single model record. The condition array is expected to contain a unique index (primary or otherwise).
	 *
	 * Chainable.
	 *
	 * @param array $condition
	 * @param array $fields Optional array of fields for lazy-loading (just load things you need)
	 *
	 * @return mixed
	 * @throws HTTPException\NotFoundException
	 */
	public function fetch(array $condition, array $fields = [])
	{
		$entity = $this->dba->selectFirst(static::$table_name, $fields, $condition);

		if (!$entity) {
			throw new HTTPException\NotFoundException(static::class . ' record not found.');
		}

		return new self::$entity_class($entity);
	}

	/**
	 * Deletes the model record from the database.
	 * Prevents further methods from being called by wiping the internal model data.
	 */
	public function delete(BaseEntity &$entity)
	{
		if ($this->dba->delete(static::$table_name, ['id' => $entity->id])) {
			unset($entity);
		}
	}
}
