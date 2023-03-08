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

		$header = '';

		if (strpos($search, '@') === 0) {
			$search  = trim(substr($search, 1));
			$type    = Search::TYPE_PEOPLE;
			$header  = DI::l10n()->t('People Search - %s', $search);

			if (strrpos($search, '@') > 0) {
				$results = Search::getContactsFromProbe(Network::convertToIdn($search));
			}
		}

		if (strpos($search, '!') === 0) {
			$search = trim(substr($search, 1));
			$type   = Search::TYPE_FORUM;
			$header = DI::l10n()->t('Forum Search - %s', $search);
		}

		$search = Network::convertToIdn($search);

		if (DI::mode()->isMobile()) {
			$itemsPerPage = DI::pConfig()->get(DI::userSession()->getLocalUserId(), 'system', 'itemspage_mobile_network',
				DI::config()->get('system', 'itemspage_network_mobile'));
		} else {
			$itemsPerPage = DI::pConfig()->get(DI::userSession()->getLocalUserId(), 'system', 'itemspage_network',
				DI::config()->get('system', 'itemspage_network'));
		}

		$pager = new Pager(DI::l10n(), DI::args()->getQueryString(), $itemsPerPage);

		if ($localSearch && empty($results)) {
			$pager->setItemsPerPage(80);
			$results = Search::getContactsFromLocalDirectory($search, $type, $pager->getStart(), $pager->getItemsPerPage());
		} elseif (Search::getGlobalDirectory() && empty($results)) {
			$results = Search::getContactsFromGlobalDirectory($search, $type, $pager->getPage());
			$pager->setItemsPerPage($results->getItemsPage());
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

		$entries = [];
		foreach ($results->getResults() as $result) {

			// in case the result is a contact result, add a contact-specific entry
			if ($result instanceof ContactResult) {
				$contact = Model\Contact::getByURLForUser($result->getUrl(), DI::userSession()->getLocalUserId());
				if (!empty($contact)) {
					$entries[] = Contact::getContactTemplateVars($contact);
				}
			}
		}

		$tpl = Renderer::getMarkupTemplate('contact/list.tpl');
		return Renderer::replaceMacros($tpl, [
			'title'     => $header,
			'$contacts' => $entries,
			'$paginate' => $pager->renderFull($results->getTotal()),
		]);
	}
}
