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

namespace Friendica\Module\Search;

use Friendica\Content\Conversation;
use Friendica\Content\Nav;
use Friendica\Content\Pager;
use Friendica\Content\Text\HTML;
use Friendica\Content\Widget;
use Friendica\Core\Renderer;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Model\Post\Category;
use Friendica\Module\BaseSearch;
use Friendica\Module\Security\Login;

class Filed extends BaseSearch
{
	protected function content(array $request = []): string
	{
		if (!DI::userSession()->getLocalUserId()) {
			return Login::form();
		}

		DI::page()['aside'] .= Widget::fileAs(DI::args()->getCommand(), $_GET['file'] ?? '');

		if (DI::pConfig()->get(DI::userSession()->getLocalUserId(), 'system', 'infinite_scroll') && ($_GET['mode'] ?? '') != 'minimal') {
			$tpl = Renderer::getMarkupTemplate('infinite_scroll_head.tpl');
			$o = Renderer::replaceMacros($tpl, ['$reload_uri' => DI::args()->getQueryString()]);
		} else {
			$o = '';
		}

		$file = $_GET['file'] ?? '';

		// Rawmode is used for fetching new content at the end of the page
		if (!(isset($_GET['mode']) && ($_GET['mode'] == 'raw'))) {
			Nav::setSelected(DI::args()->get(0));
		}

		if (DI::mode()->isMobile()) {
			$itemspage_network = DI::pConfig()->get(DI::userSession()->getLocalUserId(), 'system', 'itemspage_mobile_network',
				DI::config()->get('system', 'itemspage_network_mobile'));
		} else {
			$itemspage_network = DI::pConfig()->get(DI::userSession()->getLocalUserId(), 'system', 'itemspage_network',
				DI::config()->get('system', 'itemspage_network'));
		}

		$last_uriid = isset($_GET['last_uriid']) ? intval($_GET['last_uriid']) : 0;

		$pager = new Pager(DI::l10n(), DI::args()->getQueryString(), $itemspage_network);

		$term_condition = ['type' => Category::FILE, 'uid' => DI::userSession()->getLocalUserId()];
		if ($file) {
			$term_condition['name'] = $file;
		}

		if (!empty($last_uriid)) {
			$term_condition = DBA::mergeConditions($term_condition, ["`uri-id` < ?", $last_uriid]);
		}

		$term_params = ['order' => ['uri-id' => true], 'limit' => [$pager->getStart(), $pager->getItemsPerPage()]];
		$result = DBA::select('category-view', ['uri-id'], $term_condition, $term_params);

		$count = DBA::count('category-view', $term_condition);

		$posts = [];
		while ($term = DBA::fetch($result)) {
			$posts[] = $term['uri-id'];
		}
		DBA::close($result);

		if (count($posts) == 0) {
			return '';
		}
		$item_condition = ['uid' => [0, DI::userSession()->getLocalUserId()], 'uri-id' => $posts];
		$item_params = ['order' => ['uri-id' => true, 'uid' => true]];

		$items = Post::toArray(Post::selectForUser(DI::userSession()->getLocalUserId(), Item::DISPLAY_FIELDLIST, $item_condition, $item_params));

		$o .= DI::conversation()->create($items, Conversation::MODE_FILED, false, false, '', DI::userSession()->getLocalUserId());

		if (DI::pConfig()->get(DI::userSession()->getLocalUserId(), 'system', 'infinite_scroll')) {
			$o .= HTML::scrollLoader();
		} else {
			$o .= $pager->renderMinimal($count);
		}

		return $o;
	}
}
