<?php

function follow_widget() {

	return replace_macros(get_markup_template('follow.tpl'),array(
		'$connect' => t('Add New Contact'),
		'$desc' => t('Enter address or web location'),
		'$hint' => t('Example: bob@example.com, http://example.com/barbara'),
		'$follow' => t('Connect')
	));

}

function findpeople_widget() {

	$a = get_app();

	$inv = (($a->config['register_policy'] != REGISTER_CLOSED) ? t('Invite Friends') : '');

	if(get_config('system','invitation_only')) {
		$x = get_pconfig(local_user(),'system','invites_remaining');
		if($x || is_site_admin()) {
			$a->page['aside'] .= '<div class="side-link" id="side-invite-remain">' 
			. sprintf( tt('%d invitation available','%d invitations available',$x), $x) 
			. '</div>' . $inv;
		}
	}
 
	return replace_macros(get_markup_template('peoplefind.tpl'),array(
		'$findpeople' => t('Find People'),
		'$desc' => t('Enter name or interest'),
		'$label' => t('Connect/Follow'),
		'$hint' => t('Examples: Robert Morgenstein, Fishing'),
		'$findthem' => t('Find'),
		'$suggest' => t('Friend Suggestions'),
		'$similar' => t('Similar Interests'),
		'$inv' => $inv
	));

}


function networks_widget($baseurl,$selected = '') {

	$a = get_app();

	if(! local_user())
		return '';

	
	$r = q("select distinct(network) from contact where uid = %d",
		intval(local_user())
	);

	$nets = array();
	if(count($r)) {
		require_once('include/contact_selectors.php');
		foreach($r as $rr) {
				if($rr['network'])
					$nets[] = array('ref' => $rr['network'], 'name' => network_to_name($rr['network']), 'selected' => (($selected == $rr['network']) ? 'selected' : '' ));
		}
	}

	if(count($nets) < 2)
		return '';

	return replace_macros(get_markup_template('nets.tpl'),array(
		'$title' => t('Networks'),
		'$desc' => '',
		'$sel_all' => (($selected == '') ? 'selected' : ''),
		'$all' => t('All Networks'),
		'$nets' => $nets,
		'$base' => $baseurl,

	));
}

function fileas_widget($baseurl,$selected = '') {
	$a = get_app();
	if(! local_user())
		return '';

	$saved = get_pconfig(local_user(),'system','filetags');
	if(! strlen($saved))
		return;

	$matches = false;
	$terms = array();
    $cnt = preg_match_all('/\[(.*?)\]/',$saved,$matches,PREG_SET_ORDER);
    if($cnt) {
		foreach($matches as $mtch) {
			$unescaped = file_tag_decode($mtch[1]);
			$terms[] = array('name' => $unescaped,'selected' => (($selected == $unescaped) ? 'selected' : ''));
		}
	}

	return replace_macros(get_markup_template('fileas_widget.tpl'),array(
		'$title' => t('File Selections'),
		'$desc' => '',
		'$sel_all' => (($selected == '') ? 'selected' : ''),
		'$all' => t('Everything'),
		'$terms' => $terms,
		'$base' => $baseurl,

	));
}

