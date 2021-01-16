<?php
/**
 * @copyright Copyright (C) 2020, Friendica
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

use Friendica\Content\Nav;
use Friendica\Content\Pager;
use Friendica\Content\Text\HTML;
use Friendica\Content\Widget;
use Friendica\Core\Cache\Duration;
use Friendica\Core\Logger;
use Friendica\Core\Renderer;
use Friendica\Core\Search;
use Friendica\Core\Session;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Item;
use Friendica\Model\ItemContent;
use Friendica\Model\Post;
use Friendica\Model\Tag;
use Friendica\Module\BaseSearch;
use Friendica\Network\HTTPException;
use Friendica\Util\Strings;

class Index extends BaseSearch
{
	public static function content(array $parameters = [])
	{
		$search = (!empty($_GET['q']) ? Strings::escapeTags(trim(rawurldecode($_GET['q']))) : '');

		if (DI::config()->get('system', 'block_public') && !Session::isAuthenticated()) {
			throw new HTTPException\ForbiddenException(DI::l10n()->t('Public access denied.'));
		}

		if (DI::config()->get('system', 'local_search') && !Session::isAuthenticated()) {
			$e = new HTTPException\ForbiddenException(DI::l10n()->t('Only logged in users are permitted to perform a search.'));
			$e->httpdesc = DI::l10n()->t('Public access denied.');
			throw $e;
		}

		if (DI::config()->get('system', 'permit_crawling') && !Session::isAuthenticated()) {
			// Default values:
			// 10 requests are "free", after the 11th only a call per minute is allowed

			$free_crawls = intval(DI::config()->get('system', 'free_crawls'));
			if ($free_crawls == 0)
				$free_crawls = 10;

			$crawl_permit_period = intval(DI::config()->get('system', 'crawl_permit_period'));
			if ($crawl_permit_period == 0)
				$crawl_permit_period = 10;

			$remote = $_SERVER['REMOTE_ADDR'];
			$result = DI::cache()->get('remote_search:' . $remote);
			if (!is_null($result)) {
				$resultdata = json_decode($result);
				if (($resultdata->time > (time() - $crawl_permit_period)) && ($resultdata->accesses > $free_crawls)) {
					throw new HTTPException\TooManyRequestsException(DI::l10n()->t('Only one search per minute is permitted for not logged in users.'));
				}
				DI::cache()->set('remote_search:' . $remote, json_encode(['time' => time(), 'accesses' => $resultdata->accesses + 1]), Duration::HOUR);
			} else {
				DI::cache()->set('remote_search:' . $remote, json_encode(['time' => time(), 'accesses' => 1]), Duration::HOUR);
			}
		}

		if (local_user()) {
			DI::page()['aside'] .= Widget\SavedSearches::getHTML(Search::getSearchPath($search), $search);
		}

		Nav::setSelected('search');

		$tag = false;
		if (!empty($_GET['tag'])) {
			$tag = true;
			$search = '#' . Strings::escapeTags(trim(rawurldecode($_GET['tag'])));
		}

		// contruct a wrapper for the search header
		$o = Renderer::replaceMacros(Renderer::getMarkupTemplate('content_wrapper.tpl'), [
			'name' => 'search-header',
			'$title' => DI::l10n()->t('Search'),
			'$title_size' => 3,
			'$content' => HTML::search($search, 'search-box', false)
		]);

		if (!$search) {
			return $o;
		}

		if (strpos($search, '#') === 0) {
			$tag = true;
			$search = substr($search, 1);
		}

		self::tryRedirectToProfile($search);

		if (strpos($search, '@') === 0 || strpos($search, '!') === 0) {
			return self::performContactSearch($search);
		}

		self::tryRedirectToPost($search);

		if (!empty($_GET['search-option'])) {
			switch ($_GET['search-option']) {
				case 'fulltext':
					break;
				case 'tags':
					$tag = true;
					break;
				case 'contacts':
					return self::performContactSearch($search, '@');
				case 'forums':
					return self::performContactSearch($search, '!');
			}
		}

		// Don't perform a fulltext or tag search on search results that look like an URL
		// Tags don't look like an URL and the fulltext search does only work with natural words
		if (parse_url($search, PHP_URL_SCHEME) && parse_url($search, PHP_URL_HOST)) {
			Logger::info('Skipping tag and fulltext search since the search looks like a URL.', ['q' => $search]);
			notice(DI::l10n()->t('No results.'));
			return $o;
		}

		$tag = $tag || DI::config()->get('system', 'only_tag_search');

		// Here is the way permissions work in the search module...
		// Only public posts can be shown
		// OR your own posts if you are a logged in member
		// No items will be shown if the member has a blocked profile wall.

		if (DI::mode()->isMobile()) {
			$itemsPerPage = DI::pConfig()->get(local_user(), 'system', 'itemspage_mobile_network',
				DI::config()->get('system', 'itemspage_network_mobile'));
		} else {
			$itemsPerPage = DI::pConfig()->get(local_user(), 'system', 'itemspage_network',
				DI::config()->get('system', 'itemspage_network'));
		}

		$last_uriid = isset($_GET['last_uriid']) ? intval($_GET['last_uriid']) : 0;

		$pager = new Pager(DI::l10n(), DI::args()->getQueryString(), $itemsPerPage);

		if ($tag) {
			Logger::info('Start tag search.', ['q' => $search]);
			$uriids = Tag::getURIIdListByTag($search, local_user(), $pager->getStart(), $pager->getItemsPerPage(), $last_uriid);
			$count = Tag::countByTag($search, local_user());
		} else {
			Logger::info('Start fulltext search.', ['q' => $search]);
			$uriids = ItemContent::getURIIdListBySearch($search, local_user(), $pager->getStart(), $pager->getItemsPerPage(), $last_uriid);
			$count = ItemContent::countBySearch($search, local_user());
		}

		if (!empty($uriids)) {
			$params = ['order' => ['id' => true], 'group_by' => ['uri-id']];
			$items = Item::inArray(Item::selectForUser(local_user(), [], ['uri-id' => $uriids], $params));
		}

		if (empty($items)) {
			if (empty($last_uriid)) {
				notice(DI::l10n()->t('No results.'));
			}
			return $o;
		}

		if (DI::pConfig()->get(local_user(), 'system', 'infinite_scroll')) {
			$tpl = Renderer::getMarkupTemplate('infinite_scroll_head.tpl');
			$o .= Renderer::replaceMacros($tpl, ['$reload_uri' => DI::args()->getQueryString()]);
		}

		if ($tag) {
			$title = DI::l10n()->t('Items tagged with: %s', $search);
		} else {
			$title = DI::l10n()->t('Results for: %s', $search);
		}

		$o .= Renderer::replaceMacros(Renderer::getMarkupTemplate('section_title.tpl'), [
			'$title' => $title
		]);

		Logger::info('Start Conversation.', ['q' => $search]);

		$o .= conversation(DI::app(), $items, 'search', false, false, 'commented', local_user());

		if (DI::pConfig()->get(local_user(), 'system', 'infinite_scroll')) {
			$o .= HTML::scrollLoader();
		} else {
			$o .= $pager->renderMinimal($count);
		}


		return $o;
	}

	/**
	 * Tries to redirect to a local profile page based on the input.
	 *
	 * This method separates logged in and anonymous users. Logged in users can trigger contact probes to import
	 * non-existing contacts while anonymous users can only trigger a local lookup.
	 *
	 * Formats matched:
	 * - @user@domain
	 * - user@domain
	 * - Any fully-formed URL
	 *
	 * @param string  $search
	 * @throws HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	private static function tryRedirectToProfile(string $search)
	{
		$isUrl = !empty(parse_url($search, PHP_URL_SCHEME));
		$isAddr = (bool)preg_match('/^@?([a-z0-9.-_]+@[a-z0-9.-_:]+)$/i', trim($search), $matches);

		if (!$isUrl && !$isAddr) {
			return;
		}

		if ($isAddr) {
			$search = $matches[1];
		}

		if (local_user()) {
			// User-specific contact URL/address search
			$contact_id = Contact::getIdForURL($search, local_user());
			if (!$contact_id) {
				// User-specific contact URL/address search and probe
				$contact_id = Contact::getIdForURL($search);
			}
		} else {
			// Cheaper local lookup for anonymous users, no probe
			if ($isAddr) {
				$contact = Contact::selectFirst(['id'], ['addr' => $search, 'uid' => 0]);
			} else {
				$contact = Contact::getByURL($search, null, ['id']) ?: ['id' => 0];
			}

			if (DBA::isResult($contact)) {
				$contact_id = $contact['id'];
			}
		}

		if (!empty($contact_id)) {
			DI::baseUrl()->redirect('contact/' . $contact_id);
		}
	}

	/**
	 * Fetch/search a post by URL and redirects to its local representation if it was found.
	 *
	 * @param string  $search
	 * @throws HTTPException\InternalServerErrorException
	 */
	private static function tryRedirectToPost(string $search)
	{
		if (parse_url($search, PHP_URL_SCHEME) == '') {
			return;
		}

		if (local_user()) {
			// Post URL search
			$item_id = Item::fetchByLink($search, local_user());
			if (!$item_id) {
				// If the user-specific search failed, we search and probe a public post
				$item_id = Item::fetchByLink($search);
			}
		} else {
			// Cheaper local lookup for anonymous users, no probe
			$item_id = Item::searchByLink($search);
		}

		if (!empty($item_id)) {
			$item = Post::selectFirst(['guid'], ['id' => $item_id]);
			if (DBA::isResult($item)) {
				DI::baseUrl()->redirect('display/' . $item['guid']);
			}
		}
	}
}
