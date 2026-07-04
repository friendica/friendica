<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module;

use Friendica\BaseModule;
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
		if (isset($this->parameters['nick'])) {
			// Get infos about a specific nick (public)
			$which = $this->parameters['nick'];
		} elseif (DI::userSession()->getLocalUserId() && isset($this->parameters['profile']) && DI::args()->get(2) == 'view') {
			// view infos about a known profile (needs a login)
			$which = DI::userSession()->getLocalUserNickname();
		} else {
			$this->jsonError(403, 'Authentication required');
		}

		$owner = User::getOwnerDataByNick($which);

		if (empty($owner['uid'])) {
			$this->jsonError(404, 'Profile not found');
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

		$dfrn_pages = ['notify', 'poll'];
		foreach ($dfrn_pages as $dfrn) {
			$json_info["dfrn-{$dfrn}"] = DI::baseUrl() . "/dfrn_{$dfrn}/{$which}";
		}

		if (!$owner['net-publish']) {
			$json_info['hide'] = true;
			$this->jsonExit($json_info);
		}

		$keywords = $owner['pub_keywords'] ?? '';
		$keywords = str_replace(['#', ',', ' ', ',,'], ['', ' ', ',', ','], $keywords);
		$keywords = explode(',', $keywords);

		$json_info['fn']       = $owner['name'];
		$json_info['photo']    = User::getAvatarUrl($owner);
		$json_info['tags']     = $keywords;
		$json_info['language'] = $owner['language'];

		if (!empty($owner['last-item'])) {
			$json_info['updated'] = date("c", strtotime((string) $owner['last-item']));
		}

		if (!($owner['hide-friends'] ?? false)) {
			$apcontact             = APContact::getByURL($owner['url']);
			$json_info['contacts'] = max($apcontact['following_count'], $apcontact['followers_count']);
		}

		// We display the last activity (post or login), reduced to year and week number
		$last_active = strtotime((string) $owner['last-item']);
		if ($owner['last-activity'] && $last_active < strtotime((string) $owner['last-activity'])) {
			$last_active = strtotime((string) $owner['last-activity']);
		}
		$json_info['last-activity'] = date('o-W', $last_active);

		//These are optional fields.
		$profile_fields = ['about', 'locality', 'region', 'postal-code', 'country-name', 'xmpp', 'matrix'];
		foreach ($profile_fields as $field) {
			if (!empty($owner[$field])) {
				$json_info[$field] = $owner[$field];
			}
		}

		$this->jsonExit($json_info);
	}
}
