<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
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

namespace Friendica;

use Friendica\Network\HTTPException;

/**
 * The Entity classes directly inheriting from this abstract class are meant to represent a single business entity.
 * Their properties may or may not correspond with the database fields of the table we use to represent it.
 * Each model method must correspond to a business action being performed on this entity.
 * Only these methods will be allowed to alter the model data.
 *
 * To persist such a model, the associated Repository must be instantiated and the "save" method must be called
 * and passed the entity as a parameter.
 *
 * Ideally, the constructor should only be called in the associated Factory which will instantiate entities depending
 * on the provided data.
 *
 * Since these objects aren't meant to be using any dependency, including logging, unit tests can and must be
 * written for each and all of their methods
 */
abstract class BaseEntity extends BaseDataTransferObject
{
	/**
	 * @param string $name
	 * @return mixed
	 * @throws HTTPException\InternalServerErrorException
	 */
	public function __get(string $name)
	{
		if (!property_exists($this, $name)) {
			throw new HTTPException\InternalServerErrorException('Unknown property ' . $name . ' in Entity ' . static::class);
		}

		return $this->$name;
	}

	/**
	 * @param mixed $name
	 * @return bool
	 * @throws HTTPException\InternalServerErrorException
	 */
	public function __isset($name): bool
	{
		if (!property_exists($this, $name)) {
			throw new HTTPException\InternalServerErrorException('Unknown property ' . $name . ' of type ' . gettype($name) . ' in Entity ' . static::class);
		}

		return !empty($this->$name);
	}
}
