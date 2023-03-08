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

namespace Friendica\Object\Api\Friendica;

use Friendica\BaseDataTransferObject;

/**
 * Class Group
 *
 *
 */
class Group extends BaseDataTransferObject
{
	/** @var string */
	protected $name;
	/** @var int */
	protected $id;
	/** @var string */
	protected $id_str;
	/** @var array */
	protected $user;
	/** @var string */
	protected $mode;

	/**
	 * Creates an Group entity array
	 *
	 * @param array $group
	 * @param array $user
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public function __construct(array $group, array $user)
	{
		$this->name   = $group['name'];
		$this->id     = $group['id'];
		$this->id_str = (string)$group['id'];
		$this->user   = $user;
		$this->mode   = $group['visible'] ? 'public' : 'private';
	}
}
