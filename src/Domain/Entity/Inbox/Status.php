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

namespace Friendica\Domain\Entity\Inbox;

use Friendica\BaseEntity;

/**
 * Entity class for table inbox-status
 *
 * Status of ActivityPub inboxes
 */
class Status extends BaseEntity
{
	/**
	 * @var string
	 * URL of the inbox
	 */
	private $url;

	/**
	 * @var string
	 * Creation date of this entry
	 */
	private $created = '0001-01-01 00:00:00';

	/**
	 * @var string
	 * Date of the last successful delivery
	 */
	private $success = '0001-01-01 00:00:00';

	/**
	 * @var string
	 * Date of the last failed delivery
	 */
	private $failure = '0001-01-01 00:00:00';

	/**
	 * @var string
	 * Previous delivery date
	 */
	private $previous = '0001-01-01 00:00:00';

	/**
	 * @var bool
	 * Is the inbox archived?
	 */
	private $archive = '0';

	/**
	 * @var bool
	 * Is it a shared inbox?
	 */
	private $shared = '0';

	/**
	 * {@inheritDoc}
	 */
	public function toArray()
	{
		return [
			'url' => $this->url,
			'created' => $this->created,
			'success' => $this->success,
			'failure' => $this->failure,
			'previous' => $this->previous,
			'archive' => $this->archive,
			'shared' => $this->shared,
		];
	}

	/**
	 * @return string
	 * Get URL of the inbox
	 */
	public function getUrl()
	{
		return $this->url;
	}

	/**
	 * @return string
	 * Get Creation date of this entry
	 */
	public function getCreated()
	{
		return $this->created;
	}

	/**
	 * @param string $created
	 * Set Creation date of this entry
	 */
	public function setCreated(string $created)
	{
		$this->created = $created;
	}

	/**
	 * @return string
	 * Get Date of the last successful delivery
	 */
	public function getSuccess()
	{
		return $this->success;
	}

	/**
	 * @param string $success
	 * Set Date of the last successful delivery
	 */
	public function setSuccess(string $success)
	{
		$this->success = $success;
	}

	/**
	 * @return string
	 * Get Date of the last failed delivery
	 */
	public function getFailure()
	{
		return $this->failure;
	}

	/**
	 * @param string $failure
	 * Set Date of the last failed delivery
	 */
	public function setFailure(string $failure)
	{
		$this->failure = $failure;
	}

	/**
	 * @return string
	 * Get Previous delivery date
	 */
	public function getPrevious()
	{
		return $this->previous;
	}

	/**
	 * @param string $previous
	 * Set Previous delivery date
	 */
	public function setPrevious(string $previous)
	{
		$this->previous = $previous;
	}

	/**
	 * @return bool
	 * Get Is the inbox archived?
	 */
	public function getArchive()
	{
		return $this->archive;
	}

	/**
	 * @param bool $archive
	 * Set Is the inbox archived?
	 */
	public function setArchive(bool $archive)
	{
		$this->archive = $archive;
	}

	/**
	 * @return bool
	 * Get Is it a shared inbox?
	 */
	public function getShared()
	{
		return $this->shared;
	}

	/**
	 * @param bool $shared
	 * Set Is it a shared inbox?
	 */
	public function setShared(bool $shared)
	{
		$this->shared = $shared;
	}
}
