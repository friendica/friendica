<?php
require_once("include/Scrape.php");
require_once("include/socgraph.php");
require_once('include/group.php');
require_once('include/salmon.php');
require_once('include/ostatus.php');
require_once("include/Photo.php");
require_once('include/diaspora.php');

function update_contact($id) {
	/*
	Warning: Never ever fetch the public key via probe_url and write it into the contacts.
	This will reliably kill your communication with Friendica contacts.
	*/

	$r = q("SELECT `url`, `nurl`, `addr`, `alias`, `batch`, `notify`, `poll`, `poco`, `network` FROM `contact` WHERE `id` = %d", intval($id));
	if (!$r)
		return false;

	$ret = probe_url($r[0]["url"]);

	// If probe_url fails the network code will be different
	if ($ret["network"] != $r[0]["network"])
		return false;

	$update = false;

	// make sure to not overwrite existing values with blank entries
	foreach ($ret AS $key => $val) {
		if (isset($r[0][$key]) AND ($r[0][$key] != "") AND ($val == ""))
			$ret[$key] = $r[0][$key];

		if (isset($r[0][$key]) AND ($ret[$key] != $r[0][$key]))
			$update = true;
	}

	if (!$update)
		return true;

	q("UPDATE `contact` SET `url` = '%s', `nurl` = '%s', `addr` = '%s', `alias` = '%s', `batch` = '%s', `notify` = '%s', `poll` = '%s', `poco` = '%s' WHERE `id` = %d",
		dbesc($ret['url']),
		dbesc(normalise_link($ret['url'])),
		dbesc($ret['addr']),
		dbesc($ret['alias']),
		dbesc($ret['batch']),
		dbesc($ret['notify']),
		dbesc($ret['poll']),
		dbesc($ret['poco']),
		intval($id)
	);

	// Update the corresponding gcontact entry
	poco_last_updated($ret["url"]);

	return true;
}

//
// Takes a $uid and a url/handle and adds a new contact
// Currently if the contact is DFRN, interactive needs to be true, to redirect to the
// dfrn_request page.

// Otherwise this can be used to bulk add statusnet contacts, twitter contacts, etc.
// Returns an array
//  $return['success'] boolean true if successful
//  $return['message'] error text if success is false.



function new_contact($uid,$url,$interactive = false) {

	$result = array('cid' => -1, 'success' => false,'message' => '');

	$a = get_app();

	// remove ajax junk, e.g. Twitter

	$url = str_replace('/#!/','/',$url);

	if (! allowed_url($url)) {
		$result['message'] = t('Disallowed profile URL.');
		return $result;
	}

	if (! $url) {
		$result['message'] = t('Connect URL missing.');
		return $result;
	}

	$arr = array('url' => $url, 'contact' => array());

	call_hooks('follow', $arr);

	if (x($arr['contact'],'name')) {
		$ret = $arr['contact'];
	}
	else {
		$ret = probe_url($url);
	}

	if ($ret['network'] === NETWORK_DFRN) {
		if ($interactive) {
			if (strlen($a->path)) {
				$myaddr = bin2hex(App::get_baseurl() . '/profile/' . $a->user['nickname']);
			} else {
				$myaddr = bin2hex($a->user['nickname'] . '@' . $a->get_hostname());
			}

			goaway($ret['request'] . "&addr=$myaddr");

			// NOTREACHED
		}
	} elseif (get_config('system','dfrn_only')) {
		$result['message'] = t('This site is not configured to allow communications with other networks.') . EOL;
		$result['message'] != t('No compatible communication protocols or feeds were discovered.') . EOL;
		return $result;
	}

	// This extra param just confuses things, remove it
	if ($ret['network'] === NETWORK_DIASPORA) {
		$ret['url'] = str_replace('?absolute=true','',$ret['url']);
	}

	// do we have enough information?

	if (! ((x($ret,'name')) && (x($ret,'poll')) && ((x($ret,'url')) || (x($ret,'addr'))))) {
		$result['message'] .=  t('The profile address specified does not provide adequate information.') . EOL;
		if (! x($ret,'poll')) {
			$result['message'] .= t('No compatible communication protocols or feeds were discovered.') . EOL;
		}
		if (! x($ret,'name')) {
			$result['message'] .=  t('An author or name was not found.') . EOL;
		}
		if (! x($ret,'url')) {
			$result['message'] .=  t('No browser URL could be matched to this address.') . EOL;
		}
		if (strpos($url,'@') !== false) {
			$result['message'] .=  t('Unable to match @-style Identity Address with a known protocol or email contact.') . EOL;
			$result['message'] .=  t('Use mailto: in front of address to force email check.') . EOL;
		}
		return $result;
	}

	if ($ret['network'] === NETWORK_OSTATUS && get_config('system','ostatus_disabled')) {
		$result['message'] .= t('The profile address specified belongs to a network which has been disabled on this site.') . EOL;
		$ret['notify'] = '';
	}

	if (! $ret['notify']) {
		$result['message'] .=  t('Limited profile. This person will be unable to receive direct/personal notifications from you.') . EOL;
	}

	$writeable = ((($ret['network'] === NETWORK_OSTATUS) && ($ret['notify'])) ? 1 : 0);

	$subhub = (($ret['network'] === NETWORK_OSTATUS) ? true : false);

	$hidden = (($ret['network'] === NETWORK_MAIL) ? 1 : 0);

	if (in_array($ret['network'], array(NETWORK_MAIL, NETWORK_DIASPORA))) {
		$writeable = 1;
	}

	// check if we already have a contact
	// the poll url is more reliable than the profile url, as we may have
	// indirect links or webfinger links

	$r = q("SELECT * FROM `contact` WHERE `uid` = %d AND `poll` IN ('%s', '%s') AND `network` = '%s' LIMIT 1",
		intval($uid),
		dbesc($ret['poll']),
		dbesc(normalise_link($ret['poll'])),
		dbesc($ret['network'])
	);

	if (!dbm::is_result($r))
		$r = q("SELECT * FROM `contact` WHERE `uid` = %d AND `nurl` = '%s' AND `network` = '%s' LIMIT 1",
			intval($uid), dbesc(normalise_link($url)), dbesc($ret['network'])
	);

	if (dbm::is_result($r)) {
		// update contact
		if ($r[0]['rel'] == CONTACT_IS_FOLLOWER || ($network === NETWORK_DIASPORA && $r[0]['rel'] == CONTACT_IS_SHARING)) {
			q("UPDATE `contact` SET `rel` = %d , `subhub` = %d, `readonly` = 0 WHERE `id` = %d AND `uid` = %d",
				intval(CONTACT_IS_FRIEND),
				intval($subhub),
				intval($r[0]['id']),
				intval($uid)
			);
		}
	} else {
		// check service class limits

		$r = q("SELECT COUNT(*) AS `total` FROM `contact` WHERE `uid` = %d AND `pending` = 0 AND `self` = 0",
			intval($uid)
		);
		if (dbm::is_result($r))
			$total_contacts = $r[0]['total'];

		if (! service_class_allows($uid,'total_contacts',$total_contacts)) {
			$result['message'] .= upgrade_message();
			return $result;
		}

		$r = q("SELECT COUNT(`network`) AS `total` FROM `contact` WHERE `uid` = %d AND `network` = '%s' AND `pending` = 0 AND `self` = 0",
			intval($uid),
			dbesc($network)
		);
		if (dbm::is_result($r)) {
			$total_network = $r[0]['total'];
		}

		if (! service_class_allows($uid,'total_contacts_' . $network,$total_network)) {
			$result['message'] .= upgrade_message();
			return $result;
		}

		$new_relation = ((in_array($ret['network'], array(NETWORK_MAIL, NETWORK_DIASPORA))) ? CONTACT_IS_FRIEND : CONTACT_IS_SHARING);

		// create contact record
		$r = q("INSERT INTO `contact` ( `uid`, `created`, `url`, `nurl`, `addr`, `alias`, `batch`, `notify`, `poll`, `poco`, `name`, `nick`, `network`, `pubkey`, `rel`, `priority`,
			`writable`, `hidden`, `blocked`, `readonly`, `pending`, `subhub` )
			VALUES ( %d, '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', %d, %d, %d, %d, 0, 0, 0, %d ) ",
			intval($uid),
			dbesc(datetime_convert()),
			dbesc($ret['url']),
			dbesc(normalise_link($ret['url'])),
			dbesc($ret['addr']),
			dbesc($ret['alias']),
			dbesc($ret['batch']),
			dbesc($ret['notify']),
			dbesc($ret['poll']),
			dbesc($ret['poco']),
			dbesc($ret['name']),
			dbesc($ret['nick']),
			dbesc($ret['network']),
			dbesc($ret['pubkey']),
			intval($new_relation),
			intval($ret['priority']),
			intval($writeable),
			intval($hidden),
			intval($subhub)
		);
	}

	$r = q("SELECT * FROM `contact` WHERE `url` = '%s' AND `network` = '%s' AND `uid` = %d LIMIT 1",
		dbesc($ret['url']),
		dbesc($ret['network']),
		intval($uid)
	);

	if (! dbm::is_result($r)) {
		$result['message'] .=  t('Unable to retrieve contact information.') . EOL;
		return $result;
	}

	$contact = $r[0];
	$contact_id  = $r[0]['id'];
	$result['cid'] = $contact_id;

	$def_gid = get_default_group($uid, $contact["network"]);
	if (intval($def_gid)) {
		group_add_member($uid, '', $contact_id, $def_gid);
	}

	// Update the avatar
	update_contact_avatar($ret['photo'],$uid,$contact_id);

	// pull feed and consume it, which should subscribe to the hub.

	proc_run(PRIORITY_HIGH, "include/onepoll.php", $contact_id, "force");

	$r = q("SELECT `contact`.*, `user`.* FROM `contact` INNER JOIN `user` ON `contact`.`uid` = `user`.`uid`
			WHERE `user`.`uid` = %d AND `contact`.`self` LIMIT 1",
			intval($uid)
	);

	if (dbm::is_result($r)) {
		if (($contact['network'] == NETWORK_OSTATUS) && (strlen($contact['notify']))) {
			// create a follow slap
			$item = array();
			$item['verb'] = ACTIVITY_FOLLOW;
			$item['follow'] = $contact["url"];
			$slap = ostatus::salmon($item, $r[0]);
			slapper($r[0], $contact['notify'], $slap);
		}

		if ($contact['network'] == NETWORK_DIASPORA) {
			$ret = Diaspora::send_share($a->user,$contact);
			logger('share returns: '.$ret);
		}
	}

	$result['success'] = true;
	return $result;
}
