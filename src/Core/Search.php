<?php

namespace Friendica\Core;

use Friendica\Database\DBA;
use Friendica\Model\Contact;
use Friendica\Model\GContact;
use Friendica\Network\HTTPException;
use Friendica\Network\Probe;
use Friendica\Object\Search\ContactResult;
use Friendica\Object\Search\ResultList;
use Friendica\Registry\Core;
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
	public static function getContactsFromProbe($user)
	{
		$emptyResultList = new ResultList(1, 0, 1);

		if ((filter_var($user, FILTER_VALIDATE_EMAIL) && Network::isEmailDomainValid($user)) ||
		    (substr(Strings::normaliseLink($user), 0, 7) == "http://")) {

			/// @todo Possibly use "getIdForURL" instead?
			$user_data = Probe::uri($user);
			if (empty($user_data)) {
				return $emptyResultList;
			}

			if (!in_array($user_data["network"], Protocol::FEDERATED)) {
				return $emptyResultList;
			}

			// Ensure that we do have a contact entry
			Contact::getIdForURL($user_data['url'] ?? '');

			$contactDetails = Contact::getDetailsByURL($user_data['url'] ?? '', local_user());

			$result = new ContactResult(
				$user_data['name'] ?? '',
				$user_data['addr'] ?? '',
				($contactDetails['addr'] ?? '') ?: ($user_data['url'] ?? ''),
				$user_data['url'] ?? '',
				$user_data['photo'] ?? '',
				$user_data['network'] ?? '',
				$contactDetails['id'] ?? 0,
				0,
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
	 * @see https://github.com/friendica/friendica-directory/blob/master/docs/Protocol.md#search
	 *
	 * @param string $search
	 * @param int    $type specific type of searching
	 * @param int    $page
	 *
	 * @return ResultList
	 * @throws HTTPException\InternalServerErrorException
	 */
	public static function getContactsFromGlobalDirectory($search, $type = self::TYPE_ALL, $page = 1)
	{
		$server = Core::config()->get('system', 'directory', self::DEFAULT_DIRECTORY);

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

		$resultJson = Network::fetchUrl($searchUrl, false, 0, 'application/json');

		$results = json_decode($resultJson, true);

		$resultList = new ResultList(
			($results['page']         ?? 0) ?: 1,
			 $results['count']        ?? 0,
			($results['itemsperpage'] ?? 0) ?: 30
		);

		$profiles = $results['profiles'] ?? [];

		foreach ($profiles as $profile) {
			$profile_url = $profile['profile_url'] ?? '';
			$contactDetails = Contact::getDetailsByURL($profile_url, local_user());

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
	public static function getContactsFromLocalDirectory($search, $type = self::TYPE_ALL, $start = 0, $itemPage = 80)
	{
		$config = Core::config();

		$diaspora = $config->get('system', 'diaspora_enabled') ? Protocol::DIASPORA : Protocol::DFRN;
		$ostatus  = !$config->get('system', 'ostatus_disabled') ? Protocol::OSTATUS : Protocol::DFRN;

		$wildcard = Strings::escapeHtml('%' . $search . '%');

		$count = DBA::count('gcontact', [
			'NOT `hide`
			AND `network` IN (?, ?, ?, ?)
			AND ((`last_contact` >= `last_failure`) OR (`updated` >= `last_failure`))
			AND (`url` LIKE ? OR `name` LIKE ? OR `location` LIKE ? 
				OR `addr` LIKE ? OR `about` LIKE ? OR `keywords` LIKE ?)
			AND `community` = ?',
			Protocol::ACTIVITYPUB, Protocol::DFRN, $ostatus, $diaspora,
			$wildcard, $wildcard, $wildcard,
			$wildcard, $wildcard, $wildcard,
			($type === self::TYPE_FORUM),
		]);

		$resultList = new ResultList($start, $itemPage, $count);

		if (empty($count)) {
			return $resultList;
		}

		$data = DBA::select('gcontact', ['nurl'], [
			'NOT `hide`
			AND `network` IN (?, ?, ?, ?)
			AND ((`last_contact` >= `last_failure`) OR (`updated` >= `last_failure`))
			AND (`url` LIKE ? OR `name` LIKE ? OR `location` LIKE ? 
				OR `addr` LIKE ? OR `about` LIKE ? OR `keywords` LIKE ?)
			AND `community` = ?',
			Protocol::ACTIVITYPUB, Protocol::DFRN, $ostatus, $diaspora,
			$wildcard, $wildcard, $wildcard,
			$wildcard, $wildcard, $wildcard,
			($type === self::TYPE_FORUM),
		], [
			'group_by' => ['nurl', 'updated'],
			'limit'    => [$start, $itemPage],
			'order'    => ['updated' => 'DESC']
		]);

		if (!DBA::isResult($data)) {
			return $resultList;
		}

		while ($row = DBA::fetch($data)) {
			$urlParts = parse_url($row["nurl"]);

			// Ignore results that look strange.
			// For historic reasons the gcontact table does contain some garbage.
			if (!empty($urlParts['query']) || !empty($urlParts['fragment'])) {
				continue;
			}

			$contact = Contact::getDetailsByURL($row["nurl"], local_user());

			if ($contact["name"] == "") {
				$contact["name"] = end(explode("/", $urlParts["path"]));
			}

			$result = new ContactResult(
				$contact["name"],
				$contact["addr"],
				$contact["addr"],
				$contact["url"],
				$contact["photo"],
				$contact["network"],
				$contact["cid"],
				$contact["zid"],
				$contact["keywords"]
			);

			$resultList->addResult($result);
		}

		DBA::close($data);

		// Add found profiles from the global directory to the local directory
		Worker::add(PRIORITY_LOW, 'SearchDirectory', $search);

		return $resultList;
	}

	/**
	 * Searching for global contacts for autocompletion
	 *
	 * @brief Searching for global contacts for autocompletion
	 * @param string $search Name or part of a name or nick
	 * @param string $mode   Search mode (e.g. "community")
	 * @param int    $page   Page number (starts at 1)
	 * @return array with the search results
	 * @throws HTTPException\InternalServerErrorException
	 */
	public static function searchGlobalContact($search, $mode, int $page = 1)
	{
		if (Config::get('system', 'block_public') && !Session::isAuthenticated()) {
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
		if (Config::get('system', 'poco_local_search')) {
			$return = GContact::searchByName($search, $mode);
		} else {
			$p = $page > 1 ? 'p=' . $page : '';
			$curlResult = Network::curl(self::getGlobalDirectory() . '/search/people?' . $p . '&q=' . urlencode($search), false, ['accept_content' => 'application/json']);
			if ($curlResult->isSuccess()) {
				$searchResult = json_decode($curlResult->getBody(), true);
				if (!empty($searchResult['profiles'])) {
					$return = $searchResult['profiles'];
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
	public static function getGlobalDirectory()
	{
		return Config::get('system', 'directory', self::DEFAULT_DIRECTORY);
	}
}
