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
 * Entity class for table oembed
 *
 * cache for OEmbed queries
 */
class Oembed extends BaseEntity
{
	/**
	 * @var string
	 * page url
	 */
	private $url;

	/**
	 * @var int
	 * Maximum width passed to Oembed
	 */
	private $maxwidth;

	/**
	 * @var string
	 * OEmbed data of the page
	 */
	private $content;

	/**
	 * @var string
	 * datetime of creation
	 */
	private $created = '0001-01-01 00:00:00';

	/**
	 * {@inheritDoc}
	 */
	public function toArray()
	{
		return [
			'url' => $this->url,
			'maxwidth' => $this->maxwidth,
			'content' => $this->content,
			'created' => $this->created,
		];
	}

	/**
	 * @return string
	 * Get page url
	 */
	public function getUrl()
	{
		return $this->url;
	}

	/**
	 * @return int
	 * Get Maximum width passed to Oembed
	 */
	public function getMaxwidth()
	{
		return $this->maxwidth;
	}

	/**
	 * @return string
	 * Get OEmbed data of the page
	 */
	public function getContent()
	{
		return $this->content;
	}

	/**
	 * @param string $content
	 * Set OEmbed data of the page
	 */
	public function setContent(string $content)
	{
		$this->content = $content;
	}

	/**
	 * @return string
	 * Get datetime of creation
	 */
	public function getCreated()
	{
		return $this->created;
	}

	/**
	 * @param string $created
	 * Set datetime of creation
	 */
	public function setCreated(string $created)
	{
		$this->created = $created;
	}
}
