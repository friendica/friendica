<?php

namespace Friendica;

use Friendica\Database\Database;
use Friendica\Network\HTTPException;
use Psr\Log\LoggerInterface;

/**
 * Class BaseModel
 *
 * The Model classes inheriting from this abstract class are meant to represent a single database record.
 * The associated table name has to be provided in the child class, and the table is expected to have a unique `id` field.
 *
 * @property int id
 */
abstract class BaseModel
{
	protected static $table_name;

	/** @var Database */
	protected $dba;
	/** @var LoggerInterface */
	protected $logger;

	/**
	 * Model record abstraction.
	 * Child classes never have to interact directly with it.
	 * Please use the magic getter instead.
	 *
	 * @var array
	 */
	private $data = [];

	/**
	 * @param Database        $dba
	 * @param LoggerInterface $logger
	 * @param array           $data   Table row attributes
	 */
	public function __construct(Database $dba, LoggerInterface $logger, array $data = [])
	{
		$this->dba = $dba;
		$this->logger = $logger;
		$this->data = $data;
	}

	/**
	 * Magic getter. This allows to retrieve model fields with the following syntax:
	 * - $model->field (outside of class)
	 * - $this->field (inside of class)
	 *
	 * @param $name
	 * @return mixed
	 * @throws HTTPException\InternalServerErrorException
	 */
	public function __get($name)
	{
		if (empty($this->data['id'])) {
			throw new HTTPException\InternalServerErrorException(static::class . ' record uninitialized');
		}

		if (!array_key_exists($name, $this->data)) {
			throw new HTTPException\InternalServerErrorException('Field ' . $name . ' not found in ' . static::class);
		}

		return $this->data[$name];
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
	public function fetch(array $condition)
	{
		$data = $this->dba->selectFirst(static::$table_name, [], $condition);

		if (!$data) {
			throw new HTTPException\NotFoundException(static::class . ' record not found.');
		}

		return new static($this->dba, $this->logger, $data);
	}

	/**
	 * Deletes the model record from the database.
	 * Prevents further methods from being called by wiping the internal model data.
	 */
	public function delete()
	{
		if ($this->dba->delete(static::$table_name, ['id' => $this->id])) {
			$this->data = [];
		}
	}
}
