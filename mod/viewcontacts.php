<?php
require_once('include/Contact.php');
require_once('include/contact_selectors.php');

function viewcontacts_init(&$a) {

	if((get_config('system','block_public')) && (! local_user()) && (! remote_user())) {
		return;
	}

	nav_set_selected('home');

	if($a->argc > 1) {
		$nick = $a->argv[1];
		$r = q("SELECT * FROM `user` WHERE `nickname` = '%s' AND `blocked` = 0 LIMIT 1",
			dbesc($nick)
		);

		if (! dbm::is_result($r)) {
			return;
		}

		$a->data['user'] = $r[0];
		$a->profile_uid = $r[0]['uid'];
		$is_owner = (local_user() && (local_user() == $a->profile_uid));

		profile_load($a,$a->argv[1]);
	}
}


function viewcontacts_content(&$a) {
	require_once("mod/proxy.php");

	if((get_config('system','block_public')) && (! local_user()) && (! remote_user())) {
		notice( t('Public access denied.') . EOL);
		return;
	}

	$o = "";

	// tabs
	$o .= profile_tabs($a,$is_owner, $a->data['user']['nickname']);

	if(((! count($a->profile)) || ($a->profile['hide-friends']))) {
		notice( t('Permission denied.') . EOL);
		return $o;
	}

	$r = q("SELECT COUNT(*) AS `total` FROM `contact`
		WHERE `uid` = %d AND (NOT `blocked` OR `pending`) AND NOT `hidden` AND NOT `archive`
			AND `network` IN ('%s', '%s', '%s')",
		intval($a->profile['uid']),
		dbesc(NETWORK_DFRN),
		dbesc(NETWORK_DIASPORA),
		dbesc(NETWORK_OSTATUS)
	);
	if (dbm::is_result($r))
		$a->set_pager_total($r[0]['total']);

	$r = q("SELECT * FROM `contact`
		WHERE `uid` = %d AND (NOT `blocked` OR `pending`) AND NOT `hidden` AND NOT `archive`
			AND `network` IN ('%s', '%s', '%s')
		ORDER BY `name` ASC LIMIT %d, %d",
		intval($a->profile['uid']),
		dbesc(NETWORK_DFRN),
		dbesc(NETWORK_DIASPORA),
		dbesc(NETWORK_OSTATUS),
		intval($a->pager['start']),
		intval($a->pager['itemspage'])
	);
	if (!dbm::is_result($r)) {
		info(t('No contacts.').EOL);
		return $o;
	}

	$contacts = array();

	foreach ($r as $rr) {
		/// @TODO This triggers an E_NOTICE if 'self' is not there
		if ($rr['self']) {
			continue;
		}

		$url = $rr['url'];

		// route DFRN profiles through the redirect

		$is_owner = ((local_user() && ($a->profile['profile_uid'] == local_user())) ? true : false);

		if($is_owner && ($rr['network'] === NETWORK_DFRN) && ($rr['rel']))
			$url = 'redir/' . $rr['id'];
		else
			$url = zrl($url);

		$contact_details = get_contact_details_by_url($rr['url'], $a->profile['uid'], $rr);

		$contacts[] = array(
			'id' => $rr['id'],
			'img_hover' => sprintf( t('Visit %s\'s profile [%s]'), $contact_details['name'], $rr['url']),
			'photo_menu' => contact_photo_menu($rr),
			'thumb' => proxy_url($contact_details['thumb'], false, PROXY_SIZE_THUMB),
			'name' => htmlentities(substr($contact_details['name'],0,20)),
			'username' => htmlentities($contact_details['name']),
			'details'       => $contact_details['location'],
			'tags'          => $contact_details['keywords'],
			'about'         => $contact_details['about'],
			'account_type'  => account_type($contact_details),
			'url' => $url,
			'sparkle' => '',
			'itemurl' => (($contact_details['addr'] != "") ? $contact_details['addr'] : $rr['url']),
			'network' => network_to_name($rr['network'], $rr['url']),
		);
	}


	$tpl = get_markup_template("viewcontact_template.tpl");
	$o .= replace_macros($tpl, array(
		'$title' => t('Contacts'),
		'$contacts' => $contacts,
		'$paginate' => paginate($a),
	));


	return $o;
}
