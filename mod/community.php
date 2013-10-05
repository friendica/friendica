<?php

function community_init(&$a) {
	if(! local_user()) {
		unset($_SESSION['theme']);
		unset($_SESSION['mobile-theme']);
	}


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
	}

	if(x($a->data,'search'))
		$search = notags(trim($a->data['search']));
	else
		$search = ((x($_GET,'search')) ? notags(trim(rawurldecode($_GET['search']))) : '');


	// Here is the way permissions work in this module...
	// Only public posts can be shown
	// OR your own posts if you are a logged in member

	if( (! get_config('alt_pager', 'global')) && (! get_pconfig(local_user(),'system','alt_pager')) ) {
		$r = q("SELECT COUNT(distinct(`item`.`uri`)) AS `total`
			FROM `item` LEFT JOIN `contact` ON `contact`.`id` = `item`.`contact-id` LEFT JOIN `user` ON `user`.`uid` = `item`.`uid`
			WHERE `item`.`visible` = 1 AND `item`.`deleted` = 0 and `item`.`moderated` = 0
			AND `item`.`allow_cid` = ''  AND `item`.`allow_gid` = '' 
			AND `item`.`deny_cid`  = '' AND `item`.`deny_gid`  = ''
			AND `item`.`private` = 0 AND `item`.`wall` = 1 AND `user`.`hidewall` = 0 
			AND `contact`.`blocked` = 0 AND `contact`.`pending` = 0"
		);

		if(count($r))
			$a->set_pager_total($r[0]['total']);

		if(! $r[0]['total']) {
			info( t('No results.') . EOL);
			return $o;
		}

	}

	//$r = q("SELECT distinct(`item`.`uri`)
	$r = q("SELECT `item`.`uri`, `item`.*, `item`.`id` AS `item_id`, 
		`contact`.`name`, `contact`.`photo`, `contact`.`url`, `contact`.`alias`, `contact`.`rel`,
		`contact`.`network`, `contact`.`thumb`, `contact`.`self`, `contact`.`writable`, 
		`contact`.`id` AS `cid`, `contact`.`uid` AS `contact-uid`,
		`user`.`nickname`, `user`.`hidewall`
		FROM `item` FORCE INDEX (`received`, `wall`) LEFT JOIN `contact` ON `contact`.`id` = `item`.`contact-id`
		LEFT JOIN `user` ON `user`.`uid` = `item`.`uid`
		WHERE `item`.`visible` = 1 AND `item`.`deleted` = 0 and `item`.`moderated` = 0
		AND `item`.`allow_cid` = ''  AND `item`.`allow_gid` = ''
		AND `item`.`deny_cid`  = '' AND `item`.`deny_gid`  = '' 
		AND `item`.`private` = 0 AND `item`.`wall` = 1 AND `item`.`id` = `item`.`parent`
		AND `user`.`hidewall` = 0
		AND `contact`.`blocked` = 0 AND `contact`.`pending` = 0 AND `contact`.`self`
		ORDER BY `received` DESC LIMIT %d, %d ",
		intval($a->pager['start']),
		intval($a->pager['itemspage'])

	);
//		group by `item`.`uri`
//		AND `item`.`private` = 0 AND `item`.`wall` = 1 AND `item`.`id` = `item`.`parent`
//		AND `contact`.`blocked` = 0 AND `contact`.`pending` = 0 AND `contact`.`self`

	if(! count($r)) {
		info( t('No results.') . EOL);
		return $o;
	}

	// we behave the same in message lists as the search module

	$o .= conversation($a,$r,'community',$update);

	if( get_config('alt_pager', 'global') || get_pconfig(local_user(),'system','alt_pager') ) {
	        $o .= alt_pager($a,count($r));
	}
	else {
	        $o .= paginate($a);
	}

	return $o;
}

