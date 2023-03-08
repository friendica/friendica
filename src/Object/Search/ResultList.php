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

namespace Friendica\Object\Search;

use Friendica\Model\Search;

/**
 * A list of search results with metadata
 *
 * @see Search for details
 */
class ResultList
{
	/**
	 * Page of the result list
	 * @var int
	 */
	private $page;
	/**
	 * Total count of results
	 * @var int
	 */
	private $total;
	/**
	 * items per page
	 * @var int
	 */
	private $itemsPage;
	/**
	 * Array of results
	 *
	 * @var IResult[]
	 */
	private $results;

	/**
	 * @return int
	 */
	public function getPage()
	{
		return $this->page;
	}

	/**
	 * @return int
	 */
	public function getTotal()
	{
		return $this->total;
	}

	/**
	 * @return int
	 */
	public function getItemsPage()
	{
		return $this->itemsPage;
	}

	/**
	 * @return IResult[]
	 */
	public function getResults()
	{
		return $this->results;
	}

	/**
	 * @param int             $page
	 * @param int             $total
	 * @param int             $itemsPage
	 * @param IResult[] $results
	 */
	public function __construct($page = 0, $total = 0, $itemsPage = 0, array $results = [])
	{
		$this->page      = $page;
		$this->total     = $total;
		$this->itemsPage = $itemsPage;

		$this->results = $results;
	}

	/**
	 * Adds a result to the result list
	 *
	 * @param IResult $result
	 */
	public function addResult(IResult $result)
	{
		$this->results[] = $result;
	}
}
