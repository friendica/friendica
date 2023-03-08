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
 * See update_profile.php for documentation
 */

namespace Friendica\Module\Conversation;

use Friendica\BaseModule;
use Friendica\Content\BoundariesPager;
use Friendica\Content\Conversation;
use Friendica\Content\Feature;
use Friendica\Content\Nav;
use Friendica\Content\Text\HTML;
use Friendica\Content\Widget;
use Friendica\Content\Widget\TrendingTags;
use Friendica\Core\Renderer;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Model\User;
use Friendica\Network\HTTPException;

class Community extends BaseModule
{
	/**
	 * Type of the community page
	 * @{
	 */
	const DISABLED         = -2;
	const DISABLED_VISITOR = -1;
	const LOCAL            = 0;
	const GLOBAL           = 1;
	const LOCAL_AND_GLOBAL = 2;
	/**
	 * @}
	 */

	protected static $page_style;
	protected static $content;
	protected static $accountTypeString;
	protected static $accountType;
	protected static $itemsPerPage;
	protected static $min_id;
	protected static $max_id;
	protected static $item_id;

	protected function content(array $request = []): string
	{
		$this->parseRequest();

		$t = Renderer::getMarkupTemplate("community.tpl");
		$o = Renderer::replaceMacros($t, [
			'$content' => '',
			'$header' => '',
			'$show_global_community_hint' => (self::$content == 'global') && DI::config()->get('system', 'show_global_community_hint'),
			'$global_community_hint' => DI::l10n()->t("This community stream shows all public posts received by this node. They may not reflect the opinions of this node’s users.")
		]);

		if (DI::pConfig()->get(DI::userSession()->getLocalUserId(), 'system', 'infinite_scroll')) {
			$tpl = Renderer::getMarkupTemplate('infinite_scroll_head.tpl');
			$o .= Renderer::replaceMacros($tpl, ['$reload_uri' => DI::args()->getQueryString()]);
		}

		if (empty($_GET['mode']) || ($_GET['mode'] != 'raw')) {
			$tabs = [];

			if ((DI::userSession()->isAuthenticated() || in_array(self::$page_style, [self::LOCAL_AND_GLOBAL, self::LOCAL])) && empty(DI::config()->get('system', 'singleuser'))) {
				$tabs[] = [
					'label' => DI::l10n()->t('Local Community'),
					'url' => 'community/local',
					'sel' => self::$content == 'local' ? 'active' : '',
					'title' => DI::l10n()->t('Posts from local users on this server'),
					'id' => 'community-local-tab',
					'accesskey' => 'l'
				];
			}
	
			if (DI::userSession()->isAuthenticated() || in_array(self::$page_style, [self::LOCAL_AND_GLOBAL, self::GLOBAL])) {
				$tabs[] = [
					'label' => DI::l10n()->t('Global Community'),
					'url' => 'community/global',
					'sel' => self::$content == 'global' ? 'active' : '',
					'title' => DI::l10n()->t('Posts from users of the whole federated network'),
					'id' => 'community-global-tab',
					'accesskey' => 'g'
				];
			}

			$tab_tpl = Renderer::getMarkupTemplate('common_tabs.tpl');
			$o .= Renderer::replaceMacros($tab_tpl, ['$tabs' => $tabs]);

			Nav::setSelected('community');

			DI::page()['aside'] .= Widget::accountTypes('community/' . self::$content, self::$accountTypeString);
	
			if (DI::userSession()->getLocalUserId() && DI::config()->get('system', 'community_no_sharer')) {
				$path = self::$content;
				if (!empty($this->parameters['accounttype'])) {
					$path .= '/' . $this->parameters['accounttype'];
				}
				$query_parameters = [];
		
				if (!empty($_GET['min_id'])) {
					$query_parameters['min_id'] = $_GET['min_id'];
				}
				if (!empty($_GET['max_id'])) {
					$query_parameters['max_id'] = $_GET['max_id'];
				}
				if (!empty($_GET['last_commented'])) {
					$query_parameters['max_id'] = $_GET['last_commented'];
				}
		
				$path_all = $path . (!empty($query_parameters) ? '?' . http_build_query($query_parameters) : '');
				$path_no_sharer = $path . '?' . http_build_query(array_merge($query_parameters, ['no_sharer' => true]));
				DI::page()['aside'] .= Renderer::replaceMacros(Renderer::getMarkupTemplate('widget/community_sharer.tpl'), [
					'$title'           => DI::l10n()->t('Own Contacts'),
					'$path_all'        => $path_all,
					'$path_no_sharer'  => $path_no_sharer,
					'$no_sharer'       => !empty($_REQUEST['no_sharer']),
					'$all'             => DI::l10n()->t('Include'),
					'$no_sharer_label' => DI::l10n()->t('Hide'),
				]);
			}
	
			if (Feature::isEnabled(DI::userSession()->getLocalUserId(), 'trending_tags')) {
				DI::page()['aside'] .= TrendingTags::getHTML(self::$content);
			}

			// We need the editor here to be able to reshare an item.
			if (DI::userSession()->isAuthenticated()) {
				$o .= DI::conversation()->statusEditor([], 0, true);
			}
		}

		$items = self::getItems();

		if (!DBA::isResult($items)) {
			DI::sysmsg()->addNotice(DI::l10n()->t('No results.'));
			return $o;
		}

		$o .= DI::conversation()->create($items, Conversation::MODE_COMMUNITY, false, false, 'commented', DI::userSession()->getLocalUserId());

		$pager = new BoundariesPager(
			DI::l10n(),
			DI::args()->getQueryString(),
			$items[0]['commented'],
			$items[count($items) - 1]['commented'],
			self::$itemsPerPage
		);

		if (DI::pConfig()->get(DI::userSession()->getLocalUserId(), 'system', 'infinite_scroll')) {
			$o .= HTML::scrollLoader();
		} else {
			$o .= $pager->renderMinimal(count($items));
		}

		return $o;
	}

	/**
	 * Computes module parameters from the request and local configuration
	 *
	 * @throws HTTPException\BadRequestException
	 * @throws HTTPException\ForbiddenException
	 */
	protected function parseRequest()
	{
		if (DI::config()->get('system', 'block_public') && !DI::userSession()->isAuthenticated()) {
			throw new HTTPException\ForbiddenException(DI::l10n()->t('Public access denied.'));
		}

		self::$page_style = DI::config()->get('system', 'community_page_style');

		if (self::$page_style == self::DISABLED) {
			throw new HTTPException\ForbiddenException(DI::l10n()->t('Access denied.'));
		}

		self::$accountTypeString = $_GET['accounttype'] ?? $this->parameters['accounttype'] ?? '';
		self::$accountType = User::getAccountTypeByString(self::$accountTypeString);

		self::$content = $this->parameters['content'] ?? '';
		if (!self::$content) {
			if (!empty(DI::config()->get('system', 'singleuser'))) {
				// On single user systems only the global page does make sense
				self::$content = 'global';
			} else {
				// When only the global community is allowed, we use this as default
				self::$content = self::$page_style == self::GLOBAL ? 'global' : 'local';
			}
		}

		if (!in_array(self::$content, ['local', 'global'])) {
			throw new HTTPException\BadRequestException(DI::l10n()->t('Community option not available.'));
		}

		// Check if we are allowed to display the content to visitors
		if (!DI::userSession()->isAuthenticated()) {
			$available = self::$page_style == self::LOCAL_AND_GLOBAL;

			if (!$available) {
				$available = (self::$page_style == self::LOCAL) && (self::$content == 'local');
			}

			if (!$available) {
				$available = (self::$page_style == self::GLOBAL) && (self::$content == 'global');
			}

			if (!$available) {
				throw new HTTPException\ForbiddenException(DI::l10n()->t('Not available.'));
			}
		}

		if (DI::mode()->isMobile()) {
			self::$itemsPerPage = DI::pConfig()->get(DI::userSession()->getLocalUserId(), 'system', 'itemspage_mobile_network',
				DI::config()->get('system', 'itemspage_network_mobile'));
		} else {
			self::$itemsPerPage = DI::pConfig()->get(DI::userSession()->getLocalUserId(), 'system', 'itemspage_network',
				DI::config()->get('system', 'itemspage_network'));
		}

		if (!empty($_GET['item'])) {
			$item = Post::selectFirst(['parent'], ['id' => $_GET['item']]);
			self::$item_id = $item['parent'] ?? 0;
		} else {
			self::$item_id = 0;
		}

		self::$min_id = $_GET['min_id'] ?? null;
		self::$max_id   = $_GET['max_id']   ?? null;
		self::$max_id   = $_GET['last_commented'] ?? self::$max_id;
	}

	/**
	 * Computes the displayed items.
	 *
	 * Community pages have a restriction on how many successive posts by the same author can show on any given page,
	 * so we may have to retrieve more content beyond the first query
	 *
	 * @return array
	 * @throws \Exception
	 */
	protected static function getItems()
	{
		$items = self::selectItems(self::$min_id, self::$max_id, self::$item_id, self::$itemsPerPage);

		$maxpostperauthor = (int) DI::config()->get('system', 'max_author_posts_community_page');
		if ($maxpostperauthor != 0 && self::$content == 'local') {
			$count = 1;
			$previousauthor = '';
			$numposts = 0;
			$selected_items = [];

			while (count($selected_items) < self::$itemsPerPage && ++$count < 50 && count($items) > 0) {
				foreach ($items as $item) {
					if ($previousauthor == $item["author-link"]) {
						++$numposts;
					} else {
						$numposts = 0;
					}
					$previousauthor = $item["author-link"];

					if (($numposts < $maxpostperauthor) && (count($selected_items) < self::$itemsPerPage)) {
						$selected_items[] = $item;
					}
				}

				// If we're looking at a "previous page", the lookup continues forward in time because the list is
				// sorted in chronologically decreasing order
				if (isset(self::$min_id)) {
					self::$min_id = $items[0]['commented'];
				} else {
					// In any other case, the lookup continues backwards in time
					self::$max_id = $items[count($items) - 1]['commented'];
				}

				$items = self::selectItems(self::$min_id, self::$max_id, self::$item_id, self::$itemsPerPage);
			}
		} else {
			$selected_items = $items;
		}

		return $selected_items;
	}

	/**
	 * Database query for the comunity page
	 *
	 * @param $min_id
	 * @param $max_id
	 * @param $itemspage
	 * @return array
	 * @throws \Exception
	 * @TODO Move to repository/factory
	 */
	private static function selectItems($min_id, $max_id, $item_id, $itemspage)
	{
		if (self::$content == 'local') {
			if (!is_null(self::$accountType)) {
				$condition = ["`wall` AND `origin` AND `private` = ? AND `owner-contact-type` = ?", Item::PUBLIC, self::$accountType];
			} else {
 				$condition = ["`wall` AND `origin` AND `private` = ?", Item::PUBLIC];
			}
		} elseif (self::$content == 'global') {
			if (!is_null(self::$accountType)) {
				$condition = ["`uid` = ? AND `private` = ? AND `owner-contact-type` = ?", 0, Item::PUBLIC, self::$accountType];
			} else {
				$condition = ["`uid` = ? AND `private` = ?", 0, Item::PUBLIC];
			}
		} else {
			return [];
		}

		$params = ['order' => ['commented' => true], 'limit' => $itemspage];

		if (!empty($item_id)) {
			$condition[0] .= " AND `id` = ?";
			$condition[] = $item_id;
		} else {
			if (DI::userSession()->getLocalUserId() && !empty($_REQUEST['no_sharer'])) {
				$condition[0] .= " AND NOT `uri-id` IN (SELECT `uri-id` FROM `post-user` WHERE `post-user`.`uid` = ? AND `post-user`.`uri-id` = `post-thread-user-view`.`uri-id`)";
				$condition[] = DI::userSession()->getLocalUserId();
			}
	
			if (isset($max_id)) {
				$condition[0] .= " AND `commented` < ?";
				$condition[] = $max_id;
			}

			if (isset($min_id)) {
				$condition[0] .= " AND `commented` > ?";
				$condition[] = $min_id;

				// Previous page case: we want the items closest to min_id but for that we need to reverse the query order
				if (!isset($max_id)) {
					$params['order']['commented'] = false;
				}
			}
		}

		$r = Post::selectThreadForUser(0, ['uri-id', 'commented', 'author-link'], $condition, $params);

		$items = Post::toArray($r);
		if (empty($items)) {
			return [];
		}

		// Previous page case: once we get the relevant items closest to min_id, we need to restore the expected display order
		if (empty($item_id) && isset($min_id) && !isset($max_id)) {
			$items = array_reverse($items);
		}

		return $items;
	}
}
