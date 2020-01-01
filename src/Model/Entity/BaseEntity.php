<?php

namespace Friendica\Model\Entity;

use ArrayAccess;
use Friendica\Network\HTTPException;

abstract class BaseEntity implements ArrayAccess
{
	/**
	 * Model record abstraction.
	 * Child classes never have to interact directly with it.
	 * Please use the magic getter instead.
	 *
	 * @var array
	 */
	protected $data = [];

	/**
	 * Private constructor of the entity
	 * Entities are not meant to get created manually by hand
	 *
	 * Instead use the static create() function
	 *
	 * @param array $data
	 */
	private function __construct(array $data = [])
	{
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
			throw new HTTPException\InternalServerErrorException(static::class . ' entity uninitialized');
		}

		if (!array_key_exists($name, $this->data)) {
			throw new HTTPException\InternalServerErrorException(sprintf("Field '%s' not found in %s", $name,  static::class));
		}

		return $this->data[$name];
	}

	public static function create(array $data)
	{
		return new static($data);
	}

	public static function createFromPrototype($prototype, array $data)
	{
		$entity = clone $prototype;
		$entity->data = $data;

		return $entity;
	}

	/**
	 * @inheritDoc
	 */
	public function offsetExists($offset)
	{
		return array_key_exists($offset, $this->data);
	}

	/**
	 * @inheritDoc
	 */
	public function offsetGet($offset)
	{
		if ($this->offsetExists($offset)) {
			return $this->data[$offset];
		} else {
			return null;
		}
	}

	/**
	 * @inheritDoc
	 */
	public function offsetSet($offset, $value)
	{
		$this->__set($offset, $value);
	}

	/**
	 * @inheritDoc
	 */
	public function offsetUnset($offset)
	{
		$this->__set($offset, null);
	}
}
