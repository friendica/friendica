<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Core;

use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Network\HTTPClient\Client\HttpClientAccept;
use Friendica\Network\HTTPClient\Client\HttpClientOptions;
use Friendica\Network\HTTPClient\Client\HttpClientRequest;
use Friendica\Network\HTTPException;
use Friendica\Object\Search\ContactResult;
use Friendica\Object\Search\ResultList;
use Friendica\Util\Network;
use GuzzleHttp\Psr7\Uri;

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
	const TYPE_GROUP  = 1;
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
	public static function getContactsFromProbe(string $user, $only_group = false): ResultList
	{
		$emptyResultList = new ResultList();

		if (empty(parse_url($user, PHP_URL_SCHEME)) && !(filter_var($user, FILTER_VALIDATE_EMAIL) || Network::isEmailDomainValid($user))) {
			return $emptyResultList;
		}

		$user_data = Contact::getByURL($user);
		if (empty($user_data)) {
			return $emptyResultList;
		}

		if ($only_group && ($user_data['contact-type'] != Contact::TYPE_COMMUNITY)) {
			return $emptyResultList;
		}

		if (!Protocol::supportsProbe($user_data['network'])) {
			return $emptyResultList;
		}

		$contactDetails = Contact::getByURLForUser($user_data['url'], DI::userSession()->getLocalUserId());

		$result = new ContactResult(
			$user_data['name'],
			$user_data['addr'],
			$user_data['addr'] ?: $user_data['url'],
			new Uri($user_data['url']),
			$user_data['photo'],
			$user_data['network'],
			$contactDetails['cid'] ?? 0,
			$user_data['id'],
			$user_data['keywords']
		);

		return new ResultList(1, 1, 1, [$result]);
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
			case self::TYPE_GROUP:
				$searchUrl .= '/group';
				break;
			case self::TYPE_PEOPLE:
				$searchUrl .= '/people';
				break;
		}
		$searchUrl .= '?q=' . urlencode($search);

		if ($page > 1) {
			$searchUrl .= '&page=' . $page;
		}

		$resultJson = DI::httpClient()->fetch($searchUrl, HttpClientAccept::JSON, 0, '', HttpClientRequest::CONTACTDISCOVER);

		$results = json_decode($resultJson, true);

		$resultList = new ResultList(
			($results['page'] ?? 0) ?: 1,
			$results['count'] ?? 0,
			($results['itemsperpage'] ?? 0) ?: 30
		);

		$profiles = $results['profiles'] ?? [];

		foreach ($profiles as $profile) {
			$profile_url    = $profile['profile_url'] ?? '';
			$contactDetails = Contact::getByURLForUser($profile_url, DI::userSession()->getLocalUserId());

			$result = new ContactResult(
				$profile['name'] ?? '',
				$profile['addr'] ?? '',
				($contactDetails['addr'] ?? '') ?: $profile_url,
				new Uri($profile_url),
				$profile['photo'] ?? '',
				Protocol::DFRN,
				$contactDetails['cid'] ?? 0,
				$contactDetails['zid'] ?? 0,
				$profile['tags']       ?? ''
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
		DI::logger()->info('Searching', ['search' => $search, 'type' => $type, 'start' => $start, 'itempage' => $itemPage]);

		$contacts = Contact::searchByName($search, $type == self::TYPE_GROUP ? 'community' : '', true);

		$resultList = new ResultList($start, count($contacts), $itemPage);

		foreach ($contacts as $contact) {
			$result = new ContactResult(
				$contact['name'],
				$contact['addr'],
				$contact['addr'] ?: $contact['url'],
				new Uri($contact['url']),
				$contact['photo'],
				$contact['network'],
				0,
				$contact['pid'],
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
		DI::logger()->info('Searching', ['search' => $search, 'mode' => $mode, 'page' => $page]);

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
			$return = Contact::searchByName($search, $mode, true);
		} else {
			$p = $page > 1 ? 'p=' . $page : '';
			try {
				$curlResult = DI::httpClient()->get(self::getGlobalDirectory() . '/search/people?' . $p . '&q=' . urlencode($search), HttpClientAccept::JSON, [HttpClientOptions::REQUEST => HttpClientRequest::CONTACTDISCOVER]);
			} catch (\Throwable $th) {
				DI::logger()->notice('Got exception', ['code' => $th->getCode(), 'message' => $th->getMessage()]);
				return [];
			}
			if ($curlResult->isSuccess()) {
				$searchResult = json_decode($curlResult->getBodyString(), true);
				if (!empty($searchResult['profiles'])) {
					// Converting Directory Search results into contact-looking records
					$return = array_map(function ($result) {
						static $contactType = [
							'People' => Contact::TYPE_PERSON,
							// Kept for backward compatibility
							'Forum'        => Contact::TYPE_COMMUNITY,
							'Group'        => Contact::TYPE_COMMUNITY,
							'Organization' => Contact::TYPE_ORGANISATION,
							'News'         => Contact::TYPE_NEWS,
							'Relay'        => Contact::TYPE_RELAY,
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
