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
 * @see https://web.archive.org/web/20160405005550/http://portablecontacts.net/draft-spec.html
 */

use Friendica\App;
use Friendica\Content\Text\BBCode;
use Friendica\Core\Logger;
use Friendica\Core\Protocol;
use Friendica\Core\Renderer;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Util\Strings;
use Friendica\Util\XML;

function poco_init(App $a) {
	if (intval(DI::config()->get('system', 'block_public')) || (DI::config()->get('system', 'block_local_dir'))) {
		throw new \Friendica\Network\HTTPException\ForbiddenException();
	}

	if (DI::args()->getArgc() > 1) {
		// Only the system mode is supported 
		throw new \Friendica\Network\HTTPException\NotFoundException();
	}

	$format = ($_GET['format'] ?? '') ?: 'json';

	$totalResults = DBA::count('profile', ['net-publish' => true]);
	if ($totalResults == 0) {
		throw new \Friendica\Network\HTTPException\ForbiddenException();
	}

	if (!empty($_GET['startIndex'])) {
		$startIndex = intval($_GET['startIndex']);
	} else {
		$startIndex = 0;
	}
	$itemsPerPage = (!empty($_GET['count']) ? intval($_GET['count']) : $totalResults);

	Logger::info("Start system mode query");
	$contacts = DBA::selectToArray('owner-view', [], ['net-publish' => true], ['limit' => [$startIndex, $itemsPerPage]]);

	Logger::info("Query done");

	$ret = [];
	if (!empty($_GET['sorted'])) {
		$ret['sorted'] = false;
	}
	if (!empty($_GET['filtered'])) {
		$ret['filtered'] = false;
	}
	if (!empty($_GET['updatedSince'])) {
		$ret['updatedSince'] = false;
	}
	$ret['startIndex']   = (int) $startIndex;
	$ret['itemsPerPage'] = (int) $itemsPerPage;
	$ret['totalResults'] = (int) $totalResults;
	$ret['entry']        = [];

	$fields_ret = [
		'id' => false,
		'displayName' => false,
		'urls' => false,
		'updated' => false,
		'preferredUsername' => false,
		'photos' => false,
		'aboutMe' => false,
		'currentLocation' => false,
		'network' => false,
		'tags' => false,
		'address' => false,
		'contactType' => false,
		'generation' => false
	];

	if (empty($_GET['fields'])) {
		foreach ($fields_ret as $k => $v) {
			$fields_ret[$k] = true;
		}
	} else {
		$fields_req = explode(',', $_GET['fields']);
		foreach ($fields_req as $f) {
			$fields_ret[trim($f)] = true;
		}
	}

	if (!is_array($contacts)) {
		throw new \Friendica\Network\HTTPException\InternalServerErrorException();
	}

	if (DBA::isResult($contacts)) {
		foreach ($contacts as $contact) {
			if (!isset($contact['updated'])) {
				$contact['updated'] = '';
			}

			if (! isset($contact['generation'])) {
				$contact['generation'] = 1;
			}

			if (($contact['keywords'] == "") && isset($contact['pub_keywords'])) {
				$contact['keywords'] = $contact['pub_keywords'];
			}
			if (isset($contact['account-type'])) {
				$contact['contact-type'] = $contact['account-type'];
			}
			$about = DI::cache()->get("about:" . $contact['updated'] . ":" . $contact['nurl']);
			if (is_null($about)) {
				$about = BBCode::convertForUriId($contact['uri-id'], $contact['about']);
				DI::cache()->set("about:" . $contact['updated'] . ":" . $contact['nurl'], $about);
			}

			// Non connected persons can only see the keywords of a Diaspora account
			if ($contact['network'] == Protocol::DIASPORA) {
				$contact['location'] = "";
				$about = "";
			}

			$entry = [];
			if ($fields_ret['id']) {
				$entry['id'] = (int)$contact['id'];
			}
			if ($fields_ret['displayName']) {
				$entry['displayName'] = $contact['name'];
			}
			if ($fields_ret['aboutMe']) {
				$entry['aboutMe'] = $about;
			}
			if ($fields_ret['currentLocation']) {
				$entry['currentLocation'] = $contact['location'];
			}
			if ($fields_ret['generation']) {
				$entry['generation'] = (int)$contact['generation'];
			}
			if ($fields_ret['urls']) {
				$entry['urls'] = [['value' => $contact['url'], 'type' => 'profile']];
				if ($contact['addr'] && ($contact['network'] !== Protocol::MAIL)) {
					$entry['urls'][] = ['value' => 'acct:' . $contact['addr'], 'type' => 'webfinger'];
				}
			}
			if ($fields_ret['preferredUsername']) {
				$entry['preferredUsername'] = $contact['nick'];
			}
			if ($fields_ret['updated']) {
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
				$entry['updated'] = date("c", strtotime($entry['updated']));
			}
			if ($fields_ret['photos']) {
				$entry['photos'] = [['value' => $contact['photo'], 'type' => 'profile']];
			}
			if ($fields_ret['network']) {
				$entry['network'] = $contact['network'];
				if ($entry['network'] == Protocol::STATUSNET) {
					$entry['network'] = Protocol::OSTATUS;
				}
				if (($entry['network'] == "") && ($contact['self'])) {
					$entry['network'] = Protocol::DFRN;
				}
			}
			if ($fields_ret['tags']) {
				$tags = str_replace(",", " ", $contact['keywords']);
				$tags = explode(" ", $tags);

				$cleaned = [];
				foreach ($tags as $tag) {
					$tag = trim(strtolower($tag));
					if ($tag != "") {
						$cleaned[] = $tag;
					}
				}

				$entry['tags'] = [$cleaned];
			}
			if ($fields_ret['address']) {
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

			if ($fields_ret['contactType']) {
				$entry['contactType'] = intval($contact['contact-type']);
			}
			$ret['entry'][] = $entry;
		}
	} else {
		$ret['entry'][] = [];
	}

	Logger::info("End of poco");

	if ($format === 'xml') {
		header('Content-type: text/xml');
		echo Renderer::replaceMacros(Renderer::getMarkupTemplate('poco_xml.tpl'), XML::arrayEscape(['$response' => $ret]));
		exit();
	}
	if ($format === 'json') {
		header('Content-type: application/json');
		echo json_encode($ret);
		exit();
	} else {
		throw new \Friendica\Network\HTTPException\InternalServerErrorException();
	}
}
