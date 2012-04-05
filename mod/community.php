<?php

function community_init(&$a) {
	if(! local_user())
		unset($_SESSION['theme']);


}


function community_content(&$a, $update = 0) {

	$o = '';

	if((get_config('system','block_public')) && (! local_user()) && (! remote_user())) {
		notice( t('Public access denied.') . EOL);
		return;
	}

	if(get_config('system','no_community_page')) {
		notice( t('Not available.') . EOL);
		return;
	}

	require_once("include/bbcode.php");
	require_once('include/security.php');
	require_once('include/conversation.php');


	$o .= '<h3>' . t('Community') . '</h3>';
	if(! $update) {
		nav_set_selected('community');
		$o .= '<div id="live-community"></div>' . "\r\n";
		$o .= "<script> var profile_uid = -1; var netargs = '/?f='; var profile_page = " . $a->pager['page'] . "; </script>\r\n";
	}

	if(x($a->data,'search'))
		$search = notags(trim($a->data['search']));
	else
		$search = ((x($_GET,'search')) ? notags(trim(rawurldecode($_GET['search']))) : '');


	// Here is the way permissions work in this module...
	// Only public posts can be shown
	// OR your own posts if you are a logged in member


	$r = q("SELECT COUNT(*) AS `total`
		FROM `item` LEFT JOIN `contact` ON `contact`.`id` = `item`.`contact-id` LEFT JOIN `user` ON `user`.`uid` = `item`.`uid`
		WHERE `item`.`visible` = 1 AND `item`.`deleted` = 0 and `item`.`moderated` = 0
		AND `item`.`allow_cid` = ''  AND `item`.`allow_gid` = '' 
		AND `item`.`deny_cid`  = '' AND `item`.`deny_gid`  = '' 
		AND `item`.`private` = 0 AND `item`.`wall` = 1 AND `user`.`hidewall` = 0 
		AND `contact`.`blocked` = 0 AND `contact`.`pending` = 0 "
	);

	if(count($r))
		$a->set_pager_total($r[0]['total']);

	if(! $r[0]['total']) {
		info( t('No results.') . EOL);
		return $o;
	}

	$r = q("SELECT `item`.*, `item`.`id` AS `item_id`, 
		`contact`.`name`, `contact`.`photo`, `contact`.`url`, `contact`.`rel`,
		`contact`.`network`, `contact`.`thumb`, `contact`.`self`, `contact`.`writable`, 
		`contact`.`id` AS `cid`, `contact`.`uid` AS `contact-uid`,
		`user`.`nickname`, `user`.`hidewall`
		FROM `item` LEFT JOIN `contact` ON `contact`.`id` = `item`.`contact-id`
		LEFT JOIN `user` ON `user`.`uid` = `item`.`uid`
		WHERE `item`.`visible` = 1 AND `item`.`deleted` = 0 and `item`.`moderated` = 0
		AND `item`.`allow_cid` = ''  AND `item`.`allow_gid` = '' 
		AND `item`.`deny_cid`  = '' AND `item`.`deny_gid`  = '' 
		AND `item`.`private` = 0 AND `item`.`wall` = 1 AND `user`.`hidewall` = 0 
		AND `contact`.`blocked` = 0 AND `contact`.`pending` = 0
		ORDER BY `received` DESC LIMIT %d, %d ",
		intval($a->pager['start']),
		intval($a->pager['itemspage'])

	);

	// we behave the same in message lists as the search module

	$o .= conversation($a,$r,'community',$update);

	$o .= paginate($a);

	return $o;
}

