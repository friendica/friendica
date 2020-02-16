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

namespace Friendica\Domain\Entity\Diaspora;

use Friendica\BaseEntity;
use Friendica\Domain\Entity\Item;
use Friendica\Network\HTTPException\NotImplementedException;

/**
 * Entity class for table diaspora-interaction
 *
 * Signed Diaspora Interaction
 */
class Interaction extends BaseEntity
{
	/**
	 * @var int
	 * Id of the item-uri table entry that contains the item uri
	 */
	private $uriId;

	/**
	 * @var string
	 * The Diaspora interaction
	 */
	private $interaction;

	/**
	 * {@inheritDoc}
	 */
	public function toArray()
	{
		return [
			'uri-id' => $this->uriId,
			'interaction' => $this->interaction,
		];
	}

	/**
	 * @return int
	 * Get Id of the item-uri table entry that contains the item uri
	 */
	public function getUriId()
	{
		return $this->uriId;
	}

	/**
	 * Get \ItemUri
	 *
	 * @return \ItemUri
	 */
	public function getItemUri()
	{
		//@todo use closure
		throw new NotImplementedException('lazy loading for id is not implemented yet');
	}

	/**
	 * @return string
	 * Get The Diaspora interaction
	 */
	public function getInteraction()
	{
		return $this->interaction;
	}

	/**
	 * @param string $interaction
	 * Set The Diaspora interaction
	 */
	public function setInteraction(string $interaction)
	{
		$this->interaction = $interaction;
	}
}
