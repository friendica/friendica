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

use Friendica\App;
use Friendica\Core\Protocol;
use Friendica\DI;
use Friendica\Model\APContact;
use Friendica\Model\Contact;
use Friendica\Protocol\ActivityPub;

function ostatus_subscribe_content(App $a)
{
	if (!local_user()) {
		notice(DI::l10n()->t('Permission denied.'));
		DI::baseUrl()->redirect('ostatus_subscribe');
		// NOTREACHED
	}

	$o = '<h2>' . DI::l10n()->t('Subscribing to contacts') . '</h2>';

	$uid = local_user();

	$counter = intval($_REQUEST['counter'] ?? 0);

	if (DI::pConfig()->get($uid, 'ostatus', 'legacy_friends') == '') {

		if ($_REQUEST['url'] == '') {
			DI::pConfig()->delete($uid, 'ostatus', 'legacy_contact');
			return $o . DI::l10n()->t('No contact provided.');
		}

		$contact = Contact::getByURL($_REQUEST['url']);
		if (!$contact) {
			DI::pConfig()->delete($uid, 'ostatus', 'legacy_contact');
			return $o . DI::l10n()->t('Couldn\'t fetch information for contact.');
		}

		if ($contact['network'] == Protocol::OSTATUS) {
			$api = $contact['baseurl'] . '/api/';

			// Fetching friends
			$curlResult = DI::httpClient()->get($api . 'statuses/friends.json?screen_name=' . $contact['nick']);

			if (!$curlResult->isSuccess()) {
				DI::pConfig()->delete($uid, 'ostatus', 'legacy_contact');
				return $o . DI::l10n()->t('Couldn\'t fetch friends for contact.');
			}

			$friends = $curlResult->getBody();
			if (empty($friends)) {
				DI::pConfig()->delete($uid, 'ostatus', 'legacy_contact');
				return $o . DI::l10n()->t('Couldn\'t fetch following contacts.');
			}
			DI::pConfig()->set($uid, 'ostatus', 'legacy_friends', $friends);
		} elseif ($apcontact = APContact::getByURL($contact['url'])) {
			if (empty($apcontact['following'])) {
				DI::pConfig()->delete($uid, 'ostatus', 'legacy_contact');
				return $o . DI::l10n()->t('Couldn\'t fetch remote profile.');
			}
			$followings = ActivityPub::fetchItems($apcontact['following']);
			if (empty($followings)) {
				DI::pConfig()->delete($uid, 'ostatus', 'legacy_contact');
				return $o . DI::l10n()->t('Couldn\'t fetch following contacts.');
			}
			DI::pConfig()->set($uid, 'ostatus', 'legacy_friends', json_encode($followings));
		} else {
			DI::pConfig()->delete($uid, 'ostatus', 'legacy_contact');
			return $o . DI::l10n()->t('Unsupported network');
		}
	}

	$friends = json_decode(DI::pConfig()->get($uid, 'ostatus', 'legacy_friends'));

	if (empty($friends)) {
		$friends = [];
	}

	$total = sizeof($friends);

	if ($counter >= $total) {
		DI::page()['htmlhead'] = '<meta http-equiv="refresh" content="0; URL=' . DI::baseUrl() . '/settings/connectors">';
		DI::pConfig()->delete($uid, 'ostatus', 'legacy_friends');
		DI::pConfig()->delete($uid, 'ostatus', 'legacy_contact');
		$o .= DI::l10n()->t('Done');
		return $o;
	}

	$friend = $friends[$counter++];

	$url = $friend->statusnet_profile_url ?? $friend;

	$o .= '<p>' . $counter . '/' . $total . ': ' . $url;

	$probed = Contact::getByURL($url);
	if (in_array($probed['network'], Protocol::FEDERATED)) {
		$result = Contact::createFromProbeForUser($a->getLoggedInUserId(), $probed['url']);
		if ($result['success']) {
			$o .= ' - ' . DI::l10n()->t('success');
		} else {
			$o .= ' - ' . DI::l10n()->t('failed');
		}
	} else {
		$o .= ' - ' . DI::l10n()->t('ignored');
	}

	$o .= '</p>';

	$o .= '<p>' . DI::l10n()->t('Keep this window open until done.') . '</p>';

	DI::page()['htmlhead'] = '<meta http-equiv="refresh" content="0; URL=' . DI::baseUrl() . '/ostatus_subscribe?counter=' . $counter . '">';

	return $o;
}
