<?php


function display_init(&$a) {

	if((get_config('system','block_public')) && (! local_user()) && (! remote_user())) {
		return;
	}

	$nick = (($a->argc > 1) ? $a->argv[1] : '');
	profile_load($a,$nick);

}


function display_content(&$a, $update = 0) {

	if((get_config('system','block_public')) && (! local_user()) && (! remote_user())) {
		notice( t('Public access denied.') . EOL);
		return;
	}

	require_once("include/bbcode.php");
	require_once('include/security.php');
	require_once('include/conversation.php');
	require_once('include/acl_selectors.php');


	$o = '';

	$a->page['htmlhead'] .= replace_macros(get_markup_template('display-head.tpl'), array());


	if($update) {
		$nick = $_REQUEST['nick'];
	}
	else {
		$nick = (($a->argc > 1) ? $a->argv[1] : '');
	}

	if($update) {
		$item_id = $_REQUEST['item_id'];
		$a->profile = array('uid' => intval($update), 'profile_uid' => intval($update));
	}
	else {
		$item_id = (($a->argc > 2) ? intval($a->argv[2]) : 0);
	}

	if(! $item_id) {
		$a->error = 404;
		notice( t('Item not found.') . EOL);
		return;
	}

	$groups = array();

	$contact = null;
	$remote_contact = false;

	$contact_id = 0;

	if(is_array($_SESSION['remote'])) {
		foreach($_SESSION['remote'] as $v) {
			if($v['uid'] == $a->profile['uid']) {
				$contact_id = $v['cid'];
				break;
			}
		}
	}

	if($contact_id) {
		$groups = init_groups_visitor($contact_id);
		$r = q("SELECT * FROM `contact` WHERE `id` = %d AND `uid` = %d LIMIT 1",
			intval($contact_id),
			intval($a->profile['uid'])
		);
		if(count($r)) {
			$contact = $r[0];
			$remote_contact = true;
		}
	}

	if(! $remote_contact) {
		if(local_user()) {
			$contact_id = $_SESSION['cid'];
			$contact = $a->contact;
		}
	}

	$r = q("SELECT * FROM `contact` WHERE `uid` = %d AND `self` = 1 LIMIT 1",
		intval($a->profile['uid'])
	);
	if(count($r))
		$a->page_contact = $r[0];

	$is_owner = ((local_user()) && (local_user() == $a->profile['profile_uid']) ? true : false);

	if($a->profile['hidewall'] && (! $is_owner) && (! $remote_contact)) {
		notice( t('Access to this profile has been restricted.') . EOL);
		return;
	}
	
	if ($is_owner) {
		$celeb = ((($a->user['page-flags'] == PAGE_SOAPBOX) || ($a->user['page-flags'] == PAGE_COMMUNITY)) ? true : false);

		$x = array(
			'is_owner' => true,
			'allow_location' => $a->user['allow_location'],
			'default_location' => $a->user['default-location'],
			'nickname' => $a->user['nickname'],
			'lockstate' => ( (is_array($a->user)) && ((strlen($a->user['allow_cid'])) || (strlen($a->user['allow_gid'])) || (strlen($a->user['deny_cid'])) || (strlen($a->user['deny_gid']))) ? 'lock' : 'unlock'),
			'acl' => populate_acl($a->user, $celeb),
			'bang' => '',
			'visitor' => 'block',
			'profile_uid' => local_user(),
			'acl_data' => construct_acl_data($a, $a->user), // For non-Javascript ACL selector
		);
		$o .= status_editor($a,$x,0,true);
	}

	$sql_extra = item_permissions_sql($a->profile['uid'],$remote_contact,$groups);

	if($update) {

dbg(1);
		$r = q("SELECT id FROM item WHERE item.uid = %d
		        AND `item`.`parent` = ( SELECT `parent` FROM `item` WHERE ( `id` = '%s' OR `uri` = '%s' ))
		        $sql_extra AND unseen = 1",
		        intval($a->profile['uid']),
		        dbesc($item_id),
		        dbesc($item_id) 
		);
dbg(0);
		if(!$r)
			return '';
	}

	$r = q("SELECT `item`.*, `item`.`id` AS `item_id`, 
		`contact`.`name`, `contact`.`photo`, `contact`.`url`, `contact`.`rel`,
		`contact`.`network`, `contact`.`thumb`, `contact`.`self`, `contact`.`writable`, 
		`contact`.`id` AS `cid`, `contact`.`uid` AS `contact-uid`
		FROM `item` LEFT JOIN `contact` ON `contact`.`id` = `item`.`contact-id`
		WHERE `item`.`uid` = %d AND `item`.`visible` = 1 AND `item`.`deleted` = 0
		and `item`.`moderated` = 0
		AND `contact`.`blocked` = 0 AND `contact`.`pending` = 0
		AND `item`.`parent` = ( SELECT `parent` FROM `item` WHERE ( `id` = '%s' OR `uri` = '%s' ))
		$sql_extra
		ORDER BY `parent` DESC, `gravity` ASC, `id` ASC ",
		intval($a->profile['uid']),
		dbesc($item_id),
		dbesc($item_id)
	);


	if(count($r)) {

		if((local_user()) && (local_user() == $a->profile['uid'])) {
			q("UPDATE `item` SET `unseen` = 0 
				WHERE `parent` = %d AND `unseen` = 1",
				intval($r[0]['parent'])
			);
		}

		$items = conv_sort($r,"`commented`");

		if(!$update)
			$o .= "<script> var netargs = '?f=&nick=" . $nick . "&item_id=" . $item_id . "'; </script>";
		$o .= conversation($a,$items,'display', $update);

	}
	else {
		$r = q("SELECT `id`,`deleted` FROM `item` WHERE `id` = '%s' OR `uri` = '%s' LIMIT 1",
			dbesc($item_id),
			dbesc($item_id)
		);
		if(count($r)) {
			if($r[0]['deleted']) {
				notice( t('Item has been removed.') . EOL );
			}
			else {	
				notice( t('Permission denied.') . EOL ); 
			}
		}
		else {
			notice( t('Item not found.') . EOL );
		}

	}

	return $o;
}

