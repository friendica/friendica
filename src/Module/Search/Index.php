<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Search;

use Friendica\App;
use Friendica\Content\Conversation;
use Friendica\Content\Nav;
use Friendica\Content\Pager;
use Friendica\Content\Text\HTML;
use Friendica\Content\Widget;
use Friendica\Core\Cache\Enum\Duration;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Core\Search;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Model\Tag;
use Friendica\Module\BaseSearch;
use Friendica\Module\Response;
use Friendica\Network\HTTPException;
use Friendica\Util\Network;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

class Index extends BaseSearch
{
	/** @var string  */
	private $remoteAddress;

	public function __construct(L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, App\Request $request, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->remoteAddress = $request->getRemoteAddress();
	}

	protected function content(array $request = []): string
	{
		$search = (!empty($_GET['q']) ? trim(rawurldecode($_GET['q'])) : '');

		if (DI::config()->get('system', 'block_public') && !DI::userSession()->isAuthenticated()) {
			throw new HTTPException\ForbiddenException(DI::l10n()->t('Public access denied.'));
		}

		if (DI::config()->get('system', 'local_search') && !DI::userSession()->isAuthenticated()) {
			throw new HTTPException\ForbiddenException(DI::l10n()->t('Only logged in users are permitted to perform a search.'));
		}

		if (DI::config()->get('system', 'permit_crawling') && !DI::userSession()->isAuthenticated()) {
			// Default values:
			// 10 requests are "free", after the 11th only a call per minute is allowed

			$free_crawls = intval(DI::config()->get('system', 'free_crawls'));
			if ($free_crawls == 0) {
				$free_crawls = 10;
			}

			$crawl_permit_period = intval(DI::config()->get('system', 'crawl_permit_period'));
			if ($crawl_permit_period == 0) {
				$crawl_permit_period = 10;
			}

			$remote = $this->remoteAddress;
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

		if (DI::userSession()->getLocalUserId()) {
			DI::page()['aside'] .= Widget\SavedSearches::getHTML(Search::getSearchPath($search), $search);
		}

		Nav::setSelected('search');

		$tag = false;
		if (!empty($_GET['tag'])) {
			$tag    = true;
			$search = '#' . trim(rawurldecode($_GET['tag']));
		}

		// construct a wrapper for the search header
		$o = Renderer::replaceMacros(Renderer::getMarkupTemplate('content_wrapper.tpl'), [
			'name'        => 'search-header',
			'$title'      => DI::l10n()->t('Search'),
			'$title_size' => 3,
			'$content'    => HTML::search($search, 'search-box', false)
		]);

		if (!$search) {
			return $o;
		}

		if (strpos($search, '#') === 0) {
			$tag    = true;
			$search = substr($search, 1);
		} else {
			if (strpos($search, '@') === 0 || strpos($search, '!') === 0) {
				return self::performContactSearch($search);
			}

			self::tryRedirectToPost($search);

			self::tryRedirectToProfile($search);

			if (!empty($_GET['search-option'])) {
				switch ($_GET['search-option']) {
					case 'fulltext':
						break;
					case 'tags':
						$tag = true;
						break;
					case 'contacts':
						return self::performContactSearch($search, '@');
					case 'groups':
						return self::performContactSearch($search, '!');
				}
			}
		}

		// Don't perform a fulltext or tag search on search results that look like an URL
		// Tags don't look like an URL and the fulltext search does only work with natural words
		if (parse_url($search, PHP_URL_SCHEME) && parse_url($search, PHP_URL_HOST)) {
			$this->logger->info('Skipping tag and fulltext search since the search looks like a URL.', ['q' => $search]);
			DI::sysmsg()->addNotice(DI::l10n()->t('No results.'));
			return $o;
		}

		$tag = $tag || DI::config()->get('system', 'only_tag_search');

		// Here is the way permissions work in the search module...
		// Only public posts can be shown
		// OR your own posts if you are a logged in member
		// No items will be shown if the member has a blocked profile wall.

		if (DI::mode()->isMobile()) {
			$itemsPerPage = DI::pConfig()->get(
				DI::userSession()->getLocalUserId(),
				'system',
				'itemspage_mobile_network',
				DI::config()->get('system', 'itemspage_network_mobile')
			);
		} else {
			$itemsPerPage = DI::pConfig()->get(
				DI::userSession()->getLocalUserId(),
				'system',
				'itemspage_network',
				DI::config()->get('system', 'itemspage_network')
			);
		}

		$last_uriid = isset($_GET['last_uriid']) ? intval($_GET['last_uriid']) : 0;

		$pager = new Pager(DI::l10n(), DI::args()->getQueryString(), $itemsPerPage);

		if ($tag) {
			$this->logger->info('Start tag search.', ['q' => $search, 'start' => $pager->getStart(), 'items' => $pager->getItemsPerPage(), 'last' => $last_uriid]);
			$uriids = Tag::getURIIdListByTag($search, DI::userSession()->getLocalUserId(), $pager->getStart(), $pager->getItemsPerPage(), $last_uriid);
			$count  = Tag::countByTag($search, DI::userSession()->getLocalUserId());
		} else {
			$this->logger->info('Start fulltext search.', ['q' => $search]);
			$uriids = Post\Content::getURIIdListBySearch($search, DI::userSession()->getLocalUserId(), $pager->getStart(), $pager->getItemsPerPage(), $last_uriid);
			$count  = Post\Content::countBySearch($search, DI::userSession()->getLocalUserId());
		}

		if (!empty($uriids)) {
			$condition = ["(`uid` = ? OR (`uid` = ? AND NOT `global`))", 0, DI::userSession()->getLocalUserId()];
			$condition = DBA::mergeConditions($condition, ['uri-id' => $uriids]);
			$params    = ['order' => ['uri-id' => true]];
			$items     = Post::toArray(Post::selectForUser(DI::userSession()->getLocalUserId(), Item::DISPLAY_FIELDLIST, $condition, $params));
		}

		if (empty($items)) {
			if (empty($last_uriid)) {
				DI::sysmsg()->addNotice(DI::l10n()->t('No results.'));
			}
			return $o;
		}

		if (DI::pConfig()->get(DI::userSession()->getLocalUserId(), 'system', 'infinite_scroll')) {
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

		$this->logger->info('Start Conversation.', ['q' => $search]);

		$o .= DI::conversation()->render($items, Conversation::MODE_SEARCH, false, false, 'commented', DI::userSession()->getLocalUserId());

		if (DI::pConfig()->get(DI::userSession()->getLocalUserId(), 'system', 'infinite_scroll')) {
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
		$search = Network::convertToIdn($search);
		$isUrl  = !empty(parse_url($search, PHP_URL_SCHEME));
		$isAddr = (bool)preg_match('/^@?([a-z0-9.-_]+@[a-z0-9.-_:]+)$/i', trim($search), $matches);

		if (!$isUrl && !$isAddr) {
			return;
		}

		if ($isAddr) {
			$search = $matches[1];
		}

		if (DI::userSession()->getLocalUserId()) {
			// User-specific contact URL/address search
			$contact_id = Contact::getIdForURL($search, DI::userSession()->getLocalUserId());
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
		if (!parse_url($search, PHP_URL_SCHEME) && !preg_match('=^[a-z]+://=', $search)) {
			return;
		}

		$search = Network::convertToIdn($search);

		if (DI::userSession()->getLocalUserId()) {
			// Post URL search
			$item_id = Item::fetchByLink($search, DI::userSession()->getLocalUserId());
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
