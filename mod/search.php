<?php

function search_saved_searches() {

	$o = '';

	$r = q("select `id`,`term` from `search` WHERE `uid` = %d",
		intval(local_user())
	);

	if(count($r)) {
		$o .= '<div id="saved-search-list" class="widget">';
		$o .= '<h3>' . t('Saved Searches') . '</h3>' . "\r\n";
		$o .= '<ul id="saved-search-ul">' . "\r\n";
		foreach($r as $rr) {
			$o .= '<li class="saved-search-li clear"><a href="search/?f=&remove=1&search=' . $rr['term'] . '" class="icon drophide savedsearchdrop" title="' . t('Remove term') . '" onclick="return confirmDelete();" onmouseover="imgbright(this);" onmouseout="imgdull(this);" ></a> <a href="search/?f=&search=' . $rr['term'] . '" class="savedsearchterm" >' . $rr['term'] . '</a></li>' . "\r\n";
		}
		$o .= '</ul><div class="clear"></div></div>' . "\r\n";
	}		

	return $o;

}


function search_init(&$a) {

	$search = ((x($_GET,'search')) ? notags(trim(rawurldecode($_GET['search']))) : '');

	if(local_user()) {
		if(x($_GET,'save') && $search) {
			$r = q("select * from `search` where `uid` = %d and `term` = '%s' limit 1",
				intval(local_user()),
				dbesc($search)
			);
			if(! count($r)) {
				q("insert into `search` ( `uid`,`term` ) values ( %d, '%s') ",
					intval(local_user()),
					dbesc($search)
				);
			}
		}
		if(x($_GET,'remove') && $search) {
			q("delete from `search` where `uid` = %d and `term` = '%s' limit 1",
				intval(local_user()),
				dbesc($search)
			);
		}

		$a->page['aside'] .= search_saved_searches();

	}
	else
		unset($_SESSION['theme']);



}



function search_post(&$a) {
	if(x($_POST,'search'))
		$a->data['search'] = $_POST['search'];
}


function search_content(&$a) {

	if((get_config('system','block_public')) && (! local_user()) && (! remote_user())) {
		notice( t('Public access denied.') . EOL);
		return;
	}

	nav_set_selected('search');

	require_once("include/bbcode.php");
	require_once('include/security.php');
	require_once('include/conversation.php');

	$o = '<div id="live-search"></div>' . "\r\n";

	$o .= '<h3>' . t('Search') . '</h3>';

	if(x($a->data,'search'))
		$search = notags(trim($a->data['search']));
	else
		$search = ((x($_GET,'search')) ? notags(trim(rawurldecode($_GET['search']))) : '');

	$tag = false;
	if(x($_GET,'tag')) {
		$tag = true;
		$search = ((x($_GET,'tag')) ? notags(trim(rawurldecode($_GET['tag']))) : '');
	}


	$o .= search($search,'search-box','/search',((local_user()) ? true : false));

	if(strpos($search,'#') === 0) {
		$tag = true;
		$search = substr($search,1);
	}
	if(strpos($search,'@') === 0) {
		require_once('mod/dirfind.php');
		return dirfind_content($a);
	}

	if(! $search)
		return $o;

	if (get_config('system','use_fulltext_engine')) {
		if($tag)
			$sql_extra = sprintf(" AND MATCH (`item`.`tag`) AGAINST ('".'"%s"'."' in boolean mode) ", '#'.preg_quote($search));
		else
			$sql_extra = sprintf(" AND MATCH (`item`.`body`) AGAINST ('".'"%s"'."' in boolean mode) ", dbesc(preg_quote($search)));
	} else {
		if($tag)
			$sql_extra = sprintf(" AND `item`.`tag` REGEXP '%s' ", 	dbesc('\\]' . preg_quote($search) . '\\['));
		else
			$sql_extra = sprintf(" AND `item`.`body` REGEXP '%s' ", dbesc(preg_quote($search)));
	}



	// Here is the way permissions work in the search module...
	// Only public posts can be shown
	// OR your own posts if you are a logged in member
	// No items will be shown if the member has a blocked profile wall. 

	$r = q("SELECT distinct(`item`.`uri`) as `total`
		FROM `item` LEFT JOIN `contact` ON `contact`.`id` = `item`.`contact-id` LEFT JOIN `user` ON `user`.`uid` = `item`.`uid`
		WHERE `item`.`visible` = 1 AND `item`.`deleted` = 0 and `item`.`moderated` = 0
		AND (( `item`.`allow_cid` = ''  AND `item`.`allow_gid` = '' AND `item`.`deny_cid`  = '' AND `item`.`deny_gid`  = '' AND `item`.`private` = 0 AND `user`.`hidewall` = 0) 
			OR `item`.`uid` = %d )
		AND `contact`.`blocked` = 0 AND `contact`.`pending` = 0
		$sql_extra group by `item`.`uri` ", 
		intval(local_user())
	);

	if(count($r))
		$a->set_pager_total(count($r));
	if(! count($r)) {
		info( t('No results.') . EOL);
		return $o;
	}

	$r = q("SELECT distinct(`item`.`uri`), `item`.*, `item`.`id` AS `item_id`, 
		`contact`.`name`, `contact`.`photo`, `contact`.`url`, `contact`.`rel`,
		`contact`.`network`, `contact`.`thumb`, `contact`.`self`, `contact`.`writable`, 
		`contact`.`id` AS `cid`, `contact`.`uid` AS `contact-uid`,
		`user`.`nickname`
		FROM `item` LEFT JOIN `contact` ON `contact`.`id` = `item`.`contact-id`
		LEFT JOIN `user` ON `user`.`uid` = `item`.`uid`
		WHERE `item`.`visible` = 1 AND `item`.`deleted` = 0 and `item`.`moderated` = 0
		AND (( `item`.`allow_cid` = ''  AND `item`.`allow_gid` = '' AND `item`.`deny_cid`  = '' AND `item`.`deny_gid`  = '' AND `item`.`private` = 0 AND `user`.`hidewall` = 0 ) 
			OR `item`.`uid` = %d )
		AND `contact`.`blocked` = 0 AND `contact`.`pending` = 0
		$sql_extra
		group by `item`.`uri`	
		ORDER BY `received` DESC LIMIT %d , %d ",
		intval(local_user()),
		intval($a->pager['start']),
		intval($a->pager['itemspage'])

	);

	if($tag) 
		$o .= '<h2>Items tagged with: ' . $search . '</h2>';
	else
		$o .= '<h2>Search results for: ' . $search . '</h2>';

	$o .= conversation($a,$r,'search',false);

	$o .= paginate($a);

	return $o;
}

