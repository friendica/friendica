<?php

// This is a purely experimental module and is not yet generally useful.

// The eventual goal is to provide a json backend to fetch content and fill the current page.
// The page will be filled in on the frontend using javascript.
// At the present time this page is based on "network", but the hope is to extend to serving
// any content (wall, community, search, etc.).
// All search parameters, etc. will be managed in javascript and sent as request params.
// Security will be managed on the backend.
// There is no "pagination query", but we will manage the "current page" on the client
// and provide a link to fetch the next page - until there are no pages left to fetch.

// With the exception of complex tag and text searches, this prototype is incredibly
// fast - e.g. one or two milliseconds to fetch parent items for the current content,
// and 10-20 milliseconds to fetch all the child items.


function content_content(App $a, $update = 0) {

	require_once('include/conversation.php');


	// Currently security is based on the logged in user

	if (! local_user()) {
		return;
	}

	$arr = array('query' => $a->query_string);

	call_hooks('content_content_init', $arr);


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
		if (dbm::is_result($r))
			foreach($r as $rr)
				$str .= '<' . $rr['id'] . '>';
		if(strlen($str))
			$def_acl = array('allow_cid' => $str);
	}


	$sql_options  = (($star) ? " and starred = 1 " : '');
	$sql_options .= (($bmark) ? " and bookmark = 1 " : '');

	$sql_nets = (($nets) ? sprintf(" and `contact`.`network` = '%s' ", dbesc($nets)) : '');

	$sql_extra = " AND `item`.`parent` IN ( SELECT `parent` FROM `item` WHERE `id` = `parent` $sql_options ) ";

	if($group) {
		$r = q("SELECT `name`, `id` FROM `group` WHERE `id` = %d AND `uid` = %d LIMIT 1",
			intval($group),
			intval($_SESSION['uid'])
		);
		if (! dbm::is_result($r)) {
			if($update)
				killme();
			notice( t('No such group') . EOL );
			goaway(App::get_baseurl(true) . '/network');
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
		$o = replace_macros(get_markup_template("section_title.tpl"),array(
			'$title' => sprintf( t('Group: %s'), $r[0]['name'])
		)) . $o;
	}
	elseif($cid) {

		$r = q("SELECT `id`,`name`,`network`,`writable`,`nurl` FROM `contact` WHERE `id` = %d
				AND `blocked` = 0 AND `pending` = 0 LIMIT 1",
			intval($cid)
		);
		if (dbm::is_result($r)) {
			$sql_extra = " AND `item`.`parent` IN ( SELECT DISTINCT(`parent`) FROM `item` WHERE 1 $sql_options AND `contact-id` = " . intval($cid) . " and deleted = 0 ) ";

		}
		else {
			killme();
		}
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
	$sql_table = "`item`";

	if(x($_GET,'search')) {
		$search = escape_tags($_GET['search']);

		if(strpos($search,'#') === 0) {
			$tag = true;
			$search = substr($search,1);
		}

		if (get_config('system','only_tag_search'))
			$tag = true;

		if($tag) {
			//$sql_extra = sprintf(" AND `term`.`term` = '%s' AND `term`.`otype` = %d AND `term`.`type` = %d ",
			//	dbesc(protect_sprintf($search)), intval(TERM_OBJ_POST), intval(TERM_HASHTAG));
			//$sql_table = "`term` INNER JOIN `item` ON `item`.`id` = `term`.`oid` AND `item`.`uid` = `term`.`uid` ";

			$sql_extra = "";
			$sql_table = sprintf("`item` INNER JOIN (SELECT `oid` FROM `term` WHERE `term` = '%s' AND `otype` = %d AND `type` = %d AND `uid` = %d ORDER BY `tid` DESC) AS `term` ON `item`.`id` = `term`.`oid` ",
				dbesc(protect_sprintf($search)), intval(TERM_OBJ_POST), intval(TERM_HASHTAG), intval(local_user()));

		} else {
			if (get_config('system','use_fulltext_engine'))
				$sql_extra = sprintf(" AND MATCH (`item`.`body`, `item`.`title`) AGAINST ('%s' in boolean mode) ", dbesc(protect_sprintf($search)));
			else
				$sql_extra = sprintf(" AND `item`.`body` REGEXP '%s' ", dbesc(protect_sprintf(preg_quote($search))));
		}

	}
	if(strlen($file)) {
		$sql_extra .= file_tag_file_query('item',unxmlify($file));
	}

	if($conv) {
		$myurl = App::get_baseurl() . '/profile/'. $a->user['nickname'];
		$myurl = substr($myurl,strpos($myurl,'://')+3);
		$myurl = str_replace('www.','',$myurl);
		$diasp_url = str_replace('/profile/','/u/',$myurl);

		$sql_extra .= sprintf(" AND `item`.`parent` IN (SELECT distinct(`parent`) from item where `author-link` IN ('https://%s', 'http://%s') OR `mention`)",
			dbesc(protect_sprintf($myurl)),
			dbesc(protect_sprintf($myurl))
		);
	}

	$pager_sql = sprintf(" LIMIT %d, %d ",intval($a->pager['start']), intval($a->pager['itemspage']));


	if($nouveau) {
		// "New Item View" - show all items unthreaded in reverse created date order

		$items = q("SELECT `item`.*, `item`.`id` AS `item_id`,
			`contact`.`name`, `contact`.`photo`, `contact`.`url`, `contact`.`rel`, `contact`.`writable`,
			`contact`.`network`, `contact`.`thumb`, `contact`.`dfrn-id`, `contact`.`self`,
			`contact`.`id` AS `cid`
			FROM $sql_table INNER JOIN `contact` ON `contact`.`id` = `item`.`contact-id`
			WHERE `item`.`uid` = %d AND `item`.`visible` = 1
			AND `item`.`deleted` = 0 and `item`.`moderated` = 0
			$simple_update
			AND `contact`.`blocked` = 0 AND `contact`.`pending` = 0
			$sql_extra $sql_nets
			ORDER BY `item`.`id` DESC $pager_sql ",
			intval($_SESSION['uid'])
		);

	}
	else {

		// Normal conversation view


		if($order === 'post')
				$ordering = "`created`";
		else
				$ordering = "`commented`";

		$start = dba_timer();

		$r = q("SELECT `item`.`id` AS `item_id`, `contact`.`uid` AS `contact_uid`
			FROM $sql_table INNER JOIN `contact` ON `contact`.`id` = `item`.`contact-id`
			WHERE `item`.`uid` = %d AND `item`.`visible` = 1 AND `item`.`deleted` = 0
			AND `item`.`moderated` = 0 AND `contact`.`blocked` = 0 AND `contact`.`pending` = 0
			AND `item`.`parent` = `item`.`id`
			$sql_extra3 $sql_extra $sql_nets
			ORDER BY `item`.$ordering DESC $pager_sql ",
			intval(local_user())
		);

		$first = dba_timer();


		// Then fetch all the children of the parents that are on this page

		$parents_arr = array();
		$parents_str = '';

		if (dbm::is_result($r)) {
			foreach($r as $rr)
				if(! in_array($rr['item_id'],$parents_arr))
					$parents_arr[] = $rr['item_id'];
			$parents_str = implode(', ', $parents_arr);

			$items = q("SELECT `item`.*, `item`.`id` AS `item_id`,
				`contact`.`name`, `contact`.`photo`, `contact`.`url`, `contact`.`alias`, `contact`.`rel`, `contact`.`writable`,
				`contact`.`network`, `contact`.`thumb`, `contact`.`dfrn-id`, `contact`.`self`,
				`contact`.`id` AS `cid`
				FROM $sql_table INNER JOIN `contact` ON `contact`.`id` = `item`.`contact-id`
				WHERE `item`.`uid` = %d AND `item`.`visible` = 1 AND `item`.`deleted` = 0
				AND `item`.`moderated` = 0
				AND `contact`.`blocked` = 0 AND `contact`.`pending` = 0
				AND `item`.`parent` IN ( %s )
				$sql_extra ",
				intval(local_user()),
				dbesc($parents_str)
			);

			$second = dba_timer();

			$items = conv_sort($items,$ordering);

		} else {
			$items = array();
		}
	}


	logger('parent dba_timer: ' . sprintf('%01.4f',$first - $start));
	logger('child  dba_timer: ' . sprintf('%01.4f',$second - $first));

	// Set this so that the conversation function can find out contact info for our wall-wall items
	$a->page_contact = $a->contact;

	$mode = (($nouveau) ? 'network-new' : 'network');

	$o = render_content($a,$items,$mode,false);


	header('Content-type: application/json');
	echo json_encode($o);
	killme();
}



function render_content(App $a, $items, $mode, $update, $preview = false) {

	require_once('include/bbcode.php');
	require_once('mod/proxy.php');

	$ssl_state = ((local_user()) ? true : false);

	$profile_owner = 0;
	$page_writeable      = false;

	$previewing = (($preview) ? ' preview ' : '');

	$edited = false;
	if (strcmp($item['created'], $item['edited'])<>0) {
		$edited = array(
			'label' => t('This entry was edited'),
			'date' => datetime_convert('UTC', date_default_timezone_get(), $item['edited'], 'r'),
			'relative' => relative_date($item['edited'])
		);
	}

	if($mode === 'network') {
		$profile_owner = local_user();
		$page_writeable = true;
	}

	if($mode === 'profile') {
		$profile_owner = $a->profile['profile_uid'];
		$page_writeable = can_write_wall($a,$profile_owner);
	}

	if($mode === 'notes') {
		$profile_owner = local_user();
		$page_writeable = true;
	}

	if($mode === 'display') {
		$profile_owner = $a->profile['uid'];
		$page_writeable = can_write_wall($a,$profile_owner);
	}

	if($mode === 'community') {
		$profile_owner = 0;
		$page_writeable = false;
	}

	if($update)
		$return_url = $_SESSION['return_url'];
	else
		$return_url = $_SESSION['return_url'] = $a->query_string;

	$cb = array('items' => $items, 'mode' => $mode, 'update' => $update, 'preview' => $preview);
	call_hooks('conversation_start',$cb);

	$items = $cb['items'];

	$cmnt_tpl    = get_markup_template('comment_item.tpl');
	$tpl         = 'wall_item.tpl';
	$wallwall    = 'wallwall_item.tpl';
	$hide_comments_tpl = get_markup_template('hide_comments.tpl');

	$conv_responses = array(
		'like' => array('title' => t('Likes','title')), 'dislike' => array('title' => t('Dislikes','title')),
		'attendyes' => array('title' => t('Attending','title')), 'attendno' => array('title' => t('Not attending','title')), 'attendmaybe' => array('title' => t('Might attend','title'))
	);


	// array with html for each thread (parent+comments)
	$threads = array();
	$threadsid = -1;

	if($items && count($items)) {

		if($mode === 'network-new' || $mode === 'search' || $mode === 'community') {

			// "New Item View" on network page or search page results
			// - just loop through the items and format them minimally for display

			//$tpl = get_markup_template('search_item.tpl');
			$tpl = 'search_item.tpl';

			foreach($items as $item) {
				$threadsid++;

				$comment     = '';
				$owner_url   = '';
				$owner_photo = '';
				$owner_name  = '';
				$sparkle     = '';

				if($mode === 'search' || $mode === 'community') {
					if(((activity_match($item['verb'],ACTIVITY_LIKE))
						|| (activity_match($item['verb'],ACTIVITY_DISLIKE))
						|| activity_match($item['verb'],ACTIVITY_ATTEND)
						|| activity_match($item['verb'],ACTIVITY_ATTENDNO)
						|| activity_match($item['verb'],ACTIVITY_ATTENDMAYBE))
						&& ($item['id'] != $item['parent']))
						continue;
					$nickname = $item['nickname'];
				}
				else
					$nickname = $a->user['nickname'];

				// prevent private email from leaking.
				if($item['network'] === NETWORK_MAIL && local_user() != $item['uid'])
						continue;

				$profile_name   = ((strlen($item['author-name']))   ? $item['author-name']   : $item['name']);
				if($item['author-link'] && (! $item['author-name']))
					$profile_name = $item['author-link'];



				$sp = false;
				$profile_link = best_link_url($item,$sp);
				if($profile_link === 'mailbox')
					$profile_link = '';
				if($sp)
					$sparkle = ' sparkle';
				else
					$profile_link = zrl($profile_link);

				// Don't rely on the author-avatar. It is better to use the data from the contact table
				$author_contact = get_contact_details_by_url($item['author-link'], $profile_owner);
				if ($author_contact["thumb"])
					$profile_avatar = $author_contact["thumb"];
				else
					$profile_avatar = $item['author-avatar'];

				$locate = array('location' => $item['location'], 'coord' => $item['coord'], 'html' => '');
				call_hooks('render_location',$locate);

				$location = ((strlen($locate['html'])) ? $locate['html'] : render_location_dummy($locate));

				localize_item($item);
				if($mode === 'network-new')
					$dropping = true;
				else
					$dropping = false;


				$drop = array(
					'dropping' => $dropping,
					'select' => t('Select'),
					'delete' => t('Delete'),
				);

				$star = false;
				$isstarred = "unstarred";

				$lock = false;
				$likebuttons = false;
				$shareable = false;

				$body = prepare_body($item,true);

				if($a->theme['template_engine'] === 'internal') {
					$name_e = template_escape($profile_name);
					$title_e = template_escape($item['title']);
					$body_e = template_escape($body);
					$text_e = strip_tags(template_escape($body));
					$location_e = template_escape($location);
					$owner_name_e = template_escape($owner_name);
				}
				else {
					$name_e = $profile_name;
					$title_e = $item['title'];
					$body_e = $body;
					$text_e = strip_tags($body);
					$location_e = $location;
					$owner_name_e = $owner_name;
				}

				//$tmp_item = replace_macros($tpl,array(
				$tmp_item = array(
					'template' => $tpl,
					'id' => (($preview) ? 'P0' : $item['item_id']),
					'linktitle' => sprintf( t('View %s\'s profile @ %s'), $profile_name, ((strlen($item['author-link'])) ? $item['author-link'] : $item['url'])),
					'profile_url' => $profile_link,
					'item_photo_menu' => item_photo_menu($item),
					'name' => $name_e,
					'sparkle' => $sparkle,
					'lock' => $lock,
					'thumb' => proxy_url($profile_avatar, false, PROXY_SIZE_THUMB),
					'title' => $title_e,
					'body' => $body_e,
					'text' => $text_e,
					'ago' => (($item['app']) ? sprintf( t('%s from %s'),relative_date($item['created']),$item['app']) : relative_date($item['created'])),
					'location' => $location_e,
					'indent' => '',
					'owner_name' => $owner_name_e,
					'owner_url' => $owner_url,
					'owner_photo' => proxy_url($owner_photo, false, PROXY_SIZE_THUMB),
					'plink' => get_plink($item),
					'edpost' => false,
					'isstarred' => $isstarred,
					'star' => $star,
					'drop' => $drop,
					'vote' => $likebuttons,
					'like' => '',
					'dislike' => '',
					'comment' => '',
					//'conv' => (($preview) ? '' : array('href'=> App::get_baseurl($ssl_state) . '/display/' . $nickname . '/' . $item['id'], 'title'=> t('View in context'))),
					'conv' => (($preview) ? '' : array('href'=> App::get_baseurl($ssl_state).'/display/'.$item['guid'], 'title'=> t('View in context'))),
					'previewing' => $previewing,
					'wait' => t('Please wait'),
				);

				$arr = array('item' => $item, 'output' => $tmp_item);
				call_hooks('display_item', $arr);

				$threads[$threadsid]['id'] = $item['item_id'];
				$threads[$threadsid]['items'] = array($arr['output']);

			}

		}
		else
		{
			// Normal View


			// Figure out how many comments each parent has
			// (Comments all have gravity of 6)
			// Store the result in the $comments array

			$comments = array();
			foreach($items as $item) {
				if((intval($item['gravity']) == 6) && ($item['id'] != $item['parent'])) {
					if(! x($comments,$item['parent']))
						$comments[$item['parent']] = 1;
					else
						$comments[$item['parent']] += 1;
				} elseif(! x($comments,$item['parent']))
					$comments[$item['parent']] = 0; // avoid notices later on
			}

			// map all the like/dislike/attendance activities for each parent item
			// Store these in the $alike and $dlike arrays

			foreach($items as $item) {
				builtin_activity_puller($item, $conv_responses);
			}

			$comments_collapsed = false;
			$comments_seen = 0;
			$comment_lastcollapsed = false;
			$comment_firstcollapsed = false;
			$blowhard = 0;
			$blowhard_count = 0;


			foreach($items as $item) {

				$comment = '';
				$template = $tpl;
				$commentww = '';
				$sparkle = '';
				$owner_url = $owner_photo = $owner_name = '';

				// We've already parsed out like/dislike for special treatment. We can ignore them now

				if(((activity_match($item['verb'],ACTIVITY_LIKE))
					|| (activity_match($item['verb'],ACTIVITY_DISLIKE)
					|| activity_match($item['verb'],ACTIVITY_ATTEND)
					|| activity_match($item['verb'],ACTIVITY_ATTENDNO)
					|| activity_match($item['verb'],ACTIVITY_ATTENDMAYBE)))
					&& ($item['id'] != $item['parent']))
					continue;

				$toplevelpost = (($item['id'] == $item['parent']) ? true : false);


				// Take care of author collapsing and comment collapsing
				// (author collapsing is currently disabled)
				// If a single author has more than 3 consecutive top-level posts, squash the remaining ones.
				// If there are more than two comments, squash all but the last 2.

				if($toplevelpost) {

					$item_writeable = (($item['writable'] || $item['self']) ? true : false);

					$comments_seen = 0;
					$comments_collapsed = false;
					$comment_lastcollapsed  = false;
					$comment_firstcollapsed = false;

					$threadsid++;
					$threads[$threadsid]['id'] = $item['item_id'];
					$threads[$threadsid]['private'] = $item['private'];
					$threads[$threadsid]['items'] = array();

				}
				else {

					// prevent private email reply to public conversation from leaking.
					if($item['network'] === NETWORK_MAIL && local_user() != $item['uid'])
							continue;

					$comments_seen ++;
					$comment_lastcollapsed  = false;
					$comment_firstcollapsed = false;
				}

				$override_comment_box = ((($page_writeable) && ($item_writeable)) ? true : false);
				$show_comment_box = ((($page_writeable) && ($item_writeable) && ($comments_seen == $comments[$item['parent']])) ? true : false);


				if(($comments[$item['parent']] > 2) && ($comments_seen <= ($comments[$item['parent']] - 2)) && ($item['gravity'] == 6)) {

					if (!$comments_collapsed){
						$threads[$threadsid]['num_comments'] = sprintf( tt('%d comment','%d comments',$comments[$item['parent']]),$comments[$item['parent']] );
						$threads[$threadsid]['hidden_comments_num'] = $comments[$item['parent']];
						$threads[$threadsid]['hidden_comments_text'] = tt('comment', 'comments', $comments[$item['parent']]);
						$threads[$threadsid]['hide_text'] = t('show more');
						$comments_collapsed = true;
						$comment_firstcollapsed = true;
					}
				}
				if(($comments[$item['parent']] > 2) && ($comments_seen == ($comments[$item['parent']] - 1))) {

					$comment_lastcollapsed = true;
				}

				$redirect_url = 'redir/' . $item['cid'] ;

				$lock = ((($item['private'] == 1) || (($item['uid'] == local_user()) && (strlen($item['allow_cid']) || strlen($item['allow_gid'])
					|| strlen($item['deny_cid']) || strlen($item['deny_gid']))))
					? t('Private Message')
					: false);


				// Top-level wall post not written by the wall owner (wall-to-wall)
				// First figure out who owns it.

				$osparkle = '';

				if(($toplevelpost) && (! $item['self']) && ($mode !== 'profile')) {

					if($item['wall']) {

						// On the network page, I am the owner. On the display page it will be the profile owner.
						// This will have been stored in $a->page_contact by our calling page.
						// Put this person as the wall owner of the wall-to-wall notice.

						$owner_url = zrl($a->page_contact['url']);
						$owner_photo = $a->page_contact['thumb'];
						$owner_name = $a->page_contact['name'];
						$template = $wallwall;
						$commentww = 'ww';
					}

					if((! $item['wall']) && $item['owner-link']) {

						$owner_linkmatch = (($item['owner-link']) && link_compare($item['owner-link'],$item['author-link']));
						$alias_linkmatch = (($item['alias']) && link_compare($item['alias'],$item['author-link']));
						$owner_namematch = (($item['owner-name']) && $item['owner-name'] == $item['author-name']);
						if((! $owner_linkmatch) && (! $alias_linkmatch) && (! $owner_namematch)) {

							// The author url doesn't match the owner (typically the contact)
							// and also doesn't match the contact alias.
							// The name match is a hack to catch several weird cases where URLs are
							// all over the park. It can be tricked, but this prevents you from
							// seeing "Bob Smith to Bob Smith via Wall-to-wall" and you know darn
							// well that it's the same Bob Smith.

							// But it could be somebody else with the same name. It just isn't highly likely.


							$owner_url = $item['owner-link'];
							$owner_photo = $item['owner-avatar'];
							$owner_name = $item['owner-name'];
							$template = $wallwall;
							$commentww = 'ww';
							// If it is our contact, use a friendly redirect link
							if((link_compare($item['owner-link'],$item['url']))
								&& ($item['network'] === NETWORK_DFRN)) {
								$owner_url = $redirect_url;
								$osparkle = ' sparkle';
							}
							else
								$owner_url = zrl($owner_url);
						}
					}
				}

				$likebuttons = '';
				$shareable = ((($profile_owner == local_user()) && ($item['private'] != 1)) ? true : false);

				if($page_writeable) {
/*					if($toplevelpost) {  */
						$likebuttons = array(
							'like' => array( t("I like this \x28toggle\x29"), t("like")),
							'dislike' => array( t("I don't like this \x28toggle\x29"), t("dislike")),
						);
						if ($shareable) $likebuttons['share'] = array( t('Share this'), t('share'));
/*					} */

					$qc = $qcomment =  null;

					if(in_array('qcomment',$a->plugins)) {
						$qc = ((local_user()) ? get_pconfig(local_user(),'qcomment','words') : null);
						$qcomment = (($qc) ? explode("\n",$qc) : null);
					}

					if(($show_comment_box) || (($show_comment_box == false) && ($override_comment_box == false) && ($item['last-child']))) {
						$comment = replace_macros($cmnt_tpl,array(
							'$return_path' => '',
							'$jsreload' => (($mode === 'display') ? $_SESSION['return_url'] : ''),
							'$type' => (($mode === 'profile') ? 'wall-comment' : 'net-comment'),
							'$id' => $item['item_id'],
							'$parent' => $item['parent'],
							'$qcomment' => $qcomment,
							'$profile_uid' =>  $profile_owner,
							'$mylink' => $a->contact['url'],
							'$mytitle' => t('This is you'),
							'$myphoto' => $a->contact['thumb'],
							'$comment' => t('Comment'),
							'$submit' => t('Submit'),
							'$edbold' => t('Bold'),
							'$editalic' => t('Italic'),
							'$eduline' => t('Underline'),
							'$edquote' => t('Quote'),
							'$edcode' => t('Code'),
							'$edimg' => t('Image'),
							'$edurl' => t('Link'),
							'$edvideo' => t('Video'),
							'$preview' => t('Preview'),
							'$sourceapp' => t($a->sourcename),
							'$ww' => (($mode === 'network') ? $commentww : ''),
							'$rand_num' => random_digits(12)
						));
					}
				}

				if (local_user() && link_compare($a->contact['url'],$item['author-link'])) {
					$edpost = array(App::get_baseurl($ssl_state)."/editpost/".$item['id'], t("Edit"));
				} else {
					$edpost = false;
				}

				$drop = '';
				$dropping = false;

				if((intval($item['contact-id']) && $item['contact-id'] == remote_user()) || ($item['uid'] == local_user()))
					$dropping = true;

				$drop = array(
					'dropping' => $dropping,
					'select' => t('Select'),
					'delete' => t('Delete'),
				);

				$star = false;
				$filer = false;

				$isstarred = "unstarred";
				if ($profile_owner == local_user()) {
					if ($toplevelpost) {
						$isstarred = (($item['starred']) ? "starred" : "unstarred");

						$star = array(
							'do' => t("add star"),
							'undo' => t("remove star"),
							'toggle' => t("toggle star status"),
							'classdo' => (($item['starred']) ? "hidden" : ""),
							'classundo' => (($item['starred']) ? "" : "hidden"),
							'starred' =>  t('starred'),
							'tagger' => t("add tag"),
							'classtagger' => "",
						);

						$r = q("SELECT `ignored` FROM `thread` WHERE `uid` = %d AND `iid` = %d LIMIT 1",
							intval($item['uid']),
							intval($item['id'])
						);

						if (dbm::is_result($r)) {
							$ignore = array(
								'do' => t("ignore thread"),
								'undo' => t("unignore thread"),
								'toggle' => t("toggle ignore status"),
								'classdo' => (($r[0]['ignored']) ? "hidden" : ""),
								'classundo' => (($r[0]['ignored']) ? "" : "hidden"),
								'ignored' =>  t('ignored'),
							);
						}
						$tagger = '';
						if (feature_enabled($profile_owner,'commtag')) {
							$tagger = array(
								'add' => t("add tag"),
								'class' => "",
							);
						}
					}
					$filer = t("save to folder");
				}


				$photo = $item['photo'];
				$thumb = $item['thumb'];

				// Post was remotely authored.

				$diff_author    = ((link_compare($item['url'],$item['author-link'])) ? false : true);

				$profile_name   = (((strlen($item['author-name']))   && $diff_author) ? $item['author-name']   : $item['name']);

				if($item['author-link'] && (! $item['author-name']))
					$profile_name = $item['author-link'];

				$sp = false;
				$profile_link = best_link_url($item,$sp);
				if ($profile_link === 'mailbox') {
					$profile_link = '';
				}
				if ($sp) {
					$sparkle = ' sparkle';
				} else {
					$profile_link = zrl($profile_link);
				}

				// Don't rely on the author-avatar. It is better to use the data from the contact table
				$author_contact = get_contact_details_by_url($item['author-link'], $profile_owner);
				if ($author_contact["thumb"]) {
					$profile_avatar = $author_contact["thumb"];
				} else {
					$profile_avatar = $item['author-avatar'];
				}

				$like    = ((x($conv_responses['like'],$item['uri'])) ? format_like($conv_responses['like'][$item['uri']],$conv_responses['like'][$item['uri'] . '-l'],'like',$item['uri']) : '');
				$dislike = ((x($conv_responses['dislike'],$item['uri'])) ? format_like($conv_responses['dislike'][$item['uri']],$conv_responses['dislike'][$item['uri'] . '-l'],'dislike',$item['uri']) : '');

				// process action responses - e.g. like/dislike/attend/agree/whatever
				$response_verbs = array('like');
				if(feature_enabled($profile_owner,'dislike'))
					$response_verbs[] = 'dislike';
				if($item['object-type'] === ACTIVITY_OBJ_EVENT) {
					$response_verbs[] = 'attendyes';
					$response_verbs[] = 'attendno';
					$response_verbs[] = 'attendmaybe';
					if($page_writeable) {
						$isevent = true;
						$attend = array( t('I will attend'), t('I will not attend'), t('I might attend'));
					}
				}
				$responses = get_responses($conv_responses,$response_verbs,'',$item);

				$locate = array('location' => $item['location'], 'coord' => $item['coord'], 'html' => '');
				call_hooks('render_location',$locate);

				$location = ((strlen($locate['html'])) ? $locate['html'] : render_location_dummy($locate));

				$indent = (($toplevelpost) ? '' : ' comment');

				$shiny = "";
				if(strcmp(datetime_convert('UTC','UTC',$item['created']),datetime_convert('UTC','UTC','now - 12 hours')) > 0)
					$shiny = 'shiny';

				//
				localize_item($item);


				$tags=array();
				foreach(explode(',',$item['tag']) as $tag){
					$tag = trim($tag);
					if ($tag!="") $tags[] = bbcode($tag);
				}

				// Build the HTML

				$body = prepare_body($item,true);
				//$tmp_item = replace_macros($template,

				if($a->theme['template_engine'] === 'internal') {
					$body_e = template_escape($body);
					$text_e = strip_tags(template_escape($body));
					$name_e = template_escape($profile_name);
					$title_e = template_escape($item['title']);
					$location_e = template_escape($location);
					$owner_name_e = template_escape($owner_name);
				}
				else {
					$body_e = $body;
					$text_e = strip_tags($body);
					$name_e = $profile_name;
					$title_e = $item['title'];
					$location_e = $location;
					$owner_name_e = $owner_name;
				}

				$tmp_item = array(
					// collapse comments in template. I don't like this much...
					'comment_firstcollapsed' => $comment_firstcollapsed,
					'comment_lastcollapsed' => $comment_lastcollapsed,
					// template to use to render item (wall, walltowall, search)
					'template' => $template,

					'type' => implode("",array_slice(explode("/",$item['verb']),-1)),
					'tags' => $tags,
					'body' => $body_e,
					'text' => $text_e,
					'id' => $item['item_id'],
					'isevent' => $isevent,
					'attend' => $attend,
					'linktitle' => sprintf( t('View %s\'s profile @ %s'), $profile_name, ((strlen($item['author-link'])) ? $item['author-link'] : $item['url'])),
					'olinktitle' => sprintf( t('View %s\'s profile @ %s'), $profile_name, ((strlen($item['owner-link'])) ? $item['owner-link'] : $item['url'])),
					'to' => t('to'),
					'wall' => t('Wall-to-Wall'),
					'vwall' => t('via Wall-To-Wall:'),
					'profile_url' => $profile_link,
					'item_photo_menu' => item_photo_menu($item),
					'name' => $name_e,
					'thumb' => proxy_url($profile_avatar, false, PROXY_SIZE_THUMB),
					'osparkle' => $osparkle,
					'sparkle' => $sparkle,
					'title' => $title_e,
					'localtime' => datetime_convert('UTC', date_default_timezone_get(), $item['created'], 'r'),
					'ago' => (($item['app']) ? sprintf( t('%s from %s'),relative_date($item['created']),$item['app']) : relative_date($item['created'])),
					'app' => $item['app'],
					'created' => relative_date($item['created']),
					'lock' => $lock,
					'location' => $location_e,
					'indent' => $indent,
					'shiny' => $shiny,
					'owner_url' => $owner_url,
					'owner_photo' => proxy_url($owner_photo, false, PROXY_SIZE_THUMB),
					'owner_name' => $owner_name_e,
					'plink' => get_plink($item),
					'edpost' => $edpost,
					'isstarred' => $isstarred,
					'star' => $star,
					'ignore'  => ((feature_enabled($profile_owner,'ignore_posts')) ? $ignore : ''),
					'tagger' => $tagger,
					'filer' => ((feature_enabled($profile_owner,'filing')) ? $filer : ''),
					'drop' => $drop,
					'vote' => $likebuttons,
					'responses' => $responses,
					'like' => $like,
					'dislike' => $dislike,
					'switchcomment' => t('Comment'),
					'comment' => $comment,
					'previewing' => $previewing,
					'wait' => t('Please wait'),
					'edited' => $edited,
					'network' => $item["item_network"],
					'network_name' => network_to_name($item['network'], $profile_link),

				);


				$arr = array('item' => $item, 'output' => $tmp_item);
				call_hooks('display_item', $arr);

				$threads[$threadsid]['items'][] = $arr['output'];
			}
		}
	}


	return $threads;

}
