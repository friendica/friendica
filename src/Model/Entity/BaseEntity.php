<?php

namespace Friendica\Model\Entity;

use Friendica\Network\HTTPException;

/**
 * This is a entity, which is per definition immutable.
 * In case we create one entity, we're aware that we cannot change their properties,
 * except per static "fromEntity()" method, which uses an old entity and clones it to a new one
 *
 * The entity-layer is loosely based on the Repository-pattern
 * @see https://designpatternsphp.readthedocs.io/en/latest/More/Repository/README.html
 *
 * @property-read int $id
 *
 * @package Friendica\Model\Entity
 */
abstract class BaseEntity
{
	/** @var int The overall count of all loaded entities in this execution */
	private static $entities = 0;
	/** @var int The execution-wide unique id of this entity */
	private $entityId;

	/**
	 * Returns the overall count of all loaded entities in this execution
	 *
	 * @return int
	 */
	public static function getEntityCount()
	{
		return self::$entities;
	}

	/**
	 * Model record abstraction.
	 * Child classes never have to interact directly with it.
	 * Please use the magic getter instead.
	 *
	 * @var array
	 */
	private $data = [];

	public function __construct(array $data)
	{
		if (!key_exists('id', $data)) {
			throw new HTTPException\InternalServerErrorException("data are incomplete, id is missing.");
		}

		$this->data = $data;
		$this->entityId = ++self::$entities;
	}

	/**
	 * At least increment the entity-id, so the new instance must not be the same as the cloned one, but can be equal
	 *
	 * same:  $entity === $entity
	 * equal: $entity == $entity
	 */
	public function __clone()
	{
		$this->entityId = ++self::$entities;
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
	 * @throws HTTPException\InternalServerErrorException Entity set is not allowed
	 */
	public function __set($name, $value)
	{
		throw new HTTPException\InternalServerErrorException('Entity set is not allowed.');
	}

	/**
	 * Creates a new entity based on a given entity
	 * @see https://designpatternsphp.readthedocs.io/en/latest/Creational/Prototype/README.html
	 *
	 * @param BaseEntity $entity
	 * @param array      $newData
	 *
	 * @return static
	 */
	public static function fromEntity(BaseEntity $entity, array $newData = [])
	{
		// cloning objects are faster than instancing a new one
		$newEntity = clone $entity;
		// because we're inside the own entity class, we can get access to the private data-array
		$newEntity->data = array_merge($newEntity->data, $newData);

		return $newEntity;
	}
}
