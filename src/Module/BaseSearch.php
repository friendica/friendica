<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module;

use Friendica\BaseModule;
use Friendica\Content\Pager;
use Friendica\Core\Renderer;
use Friendica\Core\Search;
use Friendica\DI;
use Friendica\Model;
use Friendica\Network\HTTPException;
use Friendica\Object\Search\ContactResult;
use Friendica\Object\Search\ResultList;
use Friendica\Util\Network;

/**
 * Base class for search modules
 */
class BaseSearch extends BaseModule
{
	/**
	 * Performs a contact search with an optional prefix
	 *
	 * @param string $search Search query
	 * @param string $prefix A optional prefix (e.g. @ or !) for searching
	 *
	 * @return string
	 * @throws HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function performContactSearch(string $search, string $prefix = ''): string
	{
		$config = DI::config();

		$type = Search::TYPE_ALL;

		$localSearch = $config->get('system', 'poco_local_search');

		$search = $prefix . $search;

		if (!$search) {
			return '';
		}

		$header  = '';
		$results = new ResultList();

		if (strpos($search, '@') === 0) {
			$search = trim(substr($search, 1));
			$type   = Search::TYPE_PEOPLE;
			$header = DI::l10n()->t('People Search - %s', $search);
		} elseif (strpos($search, '!') === 0) {
			$search = trim(substr($search, 1));
			$type   = Search::TYPE_GROUP;
			$header = DI::l10n()->t('Group Search - %s', $search);
		}

		$search = Network::convertToIdn($search);

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

		$pager = new Pager(DI::l10n(), DI::args()->getQueryString(), $itemsPerPage);

		if (!$results->getTotal() && !$localSearch && Search::getGlobalDirectory()) {
			$results = Search::getContactsFromGlobalDirectory($search, $type, $pager->getPage());
			$pager->setItemsPerPage($results->getItemsPage());
		}

		if (!$results->getTotal()) {
			$pager->setItemsPerPage(80);
			$results = Search::getContactsFromLocalDirectory($search, $type, $pager->getStart(), $pager->getItemsPerPage());
		}

		if (!$results->getTotal()) {
			$results = Search::getContactsFromProbe(Network::convertToIdn($search), $type == Search::TYPE_GROUP);
		}

		return self::printResult($results, $pager, $header);
	}

	/**
	 * Prints a human readable search result
	 *
	 * @param ResultList $results
	 * @param Pager      $pager
	 * @param string     $header
	 *
	 * @return string The result
	 * @throws HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	protected static function printResult(ResultList $results, Pager $pager, string $header = ''): string
	{
		if ($results->getTotal() == 0) {
			DI::sysmsg()->addNotice(DI::l10n()->t('No matches'));
			return '';
		}

		$filtered = 0;

		$entries = [];
		foreach ($results->getResults() as $result) {
			// in case the result is a contact result, add a contact-specific entry
			if ($result instanceof ContactResult) {
				if (Network::isUriBlocked($result->getUrl())) {
					$filtered++;
					continue;
				}

				$contact = Model\Contact::getByURLForUser($result->getUrl(), DI::userSession()->getLocalUserId());
				if (!empty($contact)) {
					$entries[] = Contact::getContactTemplateVars($contact);
				}
			}
		}

		$tpl = Renderer::getMarkupTemplate('contact/list.tpl');
		return Renderer::replaceMacros($tpl, [
			'$title'    => $header,
			'$filtered' => $filtered ? DI::l10n()->tt(
				'%d result was filtered out because your node blocks the domain it is registered on. You can review the list of domains your node is currently blocking in the <a href="/friendica">About page</a>.',
				'%d results were filtered out because your node blocks the domain they are registered on. You can review the list of domains your node is currently blocking in the <a href="/friendica">About page</a>.',
				$filtered
			) : '',
			'$contacts' => $entries,
			'$paginate' => $pager->renderFull($results->getTotal()),
		]);
	}
}
