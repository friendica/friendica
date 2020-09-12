<?php
/**
 * @copyright Copyright (C) 2020, Friendica
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Friendica\Domain;

use Friendica\Database\Database;
use Friendica\Network\HTTPException;
use Psr\Log\LoggerInterface;

/**
 * The Model classes inheriting from this abstract class are meant to represent a single database record.
 * The associated table name has to be provided in the child class, and the table is expected to have a unique `id` field.
 *
 * @property int id
 */
abstract class BaseModel extends BaseEntity
{
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
	 * Used to limit/avoid updates if no data was changed.
	 *
	 * @var array
	 */
    private $originalData = [];

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
		$this->originalData = $data;
	}

	public function getOriginalData()
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
	public static function createFromPrototype(BaseModel $prototype, array $data)
	{
		$model = clone $prototype;
		$model->data = $data;
		$model->originalData = $data;

		return $model;
	}

	/**
	 * Magic isset method. Returns true if the field exists, either in the data prperty array or in any of the local properties.
	 * Used by array_column() on an array of objects.
	 *
	 * @param $name
	 * @return bool
	 */
	public function __isset($name)
	{
		return in_array($name, array_merge(array_keys($this->data), array_keys(get_object_vars($this))));
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
		$this->checkValid();

		if (!array_key_exists($name, $this->data)) {
			throw new HTTPException\InternalServerErrorException('Field ' . $name . ' not found in ' . static::class);
		}

		return $this->data[$name];
	}

	/**
	 * @param string $name
	 * @param mixed $value
	 */
	public function __set($name, $value)
	{
		$this->data[$name] = $value;
	}

	public function toArray()
	{
		return $this->data;
	}

	protected function checkValid()
	{
		if (empty($this->data['id'])) {
			throw new HTTPException\InternalServerErrorException(static::class . ' record uninitialized');
		}
	}
}
