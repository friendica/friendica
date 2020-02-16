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

namespace Friendica\Domain\Entity\Gserver;

use Friendica\BaseEntity;
use Friendica\Network\HTTPException\NotImplementedException;

/**
 * Entity class for table gserver-tag
 *
 * Tags that the server has subscribed
 */
class Tag extends BaseEntity
{
	/**
	 * @var int
	 * The id of the gserver
	 */
	private $gserverId = '0';

	/**
	 * @var string
	 * Tag that the server has subscribed
	 */
	private $tag = '';

	/**
	 * {@inheritDoc}
	 */
	public function toArray()
	{
		return [
			'gserver-id' => $this->gserverId,
			'tag' => $this->tag,
		];
	}

	/**
	 * @return int
	 * Get The id of the gserver
	 */
	public function getGserverId()
	{
		return $this->gserverId;
	}

	/**
	 * Get Gserver
	 *
	 * @return Gserver
	 */
	public function getGserver()
	{
		//@todo use closure
		throw new NotImplementedException('lazy loading for id is not implemented yet');
	}

	/**
	 * @return string
	 * Get Tag that the server has subscribed
	 */
	public function getTag()
	{
		return $this->tag;
	}
}
