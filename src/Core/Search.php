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

namespace Friendica\Core;

use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Network\HTTPClient\Client\HttpClientAccept;
use Friendica\Network\HTTPException;
use Friendica\Object\Search\ContactResult;
use Friendica\Object\Search\ResultList;
use Friendica\Util\Network;
use Friendica\Util\Strings;

/**
 * Specific class to perform searches for different systems. Currently:
 * - Probe for contacts
 * - Search in the local directory
 * - Search in the global directory
 */
class Search
{
	const DEFAULT_DIRECTORY = 'https://dir.friendica.social';

	const TYPE_PEOPLE = 0;
	const TYPE_FORUM  = 1;
	const TYPE_ALL    = 2;

	/**
	 * Search a user based on his/her profile address
	 * pattern: @username@domain.tld
	 *
	 * @param string $user The user to search for
	 *
	 * @return ResultList
	 * @throws HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function getContactsFromProbe(string $user): ResultList
	{
		$emptyResultList = new ResultList(1, 0, 1);

		if ((filter_var($user, FILTER_VALIDATE_EMAIL) && Network::isEmailDomainValid($user)) ||
		    (substr(Strings::normaliseLink($user), 0, 7) == 'http://')) {

			$user_data = Contact::getByURL($user);
			if (empty($user_data)) {
				return $emptyResultList;
			}

			if (!in_array($user_data['network'], Protocol::FEDERATED)) {
				return $emptyResultList;
			}

			$contactDetails = Contact::getByURLForUser($user_data['url'] ?? '', DI::userSession()->getLocalUserId());

			$result = new ContactResult(
				$user_data['name'] ?? '',
				$user_data['addr'] ?? '',
				($contactDetails['addr'] ?? '') ?: ($user_data['url'] ?? ''),
				$user_data['url'] ?? '',
				$user_data['photo'] ?? '',
				$user_data['network'] ?? '',
				$contactDetails['id'] ?? 0,
				$user_data['id'] ?? 0,
				$user_data['tags'] ?? ''
			);

			return new ResultList(1, 1, 1, [$result]);
		} else {
			return $emptyResultList;
		}
	}

	/**
	 * Search in the global directory for occurrences of the search string
	 *
	 * @see https://github.com/friendica/friendica-directory/blob/stable/docs/Protocol.md#search
	 *
	 * @param string $search
	 * @param int    $type specific type of searching
	 * @param int    $page
	 *
	 * @return ResultList
	 * @throws HTTPException\InternalServerErrorException
	 */
	public static function getContactsFromGlobalDirectory(string $search, int $type = self::TYPE_ALL, int $page = 1): ResultList
	{
		$server = self::getGlobalDirectory();

		$searchUrl = $server . '/search';

		switch ($type) {
			case self::TYPE_FORUM:
				$searchUrl .= '/forum';
				break;
			case self::TYPE_PEOPLE:
				$searchUrl .= '/people';
				break;
		}
		$searchUrl .= '?q=' . urlencode($search);

		if ($page > 1) {
			$searchUrl .= '&page=' . $page;
		}

		$resultJson = DI::httpClient()->fetch($searchUrl, HttpClientAccept::JSON);

		$results = json_decode($resultJson, true);

		$resultList = new ResultList(
			($results['page']         ?? 0) ?: 1,
			 $results['count']        ?? 0,
			($results['itemsperpage'] ?? 0) ?: 30
		);

		$profiles = $results['profiles'] ?? [];

		foreach ($profiles as $profile) {
			$profile_url = $profile['profile_url'] ?? '';
			$contactDetails = Contact::getByURLForUser($profile_url, DI::userSession()->getLocalUserId());

			$result = new ContactResult(
				$profile['name'] ?? '',
				$profile['addr'] ?? '',
				($contactDetails['addr'] ?? '') ?: $profile_url,
				$profile_url,
				$profile['photo'] ?? '',
				Protocol::DFRN,
				$contactDetails['cid'] ?? 0,
				0,
				$profile['tags'] ?? ''
			);

			$resultList->addResult($result);
		}

		return $resultList;
	}

	/**
	 * Search in the local database for occurrences of the search string
	 *
	 * @param string $search
	 * @param int    $type
	 * @param int    $start
	 * @param int    $itemPage
	 *
	 * @return ResultList
	 * @throws HTTPException\InternalServerErrorException
	 */
	public static function getContactsFromLocalDirectory(string $search, int $type = self::TYPE_ALL, int $start = 0, int $itemPage = 80): ResultList
	{
		Logger::info('Searching', ['search' => $search, 'type' => $type, 'start' => $start, 'itempage' => $itemPage]);

		$contacts = Contact::searchByName($search, $type == self::TYPE_FORUM ? 'community' : '');

		$resultList = new ResultList($start, $itemPage, count($contacts));

		foreach ($contacts as $contact) {
			$result = new ContactResult(
				$contact['name'],
				$contact['addr'],
				$contact['addr'],
				$contact['url'],
				$contact['photo'],
				$contact['network'],
				$contact['cid'] ?? 0,
				$contact['zid'] ?? 0,
				$contact['keywords']
			);

			$resultList->addResult($result);
		}

		// Add found profiles from the global directory to the local directory
		Worker::add(Worker::PRIORITY_LOW, 'SearchDirectory', $search);

		return $resultList;
	}

	/**
	 * Searching for contacts for autocompletion
	 *
	 * @param string $search Name or part of a name or nick
	 * @param string $mode   Search mode (e.g. "community")
	 * @param int    $page   Page number (starts at 1)
	 *
	 * @return array with the search results or empty if error or nothing found
	 * @throws HTTPException\InternalServerErrorException
	 */
	public static function searchContact(string $search, string $mode, int $page = 1): array
	{
		Logger::info('Searching', ['search' => $search, 'mode' => $mode, 'page' => $page]);

		if (DI::config()->get('system', 'block_public') && !DI::userSession()->isAuthenticated()) {
			return [];
		}

		// don't search if search term has less than 2 characters
		if (!$search || mb_strlen($search) < 2) {
			return [];
		}

		if (substr($search, 0, 1) === '@') {
			$search = substr($search, 1);
		}

		// check if searching in the local global contact table is enabled
		if (DI::config()->get('system', 'poco_local_search')) {
			$return = Contact::searchByName($search, $mode);
		} else {
			$p = $page > 1 ? 'p=' . $page : '';
			$curlResult = DI::httpClient()->get(self::getGlobalDirectory() . '/search/people?' . $p . '&q=' . urlencode($search), HttpClientAccept::JSON);
			if ($curlResult->isSuccess()) {
				$searchResult = json_decode($curlResult->getBody(), true);
				if (!empty($searchResult['profiles'])) {
					// Converting Directory Search results into contact-looking records
					$return = array_map(function ($result) {
						static $contactType = [
							'People'       => Contact::TYPE_PERSON,
							'Forum'        => Contact::TYPE_COMMUNITY,
							'Organization' => Contact::TYPE_ORGANISATION,
							'News'         => Contact::TYPE_NEWS,
						];

						return [
							'name'         => $result['name'],
							'addr'         => $result['addr'],
							'url'          => $result['profile_url'],
							'network'      => Protocol::DFRN,
							'micro'        => $result['photo'],
							'contact-type' => $contactType[$result['account_type']],
						];
					}, $searchResult['profiles']);
				}
			}
		}

		return $return ?? [];
	}

	/**
	 * Returns the global directory name, used in this node
	 *
	 * @return string
	 */
	public static function getGlobalDirectory(): string
	{
		return DI::config()->get('system', 'directory', self::DEFAULT_DIRECTORY);
	}

	/**
	 * Return the search path (either fulltext search or tag search)
	 *
	 * @param string $search
	 *
	 * @return string search path
	 */
	public static function getSearchPath(string $search): string
	{
		if (substr($search, 0, 1) == '#') {
			return 'search?tag=' . urlencode(substr($search, 1));
		} else {
			return 'search?q=' . urlencode($search);
		}
	}
}
