<?php
/**
 * @copyright Copyright (C) 2010-2022, the Friendica project
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

namespace Friendica\Protocol\ActivityPub;

class FetchQueueItem
{
	/** @var string */
	private $url;
	/** @var array */
	private $child;
	/** @var string */
	private $relay_actor;
	/** @var int */
	private $completion;

	/**
	 * This constructor matches the signature of Processor::fetchMissingActivity except for the default $completion value
	 *
	 * @param string $url
	 * @param array  $child
	 * @param string $relay_actor
	 * @param int    $completion
	 */
	public function __construct(string $url, array $child = [], string $relay_actor = '', int $completion = Receiver::COMPLETION_AUTO)
	{
		$this->url         = $url;
		$this->child       = $child;
		$this->relay_actor = $relay_actor;
		$this->completion  = $completion;
	}

	public function getUrl(): string
	{
		return $this->url;
	}

	/**
	 * Array meant to be used in call_user_function_array([Processor::class, 'fetchMissingActivity']). Caller needs to
	 * provide an instance of a FetchQueue that isn't included in these parameters.
	 *
	 * @see FetchQueue::process()
	 * @return array
	 */
	public function toParameters(): array
	{
		return [$this->url, $this->child, $this->relay_actor, $this->completion];
	}
}
