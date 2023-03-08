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
use Friendica\Core\System;
use Friendica\DI;
use Friendica\Model\APContact;
use Friendica\Model\User;

/**
 * Endpoint for getting current user infos
 *
 * @see Contact::updateFromNoScrape() for usage
 */
class NoScrape extends BaseModule
{
	protected function rawContent(array $request = [])
	{
		$a = DI::app();

		if (isset($this->parameters['nick'])) {
			// Get infos about a specific nick (public)
			$which = $this->parameters['nick'];
		} elseif (DI::userSession()->getLocalUserId() && isset($this->parameters['profile']) && DI::args()->get(2) == 'view') {
			// view infos about a known profile (needs a login)
			$which = $a->getLoggedInUserNickname();
		} else {
			System::jsonError(403, 'Authentication required');
		}

		$owner = User::getOwnerDataByNick($which);

		if (empty($owner['uid'])) {
			System::jsonError(404, 'Profile not found');
		}

		$json_info = [
			'addr'         => $owner['addr'],
			'nick'         => $which,
			'guid'         => $owner['guid'],
			'key'          => $owner['upubkey'],
			'homepage'     => DI::baseUrl() . '/profile/' . $which,
			'comm'         => ($owner['account-type'] == User::ACCOUNT_TYPE_COMMUNITY),
			'account-type' => $owner['account-type'],
		];

		$dfrn_pages = ['request', 'confirm', 'notify', 'poll'];
		foreach ($dfrn_pages as $dfrn) {
			$json_info["dfrn-{$dfrn}"] = DI::baseUrl() . "/dfrn_{$dfrn}/{$which}";
		}

		if (!$owner['net-publish']) {
			$json_info['hide'] = true;
			System::jsonExit($json_info);
		}

		$keywords = $owner['pub_keywords'] ?? '';
		$keywords = str_replace(['#', ',', ' ', ',,'], ['', ' ', ',', ','], $keywords);
		$keywords = explode(',', $keywords);

		$json_info['fn']       = $owner['name'];
		$json_info['photo']    = User::getAvatarUrl($owner);
		$json_info['tags']     = $keywords;
		$json_info['language'] = $owner['language'];

		if (!empty($owner['last-item'])) {
			$json_info['updated'] = date("c", strtotime($owner['last-item']));
		}

		if (!($owner['hide-friends'] ?? false)) {
			$apcontact = APContact::getByURL($owner['url']);
			$json_info['contacts'] = max($apcontact['following_count'], $apcontact['followers_count']);
		}

		// We display the last activity (post or login), reduced to year and week number
		$last_active = strtotime($owner['last-item']);
		if ($owner['last-activity'] && $last_active < strtotime($owner['last-activity'])) {
			$last_active = strtotime($owner['last-activity']);
		}
		$json_info['last-activity'] = date('o-W', $last_active);

		//These are optional fields.
		$profile_fields = ['about', 'locality', 'region', 'postal-code', 'country-name', 'xmpp', 'matrix'];
		foreach ($profile_fields as $field) {
			if (!empty($owner[$field])) {
				$json_info[$field] = $owner[$field];
			}
		}

		System::jsonExit($json_info);
	}
}
