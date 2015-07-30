<?php

require_once('include/Contact.php');
require_once('include/socgraph.php');
require_once('include/contact_selectors.php');

function nogroup_init(&$a) {

	if(! local_user())
		return;

	require_once('include/group.php');
	require_once('include/contact_widgets.php');

	if(! x($a->page,'aside'))
		$a->page['aside'] = '';

	$a->page['aside'] .= group_side('contacts','group',false,0,$contact_id);
}


function nogroup_content(&$a) {

	if(! local_user()) {
		notice( t('Permission denied.') . EOL);
		return '';
	}

	require_once('include/Contact.php');
	$r = contacts_not_grouped(local_user());
	if(count($r)) {
		$a->set_pager_total($r[0]['total']);
	}
	$r = contacts_not_grouped(local_user(),$a->pager['start'],$a->pager['itemspage']);
	if(count($r)) {
		foreach($r as $rr) {


			$contacts[] = array(
				'img_hover' => sprintf( t('Visit %s\'s profile [%s]'),$rr['name'],$rr['url']),
				'edit_hover' => t('Edit contact'),
				'photo_menu' => contact_photo_menu($rr),
				'id' => $rr['id'],
				'alt_text' => $alt_text,
				'dir_icon' => $dir_icon,
				'thumb' => $rr['thumb'],
				'name' => $rr['name'],
				'username' => $rr['name'],
				'sparkle' => $sparkle,
				'itemurl' => $rr['url'],
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
