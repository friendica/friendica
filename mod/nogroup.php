<?php

require_once('include/Contact.php');
require_once('include/socgraph.php');
require_once('include/contact_selectors.php');

function nogroup_init(App &$a) {

	if (! local_user()) {
		return;
	}

	require_once('include/group.php');
	require_once('include/contact_widgets.php');

	if (! x($a->page,'aside')) {
		$a->page['aside'] = '';
	}

	$a->page['aside'] .= group_side('contacts','group','extended',0,$contact_id);
}


function nogroup_content(App &$a) {

	if (! local_user()) {
		notice( t('Permission denied.') . EOL);
		return '';
	}

	require_once('include/Contact.php');
	$r = contacts_not_grouped(local_user());
	if (dbm::is_result($r)) {
		$a->set_pager_total($r[0]['total']);
	}
	$r = contacts_not_grouped(local_user(),$a->pager['start'],$a->pager['itemspage']);
	if (dbm::is_result($r)) {
		foreach($r as $rr) {

			$contact_details = get_contact_details_by_url($rr['url'], local_user(), $rr);

			$contacts[] = array(
				'img_hover' => sprintf(t('Visit %s\'s profile [%s]'), $contact_details['name'], $rr['url']),
				'edit_hover' => t('Edit contact'),
				'photo_menu' => contact_photo_menu($rr),
				'id' => $rr['id'],
				'alt_text' => $alt_text,
				'dir_icon' => $dir_icon,
				'thumb' => proxy_url($contact_details['thumb'], false, PROXY_SIZE_THUMB),
				'name' => $contact_details['name'],
				'username' => $contact_details['name'],
				'details'       => $contact_details['location'],
				'tags'          => $contact_details['keywords'],
				'about'         => $contact_details['about'],
				'sparkle' => $sparkle,
				'itemurl' => (($contact_details['addr'] != "") ? $contact_details['addr'] : $rr['url']),
				'url' => $url,
				'network' => network_to_name($rr['network'], $url),
			);
		}
	}

	$tpl = get_markup_template("nogroup-template.tpl");
	$o .= replace_macros($tpl, array(
		'$header' => t('Contacts who are not members of a group'),
		'$contacts' => $contacts,
		'$paginate' => paginate($a),
	));

	return $o;

}
