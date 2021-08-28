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
use Friendica\Database\DBStructure;
use Friendica\Network\Probe;
use Friendica\Util\DateTimeFormat;

class DiasporaContact
{
	/**
	 * Fetches a profile from a given url
	 *
	 * @param string  $url    profile url
	 * @param boolean $update true = always update, false = never update, null = update when not found or outdated
	 * @return array profile array
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function getByURL(string $url, bool $update = null):array
	{
		if (empty($url)) {
			return [];
		}

		$fetched_contact = false;

		if (empty($update)) {
			if (is_null($update)) {
				$ref_update = DateTimeFormat::utc('now - 1 month');
			} else {
				$ref_update = DBA::NULL_DATETIME;
			}

			$dcontact = DBA::selectFirst('dcontact', [], ['url' => $url]);
			if (!DBA::isResult($dcontact)) {
				$dcontact = DBA::selectFirst('dcontact', [], ['alias' => $url]);
			}

			if (!DBA::isResult($dcontact)) {
				$dcontact = DBA::selectFirst('dcontact', [], ['addr' => $url]);
			}

			if (DBA::isResult($dcontact) && ($dcontact['updated'] > $ref_update)) {
				return $dcontact;
			}

			if (!is_null($update)) {
				return DBA::isResult($dcontact) ? $dcontact : [];
			}

			if (DBA::isResult($dcontact)) {
				$fetched_contact = $dcontact;
			}
		}

		$dcontact = [];

		$data = Probe::uri($url, Protocol::DIASPORA);
		if (empty($data) || ($data['network'] == Protocol::PHANTOM)) {
			return $fetched_contact;
		}

		$dcontact['url']          = $data['url'];
		$dcontact['guid']         = $data['guid'];
		$dcontact['addr']         = $data['addr'];
		$dcontact['alias']        = $data['alias'] ?? null;
		$dcontact['nick']         = $data['nick'];
		$dcontact['name']         = $data['name'] ?? null;
		$dcontact['given-name']   = $data['given_name'] ?? null;
		$dcontact['family-name']  = $data['family_name'] ?? null;
		$dcontact['photo']        = $data['photo'] ?? null;
		$dcontact['photo-medium'] = $data['photo_medium'] ?? null;
		$dcontact['photo-small']  = $data['photo_small'] ?? null;
		$dcontact['batch']        = $data['batch'] ?? null;
		$dcontact['notify']       = $data['notify'] ?? null;
		$dcontact['poll']         = $data['poll'] ?? null;
		$dcontact['subscribe']    = $data['subscribe'] ?? null;
		$dcontact['searchable']   = !($data['hide'] ?? true);
		$dcontact['pubkey']       = $data['pubkey'] ?? null;
		$dcontact['baseurl']      = $data['baseurl'] ?? null;
		$dcontact['gsid']         = $data['gsid'] ?? null;

		if ($dcontact['url'] == $dcontact['alias']) {
			$dcontact['alias'] = null;
		}

		$dcontact['uri-id'] = ItemURI::insert(['uri' => $dcontact['url'], 'guid' => $dcontact['guid']]);

		$dcontact['updated'] = DateTimeFormat::utcNow();

		// Limit the length on incoming fields
		$dcontact = DBStructure::getFieldsForTable('dcontact', $dcontact);

		if (DBA::exists('dcontact', ['url' => $dcontact['url']])) {
			DBA::update('dcontact', $dcontact, ['url' => $dcontact['url']]);
		} else {
			DBA::replace('dcontact', $dcontact);
		}

		Logger::info('Updated profile', ['url' => $url]);

		return DBA::selectFirst('dcontact', [], ['url' => $dcontact['url']]) ?: [];
	}

	/**
	 * get a url (scheme://domain.tld/u/user) from a given contact guid
	 *
	 * @param mixed $guid Hexadecimal string guid
	 *
	 * @return string the contact url or null
	 * @throws \Exception
	 */
	public static function getUrlByGuid(string $guid)
	{
		Logger::info('fcontact', ['guid' => $guid]);

		$dcontact = DBA::selectFirst('dcontact', ['url'], ['guid' => $guid]);
		if (DBA::isResult($dcontact)) {
			return $dcontact['url'];
		}

		return null;
	}
}
