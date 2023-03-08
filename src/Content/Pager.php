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

namespace Friendica\Content;

use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Util\Strings;

/**
 * The Pager has two very different output, Minimal and Full, see renderMinimal() and renderFull() for more details.
 */
class Pager
{
	/** @var int Default count of items per page */
	const ITEMS_PER_PAGE = 50;

	/** @var integer */
	private $page = 1;
	/** @var integer */
	protected $itemsPerPage = self::ITEMS_PER_PAGE;
	/** @var string */
	protected $baseQueryString = '';

	/** @var L10n */
	protected $l10n;

	/**
	 * Instantiates a new Pager with the base parameters.
	 *
	 * Guesses the page number from the GET parameter 'page'.
	 *
	 * @param L10n   $l10n
	 * @param string $queryString  The query string of the current page
	 * @param int    $itemsPerPage An optional number of items per page to override the default value
	 */
	public function __construct(L10n $l10n, string $queryString, int $itemsPerPage = 50)
	{
		$this->l10n = $l10n;

		$this->setQueryString($queryString);
		$this->setItemsPerPage($itemsPerPage);
		$this->setPage((int)($_GET['page'] ?? 0) ?: 1);
	}

	/**
	 * Returns the start offset for a LIMIT clause. Starts at 0.
	 *
	 * @return int
	 */
	public function getStart(): int
	{
		return max(0, ($this->page * $this->itemsPerPage) - $this->itemsPerPage);
	}

	/**
	 * Returns the number of items per page
	 *
	 * @return int
	 */
	public function getItemsPerPage(): int
	{
		return $this->itemsPerPage;
	}

	/**
	 * Returns the current page number
	 *
	 * @return int
	 */
	public function getPage(): int
	{
		return $this->page;
	}

	/**
	 * Returns the base query string.
	 *
	 * Warning: this isn't the same value as passed to the constructor.
	 * See setQueryString() for the inventory of transformations
	 *
	 * @see setBaseQuery()
	 * @return string
	 */
	public function getBaseQueryString()
	{
		return Strings::ensureQueryParameter($this->baseQueryString);
	}

	/**
	 * Sets the number of items per page, 1 minimum.
	 *
	 * @param int $itemsPerPage
	 */
	public function setItemsPerPage(int $itemsPerPage)
	{
		$this->itemsPerPage = max(1, intval($itemsPerPage));
	}

	/**
	 * Sets the current page number. Starts at 1.
	 *
	 * @param int $page
	 */
	public function setPage(int $page)
	{
		$this->page = max(1, $page);
	}

	/**
	 * Sets the base query string from a full query string.
	 *
	 * Strips the 'page' parameter
	 *
	 * @param string $queryString
	 */
	public function setQueryString(string $queryString)
	{
		$stripped = preg_replace('/([&?]page=[0-9]*)/', '', $queryString);

		$stripped = trim($stripped, '/');

		$this->baseQueryString = $stripped;
	}

	/**
	 * Minimal pager (newer/older)
	 *
	 * This mode is intended for reverse chronological pages and presents only two links, newer (previous) and older (next).
	 * The itemCount is the number of displayed items. If no items are displayed, the older button is disabled.
	 *
	 * Example usage:
	 *
	 * $pager = new Pager($a->query_string);
	 *
	 * $params = ['order' => ['sort_field' => true], 'limit' => [$pager->getStart(), $pager->getItemsPerPage()]];
	 * $items = DBA::toArray(DBA::select($table, $fields, $condition, $params));
	 *
	 * $html = $pager->renderMinimal(count($items));
	 *
	 * @param int $itemCount The number of displayed items on the page
	 * @return string HTML string of the pager
	 * @throws \Exception
	 */
	public function renderMinimal(int $itemCount): string
	{
		$displayedItemCount = max(0, intval($itemCount));

		$data = [
			'class' => 'pager',
			'prev'  => [
				'url'   => Strings::ensureQueryParameter($this->baseQueryString . '&page=' . ($this->getPage() - 1)),
				'text'  => $this->l10n->t('newer'),
				'class' => 'previous' . ($this->getPage() == 1 ? ' disabled' : '')
			],
			'next'  => [
				'url'   => Strings::ensureQueryParameter($this->baseQueryString . '&page=' . ($this->getPage() + 1)),
				'text'  => $this->l10n->t('older'),
				'class' =>  'next' . ($displayedItemCount < $this->getItemsPerPage() ? ' disabled' : '')
			]
		];

		$tpl = Renderer::getMarkupTemplate('paginate.tpl');
		return Renderer::replaceMacros($tpl, ['pager' => $data]);
	}

	/**
	 * Full pager (first / prev / 1 / 2 / ... / 14 / 15 / next / last)
	 *
	 * This mode presents page numbers as well as first, previous, next and last links.
	 * The itemCount is the total number of items including those not displayed.
	 *
	 * Example usage:
	 *
	 * $total = DBA::count($table, $condition);
	 *
	 * $pager = new Pager($a->query_string, $total);
	 *
	 * $params = ['limit' => [$pager->getStart(), $pager->getItemsPerPage()]];
	 * $items = DBA::toArray(DBA::select($table, $fields, $condition, $params));
	 *
	 * $html = $pager->renderFull();
	 *
	 * @param int $itemCount The total number of items including those note displayed on the page
	 * @return string HTML string of the pager
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public function renderFull(int $itemCount): string
	{
		$totalItemCount = max(0, $itemCount);

		$data = [];

		$data['class'] = 'pagination';
		if ($totalItemCount > $this->getItemsPerPage()) {
			$data['first'] = [
				'url'   => Strings::ensureQueryParameter($this->baseQueryString . '&page=1'),
				'text'  => $this->l10n->t('first'),
				'class' => $this->getPage() == 1 ? 'disabled' : ''
			];
			$data['prev'] = [
				'url'   => Strings::ensureQueryParameter($this->baseQueryString . '&page=' . ($this->getPage() - 1)),
				'text'  => $this->l10n->t('prev'),
				'class' => $this->getPage() == 1 ? 'disabled' : ''
			];

			$numpages = $totalItemCount / $this->getItemsPerPage();

			$numstart = 1;
			$numstop = $numpages;

			// Limit the number of displayed page number buttons.
			if ($numpages > 8) {
				$numstart = (($this->getPage() > 4) ? ($this->getPage() - 4) : 1);
				$numstop = (($this->getPage() > ($numpages - 7)) ? $numpages : ($numstart + 8));
			}

			$pages = [];

			for ($i = $numstart; $i <= $numstop; $i++) {
				if ($i == $this->getPage()) {
					$pages[$i] = [
						'url'   => '#',
						'text'  => $i,
						'class' => 'current active'
					];
				} else {
					$pages[$i] = [
						'url'   => Strings::ensureQueryParameter($this->baseQueryString . '&page=' . $i),
						'text'  => $i,
						'class' => 'n'
					];
				}
			}

			if (($totalItemCount % $this->getItemsPerPage()) != 0) {
				if ($i == $this->getPage()) {
					$pages[$i] = [
						'url'   => '#',
						'text'  => $i,
						'class' => 'current active'
					];
				} else {
					$pages[$i] = [
						'url'   => Strings::ensureQueryParameter($this->baseQueryString . '&page=' . $i),
						'text'  => $i,
						'class' => 'n'
					];
				}
			}

			$data['pages'] = $pages;

			$lastpage = (($numpages > intval($numpages)) ? intval($numpages)+1 : $numpages);

			$data['next'] = [
				'url'   => Strings::ensureQueryParameter($this->baseQueryString . '&page=' . ($this->getPage() + 1)),
				'text'  => $this->l10n->t('next'),
				'class' => $this->getPage() == $lastpage ? 'disabled' : ''
			];
			$data['last'] = [
				'url'   => Strings::ensureQueryParameter($this->baseQueryString . '&page=' . $lastpage),
				'text'  => $this->l10n->t('last'),
				'class' => $this->getPage() == $lastpage ? 'disabled' : ''
			];
		}

		$tpl = Renderer::getMarkupTemplate('paginate.tpl');
		return Renderer::replaceMacros($tpl, ['pager' => $data]);
	}
}
