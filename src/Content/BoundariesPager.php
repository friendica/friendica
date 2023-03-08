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
use Friendica\Util\Network;
use Friendica\Util\Strings;

/**
 * This pager should be used by lists using the min_id†/max_id† parameters
 *
 * This pager automatically identifies if the sorting is done increasingly or decreasingly if the first item id†
 * and last item id† are different. Otherwise it defaults to decreasingly like reverse chronological lists.
 *
 * † In this context, "id" refers to the value of the column that the item list is ordered by.
 */
class BoundariesPager extends Pager
{
	protected $first_item_id;
	protected $last_item_id;
	protected $first_page = true;

	/**
	 * Instantiates a new Pager with the base parameters.
	 *
	 * @param L10n    $l10n
	 * @param string  $queryString   The query string of the current page
	 * @param string  $first_item_id The id† of the first item in the displayed item list
	 * @param string  $last_item_id  The id† of the last item in the displayed item list
	 * @param integer $itemsPerPage  An optional number of items per page to override the default value
	 */
	public function __construct(L10n $l10n, string $queryString, string $first_item_id = null, string $last_item_id = null, int $itemsPerPage = 50)
	{
		parent::__construct($l10n, $queryString, $itemsPerPage);

		$this->first_item_id = $first_item_id;
		$this->last_item_id = $last_item_id;

		$parsed = parse_url($this->getBaseQueryString());
		if (!empty($parsed['query'])) {
			parse_str($parsed['query'], $queryParameters);

			$this->first_page = !($queryParameters['min_id'] ?? null) && !($queryParameters['max_id'] ?? null);

			unset($queryParameters['min_id']);
			unset($queryParameters['max_id']);

			$parsed['query'] = http_build_query($queryParameters);

			$url = Network::unparseURL($parsed);

			$this->setQueryString($url);
		}
	}

	public function getStart(): int
	{
		throw new \BadMethodCallException();
	}

	public function getPage(): int
	{
		throw new \BadMethodCallException();
	}

	/**
	 * Minimal pager (newer/older)
	 *
	 * This mode is intended for reverse chronological pages and presents only two links, newer (previous) and older (next).
	 * The itemCount is the number of displayed items. If no items are displayed, the older button is disabled.
	 *
	 * Example usage:
	 *
	 * $params = ['order' => ['sort_field' => true], 'limit' => $itemsPerPage];
	 * $items = DBA::toArray(DBA::select($table, $fields, $condition, $params));
	 *
	 * $pager = new BoundariesPager($a->query_string, $items[0]['sort_field'], $items[coutn($items) - 1]['sort_field'], $itemsPerPage);
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
				'url'   => Strings::ensureQueryParameter($this->baseQueryString .
					($this->first_item_id >= $this->last_item_id ?
						'&min_id=' . $this->first_item_id : '&max_id=' . $this->first_item_id)
				),
				'text'  => $this->l10n->t('newer'),
				'class' => 'previous' . ($this->first_page ? ' disabled' : '')
			],
			'next'  => [
				'url'   => Strings::ensureQueryParameter($this->baseQueryString .
					($this->first_item_id >= $this->last_item_id ?
					'&max_id=' . $this->last_item_id : '&min_id=' . $this->last_item_id)
				),
				'text'  => $this->l10n->t('older'),
				'class' =>  'next' . ($displayedItemCount < $this->getItemsPerPage() ? ' disabled' : '')
			]
		];

		$tpl = Renderer::getMarkupTemplate('paginate.tpl');
		return Renderer::replaceMacros($tpl, ['pager' => $data]);
	}

	/**
	 * Unsupported method, must be type-compatible
	 */
	public function renderFull(int $itemCount): string
	{
		throw new \BadMethodCallException();
	}
}
