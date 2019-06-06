<?php

namespace Friendica\Object;

/**
 * A pager class.
 */
class Pager
{
	/**
	 * The default item count per page
	 * @var integer
	 */
	const DEFAULT_ITEMS_PER_PAGE = 50;

	/**
	 * @var integer
	 */
	private $page = 1;
	/**
	 * @var integer
	 */
	private $itemsPerPage = self::DEFAULT_ITEMS_PER_PAGE;

	/**
	 * Instantiates a new Pager with the base parameters.
	 *
	 * Guesses the page number from the GET parameter 'page'.
	 *

	 * @param integer $page         The current page (default is first page)
	 * @param integer $itemsPerPage An optional number of items per page to override the default value
	 */
	public function __construct($page = 1, $itemsPerPage = self::DEFAULT_ITEMS_PER_PAGE)
	{
		$this->setPage($page);
		$this->setItemsPerPage($itemsPerPage);
	}

	/**
	 * Returns the start offset for a LIMIT clause. Starts at 0.
	 *
	 * @return integer
	 */
	public function getStart()
	{
		return max(0, ($this->page * $this->itemsPerPage) - $this->itemsPerPage);
	}

	/**
	 * Returns the number of items per page
	 *
	 * @return integer
	 */
	public function getItemsPerPage()
	{
		return $this->itemsPerPage;
	}

	/**
	 * Returns the current page number
	 *
	 * @return int
	 */
	public function getPage()
	{
		return $this->page;
	}

	/**
	 * Sets the number of items per page, 1 minimum.
	 *
	 * @param integer $itemsPerPage
	 */
	public function setItemsPerPage($itemsPerPage)
	{
		$this->itemsPerPage = max(1, intval($itemsPerPage ?? self::DEFAULT_ITEMS_PER_PAGE));
	}

	/**
	 * Sets the current page number. Starts at 1.
	 *
	 * @param integer $page
	 */
	public function setPage($page)
	{
		$this->page = max(1, intval($page ?? 1));
	}
}
