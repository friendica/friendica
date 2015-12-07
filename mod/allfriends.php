<?php

require_once('include/socgraph.php');
require_once('include/Contact.php');
require_once('include/contact_selectors.php');

function allfriends_content(&$a) {

	$o = '';
	if(! local_user()) {
		notice( t('Permission denied.') . EOL);
		return;
	}

	if($a->argc > 1)
		$cid = intval($a->argv[1]);

	if(! $cid)
		return;

	$uid = $a->user[uid];

	$c = q("SELECT `name`, `url`, `photo` FROM `contact` WHERE `id` = %d AND `uid` = %d LIMIT 1",
		intval($cid),
		intval(local_user())
	);

	$vcard_widget .= replace_macros(get_markup_template("vcard-widget.tpl"),array(
		'$name'  => htmlentities($c[0]['name']),
		'$photo' => $c[0]['photo'],
		'url'    => z_root() . '/contacts/' . $cid
	));

	if(! x($a->page,'aside'))
		$a->page['aside'] = '';
	$a->page['aside'] .= $vcard_widget;

	if(! count($c))
		return;


	$r = all_friends(local_user(),$cid);

	if(! count($r)) {
		$o .= t('No friends to display.');
		return $o;
	}

	$id = 0;

	foreach($r as $rr) {

		//get further details of the contact
		$contact_details = get_contact_details_by_url($rr['url'], $uid);

		$photo_menu = '';

		// $rr[cid] is only available for common contacts. So if the contact is a common one, use contact_photo_menu to generate the photo_menu
		// If the contact is not common to the user, Connect/Follow' will be added to the photo menu
		if ($rr[cid]) {
			$rr[id] = $rr[cid];
			$photo_menu = contact_photo_menu ($rr);
		}
		else {
			$connlnk = $a->get_baseurl() . '/follow/?url=' . $rr['url'];
			$photo_menu = array(array(t("View Profile"), zrl($rr['url'])));
			$photo_menu[] = array(t("Connect/Follow"), $connlnk);
		}

		$entry = array(
			'url'		=> $rr['url'],
			'itemurl'	=> (($contact_details['addr'] != "") ? $contact_details['addr'] : $rr['url']),
			'name'		=> htmlentities($rr['name']),
			'thumb'		=> proxy_url($rr['photo'], false, PROXY_SIZE_THUMB),
			'img_hover'	=> htmlentities($rr['name']),
			'details'	=> $contact_details['location'],
			'tags'		=> $contact_details['keywords'],
			'about'		=> $contact_details['about'],
			'account_type'	=> (($contact_details['community']) ? t('Forum') : ''),
			'network'	=> network_to_name($contact_details['network'], $contact_details['url']),
			'photo_menu'	=> $photo_menu,
			'conntxt'	=> t('Connect'),
			'connlnk'	=> $connlnk,
			'id'		=> ++$id,
		);
		$entries[] = $entry;
	}

	$tpl = get_markup_template('viewcontact_template.tpl');

	$o .= replace_macros($tpl,array(
		'$title' => sprintf( t('Friends of %s'), htmlentities($c[0]['name'])),
		'$contacts' => $entries,
	));

//	$o .= paginate($a);
	return $o;
}
