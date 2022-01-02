<?php
/**
 * @copyright Copyright (C) 2010-2022, the Friendica project
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

namespace Friendica\Module\Api\Mastodon\Accounts;

use Friendica\Core\Search as CoreSearch;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Module\BaseApi;
use Friendica\Object\Search\ContactResult;

/**
 * @see https://docs.joinmastodon.org/methods/accounts/
 */
class Search extends BaseApi
{
	/**
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	protected function rawContent(array $request = [])
	{
		self::checkAllowedScope(self::SCOPE_READ);
		$uid = self::getCurrentUserID();

		$request = $this->getRequest([
			'q'         => '',    // What to search for
			'limit'     => 40,    // Maximum number of results. Defaults to 40.
			'resolve'   => false, // Attempt WebFinger lookup. Defaults to false. Use this when q is an exact address.
			'following' => false, // Only who the user is following. Defaults to false.
		], $request);

		$accounts = [];

		if (!$request['following']) {
			if ((strrpos($request['q'], '@') > 0) && $request['resolve']) {
				$results = CoreSearch::getContactsFromProbe($request['q']);
			}

			if (empty($results)) {
				if (DI::config()->get('system', 'poco_local_search')) {
					$results = CoreSearch::getContactsFromLocalDirectory($request['q'], CoreSearch::TYPE_ALL, 0, $request['limit']);
				} elseif (!empty(DI::config()->get('system', 'directory'))) {
					$results = CoreSearch::getContactsFromGlobalDirectory($request['q'], CoreSearch::TYPE_ALL, 1);
				}
			}

			if (!empty($results)) {
				$counter = 0;
				foreach ($results->getResults() as $result) {
					if (++$counter > $request['limit']) {
						continue;
					}
					if ($result instanceof ContactResult) {
						$id = Contact::getIdForURL($result->getUrl(), 0, false);

						$accounts[] = DI::mstdnAccount()->createFromContactId($id, $uid);
					}
				}
			}
		} else {
			$contacts = Contact::searchByName($request['q'], '', $uid);

			$counter = 0;
			foreach ($contacts as $contact) {
				if (!in_array($contact['rel'], [Contact::SHARING, Contact::FRIEND])) {
					continue;
				}
				if (++$counter > $request['limit']) {
					continue;
				}
				$accounts[] = DI::mstdnAccount()->createFromContactId($contact['id'], $uid);
			}
			DBA::close($contacts);
		}

		System::jsonExit($accounts);
	}
}
