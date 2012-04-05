<?php


function lockview_content(&$a) {
  
	$type = (($a->argc > 1) ? $a->argv[1] : 0);
	if (is_numeric($type)) {
		$item_id = intval($type);
		$type='item';
	} else {
		$item_id = (($a->argc > 2) ? intval($a->argv[2]) : 0);
	}
  
	if(! $item_id)
		killme();

	if (!in_array($type, array('item','photo','event')))
		killme();
     
	$r = q("SELECT * FROM `%s` WHERE `id` = %d LIMIT 1",
		dbesc($type),
		intval($item_id)
	);
	if(! count($r))
		killme();
	$item = $r[0];
	if($item['uid'] != local_user())
		killme();


	$allowed_users = expand_acl($item['allow_cid']);
	$allowed_groups = expand_acl($item['allow_gid']);
	$deny_users = expand_acl($item['deny_cid']);
	$deny_groups = expand_acl($item['deny_gid']);

	if(($item['private']) && (! strlen($item['allow_cid'])) && (! strlen($item['allow_gid'])) 
		&& (! strlen($item['deny_cid'])) && (! strlen($item['deny_gid']))) {

		echo t('Remote privacy information not available.') . '<br />';
		killme();
	}

	$o = t('Visible to:') . '<br />';
	$l = array();

	if(count($allowed_groups)) {
		$r = q("SELECT `name` FROM `group` WHERE `id` IN ( %s )",
			dbesc(implode(', ', $allowed_groups))
		);
		if(count($r))
			foreach($r as $rr) 
				$l[] = '<b>' . $rr['name'] . '</b>';
	}
	if(count($allowed_users)) {
		$r = q("SELECT `name` FROM `contact` WHERE `id` IN ( %s )",
			dbesc(implode(', ',$allowed_users))
		);
		if(count($r))
			foreach($r as $rr) 
				$l[] = $rr['name'];

	}

	if(count($deny_groups)) {
		$r = q("SELECT `name` FROM `group` WHERE `id` IN ( %s )",
			dbesc(implode(', ', $deny_groups))
		);
		if(count($r))
			foreach($r as $rr) 
				$l[] = '<b><strike>' . $rr['name'] . '</strike></b>';
	}
	if(count($deny_users)) {
		$r = q("SELECT `name` FROM `contact` WHERE `id` IN ( %s )",
			dbesc(implode(', ',$deny_users))
		);
		if(count($r))
			foreach($r as $rr) 
				$l[] = '<strike>' . $rr['name'] . '</strike>';

	}

	echo $o . implode(', ', $l);
	killme();

}
