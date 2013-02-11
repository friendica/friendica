<?php

require_once("include/contact_selectors.php");

/**
 * 
 */

/**
 * @package acl_selectors 
 */
function group_select($selname,$selclass,$preselected = false,$size = 4) {

	$a = get_app();

	$o = '';

	$o .= "<select name=\"{$selname}[]\" id=\"$selclass\" class=\"$selclass\" multiple=\"multiple\" size=\"$size\" >\r\n";

	$r = q("SELECT * FROM `group` WHERE `deleted` = 0 AND `uid` = %d ORDER BY `name` ASC",
		intval(local_user())
	);


	$arr = array('group' => $r, 'entry' => $o);

	// e.g. 'network_pre_group_deny', 'profile_pre_group_allow'

	call_hooks($a->module . '_pre_' . $selname, $arr);

	if(count($r)) {
		foreach($r as $rr) {
			if((is_array($preselected)) && in_array($rr['id'], $preselected))
				$selected = " selected=\"selected\" ";
			else
				$selected = '';
			$trimmed = mb_substr($rr['name'],0,12);

			$o .= "<option value=\"{$rr['id']}\" $selected title=\"{$rr['name']}\" >$trimmed</option>\r\n";
		}
	
	}
	$o .= "</select>\r\n";

	call_hooks($a->module . '_post_' . $selname, $o);


	return $o;
}


function contact_selector($selname, $selclass, $preselected = false, $options) {

	$a = get_app();

	$mutual = false;
	$networks = null;
	$single = false;
	$exclude = false;
	$size = 4;

	if(is_array($options)) {
		if(x($options,'size'))
			$size = $options['size'];

		if(x($options,'mutual_friends'))
			$mutual = true;
		if(x($options,'single'))
			$single = true;
		if(x($options,'multiple'))
			$single = false;
		if(x($options,'exclude'))
			$exclude = $options['exclude'];

		if(x($options,'networks')) {
			switch($options['networks']) {
				case 'DFRN_ONLY':
					$networks = array('dfrn');
					break;
				case 'PRIVATE':
					if(is_array($a->user) && $a->user['prvnets'])
						$networks = array('dfrn','mail','dspr');
					else
						$networks = array('dfrn','face','mail', 'dspr');
					break;
				case 'TWO_WAY':
					if(is_array($a->user) && $a->user['prvnets'])
						$networks = array('dfrn','mail','dspr');
					else
						$networks = array('dfrn','face','mail','dspr','stat');
					break;					
				default:
					break;
			}
		}
	}
		
	$x = array('options' => $options, 'size' => $size, 'single' => $single, 'mutual' => $mutual, 'exclude' => $exclude, 'networks' => $networks);

	call_hooks('contact_select_options', $x);

	$o = '';

	$sql_extra = '';

	if($x['mutual']) {
		$sql_extra .= sprintf(" AND `rel` = %d ", intval(CONTACT_IS_FRIEND));
	}

	if(intval($x['exclude']))
		$sql_extra .= sprintf(" AND `id` != %d ", intval($x['exclude']));

	if(is_array($x['networks']) && count($x['networks'])) {
		for($y = 0; $y < count($x['networks']) ; $y ++)
			$x['networks'][$y] = "'" . dbesc($x['networks'][$y]) . "'";
		$str_nets = implode(',',$x['networks']);
		$sql_extra .= " AND `network` IN ( $str_nets ) ";
	}
	
	$tabindex = (x($options, 'tabindex') ? "tabindex=\"" . $options["tabindex"] . "\"" : "");

	if($x['single'])
		$o .= "<select name=\"$selname\" id=\"$selclass\" class=\"$selclass\" size=\"" . $x['size'] . "\" $tabindex >\r\n";
	else 
		$o .= "<select name=\"{$selname}[]\" id=\"$selclass\" class=\"$selclass\" multiple=\"multiple\" size=\"" . $x['size'] . "$\" $tabindex >\r\n";

	$r = q("SELECT `id`, `name`, `url`, `network` FROM `contact` 
		WHERE `uid` = %d AND `self` = 0 AND `blocked` = 0 AND `pending` = 0 AND `archive` = 0 AND `notify` != ''
		$sql_extra
		ORDER BY `name` ASC ",
		intval(local_user())
	);


	$arr = array('contact' => $r, 'entry' => $o);

	// e.g. 'network_pre_contact_deny', 'profile_pre_contact_allow'

	call_hooks($a->module . '_pre_' . $selname, $arr);

	if(count($r)) {
		foreach($r as $rr) {
			if((is_array($preselected)) && in_array($rr['id'], $preselected))
				$selected = " selected=\"selected\" ";
			else
				$selected = '';

			$trimmed = mb_substr($rr['name'],0,20);

			$o .= "<option value=\"{$rr['id']}\" $selected title=\"{$rr['name']}|{$rr['url']}\" >$trimmed</option>\r\n";
		}
	
	}

	$o .= "</select>\r\n";

	call_hooks($a->module . '_post_' . $selname, $o);

	return $o;
}



function contact_select($selname, $selclass, $preselected = false, $size = 4, $privmail = false, $celeb = false, $privatenet = false, $tabindex = null) {

	$a = get_app();

	$o = '';

	// When used for private messages, we limit correspondence to mutual DFRN/Friendica friends and the selector
	// to one recipient. By default our selector allows multiple selects amongst all contacts.

	$sql_extra = '';

	if($privmail || $celeb) {
		$sql_extra .= sprintf(" AND `rel` = %d ", intval(CONTACT_IS_FRIEND));
	}

	if($privmail) {
		$sql_extra .= " AND `network` IN ( 'dfrn', 'dspr' ) ";
	}
	elseif($privatenet) {	
		$sql_extra .= " AND `network` IN ( 'dfrn', 'mail', 'face', 'dspr' ) ";
	}

	$tabindex = ($tabindex > 0 ? "tabindex=\"$tabindex\"" : "");

	if($privmail)
		$o .= "<select name=\"$selname\" id=\"$selclass\" class=\"$selclass\" size=\"$size\" $tabindex >\r\n";
	else 
		$o .= "<select name=\"{$selname}[]\" id=\"$selclass\" class=\"$selclass\" multiple=\"multiple\" size=\"$size\" $tabindex >\r\n";

	$r = q("SELECT `id`, `name`, `url`, `network` FROM `contact` 
		WHERE `uid` = %d AND `self` = 0 AND `blocked` = 0 AND `pending` = 0 AND `archive` = 0 AND `notify` != ''
		$sql_extra
		ORDER BY `name` ASC ",
		intval(local_user())
	);


	$arr = array('contact' => $r, 'entry' => $o);

	// e.g. 'network_pre_contact_deny', 'profile_pre_contact_allow'

	call_hooks($a->module . '_pre_' . $selname, $arr);

	if(count($r)) {
		foreach($r as $rr) {
			if((is_array($preselected)) && in_array($rr['id'], $preselected))
				$selected = " selected=\"selected\" ";
			else
				$selected = '';

			$trimmed = mb_substr($rr['name'],0,20);

			$o .= "<option value=\"{$rr['id']}\" $selected title=\"{$rr['name']}|{$rr['url']}\" >$trimmed</option>\r\n";
		}
	
	}

	$o .= "</select>\r\n";

	call_hooks($a->module . '_post_' . $selname, $o);

	return $o;
}


function fixacl(&$item) {
	$item = intval(str_replace(array('<','>'),array('',''),$item));
}

function prune_deadguys($arr) {

	if(! $arr)
		return $arr;
	$str = dbesc(implode(',',$arr));
	$r = q("select id from contact where id in ( " . $str . ") and blocked = 0 and pending = 0 and archive = 0 ");
	if($r) {
		$ret = array();
		foreach($r as $rr) 
			$ret[] = intval($rr['id']);
		return $ret;
	}
	return array();
}


function get_acl_permissions($user = null) {
	$allow_cid = $allow_gid = $deny_cid = $deny_gid = false;

	if(is_array($user)) {
		$allow_cid = ((strlen($user['allow_cid'])) 
			? explode('><', $user['allow_cid']) : array() );
		$allow_gid = ((strlen($user['allow_gid']))
			? explode('><', $user['allow_gid']) : array() );
		$deny_cid  = ((strlen($user['deny_cid']))
			? explode('><', $user['deny_cid']) : array() );
		$deny_gid  = ((strlen($user['deny_gid']))
			? explode('><', $user['deny_gid']) : array() );
		array_walk($allow_cid,'fixacl');
		array_walk($allow_gid,'fixacl');
		array_walk($deny_cid,'fixacl');
		array_walk($deny_gid,'fixacl');
	}

	$allow_cid = prune_deadguys($allow_cid);

	return array(
		'allow_cid' => $allow_cid,
		'allow_gid' => $allow_gid,
		'deny_cid' => $deny_cid,
		'deny_gid' => $deny_gid,
	);
}


function populate_acl($user = null,$celeb = false) {

	$perms = get_acl_permissions($user);

	// We shouldn't need to prune deadguys from the block list. Either way they can't get the message.
	// Also no point enumerating groups and checking them, that will take place on delivery.

//	$deny_cid = prune_deadguys($deny_cid);


	/*$o = '';
	$o .= '<div id="acl-wrapper">';
	$o .= '<div id="acl-permit-outer-wrapper">';
	$o .= '<div id="acl-permit-text">' . t('Visible To:') . '</div><div id="jot-public">' . t('everybody') . '</div>';
	$o .= '<div id="acl-permit-text-end"></div>';
	$o .= '<div id="acl-permit-wrapper">';
	$o .= '<div id="group_allow_wrapper">';
	$o .= '<label id="acl-allow-group-label" for="group_allow" >' . t('Groups') . '</label>';
	$o .= group_select('group_allow','group_allow',$allow_gid);
	$o .= '</div>';
	$o .= '<div id="contact_allow_wrapper">';
	$o .= '<label id="acl-allow-contact-label" for="contact_allow" >' . t('Contacts') . '</label>';
	$o .= contact_select('contact_allow','contact_allow',$allow_cid,4,false,$celeb,true);
	$o .= '</div>';
	$o .= '</div>' . "\r\n";
	$o .= '<div id="acl-allow-end"></div>' . "\r\n";
	$o .= '</div>';
	$o .= '<div id="acl-deny-outer-wrapper">';
	$o .= '<div id="acl-deny-text">' . t('Except For:') . '</div>';
	$o .= '<div id="acl-deny-text-end"></div>';
	$o .= '<div id="acl-deny-wrapper">';
	$o .= '<div id="group_deny_wrapper" >';
	$o .= '<label id="acl-deny-group-label" for="group_deny" >' . t('Groups') . '</label>';
	$o .= group_select('group_deny','group_deny', $deny_gid);
	$o .= '</div>';
	$o .= '<div id="contact_deny_wrapper" >';
	$o .= '<label id="acl-deny-contact-label" for="contact_deny" >' . t('Contacts') . '</label>';
	$o .= contact_select('contact_deny','contact_deny', $deny_cid,4,false, $celeb,true);
	$o .= '</div>';
	$o .= '</div>' . "\r\n";
	$o .= '<div id="acl-deny-end"></div>' . "\r\n";
	$o .= '</div>';
	$o .= '</div>' . "\r\n";
	$o .= '<div id="acl-wrapper-end"></div>' . "\r\n";*/
	
	$tpl = get_markup_template("acl_selector.tpl");
	$o = replace_macros($tpl, array(
		'$showall'=> t("Visible to everybody"),
		'$show'		 => t("show"),
		'$hide'		 => t("don't show"),
		'$allowcid' => json_encode($perms['allow_cid']),
		'$allowgid' => json_encode($perms['allow_gid']),
		'$denycid' => json_encode($perms['deny_cid']),
		'$denygid' => json_encode($perms['deny_gid']),
	));
	
	
	return $o;

}

function construct_acl_data(&$a, $user) {

	// Get group and contact information for html ACL selector
	$acl_data = acl_lookup($a, 'html');

	$user_defaults = get_acl_permissions($user);

	if($acl_data['groups']) {
		foreach($acl_data['groups'] as $key=>$group) {
			// Add a "selected" flag to groups that are posted to by default
			if($user_defaults['allow_gid'] &&
			   in_array($group['id'], $user_defaults['allow_gid']) && !in_array($group['id'], $user_defaults['deny_gid']) )
				$acl_data['groups'][$key]['selected'] = 1;
			else
				$acl_data['groups'][$key]['selected'] = 0;
		}
	}
	if($acl_data['contacts']) {
		foreach($acl_data['contacts'] as $key=>$contact) {
			// Add a "selected" flag to groups that are posted to by default
			if($user_defaults['allow_cid'] &&
			   in_array($contact['id'], $user_defaults['allow_cid']) && !in_array($contact['id'], $user_defaults['deny_cid']) )
				$acl_data['contacts'][$key]['selected'] = 1;
			else
				$acl_data['contacts'][$key]['selected'] = 0;
		}
	}

	return $acl_data;

}

function acl_lookup(&$a, $out_type = 'json') {

	if(!local_user())
		return "";


	$start = (x($_REQUEST,'start')?$_REQUEST['start']:0);
	$count = (x($_REQUEST,'count')?$_REQUEST['count']:100);
	$search = (x($_REQUEST,'search')?$_REQUEST['search']:"");
	$type = (x($_REQUEST,'type')?$_REQUEST['type']:"");
	

	// For use with jquery.autocomplete for private mail completion

	if(x($_REQUEST,'query') && strlen($_REQUEST['query'])) {
		if(! $type)
			$type = 'm';
		$search = $_REQUEST['query'];
	}


	if ($search!=""){
		$sql_extra = "AND `name` LIKE '%%".dbesc($search)."%%'";
		$sql_extra2 = "AND (`attag` LIKE '%%".dbesc($search)."%%' OR `name` LIKE '%%".dbesc($search)."%%' OR `nick` LIKE '%%".dbesc($search)."%%')";
	} else {
		$sql_extra = $sql_extra2 = "";
	}
	
	// count groups and contacts
	if ($type=='' || $type=='g'){
		$r = q("SELECT COUNT(`id`) AS g FROM `group` WHERE `deleted` = 0 AND `uid` = %d $sql_extra",
			intval(local_user())
		);
		$group_count = (int)$r[0]['g'];
	} else {
		$group_count = 0;
	}
	
	if ($type=='' || $type=='c'){
		$r = q("SELECT COUNT(`id`) AS c FROM `contact` 
				WHERE `uid` = %d AND `self` = 0 
				AND `blocked` = 0 AND `pending` = 0 AND `archive` = 0
				AND `notify` != '' $sql_extra2" ,
			intval(local_user())
		);
		$contact_count = (int)$r[0]['c'];
	} 
	elseif ($type == 'm') {

		// autocomplete for Private Messages

		$r = q("SELECT COUNT(`id`) AS c FROM `contact` 
				WHERE `uid` = %d AND `self` = 0 
				AND `blocked` = 0 AND `pending` = 0 AND `archive` = 0 
				AND `network` IN ('%s','%s','%s') $sql_extra2" ,
			intval(local_user()),
			dbesc(NETWORK_DFRN),
			dbesc(NETWORK_ZOT),
			dbesc(NETWORK_DIASPORA)
		);
		$contact_count = (int)$r[0]['c'];

	}
	elseif ($type == 'a') {

		// autocomplete for Contacts

		$r = q("SELECT COUNT(`id`) AS c FROM `contact` 
				WHERE `uid` = %d AND `self` = 0 
				AND `pending` = 0 $sql_extra2" ,
			intval(local_user())
		);
		$contact_count = (int)$r[0]['c'];

	} else {
		$contact_count = 0;
	}
	
	$tot = $group_count+$contact_count;
	
	$groups = array();
	$contacts = array();
	
	if ($type=='' || $type=='g'){
		
		$r = q("SELECT `group`.`id`, `group`.`name`, GROUP_CONCAT(DISTINCT `group_member`.`contact-id` SEPARATOR ',') as uids
				FROM `group`,`group_member` 
				WHERE `group`.`deleted` = 0 AND `group`.`uid` = %d 
					AND `group_member`.`gid`=`group`.`id`
					$sql_extra
				GROUP BY `group`.`id`
				ORDER BY `group`.`name` 
				LIMIT %d,%d",
			intval(local_user()),
			intval($start),
			intval($count)
		);

		foreach($r as $g){
//		logger('acl: group: ' . $g['name'] . ' members: ' . $g['uids']);		
			$groups[] = array(
				"type"  => "g",
				"photo" => "images/twopeople.png",
				"name"  => $g['name'],
				"id"	=> intval($g['id']),
				"uids"  => array_map("intval", explode(",",$g['uids'])),
				"link"  => ''
			);
		}
	}
	
	if ($type=='' || $type=='c'){
	
		$r = q("SELECT `id`, `name`, `nick`, `micro`, `network`, `url`, `attag` FROM `contact` 
			WHERE `uid` = %d AND `self` = 0 AND `blocked` = 0 AND `pending` = 0 AND `archive` = 0 AND `notify` != ''
			$sql_extra2
			ORDER BY `name` ASC ",
			intval(local_user())
		);
	}
	elseif($type == 'm') {
		$r = q("SELECT `id`, `name`, `nick`, `micro`, `network`, `url`, `attag` FROM `contact` 
			WHERE `uid` = %d AND `self` = 0 AND `blocked` = 0 AND `pending` = 0 AND `archive` = 0
			AND `network` IN ('%s','%s','%s')
			$sql_extra2
			ORDER BY `name` ASC ",
			intval(local_user()),
			dbesc(NETWORK_DFRN),
			dbesc(NETWORK_ZOT),
			dbesc(NETWORK_DIASPORA)
		);
	}
	elseif($type == 'a') {
		$r = q("SELECT `id`, `name`, `nick`, `micro`, `network`, `url`, `attag` FROM `contact` 
			WHERE `uid` = %d AND `pending` = 0
			$sql_extra2
			ORDER BY `name` ASC ",
			intval(local_user())
		);
	}
	else
		$r = array();


	if($type == 'm' || $type == 'a') {
		$x = array();
		$x['query'] = $search;
		$x['photos'] = array();
		$x['links'] = array();
		$x['suggestions'] = array();
		$x['data'] = array();
		if(count($r)) {
			foreach($r as $g) {
				$x['photos'][] = $g['micro'];
				$x['links'][] = $g['url'];
				$x['suggestions'][] = $g['name'];
				$x['data'][] = intval($g['id']);
			}
		}
		echo json_encode($x);
		killme();
	}

	if(count($r)) {
		foreach($r as $g){
			$contacts[] = array(
				"type"  => "c",
				"photo" => $g['micro'],
				"name"  => $g['name'],
				"id"	=> intval($g['id']),
				"network" => $g['network'],
				"link" => $g['url'],
				"nick" => ($g['attag']) ? $g['attag'] : $g['nick'],
			);
		}			
	}
		
	$items = array_merge($groups, $contacts);


	if($out_type === 'html') {
		$o = array(
			'tot'		=> $tot,
			'start'	=> $start,
			'count'	=> $count,
			'groups'	=> $groups,
			'contacts'	=> $contacts,
		);
		return $o;
	}
	
	$o = array(
		'tot'	=> $tot,
		'start' => $start,
		'count'	=> $count,
		'items'	=> $items,
	);
	
	echo json_encode($o);

	killme();
}

