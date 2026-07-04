<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica;

use Friendica\Database\Database;
use Friendica\Network\HTTPException;
use Psr\Log\LoggerInterface;

/**
 * The Model classes inheriting from this abstract class are meant to represent a single database record.
 * The associated table name has to be provided in the child class, and the table is expected to have a unique `id` field.
 *
 * @property int $id
 */
abstract class BaseModel extends BaseDataTransferObject
{
	/** @var Database */
	protected $dba;
	/** @var LoggerInterface */
	protected $logger;

	/**
	 * Used to limit/avoid updates if no data was changed.
	 *
	 * @var array
	 */
	private $originalData = [];

	/**
	 * @param array           $data   Table row attributes
	 */
	public function __construct(Database $dba, LoggerInterface $logger, /**
				 * Model record abstraction.
				 * Child classes never have to interact directly with it.
				 * Please use the magic getter instead.
				 */
		private array $data = [])
	{
		$this->dba          = $dba;
		$this->logger       = $logger;
		$this->originalData = $this->data;
	}

	public function getOriginalData(): array
	{
		return $this->originalData;
	}

	public function resetOriginalData()
	{
		$this->originalData = $this->data;
	}

	/**
	 * Performance-improved model creation in a loop
	 *
	 * @param BaseModel $prototype
	 * @param array     $data
	 * @return BaseModel
	 */
	public static function createFromPrototype(BaseModel $prototype, array $data): BaseModel
	{
		$model               = clone $prototype;
		$model->data         = $data;
		$model->originalData = $data;

		return $model;
	}

	/**
	 * Magic isset method. Returns true if the field exists, either in the data property array or in any of the local properties.
	 * Used by array_column() on an array of objects.
	 *
	 * @param $name
	 * @return bool
	 */
	public function __isset($name): bool
	{
		return in_array($name, array_merge(array_keys($this->data), array_keys(get_object_vars($this))));
	}

	/**
	 * Magic getter. This allows to retrieve model fields with the following syntax:
	 * - $model->field (outside of class)
	 * - $this->field (inside of class)
	 *
	 * @param string $name Name of data to fetch
	 * @return mixed
	 * @throws HTTPException\InternalServerErrorException
	 */
	public function __get(string $name)
	{
		$this->checkValid();

		if (!array_key_exists($name, $this->data)) {
			throw new HTTPException\InternalServerErrorException('Field ' . $name . ' not found in ' . static::class);
		}

		return $this->data[$name];
	}

	/**
	 * * Magic setter. This allows to set model fields with the following syntax:
	 * - $model->field = $value (outside of class)
	 * - $this->field = $value (inside of class)
	 *
	 * @param string $name
	 * @param mixed  $value
	 */
	public function __set(string $name, $value)
	{
		$this->data[$name] = $value;
	}

	public function toArray(): array
	{
		return $this->data;
	}

	protected function checkValid()
	{
		if (!isset($this->data['id']) || is_null($this->data['id'])) {
			throw new HTTPException\InternalServerErrorException(static::class . ' record uninitialized');
		}
	}
}
