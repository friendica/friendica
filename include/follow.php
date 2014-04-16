<?php


//
// Takes a $uid and a url/handle and adds a new contact
// Currently if the contact is DFRN, interactive needs to be true, to redirect to the
// dfrn_request page.

// Otherwise this can be used to bulk add statusnet contacts, twitter contacts, etc.
// Returns an array
//  $return['success'] boolean true if successful
//  $return['message'] error text if success is false.



function new_contact($uid,$url,$interactive = false) {

	$result = array('success' => false,'message' => '');

	$a = get_app();

	// remove ajax junk, e.g. Twitter

	$url = str_replace('/#!/','/',$url);

	if(! allowed_url($url)) {
		$result['message'] = t('Disallowed profile URL.');
		return $result;
	}

	if(! $url) {
		$result['message'] = t('Connect URL missing.');
		return $result;
	}

	$arr = array('url' => $url, 'contact' => array());

	call_hooks('follow', $arr);

	if(x($arr['contact'],'name')) 
		$ret = $arr['contact'];
	else
		$ret = probe_url($url);

	if($ret['network'] === NETWORK_DFRN) {
		if($interactive) {
			if(strlen($a->path))
				$myaddr = bin2hex($a->get_baseurl() . '/profile/' . $a->user['nickname']);
			else
				$myaddr = bin2hex($a->user['nickname'] . '@' . $a->get_hostname());

			goaway($ret['request'] . "&addr=$myaddr");

			// NOTREACHED
		}
	}
	else {
		if(get_config('system','dfrn_only')) {
			$result['message'] = t('This site is not configured to allow communications with other networks.') . EOL;
			$result['message'] != t('No compatible communication protocols or feeds were discovered.') . EOL;
			return $result;
		}
	}






	// This extra param just confuses things, remove it
	if($ret['network'] === NETWORK_DIASPORA)
		$ret['url'] = str_replace('?absolute=true','',$ret['url']);


	// do we have enough information?
	
	if(! ((x($ret,'name')) && (x($ret,'poll')) && ((x($ret,'url')) || (x($ret,'addr'))))) {
		$result['message'] .=  t('The profile address specified does not provide adequate information.') . EOL;
		if(! x($ret,'poll'))
			$result['message'] .= t('No compatible communication protocols or feeds were discovered.') . EOL;
		if(! x($ret,'name'))
			$result['message'] .=  t('An author or name was not found.') . EOL;
		if(! x($ret,'url'))
			$result['message'] .=  t('No browser URL could be matched to this address.') . EOL;
		if(strpos($url,'@') !== false) {
			$result['message'] .=  t('Unable to match @-style Identity Address with a known protocol or email contact.') . EOL;
			$result['message'] .=  t('Use mailto: in front of address to force email check.') . EOL;
		}
		return $result;
	}

	if($ret['network'] === NETWORK_OSTATUS && get_config('system','ostatus_disabled')) {
		$result['message'] .= t('The profile address specified belongs to a network which has been disabled on this site.') . EOL;
		$ret['notify'] = '';
	}






	if(! $ret['notify']) {
		$result['message'] .=  t('Limited profile. This person will be unable to receive direct/personal notifications from you.') . EOL;
	}

	$writeable = ((($ret['network'] === NETWORK_OSTATUS) && ($ret['notify'])) ? 1 : 0);

	$subhub = (($ret['network'] === NETWORK_OSTATUS) ? true : false);

	$hidden = (($ret['network'] === NETWORK_MAIL) ? 1 : 0);

	if($ret['network'] === NETWORK_MAIL) {
		$writeable = 1;

	}
	if($ret['network'] === NETWORK_DIASPORA)
		$writeable = 1;

	// check if we already have a contact
	// the poll url is more reliable than the profile url, as we may have
	// indirect links or webfinger links

	$r = q("SELECT * FROM `contact` WHERE `uid` = %d AND `poll` = '%s' LIMIT 1",
		intval($uid),
		dbesc($ret['poll'])
	);


	if(count($r)) {
		// update contact
		if($r[0]['rel'] == CONTACT_IS_FOLLOWER || ($network === NETWORK_DIASPORA && $r[0]['rel'] == CONTACT_IS_SHARING)) {
			q("UPDATE `contact` SET `rel` = %d , `subhub` = %d, `readonly` = 0 WHERE `id` = %d AND `uid` = %d",
				intval(CONTACT_IS_FRIEND),
				intval($subhub),
				intval($r[0]['id']),
				intval($uid)
			);
		}
	}
	else {


		// check service class limits

		$r = q("select count(*) as total from contact where uid = %d and pending = 0 and self = 0",
			intval($uid)
		);
		if(count($r))
			$total_contacts = $r[0]['total'];

		if(! service_class_allows($uid,'total_contacts',$total_contacts)) {
			$result['message'] .= upgrade_message();
			return $result;
		}

		$r = q("select count(network) as total from contact where uid = %d and network = '%s' and pending = 0 and self = 0",
			intval($uid),
			dbesc($network)
		);
		if(count($r))
			$total_network = $r[0]['total'];

		if(! service_class_allows($uid,'total_contacts_' . $network,$total_network)) {
			$result['message'] .= upgrade_message();
			return $result;
		}

		$new_relation = (($ret['network'] === NETWORK_MAIL) ? CONTACT_IS_FRIEND : CONTACT_IS_SHARING);
		if($ret['network'] === NETWORK_DIASPORA)
			$new_relation = CONTACT_IS_FOLLOWER;

		// create contact record 
		$r = q("INSERT INTO `contact` ( `uid`, `created`, `url`, `nurl`, `addr`, `alias`, `batch`, `notify`, `poll`, `poco`, `name`, `nick`, `photo`, `network`, `pubkey`, `rel`, `priority`,
			`writable`, `hidden`, `blocked`, `readonly`, `pending`, `subhub` )
			VALUES ( %d, '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', %d, %d, %d, %d, 0, 0, 0, %d ) ",
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
			dbesc($ret['photo']),
			dbesc($ret['network']),
			dbesc($ret['pubkey']),
			intval($new_relation),
			intval($ret['priority']),
			intval($writeable),
			intval($hidden),
			intval($subhub)
		);
	}

	$r = q("SELECT * FROM `contact` WHERE `url` = '%s' AND `uid` = %d LIMIT 1",
		dbesc($ret['url']),
		intval($uid)
	);

	if(! count($r)) {
		$result['message'] .=  t('Unable to retrieve contact information.') . EOL;
		return $result;
	}

	$contact = $r[0];
	$contact_id  = $r[0]['id'];


	$g = q("select def_gid from user where uid = %d limit 1",
		intval($uid)
	);
	if($g && intval($g[0]['def_gid'])) {
		require_once('include/group.php');
		group_add_member($uid,'',$contact_id,$g[0]['def_gid']);
	}

	require_once("include/Photo.php");

	$photos = import_profile_photo($ret['photo'],$uid,$contact_id);

	$r = q("UPDATE `contact` SET `photo` = '%s',
			`thumb` = '%s',
			`micro` = '%s',
			`name-date` = '%s',
			`uri-date` = '%s',
			`avatar-date` = '%s'
			WHERE `id` = %d
		",
			dbesc($photos[0]),
			dbesc($photos[1]),
			dbesc($photos[2]),
			dbesc(datetime_convert()),
			dbesc(datetime_convert()),
			dbesc(datetime_convert()),
			intval($contact_id)
		);


	// pull feed and consume it, which should subscribe to the hub.

	proc_run('php',"include/poller.php","$contact_id");

	// create a follow slap

	$tpl = get_markup_template('follow_slap.tpl');
	$slap = replace_macros($tpl, array(
		'$name' => $a->user['username'],
		'$profile_page' => $a->get_baseurl() . '/profile/' . $a->user['nickname'],
		'$photo' => $a->contact['photo'],
		'$thumb' => $a->contact['thumb'],
		'$published' => datetime_convert('UTC','UTC', 'now', ATOM_TIME),
		'$item_id' => 'urn:X-dfrn:' . $a->get_hostname() . ':follow:' . random_string(),
		'$title' => '',
		'$type' => 'text',
		'$content' => t('following'),
		'$nick' => $a->user['nickname'],
		'$verb' => ACTIVITY_FOLLOW,
		'$ostat_follow' => ''
	));

	$r = q("SELECT `contact`.*, `user`.* FROM `contact` INNER JOIN `user` ON `contact`.`uid` = `user`.`uid`
			WHERE `user`.`uid` = %d AND `contact`.`self` = 1 LIMIT 1",
			intval($uid)
	);

	if(count($r)) {
		if(($contact['network'] == NETWORK_OSTATUS) && (strlen($contact['notify']))) {
			require_once('include/salmon.php');
			slapper($r[0],$contact['notify'],$slap);
		}
		if($contact['network'] == NETWORK_DIASPORA) {
			require_once('include/diaspora.php');
			$ret = diaspora_share($a->user,$contact);
			logger('mod_follow: diaspora_share returns: ' . $ret);
		}
	}

	$result['success'] = true;
	return $result;
}
