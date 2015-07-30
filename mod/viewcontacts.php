<?php
require_once('include/contact_selectors.php');

function viewcontacts_init(&$a) {

	if((get_config('system','block_public')) && (! local_user()) && (! remote_user())) {
		return;
	}

	profile_load($a,$a->argv[1]);
}


function viewcontacts_content(&$a) {

	require_once("mod/proxy.php");

	if((get_config('system','block_public')) && (! local_user()) && (! remote_user())) {
		notice( t('Public access denied.') . EOL);
		return;
	}

	if(((! count($a->profile)) || ($a->profile['hide-friends']))) {
		notice( t('Permission denied.') . EOL);
		return;
	}


	$r = q("SELECT COUNT(*) as `total` FROM `contact` WHERE `uid` = %d AND `blocked` = 0 AND `pending` = 0 AND `hidden` = 0 AND `archive` = 0 ",
		intval($a->profile['uid'])
	);
	if(count($r))
		$a->set_pager_total($r[0]['total']);

	$r = q("SELECT * FROM `contact` WHERE `uid` = %d AND `blocked` = 0 AND `pending` = 0 AND `hidden` = 0 AND `archive` = 0 ORDER BY `name` ASC LIMIT %d , %d ",
		intval($a->profile['uid']),
		intval($a->pager['start']),
		intval($a->pager['itemspage'])
	);
	if(! count($r)) {
		info( t('No contacts.') . EOL );
		return $o;
	}

	$contacts = array();

	foreach($r as $rr) {
		if($rr['self'])
			continue;

	    $url = $rr['url'];

		// route DFRN profiles through the redirect

		$is_owner = ((local_user() && ($a->profile['profile_uid'] == local_user())) ? true : false);

		if($is_owner && ($rr['network'] === NETWORK_DFRN) && ($rr['rel']))
			$url = 'redir/' . $rr['id'];
		else
			$url = zrl($url);

		$contacts[] = array(
			'id' => $rr['id'],
			'img_hover' => sprintf( t('Visit %s\'s profile [%s]'), $rr['name'], $rr['url']),
			'thumb' => proxy_url($rr['thumb']),
			'name' => substr($rr['name'],0,20),
			'username' => $rr['name'],
			'url' => $url,
			'sparkle' => '',
			'itemurl' => $rr['url'],
			'network' => network_to_name($rr['network'], $rr['url']),
		);
	}


	$tpl = get_markup_template("viewcontact_template.tpl");
	$o .= replace_macros($tpl, array(
		'$title' => t('View Contacts'),
		'$contacts' => $contacts,
		'$paginate' => paginate($a),
	));


	return $o;
}
