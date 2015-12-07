<?php

require_once('include/socgraph.php');
require_once('include/Contact.php');
require_once('include/contact_selectors.php');

function common_content(&$a) {

	$o = '';

	$cmd = $a->argv[1];
	$uid = intval($a->argv[2]);
	$cid = intval($a->argv[3]);
	$zcid = 0;

	if (! local_user()) {
		notice( t('Permission denied.') . EOL);
		return;
	}

	if($cmd !== 'loc' && $cmd != 'rem')
		return;

	if(! $uid)
		return;

	if($cmd === 'loc' && $cid) {
		$c = q("SELECT `name`, `url`, `photo` FROM `contact` WHERE `id` = %d AND `uid` = %d LIMIT 1",
			intval($cid),
			intval($uid)
		);
	}
	else {
		$c = q("SELECT `name`, `url`, `photo` FROM `contact` WHERE `self` = 1 AND `uid` = %d LIMIT 1",
			intval($uid)
		);
	}

	$vcard_widget .= replace_macros(get_markup_template("vcard-widget.tpl"),array(
		'$name' => htmlentities($c[0]['name']),
		'$photo' => $c[0]['photo'],
		'url' => z_root() . '/contacts/' . $cid
	));

	if(! x($a->page,'aside'))
		$a->page['aside'] = '';
	$a->page['aside'] .= $vcard_widget;

	if(! count($c))
		return;

	if(! $cid) {
		if(get_my_url()) {
			$r = q("SELECT `id` FROM `contact` WHERE `nurl` = '%s' AND `uid` = %d LIMIT 1",
				dbesc(normalise_link(get_my_url())),
				intval($profile_uid)
			);
			if(count($r))
				$cid = $r[0]['id'];
			else {
				$r = q("SELECT `id` FROM `gcontact` WHERE `nurl` = '%s' LIMIT 1",
					dbesc(normalise_link(get_my_url()))
				);
				if(count($r))
					$zcid = $r[0]['id'];
			}
		}
	}



	if($cid == 0 && $zcid == 0)
		return;


	if($cid)
		$t = count_common_friends($uid,$cid);
	else
		$t = count_common_friends_zcid($uid,$zcid);


	$a->set_pager_total($t);

	if(! $t) {
		notice( t('No contacts in common.') . EOL);
		return $o;
	}


	if($cid)
		$r = common_friends($uid,$cid);
	else
		$r = common_friends_zcid($uid,$zcid);


	if(! count($r)) {
		return $o;
	}

	$id = 0;

	foreach($r as $rr) {

		//get further details of the contact
		$contact_details = get_contact_details_by_url($rr['url'], $uid);

		// $rr[id] is needed to use contact_photo_menu()
		$rr[id] = $rr[cid];

		$photo_menu = '';
		$photo_menu = contact_photo_menu ($rr);

		$entry = array(
			'url'		=> $rr['url'],
			'itemurl'	=> (($contact_details['addr'] != "") ? $contact_details['addr'] : $rr['url']),
			'name'		=> $rr['name'],
			'thumb'		=> proxy_url($rr['photo'], false, PROXY_SIZE_THUMB),
			'img_hover'	=> htmlentities($rr['name']),
			'details'	=> $contact_details['location'],
			'tags'		=> $contact_details['keywords'],
			'about'		=> $contact_details['about'],
			'account_type'	=> (($contact_details['community']) ? t('Forum') : ''),
			'network'	=> network_to_name($contact_details['network'], $contact_details['url']),
			'photo_menu'	=> $photo_menu,
			'id'		=> ++$id,
		);
		$entries[] = $entry;
	}

	$tpl = get_markup_template('viewcontact_template.tpl');

	$o .= replace_macros($tpl,array(
		'$title' => t('Common Friends'),
		'$contacts' => $entries,
	));

//	$o .= paginate($a);
	return $o;
}
