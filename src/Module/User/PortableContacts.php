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
 * @see https://web.archive.org/web/20160405005550/http://portablecontacts.net/draft-spec.html
 */

namespace Friendica\Module\User;

use Friendica\App;
use Friendica\BaseModule;
use Friendica\Content\Text\BBCode;
use Friendica\Core\Cache\Capability\ICanCache;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\L10n;
use Friendica\Core\Protocol;
use Friendica\Core\System;
use Friendica\Database\Database;
use Friendica\Module\Response;
use Friendica\Network\HTTPException;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

/**
 * Minimal implementation of the Portable Contacts protocol
 * @see https://portablecontacts.github.io
 */
class PortableContacts extends BaseModule
{
	/** @var IManageConfigValues */
	private $config;
	/** @var Database */
	private $database;
	/** @var ICanCache */
	private $cache;

	public function __construct(ICanCache $cache, Database $database, IManageConfigValues $config, L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->config   = $config;
		$this->database = $database;
		$this->cache    = $cache;
	}

	protected function rawContent(array $request = [])
	{
		if ($this->config->get('system', 'block_public') || $this->config->get('system', 'block_local_dir')) {
			throw new HTTPException\ForbiddenException();
		}

		$format = $request['format'] ?? 'json';
		if ($format !== 'json') {
			throw new HTTPException\UnsupportedMediaTypeException();
		}

		$totalResults = $this->database->count('profile', ['net-publish' => true]);
		if (!$totalResults) {
			throw new HTTPException\ForbiddenException();
		}

		if (!empty($request['startIndex']) && is_numeric($request['startIndex'])) {
			$startIndex = intval($request['startIndex']);
		} else {
			$startIndex = 0;
		}

		$itemsPerPage = !empty($request['count']) && is_numeric($request['count']) ? intval($request['count']) : $totalResults;

		$this->logger->info('Start system mode query');
		$contacts = $this->database->selectToArray('owner-view', [], ['net-publish' => true], ['limit' => [$startIndex, $itemsPerPage]]);
		$this->logger->info('Query done');

		$return = [];
		if (!empty($request['sorted'])) {
			$return['sorted'] = false;
		}

		if (!empty($request['filtered'])) {
			$return['filtered'] = false;
		}

		if (!empty($request['updatedSince'])) {
			$return['updatedSince'] = false;
		}

		$return['startIndex']   = $startIndex;
		$return['itemsPerPage'] = $itemsPerPage;
		$return['totalResults'] = $totalResults;

		$return['entry'] = [];

		$selectedFields = [
			'id'                => false,
			'displayName'       => false,
			'urls'              => false,
			'updated'           => false,
			'preferredUsername' => false,
			'photos'            => false,
			'aboutMe'           => false,
			'currentLocation'   => false,
			'network'           => false,
			'tags'              => false,
			'address'           => false,
			'contactType'       => false,
			'generation'        => false
		];

		if (empty($request['fields']) || $request['fields'] == '@all') {
			foreach ($selectedFields as $k => $v) {
				$selectedFields[$k] = true;
			}
		} else {
			$fields_req = explode(',', $request['fields']);
			foreach ($fields_req as $f) {
				$selectedFields[trim($f)] = true;
			}
		}

		if (!$contacts) {
			$return['entry'][] = [];
		}

		foreach ($contacts as $contact) {
			if (!isset($contact['updated'])) {
				$contact['updated'] = '';
			}

			if (!isset($contact['generation'])) {
				$contact['generation'] = 1;
			}

			if (empty($contact['keywords']) && isset($contact['pub_keywords'])) {
				$contact['keywords'] = $contact['pub_keywords'];
			}

			if (isset($contact['account-type'])) {
				$contact['contact-type'] = $contact['account-type'];
			}

			$cacheKey = 'about:' . $contact['nick'] . ':' . DateTimeFormat::utc($contact['updated'], DateTimeFormat::ATOM);
			$about    = $this->cache->get($cacheKey);
			if (is_null($about)) {
				$about = BBCode::convertForUriId($contact['uri-id'], $contact['about']);
				$this->cache->set($cacheKey, $about);
			}

			// Non connected persons can only see the keywords of a Diaspora account
			if ($contact['network'] == Protocol::DIASPORA) {
				$contact['location'] = '';
				$about               = '';
			}

			$entry = [];
			if ($selectedFields['id']) {
				$entry['id'] = (int)$contact['id'];
			}

			if ($selectedFields['displayName']) {
				$entry['displayName'] = $contact['name'];
			}

			if ($selectedFields['aboutMe']) {
				$entry['aboutMe'] = $about;
			}

			if ($selectedFields['currentLocation']) {
				$entry['currentLocation'] = $contact['location'];
			}

			if ($selectedFields['generation']) {
				$entry['generation'] = (int)$contact['generation'];
			}

			if ($selectedFields['urls']) {
				$entry['urls'] = [['value' => $contact['url'], 'type' => 'profile']];
				if ($contact['addr'] && ($contact['network'] !== Protocol::MAIL)) {
					$entry['urls'][] = ['value' => 'acct:' . $contact['addr'], 'type' => 'webfinger'];
				}
			}

			if ($selectedFields['preferredUsername']) {
				$entry['preferredUsername'] = $contact['nick'];
			}

			if ($selectedFields['updated']) {
				$entry['updated'] = $contact['success_update'];

				if ($contact['name-date'] > $entry['updated']) {
					$entry['updated'] = $contact['name-date'];
				}

				if ($contact['uri-date'] > $entry['updated']) {
					$entry['updated'] = $contact['uri-date'];
				}

				if ($contact['avatar-date'] > $entry['updated']) {
					$entry['updated'] = $contact['avatar-date'];
				}

				$entry['updated'] = date('c', strtotime($entry['updated']));
			}

			if ($selectedFields['photos']) {
				$entry['photos'] = [['value' => $contact['photo'], 'type' => 'profile']];
			}

			if ($selectedFields['network']) {
				$entry['network'] = $contact['network'];
				if (($entry['network'] == '') && ($contact['self'])) {
					$entry['network'] = Protocol::DFRN;
				}
			}

			if ($selectedFields['tags']) {
				$tags = str_replace(',', ' ', $contact['keywords'] ?? '');
				$tags = explode(' ', $tags);

				$cleaned = [];
				foreach ($tags as $tag) {
					$tag = trim(strtolower($tag));
					if ($tag != '') {
						$cleaned[] = $tag;
					}
				}

				$entry['tags'] = [$cleaned];
			}

			if ($selectedFields['address']) {
				$entry['address'] = [];

				if (isset($contact['locality'])) {
					$entry['address']['locality'] = $contact['locality'];
				}

				if (isset($contact['region'])) {
					$entry['address']['region'] = $contact['region'];
				}

				if (isset($contact['country'])) {
					$entry['address']['country'] = $contact['country'];
				}
			}

			if ($selectedFields['contactType']) {
				$entry['contactType'] = intval($contact['contact-type']);
			}

			$return['entry'][] = $entry;
		}

		$this->logger->info('End of poco');

		System::jsonExit($return);
	}
}
