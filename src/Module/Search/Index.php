<?php

namespace Friendica\Module\Search;

use Friendica\Content\Nav;
use Friendica\Content\Pager;
use Friendica\Content\Text\HTML;
use Friendica\Content\Widget;
use Friendica\Core\Cache\Cache as CacheClass;
use Friendica\Core\Config;
use Friendica\Core\L10n;
use Friendica\Core\Logger;
use Friendica\Core\Renderer;
use Friendica\Core\Session;
use Friendica\Database\DBA;
use Friendica\Registry\DI;
use Friendica\Model\Contact;
use Friendica\Model\Item;
use Friendica\Model\Term;
use Friendica\Module\BaseSearchModule;
use Friendica\Network\HTTPException;
use Friendica\Registry\App;
use Friendica\Util\Strings;

class Index extends BaseSearchModule
{
	public static function content(array $parameters = [])
	{
		$search = (!empty($_GET['q']) ? Strings::escapeTags(trim(rawurldecode($_GET['q']))) : '');

		if (Config::get('system', 'block_public') && !Session::isAuthenticated()) {
			throw new HTTPException\ForbiddenException(L10n::t('Public access denied.'));
		}

		if (Config::get('system', 'local_search') && !Session::isAuthenticated()) {
			$e = new HTTPException\ForbiddenException(L10n::t('Only logged in users are permitted to perform a search.'));
			$e->httpdesc = L10n::t('Public access denied.');
			throw $e;
		}

		if (Config::get('system', 'permit_crawling') && !Session::isAuthenticated()) {
			// Default values:
			// 10 requests are "free", after the 11th only a call per minute is allowed

			$free_crawls = intval(Config::get('system', 'free_crawls'));
			if ($free_crawls == 0)
				$free_crawls = 10;

			$crawl_permit_period = intval(Config::get('system', 'crawl_permit_period'));
			if ($crawl_permit_period == 0)
				$crawl_permit_period = 10;

			$remote = $_SERVER['REMOTE_ADDR'];
			$result = DI::cache()->get('remote_search:' . $remote);
			if (!is_null($result)) {
				$resultdata = json_decode($result);
				if (($resultdata->time > (time() - $crawl_permit_period)) && ($resultdata->accesses > $free_crawls)) {
					throw new HTTPException\TooManyRequestsException(L10n::t('Only one search per minute is permitted for not logged in users.'));
				}
				DI::cache()->set('remote_search:' . $remote, json_encode(['time' => time(), 'accesses' => $resultdata->accesses + 1]), CacheClass::HOUR);
			} else {
				DI::cache()->set('remote_search:' . $remote, json_encode(['time' => time(), 'accesses' => 1]), CacheClass::HOUR);
			}
		}

		if (local_user()) {
			App::page()['aside'] .= Widget\SavedSearches::getHTML('search?q=' . urlencode($search), $search);
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
			'$title' => L10n::t('Search'),
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

		$tag = $tag || Config::get('system', 'only_tag_search');

		// Here is the way permissions work in the search module...
		// Only public posts can be shown
		// OR your own posts if you are a logged in member
		// No items will be shown if the member has a blocked profile wall.

		$pager = new Pager(App::args()->getQueryString());

		if ($tag) {
			Logger::info('Start tag search.', ['q' => $search]);

			$condition = [
				"(`uid` = 0 OR (`uid` = ? AND NOT `global`))
				AND `otype` = ? AND `type` = ? AND `term` = ?",
				local_user(), Term::OBJECT_TYPE_POST, Term::HASHTAG, $search
			];
			$params = [
				'order' => ['received' => true],
				'limit' => [$pager->getStart(), $pager->getItemsPerPage()]
			];
			$terms = DBA::select('term', ['oid'], $condition, $params);

			$itemids = [];
			while ($term = DBA::fetch($terms)) {
				$itemids[] = $term['oid'];
			}

			DBA::close($terms);

			if (!empty($itemids)) {
				$params = ['order' => ['id' => true]];
				$items = Item::selectForUser(local_user(), [], ['id' => $itemids], $params);
				$r = Item::inArray($items);
			} else {
				$r = [];
			}
		} else {
			Logger::info('Start fulltext search.', ['q' => $search]);

			$condition = [
				"(`uid` = 0 OR (`uid` = ? AND NOT `global`))
				AND `body` LIKE CONCAT('%',?,'%')",
				local_user(), $search
			];
			$params = [
				'order' => ['id' => true],
				'limit' => [$pager->getStart(), $pager->getItemsPerPage()]
			];
			$items = Item::selectForUser(local_user(), [], $condition, $params);
			$r = Item::inArray($items);
		}

		if (!DBA::isResult($r)) {
			info(L10n::t('No results.'));
			return $o;
		}

		if ($tag) {
			$title = L10n::t('Items tagged with: %s', $search);
		} else {
			$title = L10n::t('Results for: %s', $search);
		}

		$o .= Renderer::replaceMacros(Renderer::getMarkupTemplate('section_title.tpl'), [
			'$title' => $title
		]);

		Logger::info('Start Conversation.', ['q' => $search]);

		$o .= conversation(DI::app(), $r, $pager, 'search', false, false, 'commented', local_user());

		$o .= $pager->renderMinimal(count($r));

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
				$contact = Contact::selectFirst(['id' => 'cid'], ['addr' => $search, 'uid' => 0]);
			} else {
				$contact = Contact::getDetailsByURL($search, 0, ['cid' => 0]);
			}

			if (DBA::isResult($contact)) {
				$contact_id = $contact['cid'];
			}
		}

		if (!empty($contact_id)) {
			App::baseUrl()->redirect('contact/' . $contact_id);
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
			$item = Item::selectFirst(['guid'], ['id' => $item_id]);
			if (DBA::isResult($item)) {
				App::baseUrl()->redirect('display/' . $item['guid']);
			}
		}
	}
}
