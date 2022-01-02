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

namespace Friendica\Model;

use Friendica\Core\Logger;
use Friendica\Core\Protocol;
use Friendica\Database\DBA;
use Friendica\Network\Probe;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Strings;

class FContact
{
	/**
	 * Fetches data for a given handle
	 *
	 * @param string $handle The handle
	 * @param boolean $update true = always update, false = never update, null = update when not found or outdated
	 *
	 * @return array the queried data
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function getByURL($handle, $update = null)
	{
		$person = DBA::selectFirst('fcontact', [], ['network' => Protocol::DIASPORA, 'addr' => $handle]);
		if (!DBA::isResult($person)) {
			$urls = [$handle, str_replace('http://', 'https://', $handle), Strings::normaliseLink($handle)];
			$person = DBA::selectFirst('fcontact', [], ['network' => Protocol::DIASPORA, 'url' => $urls]);
		}

		if (DBA::isResult($person)) {
			Logger::debug('In cache', ['person' => $person]);

			if (is_null($update)) {
				// update record occasionally so it doesn't get stale
				$d = strtotime($person["updated"]." +00:00");
				if ($d < strtotime("now - 14 days")) {
					$update = true;
				}

				if (empty($person['guid']) || empty($person['uri-id'])) {
					$update = true;
				}
			}
		} elseif (is_null($update)) {
			$update = !DBA::isResult($person);
		} else {
			$person = [];
		}

		if ($update) {
			Logger::info('create or refresh', ['handle' => $handle]);
			$data = Probe::uri($handle, Protocol::DIASPORA);

			// Note that Friendica contacts will return a "Diaspora person"
			// if Diaspora connectivity is enabled on their server
			if ($data['network'] ?? '' === Protocol::DIASPORA) {
				self::updateFromProbeArray($data);

				$person = self::getByURL($handle, false);
			}
		}

		return $person;
	}

	/**
	 * Updates the fcontact table
	 *
	 * @param array $arr The fcontact data
	 * @throws \Exception
	 */
	public static function updateFromProbeArray($arr)
	{
		$uriid = ItemURI::insert(['uri' => $arr['url'], 'guid' => $arr['guid']]);

		$contact = Contact::getByUriId($uriid, ['id']);
		if (!empty($contact['id'])) {
			$last_interaction = DateTimeFormat::utc('now - 180 days');

			$interacted  = DBA::count('contact-relation', ["`cid` = ? AND NOT `follows` AND `last-interaction` > ?", $contact['id'], $last_interaction]);
			$interacting = DBA::count('contact-relation', ["`relation-cid` = ? AND NOT `follows` AND `last-interaction` > ?", $contact['id'], $last_interaction]);
			$posts       = Post::countPosts(['author-id' => $contact['id'], 'gravity' => [GRAVITY_PARENT, GRAVITY_COMMENT]]);
		}

		$fields = ['name' => $arr["name"], 'photo' => $arr["photo"],
			'request' => $arr["request"], 'nick' => $arr["nick"],
			'addr' => strtolower($arr["addr"]), 'guid' => $arr["guid"],
			'batch' => $arr["batch"], 'notify' => $arr["notify"],
			'poll' => $arr["poll"], 'confirm' => $arr["confirm"],
			'alias' => $arr["alias"], 'pubkey' => $arr["pubkey"],
			'uri-id' => $uriid, 'interacting_count' => $interacting ?? 0,
			'interacted_count' => $interacted ?? 0, 'post_count' => $posts ?? 0,
			'updated' => DateTimeFormat::utcNow()];

		$condition = ['url' => $arr["url"], 'network' => $arr["network"]];

		DBA::update('fcontact', $fields, $condition, true);
	}

	/**
	 * get a url (scheme://domain.tld/u/user) from a given Diaspora*
	 * fcontact guid
	 *
	 * @param mixed $fcontact_guid Hexadecimal string guid
	 *
	 * @return string the contact url or null
	 * @throws \Exception
	 */
	public static function getUrlByGuid($fcontact_guid)
	{
		Logger::info('fcontact', ['guid' => $fcontact_guid]);

		$fcontact = DBA::selectFirst('fcontact', ['url'], ["`url` != ? AND `network` = ? AND `guid` = ?", '', Protocol::DIASPORA, $fcontact_guid]);
		if (DBA::isResult($fcontact)) {
			return $fcontact['url'];
		}

		return null;
	}
}
