<?php
include_once("include/bbcode.php");
include_once("include/contact_selectors.php");
include_once("include/Scrape.php");

function notifications_post(&$a) {

	if(! local_user()) {
		goaway(z_root());
	}

	$request_id = (($a->argc > 1) ? $a->argv[1] : 0);

	if($request_id === "all")
		return;

	if($request_id) {

		$r = q("SELECT * FROM `intro` WHERE `id` = %d  AND `uid` = %d LIMIT 1",
			intval($request_id),
			intval(local_user())
		);

		if(count($r)) {
			$intro_id = $r[0]['id'];
			$contact_id = $r[0]['contact-id'];
		}
		else {
			notice( t('Invalid request identifier.') . EOL);
			return;
		}

		// If it is a friend suggestion, the contact is not a new friend but an existing friend
		// that should not be deleted.

		$fid = $r[0]['fid'];

		if($_POST['submit'] == t('Discard')) {
			$r = q("DELETE FROM `intro` WHERE `id` = %d",
				intval($intro_id)
			);
			if(! $fid) {

				// The check for blocked and pending is in case the friendship was already approved
				// and we just want to get rid of the now pointless notification

				$r = q("DELETE FROM `contact` WHERE `id` = %d AND `uid` = %d AND `self` = 0 AND `blocked` = 1 AND `pending` = 1",
					intval($contact_id),
					intval(local_user())
				);
			}
			goaway($a->get_baseurl(true) . '/notifications/intros');
		}
		if($_POST['submit'] == t('Ignore')) {
			$r = q("UPDATE `intro` SET `ignore` = 1 WHERE `id` = %d",
				intval($intro_id));
			goaway($a->get_baseurl(true) . '/notifications/intros');
		}
	}
}





function notifications_content(&$a) {

	if(! local_user()) {
		notice( t('Permission denied.') . EOL);
		return;
	}

	nav_set_selected('notifications');

	$json = (($a->argc > 1 && $a->argv[$a->argc - 1] === 'json') ? true : false);


	$o = '';
	$tabs = array(
		array(
			'label' => t('System'),
			'url'=>$a->get_baseurl(true) . '/notifications/system',
			'sel'=> (($a->argv[1] == 'system') ? 'active' : ''),
			'accesskey' => 'y',
		),
		array(
			'label' => t('Network'),
			'url'=>$a->get_baseurl(true) . '/notifications/network',
			'sel'=> (($a->argv[1] == 'network') ? 'active' : ''),
			'accesskey' => 'w',
		),
		array(
			'label' => t('Personal'),
			'url'=>$a->get_baseurl(true) . '/notifications/personal',
			'sel'=> (($a->argv[1] == 'personal') ? 'active' : ''),
			'accesskey' => 'r',
		),
		array(
			'label' => t('Home'),
			'url' => $a->get_baseurl(true) . '/notifications/home',
			'sel'=> (($a->argv[1] == 'home') ? 'active' : ''),
			'accesskey' => 'h',
		),
		array(
			'label' => t('Introductions'),
			'url' => $a->get_baseurl(true) . '/notifications/intros',
			'sel'=> (($a->argv[1] == 'intros') ? 'active' : ''),
			'accesskey' => 'i',
		),
		/*array(
			'label' => t('Messages'),
			'url' => $a->get_baseurl(true) . '/message',
			'sel'=> '',
		),*/ /*while I can have notifications for messages, this tablist is not place for message page link */
	);

	$o = "";


	if( (($a->argc > 1) && ($a->argv[1] == 'intros')) || (($a->argc == 1))) {
		nav_set_selected('introductions');
		if(($a->argc > 2) && ($a->argv[2] == 'all'))
			$sql_extra = '';
		else
			$sql_extra = " AND `ignore` = 0 ";

		$notif_tpl = get_markup_template('notifications.tpl');

		$notif_content .= '<a href="' . ((strlen($sql_extra)) ? 'notifications/intros/all' : 'notifications/intros' ) . '" id="notifications-show-hide-link" >'
			. ((strlen($sql_extra)) ? t('Show Ignored Requests') : t('Hide Ignored Requests')) . '</a></div>' . "\r\n";

		$r = q("SELECT COUNT(*)	AS `total` FROM `intro`
			WHERE `intro`.`uid` = %d $sql_extra AND `intro`.`blocked` = 0 ",
				intval($_SESSION['uid'])
		);
		if($r && count($r)) {
			$a->set_pager_total($r[0]['total']);
			$a->set_pager_itemspage(20);
		}

		$r = q("SELECT `intro`.`id` AS `intro_id`, `intro`.*, `contact`.*, `fcontact`.`name` AS `fname`,`fcontact`.`url` AS `furl`,`fcontact`.`photo` AS `fphoto`,`fcontact`.`request` AS `frequest`,
				`gcontact`.`location` AS `glocation`, `gcontact`.`about` AS `gabout`,
				`gcontact`.`keywords` AS `gkeywords`, `gcontact`.`gender` AS `ggender`,
				`gcontact`.`network` AS `gnetwork`
			FROM `intro`
				LEFT JOIN `contact` ON `contact`.`id` = `intro`.`contact-id`
				LEFT JOIN `gcontact` ON `gcontact`.`nurl` = `contact`.`nurl`
				LEFT JOIN `fcontact` ON `intro`.`fid` = `fcontact`.`id`
			WHERE `intro`.`uid` = %d $sql_extra AND `intro`.`blocked` = 0 ",
				intval($_SESSION['uid']));

		if(($r !== false) && (count($r))) {

			$sugg = get_markup_template('suggestions.tpl');
			$tpl = get_markup_template("intros.tpl");

			foreach($r as $rr) {

				if($rr['fid']) {

					$return_addr = bin2hex($a->user['nickname'] . '@' . $a->get_hostname() . (($a->path) ? '/' . $a->path : ''));

					$notif_content .= replace_macros($sugg, array(
						'$str_notifytype' => t('Notification type: '),
						'$notify_type' => t('Friend Suggestion'),
						'$intro_id' => $rr['intro_id'],
						'$madeby' => sprintf( t('suggested by %s'),$rr['name']),
						'$contact_id' => $rr['contact-id'],
						'$photo' => ((x($rr,'fphoto')) ? proxy_url($rr['fphoto'], false, PROXY_SIZE_SMALL) : "images/person-175.jpg"),
						'$fullname' => $rr['fname'],
						'$url' => zrl($rr['furl']),
						'$hidden' => array('hidden', t('Hide this contact from others'), ($rr['hidden'] == 1), ''),
						'$activity' => array('activity', t('Post a new friend activity'), (intval(get_pconfig(local_user(),'system','post_newfriend')) ? '1' : 0), t('if applicable')),

						'$knowyou' => $knowyou,
						'$approve' => t('Approve'),
						'$note' => $rr['note'],
						'$request' => $rr['frequest'] . '?addr=' . $return_addr,
						'$ignore' => t('Ignore'),
						'$discard' => t('Discard'),

					));

					continue;

				}
				$friend_selected = (($rr['network'] !== NETWORK_OSTATUS) ? ' checked="checked" ' : ' disabled ');
				$fan_selected = (($rr['network'] === NETWORK_OSTATUS) ? ' checked="checked" disabled ' : '');
				$dfrn_tpl = get_markup_template('netfriend.tpl');

				$knowyou   = '';
				$dfrn_text = '';

				if($rr['network'] === NETWORK_DFRN || $rr['network'] === NETWORK_DIASPORA) {
					if($rr['network'] === NETWORK_DFRN) {
						$knowyou = t('Claims to be known to you: ') . (($rr['knowyou']) ? t('yes') : t('no'));
						$helptext = t('Shall your connection be bidirectional or not? "Friend" implies that you allow to read and you subscribe to their posts. "Fan/Admirer" means that you allow to read but you do not want to read theirs. Approve as: ');
					} else {
						$knowyou = '';
						$helptext = t('Shall your connection be bidirectional or not? "Friend" implies that you allow to read and you subscribe to their posts. "Sharer" means that you allow to read but you do not want to read theirs. Approve as: ');
					}

					$dfrn_text = replace_macros($dfrn_tpl,array(
						'$intro_id' => $rr['intro_id'],
						'$friend_selected' => $friend_selected,
						'$fan_selected' => $fan_selected,
						'$approve_as' => $helptext,
						'$as_friend' => t('Friend'),
						'$as_fan' => (($rr['network'] == NETWORK_DIASPORA) ? t('Sharer') : t('Fan/Admirer'))
					));
				}

				$header = $rr["name"];

				$ret = probe_url($rr["url"]);

				if ($rr['gnetwork'] == "")
					$rr['gnetwork'] = $ret["network"];

				if ($ret["addr"] != "")
					$header .= " <".$ret["addr"].">";

				$header .= " (".network_to_name($rr['gnetwork'], $rr['url']).")";

				// Don't show these data until you are connected. Diaspora is doing the same.
				if($rr['gnetwork'] === NETWORK_DIASPORA) {
					$rr['glocation'] = "";
					$rr['gabout'] = "";
					$rr['ggender'] = "";
				}

				$notif_content .= replace_macros($tpl, array(
					'$header' => htmlentities($header),
					'$str_notifytype' => t('Notification type: '),
					'$notify_type' => (($rr['network'] !== NETWORK_OSTATUS) ? t('Friend/Connect Request') : t('New Follower')),
					'$dfrn_text' => $dfrn_text,
					'$dfrn_id' => $rr['issued-id'],
					'$uid' => $_SESSION['uid'],
					'$intro_id' => $rr['intro_id'],
					'$contact_id' => $rr['contact-id'],
					'$photo' => ((x($rr,'photo')) ? proxy_url($rr['photo'], false, PROXY_SIZE_SMALL) : "images/person-175.jpg"),
					'$fullname' => $rr['name'],
					'$location' => bbcode($rr['glocation'], false, false),
					'$location_label' => t('Location:'),
					'$about' => bbcode($rr['gabout'], false, false),
					'$about_label' => t('About:'),
					'$keywords' => $rr['gkeywords'],
					'$keywords_label' => t('Tags:'),
					'$gender' => $rr['ggender'],
					'$gender_label' => t('Gender:'),
					'$hidden' => array('hidden', t('Hide this contact from others'), ($rr['hidden'] == 1), ''),
					'$activity' => array('activity', t('Post a new friend activity'), (intval(get_pconfig(local_user(),'system','post_newfriend')) ? '1' : 0), t('if applicable')),
					'$url' => $rr['url'],
					'$zrl' => zrl($rr['url']),
					'$url_label' => t('Profile URL'),
					'$knowyou' => $knowyou,
					'$approve' => t('Approve'),
					'$note' => $rr['note'],
					'$ignore' => t('Ignore'),
					'$discard' => t('Discard'),

				));
			}
		}
		else
			info( t('No introductions.') . EOL);

		$o .= replace_macros($notif_tpl, array(
			'$notif_header' => t('Notifications'),
			'$tabs' => $tabs,
			'$notif_content' => $notif_content,
		));

		$o .= paginate($a);
		return $o;

	} else if (($a->argc > 1) && ($a->argv[1] == 'network')) {

		$notif_tpl = get_markup_template('notifications.tpl');

		$r = q("SELECT `item`.`id`,`item`.`parent`, `item`.`verb`, `item`.`author-name`,
				`item`.`author-link`, `item`.`author-avatar`, `item`.`created`, `item`.`object` as `object`,
				`pitem`.`author-name` as `pname`, `pitem`.`author-link` as `plink`, `pitem`.`guid` as `pguid`
				FROM `item` INNER JOIN `item` as `pitem` ON  `pitem`.`id`=`item`.`parent`
				WHERE `item`.`unseen` = 1 AND `item`.`visible` = 1 AND `pitem`.`parent` != 0 AND
				 `item`.`deleted` = 0 AND `item`.`uid` = %d AND `item`.`wall` = 0 ORDER BY `item`.`created` DESC" ,
			intval(local_user())
		);

		$tpl_item_likes = get_markup_template('notifications_likes_item.tpl');
		$tpl_item_dislikes = get_markup_template('notifications_dislikes_item.tpl');
		$tpl_item_friends = get_markup_template('notifications_friends_item.tpl');
		$tpl_item_comments = get_markup_template('notifications_comments_item.tpl');
		$tpl_item_posts = get_markup_template('notifications_posts_item.tpl');

		$notif_content = '';

		if ($r) {

			foreach ($r as $it) {
				switch($it['verb']){
					case ACTIVITY_LIKE:
						$notif_content .= replace_macros($tpl_item_likes,array(
							//'$item_link' => $a->get_baseurl(true).'/display/'.$a->user['nickname']."/".$it['parent'],
							'$item_link' => $a->get_baseurl(true).'/display/'.$it['pguid'],
							'$item_image' => proxy_url($it['author-avatar'], false, PROXY_SIZE_MICRO),
							'$item_text' => sprintf( t("%s liked %s's post"), $it['author-name'], $it['pname']),
							'$item_when' => relative_date($it['created'])
						));
						break;

					case ACTIVITY_DISLIKE:
						$notif_content .= replace_macros($tpl_item_dislikes,array(
							//'$item_link' => $a->get_baseurl(true).'/display/'.$a->user['nickname']."/".$it['parent'],
							'$item_link' => $a->get_baseurl(true).'/display/'.$it['pguid'],
							'$item_image' => proxy_url($it['author-avatar'], false, PROXY_SIZE_MICRO),
							'$item_text' => sprintf( t("%s disliked %s's post"), $it['author-name'], $it['pname']),
							'$item_when' => relative_date($it['created'])
						));
						break;

					case ACTIVITY_FRIEND:

						$xmlhead="<"."?xml version='1.0' encoding='UTF-8' ?".">";
						$obj = parse_xml_string($xmlhead.$it['object']);
						$it['fname'] = $obj->title;

						$notif_content .= replace_macros($tpl_item_friends,array(
							//'$item_link' => $a->get_baseurl(true).'/display/'.$a->user['nickname']."/".$it['parent'],
							'$item_link' => $a->get_baseurl(true).'/display/'.$it['pguid'],
							'$item_image' => proxy_url($it['author-avatar'], false, PROXY_SIZE_MICRO),
							'$item_text' => sprintf( t("%s is now friends with %s"), $it['author-name'], $it['fname']),
							'$item_when' => relative_date($it['created'])
						));
						break;

					default:
						$item_text = (($it['id'] == $it['parent'])
							? sprintf( t("%s created a new post"), $it['author-name'])
							: sprintf( t("%s commented on %s's post"), $it['author-name'], $it['pname']));
						$tpl = (($it['id'] == $it['parent']) ? $tpl_item_posts : $tpl_item_comments);

						$notif_content .= replace_macros($tpl,array(
							//'$item_link' => $a->get_baseurl(true).'/display/'.$a->user['nickname']."/".$it['parent'],
							'$item_link' => $a->get_baseurl(true).'/display/'.$it['pguid'],
							'$item_image' => proxy_url($it['author-avatar'], false, PROXY_SIZE_MICRO),
							'$item_text' => $item_text,
							'$item_when' => relative_date($it['created'])
						));
				}
			}

		} else {

			$notif_content = t('No more network notifications.');
		}

		$o .= replace_macros($notif_tpl, array(
			'$notif_header' => t('Network Notifications'),
			'$tabs' => $tabs,
			'$notif_content' => $notif_content,
		));

	} else if (($a->argc > 1) && ($a->argv[1] == 'system')) {

		$notif_tpl = get_markup_template('notifications.tpl');

		$not_tpl = get_markup_template('notify.tpl');
		require_once('include/bbcode.php');

		$r = q("SELECT * from notify where uid = %d and seen = 0 order by date desc",
			intval(local_user())
		);

		if (count($r) > 0) {
			foreach ($r as $it) {
				$notif_content .= replace_macros($not_tpl,array(
					'$item_link' => $a->get_baseurl(true).'/notify/view/'. $it['id'],
					'$item_image' => proxy_url($it['photo'], false, PROXY_SIZE_MICRO),
					'$item_text' => strip_tags(bbcode($it['msg'])),
					'$item_when' => relative_date($it['date'])
				));
			}
		} else {
			$notif_content .= t('No more system notifications.');
		}

		$o .= replace_macros($notif_tpl, array(
			'$notif_header' => t('System Notifications'),
			'$tabs' => $tabs,
			'$notif_content' => $notif_content,
		));

	} else if (($a->argc > 1) && ($a->argv[1] == 'personal')) {

		$notif_tpl = get_markup_template('notifications.tpl');

		$myurl = $a->get_baseurl(true) . '/profile/'. $a->user['nickname'];
		$myurl = substr($myurl,strpos($myurl,'://')+3);
		$myurl = str_replace(array('www.','.'),array('','\\.'),$myurl);
		$diasp_url = str_replace('/profile/','/u/',$myurl);
		$sql_extra .= sprintf(" AND ( `item`.`author-link` regexp '%s' or `item`.`tag` regexp '%s' or `item`.`tag` regexp '%s' ) ",
			dbesc($myurl . '$'),
			dbesc($myurl . '\\]'),
			dbesc($diasp_url . '\\]')
		);


		$r = q("SELECT `item`.`id`,`item`.`parent`, `item`.`verb`, `item`.`author-name`,
				`item`.`author-link`, `item`.`author-avatar`, `item`.`created`, `item`.`object` as `object`,
				`pitem`.`author-name` as `pname`, `pitem`.`author-link` as `plink`, `pitem`.`guid` as `pguid`
				FROM `item` INNER JOIN `item` as `pitem` ON  `pitem`.`id`=`item`.`parent`
				WHERE `item`.`unseen` = 1 AND `item`.`visible` = 1
				$sql_extra
				AND `item`.`deleted` = 0 AND `item`.`uid` = %d AND `item`.`wall` = 0 ORDER BY `item`.`created` DESC" ,
			intval(local_user())
		);

		$tpl_item_likes = get_markup_template('notifications_likes_item.tpl');
		$tpl_item_dislikes = get_markup_template('notifications_dislikes_item.tpl');
		$tpl_item_friends = get_markup_template('notifications_friends_item.tpl');
		$tpl_item_comments = get_markup_template('notifications_comments_item.tpl');
		$tpl_item_posts = get_markup_template('notifications_posts_item.tpl');

		$notif_content = '';

		if (count($r) > 0) {

			foreach ($r as $it) {
				switch($it['verb']){
					case ACTIVITY_LIKE:
						$notif_content .= replace_macros($tpl_item_likes,array(
							//'$item_link' => $a->get_baseurl(true).'/display/'.$a->user['nickname']."/".$it['parent'],
							'$item_link' => $a->get_baseurl(true).'/display/'.$it['pguid'],
							'$item_image' => $it['author-avatar'],
							'$item_text' => sprintf( t("%s liked %s's post"), $it['author-name'], $it['pname']),
							'$item_when' => relative_date($it['created'])
						));
						break;

					case ACTIVITY_DISLIKE:
						$notif_content .= replace_macros($tpl_item_dislikes,array(
							//'$item_link' => $a->get_baseurl(true).'/display/'.$a->user['nickname']."/".$it['parent'],
							'$item_link' => $a->get_baseurl(true).'/display/'.$it['pguid'],
							'$item_image' => $it['author-avatar'],
							'$item_text' => sprintf( t("%s disliked %s's post"), $it['author-name'], $it['pname']),
							'$item_when' => relative_date($it['created'])
						));
						break;

					case ACTIVITY_FRIEND:

						$xmlhead="<"."?xml version='1.0' encoding='UTF-8' ?".">";
						$obj = parse_xml_string($xmlhead.$it['object']);
						$it['fname'] = $obj->title;

						$notif_content .= replace_macros($tpl_item_friends,array(
							//'$item_link' => $a->get_baseurl(true).'/display/'.$a->user['nickname']."/".$it['parent'],
							'$item_link' => $a->get_baseurl(true).'/display/'.$it['pguid'],
							'$item_image' => $it['author-avatar'],
							'$item_text' => sprintf( t("%s is now friends with %s"), $it['author-name'], $it['fname']),
							'$item_when' => relative_date($it['created'])
						));
						break;

					default:
						$item_text = (($it['id'] == $it['parent'])
							? sprintf( t("%s created a new post"), $it['author-name'])
							: sprintf( t("%s commented on %s's post"), $it['author-name'], $it['pname']));
						$tpl = (($it['id'] == $it['parent']) ? $tpl_item_posts : $tpl_item_comments);

						$notif_content .= replace_macros($tpl,array(
							//'$item_link' => $a->get_baseurl(true).'/display/'.$a->user['nickname']."/".$it['parent'],
							'$item_link' => $a->get_baseurl(true).'/display/'.$it['pguid'],
							'$item_image' => $it['author-avatar'],
							'$item_text' => $item_text,
							'$item_when' => relative_date($it['created'])
						));
				}
			}

		} else {

			$notif_content = t('No more personal notifications.');
		}

		$o .= replace_macros($notif_tpl, array(
			'$notif_header' => t('Personal Notifications'),
			'$tabs' => $tabs,
			'$notif_content' => $notif_content,
		));






	} else if (($a->argc > 1) && ($a->argv[1] == 'home')) {

		$notif_tpl = get_markup_template('notifications.tpl');

		$r = q("SELECT `item`.`id`,`item`.`parent`, `item`.`verb`, `item`.`author-name`,
				`item`.`author-link`, `item`.`author-avatar`, `item`.`created`, `item`.`object` as `object`,
				`pitem`.`author-name` as `pname`, `pitem`.`author-link` as `plink`, `pitem`.`guid` as `pguid`
				FROM `item` INNER JOIN `item` as `pitem` ON  `pitem`.`id`=`item`.`parent`
				WHERE `item`.`unseen` = 1 AND `item`.`visible` = 1 AND
				 `item`.`deleted` = 0 AND `item`.`uid` = %d AND `item`.`wall` = 1 ORDER BY `item`.`created` DESC",
			intval(local_user())
		);

		$tpl_item_likes = get_markup_template('notifications_likes_item.tpl');
		$tpl_item_dislikes = get_markup_template('notifications_dislikes_item.tpl');
		$tpl_item_friends = get_markup_template('notifications_friends_item.tpl');
		$tpl_item_comments = get_markup_template('notifications_comments_item.tpl');

		$notif_content = '';

		if (count($r) > 0) {

			foreach ($r as $it) {
				switch($it['verb']){
					case ACTIVITY_LIKE:
						$notif_content .= replace_macros($tpl_item_likes,array(
							//'$item_link' => $a->get_baseurl(true).'/display/'.$a->user['nickname']."/".$it['parent'],
							'$item_link' => $a->get_baseurl(true).'/display/'.$it['pguid'],
							'$item_image' => $it['author-avatar'],
							'$item_text' => sprintf( t("%s liked %s's post"), $it['author-name'], $it['pname']),
							'$item_when' => relative_date($it['created'])
						));

						break;
					case ACTIVITY_DISLIKE:
						$notif_content .= replace_macros($tpl_item_dislikes,array(
							//'$item_link' => $a->get_baseurl(true).'/display/'.$a->user['nickname']."/".$it['parent'],
							'$item_link' => $a->get_baseurl(true).'/display/'.$it['pguid'],
							'$item_image' => $it['author-avatar'],
							'$item_text' => sprintf( t("%s disliked %s's post"), $it['author-name'], $it['pname']),
							'$item_when' => relative_date($it['created'])
						));

						break;
					case ACTIVITY_FRIEND:

						$xmlhead="<"."?xml version='1.0' encoding='UTF-8' ?".">";
						$obj = parse_xml_string($xmlhead.$it['object']);
						$it['fname'] = $obj->title;

						$notif_content .= replace_macros($tpl_item_friends,array(
							//'$item_link' => $a->get_baseurl(true).'/display/'.$a->user['nickname']."/".$it['parent'],
							'$item_link' => $a->get_baseurl(true).'/display/'.$it['pguid'],
							'$item_image' => $it['author-avatar'],
							'$item_text' => sprintf( t("%s is now friends with %s"), $it['author-name'], $it['fname']),
							'$item_when' => relative_date($it['created'])
						));

						break;
					default:
						$notif_content .= replace_macros($tpl_item_comments,array(
							//'$item_link' => $a->get_baseurl(true).'/display/'.$a->user['nickname']."/".$it['parent'],
							'$item_link' => $a->get_baseurl(true).'/display/'.$it['pguid'],
							'$item_image' => $it['author-avatar'],
							'$item_text' => sprintf( t("%s commented on %s's post"), $it['author-name'], $it['pname']),
							'$item_when' => relative_date($it['created'])
						));
				}
			}

		} else {
			$notif_content = t('No more home notifications.');
		}

		$o .= replace_macros($notif_tpl, array(
			'$notif_header' => t('Home Notifications'),
			'$tabs' => $tabs,
			'$notif_content' => $notif_content,
		));
	}

	$o .= paginate($a);
	return $o;
}
