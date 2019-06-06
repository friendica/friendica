<?php

namespace Friendica\Content;

use Exception;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Object\Pager;
use Friendica\Util\Strings;

/**
 * The Rendering Pager has two very different output, Minimal and Full, see renderMinimal() and renderFull() for more details.
 *
 * @author Hypolite Petovan <mrpetovan@gmail.com>
 */
class RenderedPager extends Pager
{
	/**
	 * Limits the amount of pages for a full rendering
	 * @var integer
	 */
	const DEFAULT_PAGE_LIMIT = 4;

	/**
	 * @var string
	 */
	private $baseQueryString = '';

	/**
	 * {@inheritDoc}
	 *
	 * @param string $queryString The query string of the current page
	 */
	public function __construct(string $queryString, $page = 1, $itemsPerPage = self::DEFAULT_ITEMS_PER_PAGE)
	{
		parent::__construct($page, $itemsPerPage);
		$this->setQueryString($queryString);
	}

	/**
	 * Creates a new Pager for rendering based on a available pager
	 *
	 * @hint useful in case you got a native pager, but additionally want to render this pager
	 *
	 * @param string $queryString
	 * @param Pager  $pager
	 *
	 * @return RenderedPager
	 */
	public static function createByPager(string $queryString, Pager $pager)
	{
		return new RenderedPager($queryString, $pager->getPage(), $pager->getItemsPerPage());
	}

	/**
	 * Returns the base query string.
	 *
	 * Warning: this isn't the same value as passed to the constructor.
	 * See setQueryString() for the inventory of transformations
	 *
	 * @return string
	 * @see setBaseQuery()
	 */
	public function getBaseQueryString()
	{
		return Strings::ensureQueryParameter($this->baseQueryString);
	}

	/**
	 * Sets the base query string from a full query string.
	 *
	 * Strips the 'page' parameter, and remove the 'q=' string for some reason.
	 *
	 * @param string $queryString
	 */
	public function setQueryString($queryString)
	{
		$stripped = preg_replace('/([&?]page=[0-9]*)/', '', $queryString);

		$stripped = str_replace('q=', '', $stripped);
		$stripped = trim($stripped, '/');

		$this->baseQueryString = $stripped;
	}

	/**
	 * @brief Minimal pager (newer/older)
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
	 * @param integer $itemCount The number of displayed items on the page
	 *
	 * @return string HTML string of the pager
	 * @throws Exception
	 */
	public function renderMinimal($itemCount)
	{
		$displayedItemCount = max(0, intval($itemCount));

		$data = [
			'class' => 'pager',
			'prev'  => [
				'url'   => Strings::ensureQueryParameter($this->baseQueryString . '&page=' . ($this->getPage() - 1)),
				'text'  => L10n::t('newer'),
				'class' => 'previous' . ($this->getPage() <= 1 ? ' disabled' : '')
			],
			'next'  => [
				'url'   => Strings::ensureQueryParameter($this->baseQueryString . '&page=' . ($this->getPage() + 1)),
				'text'  => L10n::t('older'),
				'class' => 'next' . ($displayedItemCount < $this->getItemsPerPage() ? ' disabled' : '')
			]
		];

		$tpl = Renderer::getMarkupTemplate('paginate.tpl');
		return Renderer::replaceMacros($tpl, ['pager' => $data]);
	}

	/**
	 * @brief Full pager (first / prev / 1 / 2 / ... / 14 / 15 / next / last)
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
	 * @param integer $itemCount The total number of items including those note displayed on the page
	 *
	 * @return string HTML string of the pager
	 * @throws Exception
	 */
	public function renderFull($itemCount)
	{
		$totalItemCount = max(0, intval($itemCount));

		$data = [];

		$data['class'] = 'pagination';
		if ($totalItemCount > $this->getItemsPerPage()) {
			$data['first'] = [
				'url'   => Strings::ensureQueryParameter($this->baseQueryString . '&page=1'),
				'text'  => L10n::t('first'),
				'class' => $this->getPage() == 1 ? 'disabled' : ''
			];
			$data['prev']  = [
				'url'   => Strings::ensureQueryParameter($this->baseQueryString . '&page=' . ($this->getPage() - 1)),
				'text'  => L10n::t('prev'),
				'class' => $this->getPage() == 1 ? 'disabled' : ''
			];

			$numpages = $totalItemCount / $this->getItemsPerPage();

			$numstart = 1;
			$numstop  = $numpages;

			// Limit the number of displayed page number buttons.
			if ($numpages > 8) {
				$numstart = (($this->getPage() > self::DEFAULT_PAGE_LIMIT) ? ($this->getPage() - self::DEFAULT_PAGE_LIMIT) : 1);
				$numstop  = (($this->getPage() > ($numpages - (self::DEFAULT_PAGE_LIMIT * 2) - 1)) ? $numpages : ($numstart + (self::DEFAULT_PAGE_LIMIT * 2)));
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

			$lastpage = (($numpages > intval($numpages)) ? intval($numpages) + 1 : $numpages);

			$data['next'] = [
				'url'   => Strings::ensureQueryParameter($this->baseQueryString . '&page=' . ($this->getPage() + 1)),
				'text'  => L10n::t('next'),
				'class' => $this->getPage() == $lastpage ? 'disabled' : ''
			];
			$data['last'] = [
				'url'   => Strings::ensureQueryParameter($this->baseQueryString . '&page=' . $lastpage),
				'text'  => L10n::t('last'),
				'class' => $this->getPage() == $lastpage ? 'disabled' : ''
			];
		}

		$tpl = Renderer::getMarkupTemplate('paginate.tpl');
		return Renderer::replaceMacros($tpl, ['pager' => $data]);
	}
}
