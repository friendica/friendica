<?php
/**
 * @copyright Copyright (C) 2020, Friendica
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
use Friendica\Model\Contact;
use Friendica\Network\Probe;

function ostatus_subscribe_content(App $a)
{
	if (!local_user()) {
		notice(DI::l10n()->t('Permission denied.') . EOL);
		DI::baseUrl()->redirect('ostatus_subscribe');
		// NOTREACHED
	}

	$o = '<h2>' . DI::l10n()->t('Subscribing to OStatus contacts') . '</h2>';

	$uid = local_user();

	$counter = intval($_REQUEST['counter']);

	if (DI::pConfig()->get($uid, 'ostatus', 'legacy_friends') == '') {

		if ($_REQUEST['url'] == '') {
			DI::pConfig()->delete($uid, 'ostatus', 'legacy_contact');
			return $o . DI::l10n()->t('No contact provided.');
		}

		$contact = Probe::uri($_REQUEST['url']);

		if (!$contact) {
			DI::pConfig()->delete($uid, 'ostatus', 'legacy_contact');
			return $o . DI::l10n()->t('Couldn\'t fetch information for contact.');
		}

		$api = $contact['baseurl'] . '/api/';

		// Fetching friends
		$curlResult = DI::httpRequest()->curl($api . 'statuses/friends.json?screen_name=' . $contact['nick']);

		if (!$curlResult->isSuccess()) {
			DI::pConfig()->delete($uid, 'ostatus', 'legacy_contact');
			return $o . DI::l10n()->t('Couldn\'t fetch friends for contact.');
		}

		DI::pConfig()->set($uid, 'ostatus', 'legacy_friends', $curlResult->getBody());
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

	$url = $friend->statusnet_profile_url;

	$o .= '<p>' . $counter . '/' . $total . ': ' . $url;

	$probed = Probe::uri($url);
	if ($probed['network'] == Protocol::OSTATUS) {
		$result = Contact::createFromProbe($uid, $url, true, Protocol::OSTATUS);
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
