<?php


function network_init(&$a) {
	if(! local_user()) {
		notice( t('Permission denied.') . EOL);
		return;
	}
  
	$group_id = (($a->argc > 1 && intval($a->argv[1])) ? intval($a->argv[1]) : 0);
		  
	require_once('include/group.php');
	if(! x($a->page,'aside'))
		$a->page['aside'] = '';

	$search = ((x($_GET,'search')) ? escape_tags($_GET['search']) : '');

	// We need a better way of managing a growing argument list

	// moved into savedsearches()
	// $srchurl = '/network' 
	// 		. ((x($_GET,'cid')) ? '?cid=' . $_GET['cid'] : '') 
	// 		. ((x($_GET,'star')) ? '?star=' . $_GET['star'] : '')
	// 		. ((x($_GET,'bmark')) ? '?bmark=' . $_GET['bmark'] : '');
	
	if(x($_GET,'save')) {
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
	if(x($_GET,'remove')) {
		q("delete from `search` where `uid` = %d and `term` = '%s' limit 1",
			intval(local_user()),
			dbesc($search)
		);
	}


	
	// search terms header
	if(x($_GET,'search')) {
		$a->page['content'] .= '<h2>Search Results For: '  . $search . '</h2>';
	}
	
	$a->page['aside'] .= group_side('network','network',true,$group_id);
	
	// moved to saved searches to have it in the same div
	//$a->page['aside'] .= search($search,'netsearch-box',$srchurl,true);

	$a->page['aside'] .= saved_searches($search);

}

function saved_searches($search) {

	$srchurl = '/network' 
		. ((x($_GET,'cid')) ? '?cid=' . $_GET['cid'] : '') 
		. ((x($_GET,'star')) ? '?star=' . $_GET['star'] : '')
		. ((x($_GET,'bmark')) ? '?bmark=' . $_GET['bmark'] : '');
	
	$o = '';

	$r = q("select `term` from `search` WHERE `uid` = %d",
		intval(local_user())
	);

	$saved = array();


	
	if(count($r)) {
		foreach($r as $rr) {
			$saved[] = array(
				'term'			=> $rr['term'],
				'encodedterm' 	=> urlencode($rr['term']),
				'delete'		=> t('Remove term'),
				'selected'		=> ($search==$rr['term']),
			);
		}
	}		

	
	$tpl = get_markup_template("saved_searches_aside.tpl");
	$o = replace_macros($tpl, array(
		'$title'	 => t('Saved Searches'),
		'$add'		 => t('add'),
		'$searchbox' => search($search,'netsearch-box',$srchurl,true),
		'$saved' 	 => $saved,
	));
	
	return $o;

}


function network_content(&$a, $update = 0) {

	require_once('include/conversation.php');

	if(! local_user())
    	return login(false);

	$o = '';

	// item filter tabs
	// TODO: fix this logic, reduce duplication
	//$a->page['content'] .= '<div class="tabs-wrapper">';
	
	$starred_active = '';
	$new_active = '';
	$bookmarked_active = '';
	$all_active = '';
	$search_active = '';
	
	if(($a->argc > 1 && $a->argv[1] === 'new') 
		|| ($a->argc > 2 && $a->argv[2] === 'new')) {
			$new_active = 'active';
	}
	
	if(x($_GET,'search')) {
		$search_active = 'active';
	}
	
	if(x($_GET,'star')) {
		$starred_active = 'active';
	}
	
	if($_GET['bmark']) {
		$bookmarked_active = 'active';
	}
	
	if (($new_active == '') 
		&& ($starred_active == '') 
		&& ($bookmarked_active == '')
		&& ($search_active == '')) {
			$all_active = 'active';
	}
	$postord_active = '';

	if($all_active && x($_GET,'order') && $_GET['order'] !== 'comment') {
		$all_active = '';
		$postord_active = 'active';
	}
			 

	
	// tabs
	$tabs = array(
		array(
			'label' => t('Commented Order'),
			'url'=>$a->get_baseurl() . '/' . str_replace('/new', '', $a->cmd) . ((x($_GET,'cid')) ? '?cid=' . $_GET['cid'] : ''), 
			'sel'=>$all_active,
		),
		array(
			'label' => t('Posted Order'),
			'url'=>$a->get_baseurl() . '/' . str_replace('/new', '', $a->cmd) . '?order=post' . ((x($_GET,'cid')) ? '&cid=' . $_GET['cid'] : ''), 
			'sel'=>$postord_active,
		),

		array(
			'label' => t('New'),
			'url' => $a->get_baseurl() . '/' . str_replace('/new', '', $a->cmd) . '/new' . ((x($_GET,'cid')) ? '/?cid=' . $_GET['cid'] : ''),
			'sel' => $new_active,
		),
		array(
			'label' => t('Starred'),
			'url'=>$a->get_baseurl() . '/' . str_replace('/new', '', $a->cmd) . ((x($_GET,'cid')) ? '/?cid=' . $_GET['cid'] : '') . '&star=1',
			'sel'=>$starred_active,
		),
		array(
			'label' => t('Bookmarks'),
			'url'=>$a->get_baseurl() . '/' . str_replace('/new', '', $a->cmd) . ((x($_GET,'cid')) ? '/?cid=' . $_GET['cid'] : '') . '&bmark=1',
			'sel'=>$bookmarked_active,
		),	
	);
	$tpl = get_markup_template('common_tabs.tpl');
	$o .= replace_macros($tpl, array('$tabs'=>$tabs));
	// --- end item filter tabs



	

	$contact_id = $a->cid;

	$group = 0;

	$nouveau = false;
	require_once('include/acl_selectors.php');

	$cid = ((x($_GET,'cid')) ? intval($_GET['cid']) : 0);
	$star = ((x($_GET,'star')) ? intval($_GET['star']) : 0);
	$bmark = ((x($_GET,'bmark')) ? intval($_GET['bmark']) : 0);
	$order = ((x($_GET,'order')) ? notags($_GET['order']) : 'comment');
	$liked = ((x($_GET,'liked')) ? intval($_GET['liked']) : 0);


	if(($a->argc > 2) && $a->argv[2] === 'new')
		$nouveau = true;

	if($a->argc > 1) {
		if($a->argv[1] === 'new')
			$nouveau = true;
		else {
			$group = intval($a->argv[1]);
			$def_acl = array('allow_gid' => '<' . $group . '>');
		}
	}

	if(x($_GET,'search'))
		$nouveau = true;
	if($cid)
		$def_acl = array('allow_cid' => '<' . intval($cid) . '>');

	if(! $update) {
		if(group) {
			if(($t = group_public_members($group)) && (! get_pconfig(local_user(),'system','nowarn_insecure'))) {
				notice( sprintf( tt('Warning: This group contains %s member from an insecure network.',
									'Warning: This group contains %s members from an insecure network.',
									$t), $t ) . EOL);
				notice( t('Private messages to this group are at risk of public disclosure.') . EOL);
			}
		}

		nav_set_selected('network');

		$_SESSION['return_url'] = $a->cmd;

		$celeb = ((($a->user['page-flags'] == PAGE_SOAPBOX) || ($a->user['page-flags'] == PAGE_COMMUNITY)) ? true : false);

		$x = array(
			'is_owner' => true,
			'allow_location' => $a->user['allow_location'],
			'default_location' => $a->user['default_location'],
			'nickname' => $a->user['nickname'],
			'lockstate' => ((($group) || (is_array($a->user) && ((strlen($a->user['allow_cid'])) || (strlen($a->user['allow_gid'])) || (strlen($a->user['deny_cid'])) || (strlen($a->user['deny_gid']))))) ? 'lock' : 'unlock'),
			'acl' => populate_acl((($group || $cid) ? $def_acl : $a->user), $celeb),
			'bang' => (($group || $cid) ? '!' : ''),
			'visitor' => 'block',
			'profile_uid' => local_user()
		);

		$o .= status_editor($a,$x);

		// The special div is needed for liveUpdate to kick in for this page.
		// We only launch liveUpdate if you are on the front page, you aren't
		// filtering by group and also you aren't writing a comment (the last
		// criteria is discovered in javascript).

			$o .= '<div id="live-network"></div>' . "\r\n";
			$o .= "<script> var profile_uid = " . $_SESSION['uid'] 
				. "; var netargs = '" . substr($a->cmd,8)
				. '?f='
				. ((x($_GET,'cid')) ? '&cid=' . $_GET['cid'] : '')
				. ((x($_GET,'search')) ? '&search=' . $_GET['search'] : '') 
				. ((x($_GET,'star')) ? '&star=' . $_GET['star'] : '') 
				. ((x($_GET,'order')) ? '&order=' . $_GET['order'] : '') 
				. ((x($_GET,'bmark')) ? '&bmark=' . $_GET['bmark'] : '') 
				. ((x($_GET,'liked')) ? '&liked=' . $_GET['liked'] : '') 
				. "'; var profile_page = " . $a->pager['page'] . "; </script>\r\n";

	}

	// We aren't going to try and figure out at the item, group, and page
	// level which items you've seen and which you haven't. If you're looking
	// at the top level network page just mark everything seen. 
	
	if((! $group) && (! $cid) && (! $star)) {
		$r = q("UPDATE `item` SET `unseen` = 0 
			WHERE `unseen` = 1 AND `uid` = %d",
			intval($_SESSION['uid'])
		);
	}

	// We don't have to deal with ACL's on this page. You're looking at everything
	// that belongs to you, hence you can see all of it. We will filter by group if
	// desired. 

	$star_sql = (($star) ?  " AND `starred` = 1 " : '');

	if($bmark)
		$star_sql .= " AND `bookmark` = 1 ";

	$sql_extra = " AND `item`.`parent` IN ( SELECT `parent` FROM `item` WHERE `id` = `parent` $star_sql ) ";

	if($group) {
		$r = q("SELECT `name`, `id` FROM `group` WHERE `id` = %d AND `uid` = %d LIMIT 1",
			intval($group),
			intval($_SESSION['uid'])
		);
		if(! count($r)) {
			if($update)
				killme();
			notice( t('No such group') . EOL );
			goaway($a->get_baseurl() . '/network');
			// NOTREACHED
		}

		$contacts = expand_groups(array($group));
		if((is_array($contacts)) && count($contacts)) {
			$contact_str = implode(',',$contacts);
		}
		else {
				$contact_str = ' 0 ';
				info( t('Group is empty'));
		}


		$sql_extra = " AND `item`.`parent` IN ( SELECT `parent` FROM `item` WHERE `id` = `parent` $star_sql AND ( `contact-id` IN ( $contact_str ) OR `allow_gid` REGEXP '<" . intval($group) . ">' )) ";
		$o = '<h2>' . t('Group: ') . $r[0]['name'] . '</h2>' . $o;
	}
	elseif($cid) {

		$r = q("SELECT `id`,`name`,`network`,`writable` FROM `contact` WHERE `id` = %d 
				AND `blocked` = 0 AND `pending` = 0 LIMIT 1",
			intval($cid)
		);
		if(count($r)) {
			$sql_extra = " AND `item`.`parent` IN ( SELECT `parent` FROM `item` WHERE `id` = `parent` $star_sql AND `contact-id` IN ( " . intval($cid) . " )) ";
			$o = '<h2>' . t('Contact: ') . $r[0]['name'] . '</h2>' . $o;
			if($r[0]['network'] !== NETWORK_MAIL && $r[0]['network'] !== NETWORK_DFRN && $r[0]['network'] !== NETWORK_FACEBOOK && $r[0]['network'] !== NETWORK_DIASPORA && $r[0]['writable'] && (! get_pconfig(local_user(),'system','nowarn_insecure'))) {
				notice( t('Private messages to this person are at risk of public disclosure.') . EOL);
			}

		}
		else {
			notice( t('Invalid contact.') . EOL);
			goaway($a->get_baseurl() . '/network');
			// NOTREACHED
		}
	}

	if((! $group) && (! $cid) && (! $update)) {
		$o .= get_birthdays();
		$o .= get_events();
	}

	$sql_extra2 = (($nouveau) ? '' : " AND `item`.`parent` = `item`.`id` ");

	if(x($_GET,'search')) {
		$search = escape_tags($_GET['search']);
		$sql_extra .= sprintf(" AND ( `item`.`body` REGEXP '%s' OR `item`.`tag` REGEXP '%s' ) ",
			dbesc($search),
			dbesc('\\]' . $search . '\\[')
		);
	}

	$r = q("SELECT COUNT(*) AS `total`
		FROM `item` LEFT JOIN `contact` ON `contact`.`id` = `item`.`contact-id`
		WHERE `item`.`uid` = %d AND `item`.`visible` = 1 AND `item`.`deleted` = 0
		AND `contact`.`blocked` = 0 AND `contact`.`pending` = 0
		$sql_extra2
		$sql_extra ",
		intval($_SESSION['uid'])
	);

	if(count($r)) {
		$a->set_pager_total($r[0]['total']);
		$a->set_pager_itemspage(40);
	}


	if($nouveau) {

		// "New Item View" - show all items unthreaded in reverse created date order

		$r = q("SELECT `item`.*, `item`.`id` AS `item_id`, 
			`contact`.`name`, `contact`.`photo`, `contact`.`url`, `contact`.`rel`, `contact`.`writable`,
			`contact`.`network`, `contact`.`thumb`, `contact`.`dfrn-id`, `contact`.`self`,
			`contact`.`id` AS `cid`, `contact`.`uid` AS `contact-uid`
			FROM `item`, `contact`
			WHERE `item`.`uid` = %d AND `item`.`visible` = 1 AND `item`.`deleted` = 0
			AND `contact`.`id` = `item`.`contact-id`
			AND `contact`.`blocked` = 0 AND `contact`.`pending` = 0
			$sql_extra
			ORDER BY `item`.`received` DESC LIMIT %d ,%d ",
			intval($_SESSION['uid']),
			intval($a->pager['start']),
			intval($a->pager['itemspage'])
		);
		
	}
	else {

		// Normal conversation view


		if($order === 'post')
				$ordering = "`created`";
		else
				$ordering = "`commented`";

		// Fetch a page full of parent items for this page

		$r = q("SELECT `item`.`id` AS `item_id`, `contact`.`uid` AS `contact_uid`
			FROM `item` LEFT JOIN `contact` ON `contact`.`id` = `item`.`contact-id`
			WHERE `item`.`uid` = %d AND `item`.`visible` = 1 AND `item`.`deleted` = 0
			AND `contact`.`blocked` = 0 AND `contact`.`pending` = 0
			AND `item`.`parent` = `item`.`id`
			$sql_extra
			ORDER BY `item`.$ordering DESC LIMIT %d ,%d ",
			intval(local_user()),
			intval($a->pager['start']),
			intval($a->pager['itemspage'])
		);

		// Then fetch all the children of the parents that are on this page

		$parents_arr = array();
		$parents_str = '';

		if(count($r)) {
			foreach($r as $rr)
				$parents_arr[] = $rr['item_id'];
			$parents_str = implode(', ', $parents_arr);

			$r = q("SELECT `item`.*, `item`.`id` AS `item_id`,
				`contact`.`name`, `contact`.`photo`, `contact`.`url`, `contact`.`rel`, `contact`.`writable`,
				`contact`.`network`, `contact`.`thumb`, `contact`.`dfrn-id`, `contact`.`self`,
				`contact`.`id` AS `cid`, `contact`.`uid` AS `contact-uid`
				FROM `item`, (SELECT `p`.`id`,`p`.`created`,`p`.`commented` FROM `item` AS `p` WHERE `p`.`parent`=`p`.`id`) as `parentitem`, `contact`
				WHERE `item`.`uid` = %d AND `item`.`visible` = 1 AND `item`.`deleted` = 0
				AND `contact`.`id` = `item`.`contact-id`
				AND `contact`.`blocked` = 0 AND `contact`.`pending` = 0
				AND `item`.`parent` = `parentitem`.`id` AND `item`.`parent` IN ( %s )
				$sql_extra
				ORDER BY `parentitem`.$ordering DESC, `parentitem`.`id` ASC, `item`.`gravity` ASC, `item`.`created` ASC ",
				intval(local_user()),
				dbesc($parents_str)
			);
		}	
	}

	// Set this so that the conversation function can find out contact info for our wall-wall items
	$a->page_contact = $a->contact;

	$mode = (($nouveau) ? 'network-new' : 'network');

	$o .= conversation($a,$r,$mode,$update);

	if(! $update) {
		$o .= paginate($a);
	}

	return $o;
}
