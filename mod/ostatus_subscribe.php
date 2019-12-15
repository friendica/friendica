<?php
/**
 * @file mod/ostatus_subscribe.php
 */

use Friendica\App;
use Friendica\Core\L10n;
use Friendica\Core\PConfig;
use Friendica\Core\Protocol;
use Friendica\Core\System;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Network\Probe;
use Friendica\Util\Network;

function ostatus_subscribe_content(App $a)
{
	if (!local_user()) {
		notice(L10n::t('Permission denied.') . EOL);
		DI::baseUrl()->redirect('ostatus_subscribe');
		// NOTREACHED
	}

	$o = '<h2>' . L10n::t('Subscribing to OStatus contacts') . '</h2>';

	$uid = local_user();

	$a = \get_app();

	$counter = intval($_REQUEST['counter']);

	if (PConfig::get($uid, 'ostatus', 'legacy_friends') == '') {

		if ($_REQUEST['url'] == '') {
			PConfig::delete($uid, 'ostatus', 'legacy_contact');
			return $o . L10n::t('No contact provided.');
		}

		$contact = Probe::uri($_REQUEST['url']);

		if (!$contact) {
			PConfig::delete($uid, 'ostatus', 'legacy_contact');
			return $o . L10n::t('Couldn\'t fetch information for contact.');
		}

		$api = $contact['baseurl'] . '/api/';

		// Fetching friends
		$curlResult = Network::curl($api . 'statuses/friends.json?screen_name=' . $contact['nick']);

		if (!$curlResult->isSuccess()) {
			PConfig::delete($uid, 'ostatus', 'legacy_contact');
			return $o . L10n::t('Couldn\'t fetch friends for contact.');
		}

		PConfig::set($uid, 'ostatus', 'legacy_friends', $curlResult->getBody());
	}

	$friends = json_decode(PConfig::get($uid, 'ostatus', 'legacy_friends'));

	if (empty($friends)) {
		$friends = [];
	}

	$total = sizeof($friends);

	if ($counter >= $total) {
		$a->page['htmlhead'] = '<meta http-equiv="refresh" content="0; URL=' . System::baseUrl() . '/settings/connectors">';
		PConfig::delete($uid, 'ostatus', 'legacy_friends');
		PConfig::delete($uid, 'ostatus', 'legacy_contact');
		$o .= L10n::t('Done');
		return $o;
	}

	$friend = $friends[$counter++];

	$url = $friend->statusnet_profile_url;

	$o .= '<p>' . $counter . '/' . $total . ': ' . $url;

	$probed = Probe::uri($url);
	if ($probed['network'] == Protocol::OSTATUS) {
		$result = Contact::createFromProbe($uid, $url, true, Protocol::OSTATUS);
		if ($result['success']) {
			$o .= ' - ' . L10n::t('success');
		} else {
			$o .= ' - ' . L10n::t('failed');
		}
	} else {
		$o .= ' - ' . L10n::t('ignored');
	}

	$o .= '</p>';

	$o .= '<p>' . L10n::t('Keep this window open until done.') . '</p>';

	$a->page['htmlhead'] = '<meta http-equiv="refresh" content="0; URL=' . System::baseUrl() . '/ostatus_subscribe?counter=' . $counter . '">';

	return $o;
}
