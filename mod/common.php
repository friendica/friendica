<?php

require_once('include/socgraph.php');
require_once('include/Contact.php');
require_once('include/contact_selectors.php');
require_once('mod/contacts.php');

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

	if ($cmd !== 'loc' && $cmd != 'rem') {
		return;
	}

	if (! $uid) {
		return;
	}

	if ($cmd === 'loc' && $cid) {
		$c = q("SELECT `name`, `url`, `photo` FROM `contact` WHERE `id` = %d AND `uid` = %d LIMIT 1",
			intval($cid),
			intval($uid)
		);
		/// @TODO Handle $c with dbm::is_result()
		$a->page['aside'] = "";
		profile_load($a, "", 0, get_contact_details_by_url($c[0]["url"]));
	} else {
		$c = q("SELECT `name`, `url`, `photo` FROM `contact` WHERE `self` = 1 AND `uid` = %d LIMIT 1",
			intval($uid)
		);
		/// @TODO Handle $c with dbm::is_result()

		$vcard_widget .= replace_macros(get_markup_template("vcard-widget.tpl"),array(
			'$name' => htmlentities($c[0]['name']),
			'$photo' => $c[0]['photo'],
			'url' => 'contacts/' . $cid
		));

		if (! x($a->page,'aside')) {
			$a->page['aside'] = '';
		}
		$a->page['aside'] .= $vcard_widget;
	}

	if(! count($c))
		return;

	if(! $cid) {
		if(get_my_url()) {
			$r = q("SELECT `id` FROM `contact` WHERE `nurl` = '%s' AND `uid` = %d LIMIT 1",
				dbesc(normalise_link(get_my_url())),
				intval($profile_uid)
			);
			if (dbm::is_result($r))
				$cid = $r[0]['id'];
			else {
				$r = q("SELECT `id` FROM `gcontact` WHERE `nurl` = '%s' LIMIT 1",
					dbesc(normalise_link(get_my_url()))
				);
				if (dbm::is_result($r))
					$zcid = $r[0]['id'];
			}
		}
	}

	if ($cid == 0 && $zcid == 0) {
		return;
	}

	if ($cid) {
		$t = count_common_friends($uid, $cid);
	} else {
		$t = count_common_friends_zcid($uid, $zcid);
	}

	if (count($t)) {
		$a->set_pager_total($t);
	} else {
		notice( t('No contacts in common.') . EOL);
		return $o;
	}


	if ($cid) {
		$r = common_friends($uid, $cid, $a->pager['start'], $a->pager['itemspage']);
	} else {
		$r = common_friends_zcid($uid, $zcid, $a->pager['start'], $a->pager['itemspage']);
	}


	if (! dbm::is_result($r)) {
		return $o;
	}

	$id = 0;

	foreach ($r as $rr) {

		//get further details of the contact
		$contact_details = get_contact_details_by_url($rr['url'], $uid);

		// $rr['id'] is needed to use contact_photo_menu()
		/// @TODO Adding '/" here avoids E_NOTICE on missing constants
		$rr['id'] = $rr['cid'];

		$photo_menu = '';
		$photo_menu = contact_photo_menu($rr);

		$entry = array(
			'url'          => $rr['url'],
			'itemurl'      => (($contact_details['addr'] != "") ? $contact_details['addr'] : $rr['url']),
			'name'         => $contact_details['name'],
			'thumb'        => proxy_url($contact_details['thumb'], false, PROXY_SIZE_THUMB),
			'img_hover'    => htmlentities($contact_details['name']),
			'details'      => $contact_details['location'],
			'tags'         => $contact_details['keywords'],
			'about'        => $contact_details['about'],
			'account_type' => account_type($contact_details),
			'network'      => network_to_name($contact_details['network'], $contact_details['url']),
			'photo_menu'   => $photo_menu,
			'id'           => ++$id,
		);
		$entries[] = $entry;
	}

	if ($cmd === 'loc' && $cid && $uid == local_user()) {
		$tab_str = contacts_tab($a, $cid, 4);
	} else {
		$title = t('Common Friends');
	}

	$tpl = get_markup_template('viewcontact_template.tpl');

	$o .= replace_macros($tpl,array(
		'$title'    => $title,
		'$tab_str'  => $tab_str,
		'$contacts' => $entries,
		'$paginate' => paginate($a),
	));

	return $o;
}
