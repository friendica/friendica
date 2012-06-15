<?php


function network_init(&$a) {
	if(! local_user()) {
		notice( t('Permission denied.') . EOL);
		return;
	}
	
	$is_a_date_query = false;

	if($a->argc > 1) {
		for($x = 1; $x < $a->argc; $x ++) {
			if(is_a_date_arg($a->argv[$x])) {
				$is_a_date_query = true;
				break;
			}
		}
	}
    
    // convert query string to array and remove first element (wich is friendica args)
    $query_array = array();
    parse_str($a->query_string, $query_array);
    array_shift($query_array);
    
	// fetch last used tab and redirect if needed
	$sel_tabs = network_query_get_sel_tab($a);
	$last_sel_tabs = get_pconfig(local_user(), 'network.view','tab.selected');
	if (is_array($last_sel_tabs)){
		$tab_urls = array(
			'/network?f=&order=comment',//all
			'/network?f=&order=post',		//postord
			'/network?f=&conv=1',			//conv
			'/network/new',					//new
			'/network?f=&star=1',			//starred
			'/network?f=&bmark=1',			//bookmarked
			'/network?f=&spam=1',			//spam
		);
		
		// redirect if current selected tab is 'no_active' and
		// last selected tab is _not_ 'all_active'. 
		// and this isn't a date query

		if ($sel_tabs[0] == 'active' && $last_sel_tabs[0]!='active' && (! $is_a_date_query)) {
			$k = array_search('active', $last_sel_tabs);
          
            // merge tab querystring with request querystring
            $dest_qa = array();
            list($dest_url,$dest_qs) = explode("?", $tab_urls[$k]);
            parse_str( $dest_qs, $dest_qa);
            $dest_qa = array_merge($query_array, $dest_qa);
            $dest_qs = build_querystring($dest_qa);
            
            // groups filter is in form of "network/nnn". Add it to $dest_url, if it's possible
            if ($a->argc==2 && is_numeric($a->argv[1]) && strpos($dest_url, "/",1)===false){
                $dest_url .= "/".$a->argv[1];
            }

			goaway($a->get_baseurl() . $dest_url."?".$dest_qs);
		}
	}
	
	$group_id = (($a->argc > 1 && intval($a->argv[1])) ? intval($a->argv[1]) : 0);
		  
	require_once('include/group.php');
	require_once('include/contact_widgets.php');
	require_once('include/items.php');

	if(! x($a->page,'aside'))
		$a->page['aside'] = '';

	$search = ((x($_GET,'search')) ? escape_tags($_GET['search']) : '');

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
		$a->page['content'] .= '<h2>' . t('Search Results For:') . ' '  . $search . '</h2>';
	}

	$a->page['aside'] .= group_side('network','network',true,$group_id);
	$a->page['aside'] .= posted_date_widget($a->get_baseurl() . '/network',local_user(),false);	
	$a->page['aside'] .= networks_widget($a->get_baseurl(true) . '/network',(x($_GET, 'nets') ? $_GET['nets'] : ''));
	$a->page['aside'] .= saved_searches($search);
	$a->page['aside'] .= fileas_widget($a->get_baseurl(true) . '/network',(x($_GET, 'file') ? $_GET['file'] : ''));

}

function saved_searches($search) {

	$a = get_app();

	$srchurl = '/network?f=' 
		. ((x($_GET,'cid'))   ? '&cid='   . $_GET['cid']   : '') 
		. ((x($_GET,'star'))  ? '&star='  . $_GET['star']  : '')
		. ((x($_GET,'bmark')) ? '&bmark=' . $_GET['bmark'] : '')
		. ((x($_GET,'conv'))  ? '&conv='  . $_GET['conv']  : '')
		. ((x($_GET,'nets'))  ? '&nets='  . $_GET['nets']  : '')
		. ((x($_GET,'cmin'))  ? '&cmin='  . $_GET['cmin']  : '')
		. ((x($_GET,'cmax'))  ? '&cmax='  . $_GET['cmax']  : '')
		. ((x($_GET,'file'))  ? '&file='  . $_GET['file']  : '');
	;
	
	$o = '';

	$r = q("select `id`,`term` from `search` WHERE `uid` = %d",
		intval(local_user())
	);

	$saved = array();

	if(count($r)) {
		foreach($r as $rr) {
			$saved[] = array(
				'id'            => $rr['id'],
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

/**
 * Return selected tab from query
 * 
 * urls -> returns
 * 		'/network'					=> $no_active = 'active'
 * 		'/network?f=&order=comment'	=> $comment_active = 'active'
 * 		'/network?f=&order=post'	=> $postord_active = 'active'
 * 		'/network?f=&conv=1',		=> $conv_active = 'active'
 * 		'/network/new',				=> $new_active = 'active'
 * 		'/network?f=&star=1',		=> $starred_active = 'active'
 * 		'/network?f=&bmark=1',		=> $bookmarked_active = 'active'
 * 		'/network?f=&spam=1',		=> $spam_active = 'active'
 * 
 * @return Array ( $no_active, $comment_active, $postord_active, $conv_active, $new_active, $starred_active, $bookmarked_active, $spam_active );
 */
function network_query_get_sel_tab($a) {
	$no_active='';
	$starred_active = '';
	$new_active = '';
	$bookmarked_active = '';
	$all_active = '';
	$search_active = '';
	$conv_active = '';
	$spam_active = '';
	$postord_active = '';

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
	
	if(x($_GET,'bmark')) {
		$bookmarked_active = 'active';
	}

	if(x($_GET,'conv')) {
		$conv_active = 'active';
	}

	if(x($_GET,'spam')) {
		$spam_active = 'active';
	}

	
	
	if (($new_active == '') 
		&& ($starred_active == '') 
		&& ($bookmarked_active == '')
		&& ($conv_active == '')
		&& ($search_active == '')
		&& ($spam_active == '')) {
			$no_active = 'active';
	}

	if ($no_active=='active' && x($_GET,'order')) {
		switch($_GET['order']){
		 case 'post': $postord_active = 'active'; $no_active=''; break;
		 case 'comment' : $all_active = 'active'; $no_active=''; break;
		}
	}
	
	return array($no_active, $all_active, $postord_active, $conv_active, $new_active, $starred_active, $bookmarked_active, $spam_active);
}


function network_content(&$a, $update = 0) {

	require_once('include/conversation.php');

	if(! local_user()) {
		$_SESSION['return_url'] = $a->query_string;
    	return login(false);
	}

	$arr = array('query' => $a->query_string);

	call_hooks('network_content_init', $arr);


	$datequery = $datequery2 = '';

	$group = 0;

	$nouveau = false;

	if($a->argc > 1) {
		for($x = 1; $x < $a->argc; $x ++) {
			if(is_a_date_arg($a->argv[$x])) {
				if($datequery)
					$datequery2 = escape_tags($a->argv[$x]);
				else {
					$datequery = escape_tags($a->argv[$x]);
					$_GET['order'] = 'post';
				}
			}
			elseif($a->argv[$x] === 'new') {
				$nouveau = true;
			}
			elseif(intval($a->argv[$x])) {
				$group = intval($a->argv[$x]);
				$def_acl = array('allow_gid' => '<' . $group . '>');
			}
		}
	}


	$o = '';

	// item filter tabs
	// TODO: fix this logic, reduce duplication
	//$a->page['content'] .= '<div class="tabs-wrapper">';
	
	list($no_active, $all_active, $postord_active, $conv_active, $new_active, $starred_active, $bookmarked_active, $spam_active) = network_query_get_sel_tab($a);
	// if no tabs are selected, defaults to comments
	if ($no_active=='active') $all_active='active';
	//echo "<pre>"; var_dump($no_active, $all_active, $postord_active, $conv_active, $new_active, $starred_active, $bookmarked_active, $spam_active); killme();

	$cmd = (($datequery) ? '' : $a->cmd);
	$len_naked_cmd = strlen(str_replace('/new','',$cmd));		

	// tabs
	$tabs = array(
		array(
			'label' => t('Commented Order'),
			'url'=>$a->get_baseurl(true) . '/' . str_replace('/new', '', $cmd) . '?f=&order=comment' . ((x($_GET,'cid')) ? '&cid=' . $_GET['cid'] : ''), 
			'sel'=>$all_active,
			'title'=> t('Sort by Comment Date'),
		),
		array(
			'label' => t('Posted Order'),
			'url'=>$a->get_baseurl(true) . '/' . str_replace('/new', '', $cmd) . '?f=&order=post' . ((x($_GET,'cid')) ? '&cid=' . $_GET['cid'] : ''), 
			'sel'=>$postord_active,
			'title' => t('Sort by Post Date'),
		),

		array(
			'label' => t('Personal'),
			'url' => $a->get_baseurl(true) . '/' . str_replace('/new', '', $cmd) . ((x($_GET,'cid')) ? '/?f=&cid=' . $_GET['cid'] : '') . '&conv=1',
			'sel' => $conv_active,
			'title' => t('Posts that mention or involve you'),
		),
		array(
			'label' => t('New'),
			'url' => $a->get_baseurl(true) . '/' . str_replace('/new', '', $cmd) . ($len_naked_cmd ? '/' : '') . 'new' . ((x($_GET,'cid')) ? '/?f=&cid=' . $_GET['cid'] : ''),
			'sel' => $new_active,
			'title' => t('Activity Stream - by date'),
		),
		array(
			'label' => t('Starred'),
			'url'=>$a->get_baseurl(true) . '/' . str_replace('/new', '', $cmd) . ((x($_GET,'cid')) ? '/?f=&cid=' . $_GET['cid'] : '') . '&star=1',
			'sel'=>$starred_active,
			'title' => t('Favourite Posts'),
		),
		array(
			'label' => t('Shared Links'),
			'url'=>$a->get_baseurl(true) . '/' . str_replace('/new', '', $cmd) . ((x($_GET,'cid')) ? '/?f=&cid=' . $_GET['cid'] : '') . '&bmark=1',
			'sel'=>$bookmarked_active,
			'title'=> t('Interesting Links'),
		),	
//		array(
//			'label' => t('Spam'),
//			'url'=>$a->get_baseurl(true) . '/network?f=&spam=1'
//			'sel'=> $spam_active,
//			'title' => t('Posts flagged as SPAM'),
//		),	

	);
	
	// save selected tab, but only if not in search or file mode
	if(!x($_GET,'search') && !x($_GET,'file')) {
		set_pconfig( local_user(), 'network.view','tab.selected',array($all_active, $postord_active, $conv_active, $new_active, $starred_active, $bookmarked_active, $spam_active) );
	}

	$arr = array('tabs' => $tabs);
	call_hooks('network_tabs', $arr);

	$o .= replace_macros(get_markup_template('common_tabs.tpl'), array('$tabs'=> $arr['tabs']));

	// --- end item filter tabs



	

	$contact_id = $a->cid;

	require_once('include/acl_selectors.php');

	$cid = ((x($_GET,'cid')) ? intval($_GET['cid']) : 0);
	$star = ((x($_GET,'star')) ? intval($_GET['star']) : 0);
	$bmark = ((x($_GET,'bmark')) ? intval($_GET['bmark']) : 0);
	$order = ((x($_GET,'order')) ? notags($_GET['order']) : 'comment');
	$liked = ((x($_GET,'liked')) ? intval($_GET['liked']) : 0);
	$conv = ((x($_GET,'conv')) ? intval($_GET['conv']) : 0);
	$spam = ((x($_GET,'spam')) ? intval($_GET['spam']) : 0);
	$nets = ((x($_GET,'nets')) ? $_GET['nets'] : '');
	$cmin = ((x($_GET,'cmin')) ? intval($_GET['cmin']) : 0);
	$cmax = ((x($_GET,'cmax')) ? intval($_GET['cmax']) : 99);
	$file = ((x($_GET,'file')) ? $_GET['file'] : '');



	if(x($_GET,'search') || x($_GET,'file'))
		$nouveau = true;
	if($cid)
		$def_acl = array('allow_cid' => '<' . intval($cid) . '>');

	if($nets) {
		$r = q("select id from contact where uid = %d and network = '%s' and self = 0",
			intval(local_user()),
			dbesc($nets)
		);

		$str = '';
		if(count($r))
			foreach($r as $rr)
				$str .= '<' . $rr['id'] . '>';
		if(strlen($str))
			$def_acl = array('allow_cid' => $str);
	}

	if(! $update) {
		if($group) {
			if(($t = group_public_members($group)) && (! get_pconfig(local_user(),'system','nowarn_insecure'))) {
				notice( sprintf( tt('Warning: This group contains %s member from an insecure network.',
									'Warning: This group contains %s members from an insecure network.',
									$t), $t ) . EOL);
				notice( t('Private messages to this group are at risk of public disclosure.') . EOL);
			}
		}

		nav_set_selected('network');

		$celeb = ((($a->user['page-flags'] == PAGE_SOAPBOX) || ($a->user['page-flags'] == PAGE_COMMUNITY)) ? true : false);

		$x = array(
			'is_owner' => true,
			'allow_location' => $a->user['allow_location'],
			'default_location' => $a->user['default-location'],
			'nickname' => $a->user['nickname'],
			'lockstate' => ((($group) || ($cid) || ($nets) || (is_array($a->user) && ((strlen($a->user['allow_cid'])) || (strlen($a->user['allow_gid'])) || (strlen($a->user['deny_cid'])) || (strlen($a->user['deny_gid']))))) ? 'lock' : 'unlock'),
			'acl' => populate_acl((($group || $cid || $nets) ? $def_acl : $a->user), $celeb),
			'bang' => (($group || $cid || $nets) ? '!' : ''),
			'visitor' => 'block',
			'profile_uid' => local_user()
		);

		$o .= status_editor($a,$x);

	}


	// We don't have to deal with ACL's on this page. You're looking at everything
	// that belongs to you, hence you can see all of it. We will filter by group if
	// desired. 

	
	$sql_options  = (($star) ? " and starred = 1 " : '');
	$sql_options .= (($bmark) ? " and bookmark = 1 " : '');

	$sql_nets = (($nets) ? sprintf(" and `contact`.`network` = '%s' ", dbesc($nets)) : '');

	$sql_extra = " AND `item`.`parent` IN ( SELECT `parent` FROM `item` WHERE `id` = `parent` $sql_options ) ";

	if($group) {
		$r = q("SELECT `name`, `id` FROM `group` WHERE `id` = %d AND `uid` = %d LIMIT 1",
			intval($group),
			intval($_SESSION['uid'])
		);
		if(! count($r)) {
			if($update)
				killme();
			notice( t('No such group') . EOL );
			goaway($a->get_baseurl(true) . '/network');
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

		$sql_extra = " AND `item`.`parent` IN ( SELECT DISTINCT(`parent`) FROM `item` WHERE 1 $sql_options AND ( `contact-id` IN ( $contact_str ) OR `allow_gid` like '" . protect_sprintf('%<' . intval($group) . '>%') . "' ) and deleted = 0 ) ";
		$o = '<h2>' . t('Group: ') . $r[0]['name'] . '</h2>' . $o;
	}
	elseif($cid) {

		$r = q("SELECT `id`,`name`,`network`,`writable`,`nurl` FROM `contact` WHERE `id` = %d 
				AND `blocked` = 0 AND `pending` = 0 LIMIT 1",
			intval($cid)
		);
		if(count($r)) {
			$sql_extra = " AND `item`.`parent` IN ( SELECT DISTINCT(`parent`) FROM `item` WHERE 1 $sql_options AND `contact-id` = " . intval($cid) . " and deleted = 0 ) ";
			$o = '<h2>' . t('Contact: ') . $r[0]['name'] . '</h2>' . $o;
			if($r[0]['network'] === NETWORK_OSTATUS && $r[0]['writable'] && (! get_pconfig(local_user(),'system','nowarn_insecure'))) {
				notice( t('Private messages to this person are at risk of public disclosure.') . EOL);
			}

		}
		else {
			notice( t('Invalid contact.') . EOL);
			goaway($a->get_baseurl(true) . '/network');
			// NOTREACHED
		}
	}

	if((! $group) && (! $cid) && (! $update)) {
		$o .= get_birthdays();
		$o .= get_events();
	}

	if(! $update) {
		// The special div is needed for liveUpdate to kick in for this page.
		// We only launch liveUpdate if you aren't filtering in some incompatible 
		// way and also you aren't writing a comment (discovered in javascript).

		$o .= '<div id="live-network"></div>' . "\r\n";
		$o .= "<script> var profile_uid = " . $_SESSION['uid'] 
			. "; var netargs = '" . substr($a->cmd,8)
			. '?f='
			. ((x($_GET,'cid'))    ? '&cid='    . $_GET['cid']    : '')
			. ((x($_GET,'search')) ? '&search=' . $_GET['search'] : '') 
			. ((x($_GET,'star'))   ? '&star='   . $_GET['star']   : '') 
			. ((x($_GET,'order'))  ? '&order='  . $_GET['order']  : '') 
			. ((x($_GET,'bmark'))  ? '&bmark='  . $_GET['bmark']  : '') 
			. ((x($_GET,'liked'))  ? '&liked='  . $_GET['liked']  : '') 
			. ((x($_GET,'conv'))   ? '&conv='   . $_GET['conv']   : '') 
			. ((x($_GET,'spam'))   ? '&spam='   . $_GET['spam']   : '') 
			. ((x($_GET,'nets'))   ? '&nets='   . $_GET['nets']   : '') 
			. ((x($_GET,'cmin'))   ? '&cmin='   . $_GET['cmin']   : '') 
			. ((x($_GET,'cmax'))   ? '&cmax='   . $_GET['cmax']   : '') 
			. ((x($_GET,'file'))   ? '&file='   . $_GET['file']   : '') 

			. "'; var profile_page = " . $a->pager['page'] . "; </script>\r\n";
	}

	$sql_extra3 = '';

	if($datequery) {
		$sql_extra3 .= protect_sprintf(sprintf(" AND item.created <= '%s' ", dbesc(datetime_convert(date_default_timezone_get(),'',$datequery))));
	}
	if($datequery2) {
		$sql_extra3 .= protect_sprintf(sprintf(" AND item.created >= '%s' ", dbesc(datetime_convert(date_default_timezone_get(),'',$datequery2))));
	}

	$sql_extra2 = (($nouveau) ? '' : " AND `item`.`parent` = `item`.`id` ");
	$sql_extra3 = (($nouveau) ? '' : $sql_extra3);

	if(x($_GET,'search')) {
		$search = escape_tags($_GET['search']);
		if (get_config('system','use_fulltext_engine')) {
			if(strpos($search,'#') === 0)
				$sql_extra .= sprintf(" AND (MATCH(tag) AGAINST ('".'"%s"'."' in boolean mode)) ",
					dbesc(protect_sprintf($search))
				);
			else
				$sql_extra .= sprintf(" AND (MATCH(`item`.`body`) AGAINST ('".'"%s"'."' in boolean mode) or MATCH(tag) AGAINST ('".'"%s"'."' in boolean mode)) ",
					dbesc(protect_sprintf($search)),
					dbesc(protect_sprintf($search))
				);
		} else {
			$sql_extra .= sprintf(" AND ( `item`.`body` like '%s' OR `item`.`tag` like '%s' ) ",
					dbesc(protect_sprintf('%' . $search . '%')),
					dbesc(protect_sprintf('%]' . $search . '[%'))
			);
		}
	}
	if(strlen($file)) {
		$sql_extra .= file_tag_file_query('item',unxmlify($file));
	}

	if($conv) {
		$myurl = $a->get_baseurl() . '/profile/'. $a->user['nickname'];
		$myurl = substr($myurl,strpos($myurl,'://')+3);
		$myurl = str_replace('www.','',$myurl);
		$diasp_url = str_replace('/profile/','/u/',$myurl);
		if (get_config('system','use_fulltext_engine'))
			$sql_extra .= sprintf(" AND `item`.`parent` IN (SELECT distinct(`parent`) from item where (MATCH(`author-link`) AGAINST ('".'"%s"'."' in boolean mode) or MATCH(`tag`) AGAINST ('".'"%s"'."' in boolean mode) or MATCH(tag) AGAINST ('".'"%s"'."' in boolean mode))) ",
				dbesc(protect_sprintf($myurl)),
				dbesc(protect_sprintf($myurl)),
				dbesc(protect_sprintf($diasp_url))
			);
		else
			$sql_extra .= sprintf(" AND `item`.`parent` IN (SELECT distinct(`parent`) from item where ( `author-link` like '%s' or `tag` like '%s' or tag like '%s' )) ",
				dbesc(protect_sprintf('%' . $myurl)),
				dbesc(protect_sprintf('%' . $myurl . ']%')),
				dbesc(protect_sprintf('%' . $diasp_url . ']%'))
			);

	}

	if($update) {

		// only setup pagination on initial page view
		$pager_sql = '';

	}
	else {
		$r = q("SELECT COUNT(*) AS `total`
			FROM `item` LEFT JOIN `contact` ON `contact`.`id` = `item`.`contact-id`
			WHERE `item`.`uid` = %d AND `item`.`visible` = 1 AND `item`.`deleted` = 0
			AND `contact`.`blocked` = 0 AND `contact`.`pending` = 0
			$sql_extra2 $sql_extra3
			$sql_extra $sql_nets ",
			intval($_SESSION['uid'])
		);

		if(count($r)) {
			$a->set_pager_total($r[0]['total']);
	                $itemspage_network = get_pconfig(local_user(),'system','itemspage_network');
                        $a->set_pager_itemspage(((intval($itemspage_network)) ? $itemspage_network : 40));
		}
		$pager_sql = sprintf(" LIMIT %d, %d ",intval($a->pager['start']), intval($a->pager['itemspage']));
	}

	$simple_update = (($update) ? " and `item`.`unseen` = 1 " : '');

	if($nouveau) {
		// "New Item View" - show all items unthreaded in reverse created date order

		$items = q("SELECT `item`.*, `item`.`id` AS `item_id`, 
			`contact`.`name`, `contact`.`photo`, `contact`.`url`, `contact`.`rel`, `contact`.`writable`,
			`contact`.`network`, `contact`.`thumb`, `contact`.`dfrn-id`, `contact`.`self`,
			`contact`.`id` AS `cid`, `contact`.`uid` AS `contact-uid`
			FROM `item`, `contact`
			WHERE `item`.`uid` = %d AND `item`.`visible` = 1 
			AND `item`.`deleted` = 0 and `item`.`moderated` = 0
			$simple_update
			AND `contact`.`id` = `item`.`contact-id`
			AND `contact`.`blocked` = 0 AND `contact`.`pending` = 0
			$sql_extra $sql_nets
			ORDER BY `item`.`received` DESC $pager_sql ",
			intval($_SESSION['uid'])
		);

	}
	else {

		// Normal conversation view


		if($order === 'post')
				$ordering = "`created`";
		else
				$ordering = "`commented`";

		// Fetch a page full of parent items for this page

		if($update) {
			$r = q("SELECT `parent` AS `item_id`, `contact`.`uid` AS `contact_uid`
				FROM `item` LEFT JOIN `contact` ON `contact`.`id` = `item`.`contact-id`
				WHERE `item`.`uid` = %d AND `item`.`visible` = 1 AND `item`.`deleted` = 0
				and `item`.`moderated` = 0 and `item`.`unseen` = 1
				AND `contact`.`blocked` = 0 AND `contact`.`pending` = 0
				$sql_extra3 $sql_extra $sql_nets ",
				intval(local_user())
			);
		}
		else {
			$r = q("SELECT `item`.`id` AS `item_id`, `contact`.`uid` AS `contact_uid`
				FROM `item` LEFT JOIN `contact` ON `contact`.`id` = `item`.`contact-id`
				WHERE `item`.`uid` = %d AND `item`.`visible` = 1 AND `item`.`deleted` = 0
				AND `item`.`moderated` = 0 AND `contact`.`blocked` = 0 AND `contact`.`pending` = 0
				AND `item`.`parent` = `item`.`id`
				$sql_extra3 $sql_extra $sql_nets
				ORDER BY `item`.$ordering DESC $pager_sql ",
				intval(local_user())
			);
		}

		// Then fetch all the children of the parents that are on this page

		$parents_arr = array();
		$parents_str = '';

		if(count($r)) {
			foreach($r as $rr)
				if(! in_array($rr['item_id'],$parents_arr))
					$parents_arr[] = $rr['item_id'];
			$parents_str = implode(', ', $parents_arr);

			$items = q("SELECT `item`.*, `item`.`id` AS `item_id`,
				`contact`.`name`, `contact`.`photo`, `contact`.`url`, `contact`.`alias`, `contact`.`rel`, `contact`.`writable`,
				`contact`.`network`, `contact`.`thumb`, `contact`.`dfrn-id`, `contact`.`self`,
				`contact`.`id` AS `cid`, `contact`.`uid` AS `contact-uid`
				FROM `item`, `contact`
				WHERE `item`.`uid` = %d AND `item`.`visible` = 1 AND `item`.`deleted` = 0
				AND `item`.`moderated` = 0 AND `contact`.`id` = `item`.`contact-id`
				AND `contact`.`blocked` = 0 AND `contact`.`pending` = 0
				AND `item`.`parent` IN ( %s )
				$sql_extra ",
				intval(local_user()),
				dbesc($parents_str)
			);

			$items = conv_sort($items,$ordering);

		} else {
			$items = array();
		}
	}


	// We aren't going to try and figure out at the item, group, and page
	// level which items you've seen and which you haven't. If you're looking
	// at the top level network page just mark everything seen. 
	
	if((! $group) && (! $cid) && (! $star)) {
		$r = q("UPDATE `item` SET `unseen` = 0 
			WHERE `unseen` = 1 AND `uid` = %d",
			intval(local_user())
		);
	}

	// Set this so that the conversation function can find out contact info for our wall-wall items
	$a->page_contact = $a->contact;

	$mode = (($nouveau) ? 'network-new' : 'network');

	$o .= conversation($a,$items,$mode,$update);

	if(! $update) {
		$o .= paginate($a);
	}

	return $o;
}
