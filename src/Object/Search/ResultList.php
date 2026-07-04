<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

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
	public function __construct(
		/**
		 * Page of the result list
		 */
		private $page = 0,
		/**
		 * Total count of results
		 */
		private $total = 0,
		/**
		 * items per page
		 */
		private $itemsPage = 0,
		/**
		 * Array of results
		 */
		private array $results = [],
	) {}

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
