<?php

function notes_init(&$a) {

	if(! local_user())
		return;

	$profile = 0;

	$which = $a->user['nickname'];

	nav_set_selected('home');

//	profile_load($a,$which,$profile);

}


function notes_content(&$a,$update = false) {

	if(! local_user()) {
		notice( t('Permission denied.') . EOL);
		return;
	}

	require_once("include/bbcode.php");
	require_once('include/security.php');
	require_once('include/conversation.php');
	require_once('include/acl_selectors.php');
	$groups = array();


	$o = '';

	$remote_contact = false;

	$contact_id = $_SESSION['cid'];
	$contact = $a->contact;

	$is_owner = true;

	$o ="";
	$o .= profile_tabs($a,True);

	if(! $update) {
		$o .= '<h3>' . t('Personal Notes') . '</h3>';

		$commpage = false;
		$commvisitor = false;

		$x = array(
			'is_owner' => $is_owner,
			'allow_location' => (($a->user['allow_location']) ? true : false),
			'default_location' => $a->user['default-location'],
			'nickname' => $a->user['nickname'],
			'lockstate' => 'lock',
			'acl' => '',
			'bang' => '',
			'visitor' => 'block',
			'profile_uid' => local_user(),
			'button' => t('Save'),
			'acl_data' => '',
		);

		$o .= status_editor($a,$x,$a->contact['id']);

	}

	// Construct permissions

	// default permissions - anonymous user
	
	$sql_extra = " AND `allow_cid` = '<" . $a->contact['id'] . ">' ";

	$r = q("SELECT COUNT(*) AS `total`
		FROM `item` LEFT JOIN `contact` ON `contact`.`id` = `item`.`contact-id`
		WHERE `item`.`uid` = %d AND `item`.`visible` = 1 and `item`.`moderated` = 0 
		AND `item`.`deleted` = 0 AND `item`.`type` = 'note'
		AND `contact`.`blocked` = 0 AND `contact`.`pending` = 0 AND `contact`.`self` = 1
		AND `item`.`id` = `item`.`parent` AND `item`.`wall` = 0
		$sql_extra ",
		intval(local_user())

	);

	if(count($r)) {
		$a->set_pager_total($r[0]['total']);
		$a->set_pager_itemspage(40);
	}

	$r = q("SELECT `item`.`id` AS `item_id`, `contact`.`uid` AS `contact-uid`
		FROM `item` LEFT JOIN `contact` ON `contact`.`id` = `item`.`contact-id`
		WHERE `item`.`uid` = %d AND `item`.`visible` = 1 AND `item`.`deleted` = 0 
		and `item`.`moderated` = 0 AND `item`.`type` = 'note'
		AND `contact`.`blocked` = 0 AND `contact`.`pending` = 0 AND `contact`.`self` = 1
		AND `item`.`id` = `item`.`parent` AND `item`.`wall` = 0
		$sql_extra
		ORDER BY `item`.`created` DESC LIMIT %d ,%d ",
		intval(local_user()),
		intval($a->pager['start']),
		intval($a->pager['itemspage'])

	);

	$parents_arr = array();
	$parents_str = '';

	if(count($r)) {
		foreach($r as $rr)
			$parents_arr[] = $rr['item_id'];
		$parents_str = implode(', ', $parents_arr);
 
		$r = q("SELECT `item`.*, `item`.`id` AS `item_id`, 
			`contact`.`name`, `contact`.`photo`, `contact`.`url`, `contact`.`alias`, `contact`.`network`, `contact`.`rel`, 
			`contact`.`thumb`, `contact`.`self`, `contact`.`writable`, 
			`contact`.`id` AS `cid`, `contact`.`uid` AS `contact-uid`
			FROM `item` LEFT JOIN `contact` ON `contact`.`id` = `item`.`contact-id`
			WHERE `item`.`uid` = %d AND `item`.`visible` = 1 and `item`.`moderated` = 0 AND `item`.`deleted` = 0
			AND `contact`.`blocked` = 0 AND `contact`.`pending` = 0
			AND `item`.`parent` IN ( %s )
			$sql_extra
			ORDER BY `parent` DESC, `gravity` ASC, `item`.`id` ASC ",
			intval(local_user()),
			dbesc($parents_str)
		);

		if(count($r)) {
			$items = conv_sort($r,"`commented`");

			$o .= conversation($a,$items,'notes',$update);
		}
	}


	$o .= paginate($a);
	return $o;
}
