<?php

/**
 * @copyright Copyright (C) 2020, Friendica
 *
 * @license GNU APGL version 3 or any later version
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
 * Used to check/generate entities for the Friendica codebase
 */

declare(strict_types=1);

namespace Friendica\Domain\Entity;

use Friendica\BaseEntity;

/**
 * Entity class for table storage
 *
 * Data stored by Database storage backend
 */
class Storage extends BaseEntity
{
	/**
	 * @var int
	 * Auto incremented image data id
	 */
	private $id;

	/**
	 * @var int
	 * file data
	 */
	private $data;

	/**
	 * {@inheritDoc}
	 */
	public function toArray()
	{
		return [
			'id' => $this->id,
			'data' => $this->data,
		];
	}

	/**
	 * @return int
	 * Get Auto incremented image data id
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @return int
	 * Get file data
	 */
	public function getData()
	{
		return $this->data;
	}

	/**
	 * @param int $data
	 * Set file data
	 */
	public function setData(int $data)
	{
		$this->data = $data;
	}
}
