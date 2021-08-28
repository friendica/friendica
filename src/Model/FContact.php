<?php
/**
 * @copyright Copyright (C) 2010-2021, the Friendica project
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
use Friendica\DI;
use Friendica\Network\Probe;
use Friendica\Protocol\Activity;
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
	private static function getByURL($handle, $update = null, $network = Protocol::DIASPORA)
	{
		$person = DBA::selectFirst('fcontact', [], ['network' => $network, 'addr' => $handle]);
		if (!DBA::isResult($person)) {
			$urls = [$handle, str_replace('http://', 'https://', $handle), Strings::normaliseLink($handle)];
			$person = DBA::selectFirst('fcontact', [], ['network' => $network, 'url' => $urls]);
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
			$r = Probe::uri($handle, $network);

			// Note that Friendica contacts will return a "Diaspora person"
			// if Diaspora connectivity is enabled on their server
			if ($r && ($r["network"] === $network)) {
				self::updateFContact($r);

				$person = self::getByURL($handle, false, $network);
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
	private static function updateFContact($arr)
	{
		$fields = ['name' => $arr["name"], 'photo' => $arr["photo"],
			'request' => $arr["request"], 'nick' => $arr["nick"],
			'addr' => strtolower($arr["addr"]), 'guid' => $arr["guid"],
			'batch' => $arr["batch"], 'notify' => $arr["notify"],
			'poll' => $arr["poll"], 'confirm' => $arr["confirm"],
			'alias' => $arr["alias"], 'pubkey' => $arr["pubkey"],
			'uri-id' => ItemURI::insert(['uri' => $arr['url'], 'guid' => $arr['guid']]),
			'updated' => DateTimeFormat::utcNow()];

		$condition = ['url' => $arr["url"], 'network' => $arr["network"]];

		DBA::update('fcontact', $fields, $condition, true);
	}

	/**
	 * Suggest a given contact to a given user from a given contact
	 *
	 * @param integer $uid
	 * @param integer $cid
	 * @param integer $from_cid
	 * @return bool   Was the adding successful?
	 */
	public static function addSuggestion(int $uid, int $cid, int $from_cid, string $note = '')
	{
		$owner = User::getOwnerDataById($uid);
		$contact = Contact::getById($cid);
		$from_contact = Contact::getById($from_cid);

		if (DBA::exists('contact', ['nurl' => Strings::normaliseLink($contact['url']), 'uid' => $uid])) {
			return false;
		}

		$fcontact = self::getByURL($contact['url'], null, $contact['network']);
		if (empty($fcontact)) {
			Logger::warning('FContact had not been found', ['fcontact' => $contact['url']]);
			return false;
		}

		$fid = $fcontact['id'];

		// Quit if we already have an introduction for this person
		if (DBA::exists('intro', ['uid' => $uid, 'fid' => $fid])) {
			return false;
		}

		$suggest = [];
		$suggest['uid'] = $uid;
		$suggest['cid'] = $from_cid;
		$suggest['url'] = $contact['url'];
		$suggest['name'] = $contact['name'];
		$suggest['photo'] = $contact['photo'];
		$suggest['request'] = $contact['request'];
		$suggest['title'] = '';
		$suggest['body'] = $note;

		$hash = Strings::getRandomHex();
		$fields = ['uid' => $suggest['uid'], 'fid' => $fid, 'contact-id' => $suggest['cid'], 
			'note' => $suggest['body'], 'hash' => $hash, 'datetime' => DateTimeFormat::utcNow(), 'blocked' => false];
		DBA::insert('intro', $fields);

		notification([
			'type'  => Notification\Type::SUGGEST,
			'otype' => Notification\ObjectType::INTRO,
			'verb'  => Activity::REQ_FRIEND,
			'uid'   => $owner['uid'],
			'cid'   => $from_contact['uid'],
			'item'  => $suggest,
			'link'  => DI::baseUrl().'/notifications/intros',
		]);

		return true;
	}
}
