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
use Friendica\Network\HTTPException\NotImplementedException;

/**
 * Entity class for table poll_result
 *
 * data for polls - currently unused
 */
class PollResult extends BaseEntity
{
	/**
	 * @var int
	 * sequential ID
	 */
	private $id;

	/** @var int */
	private $pollId = '0';

	/**
	 * @var string
	 */
	private $choice = '0';

	/**
	 * {@inheritDoc}
	 */
	public function toArray()
	{
		return [
			'id' => $this->id,
			'poll_id' => $this->pollId,
			'choice' => $this->choice,
		];
	}

	/**
	 * @return int
	 * Get sequential ID
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @return int
	 */
	public function getPollId()
	{
		return $this->pollId;
	}

	/**
	 * @param int $pollId
	 */
	public function setPollId(int $pollId)
	{
		$this->pollId = $pollId;
	}

	/**
	 * Get Poll
	 *
	 * @return Poll
	 */
	public function getPoll()
	{
		//@todo use closure
		throw new NotImplementedException('lazy loading for id is not implemented yet');
	}

	/**
	 * @return string
	 * Get
	 */
	public function getChoice()
	{
		return $this->choice;
	}

	/**
	 * @param string $choice
	 * Set
	 */
	public function setChoice(string $choice)
	{
		$this->choice = $choice;
	}
}
