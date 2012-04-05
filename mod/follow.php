<?php

require_once('Scrape.php');

function follow_init(&$a) {

	if(! local_user()) {
		notice( t('Permission denied.') . EOL);
		goaway($_SESSION['return_url']);
		// NOTREACHED
	}

	$url = $orig_url = notags(trim($_REQUEST['url']));

	// remove ajax junk, e.g. Twitter

	$url = str_replace('/#!/','/',$url);

	if(! allowed_url($url)) {
		notice( t('Disallowed profile URL.') . EOL);
		goaway($_SESSION['return_url']);
		// NOTREACHED
	}


	if(! $url) {
		notice( t('Connect URL missing.') . EOL);
		goaway($_SESSION['return_url']);
		// NOTREACHED
	}


	$ret = probe_url($url);

	if($ret['network'] === NETWORK_DFRN) {
		if(strlen($a->path))
			$myaddr = bin2hex($a->get_baseurl() . '/profile/' . $a->user['nickname']);
		else
			$myaddr = bin2hex($a->user['nickname'] . '@' . $a->get_hostname());
 
		goaway($ret['request'] . "&addr=$myaddr");
		
		// NOTREACHED
	}
	else {
		if(get_config('system','dfrn_only')) {
			notice( t('This site is not configured to allow communications with other networks.') . EOL);
			notice( t('No compatible communication protocols or feeds were discovered.') . EOL);
			goaway($_SESSION['return_url']);
		}
	}
	
	// This just confuses things, remove it
	if($ret['network'] === NETWORK_DIASPORA)
		$ret['url'] = str_replace('?absolute=true','',$ret['url']);


	// do we have enough information?
	
	if(! ((x($ret,'name')) && (x($ret,'poll')) && ((x($ret,'url')) || (x($ret,'addr'))))) {
		notice( t('The profile address specified does not provide adequate information.') . EOL);
		if(! x($ret,'poll'))
			notice( t('No compatible communication protocols or feeds were discovered.') . EOL);
		if(! x($ret,'name'))
			notice( t('An author or name was not found.') . EOL);
		if(! x($ret,'url'))
			notice( t('No browser URL could be matched to this address.') . EOL);
		if(strpos($url,'@') !== false)
			notice('Unable to match @-style Identity Address with a known protocol or email contact');
		goaway($_SESSION['return_url']);
	}

	if($ret['network'] === NETWORK_OSTATUS && get_config('system','ostatus_disabled')) {
		notice( t('The profile address specified belongs to a network which has been disabled on this site.') . EOL);
		$ret['notify'] = '';
	}

	if(! $ret['notify']) {
		notice( t('Limited profile. This person will be unable to receive direct/personal notifications from you.') . EOL);
	}

	$writeable = ((($ret['network'] === NETWORK_OSTATUS) && ($ret['notify'])) ? 1 : 0);
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
		intval(local_user()),
		dbesc($ret['poll'])
	);			

	if(count($r)) {
		// update contact
		if($r[0]['rel'] == CONTACT_IS_FOLLOWER || ($network === NETWORK_DIASPORA && $r[0]['rel'] == CONTACT_IS_SHARING)) {
			q("UPDATE `contact` SET `rel` = %d , `readonly` = 0 WHERE `id` = %d AND `uid` = %d LIMIT 1",
				intval(CONTACT_IS_FRIEND),
				intval($r[0]['id']),
				intval(local_user())
			);
		}
	}
	else {

		$new_relation = (($ret['network'] === NETWORK_MAIL) ? CONTACT_IS_FRIEND : CONTACT_IS_SHARING);
		if($ret['network'] === NETWORK_DIASPORA)
			$new_relation = CONTACT_IS_FOLLOWER;

		// create contact record 
		$r = q("INSERT INTO `contact` ( `uid`, `created`, `url`, `nurl`, `addr`, `alias`, `batch`, `notify`, `poll`, `poco`, `name`, `nick`, `photo`, `network`, `pubkey`, `rel`, `priority`,
			`writable`, `hidden`, `blocked`, `readonly`, `pending` )
			VALUES ( %d, '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', %d, %d, %d, %d, 0, 0, 0 ) ",
			intval(local_user()),
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
			intval($hidden)
		);
	}

	$r = q("SELECT * FROM `contact` WHERE `url` = '%s' AND `uid` = %d LIMIT 1",
		dbesc($ret['url']),
		intval(local_user())
	);

	if(! count($r)) {
		notice( t('Unable to retrieve contact information.') . EOL);
		goaway($_SESSION['return_url']);
		// NOTREACHED
	}

	$contact = $r[0];
	$contact_id  = $r[0]['id'];

	require_once("Photo.php");

	$photos = import_profile_photo($ret['photo'],local_user(),$contact_id);

	$r = q("UPDATE `contact` SET `photo` = '%s', 
			`thumb` = '%s',
			`micro` = '%s', 
			`name-date` = '%s', 
			`uri-date` = '%s', 
			`avatar-date` = '%s'
			WHERE `id` = %d LIMIT 1
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

	$r = q("SELECT `contact`.*, `user`.* FROM `contact` LEFT JOIN `user` ON `contact`.`uid` = `user`.`uid` 
			WHERE `user`.`uid` = %d AND `contact`.`self` = 1 LIMIT 1",
			intval(local_user())
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

	if(strstr($_SESSION['return_url'],'contacts'))
		goaway($a->get_baseurl() . '/contacts/' . $contact_id);

	goaway($_SESSION['return_url']);
	// NOTREACHED
}
