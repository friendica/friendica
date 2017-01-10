<?php

/**
 * @file mod/notifications.php
 * @brief The notifications module
 */

require_once("include/NotificationsManager.php");
require_once("include/contact_selectors.php");
require_once("include/network.php");

function notifications_post(App &$a) {

	if (! local_user()) {
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

		if (dbm::is_result($r)) {
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
			goaway('notifications/intros');
		}
		if($_POST['submit'] == t('Ignore')) {
			$r = q("UPDATE `intro` SET `ignore` = 1 WHERE `id` = %d",
				intval($intro_id));
			goaway('notifications/intros');
		}
	}
}

function notifications_content(App &$a) {

	if (! local_user()) {
		notice( t('Permission denied.') . EOL);
		return;
	}

	$page	=	(x($_REQUEST,'page')		? $_REQUEST['page']		: 1);
	$show	=	(x($_REQUEST,'show')		? $_REQUEST['show']		: 0);

	nav_set_selected('notifications');

	$json = (($a->argc > 1 && $a->argv[$a->argc - 1] === 'json') ? true : false);

	$nm = new NotificationsManager();

	$o = '';
	// Get the nav tabs for the notification pages
	$tabs = $nm->getTabs();
	$notif_content = array();

	// Notification results per page
	$perpage = 20;
	$startrec = ($page * $perpage) - $perpage;

	// Get introductions
	if( (($a->argc > 1) && ($a->argv[1] == 'intros')) || (($a->argc == 1))) {
		nav_set_selected('introductions');
		$notif_header = t('Notifications');

		$all = (($a->argc > 2) && ($a->argv[2] == 'all'));

		$notifs = $nm->introNotifs($all, $startrec, $perpage);

	// Get the network notifications
	} else if (($a->argc > 1) && ($a->argv[1] == 'network')) {

		$notif_header = t('Network Notifications');
		$notifs = $nm->networkNotifs($show, $startrec, $perpage);

	// Get the system notifications
	} else if (($a->argc > 1) && ($a->argv[1] == 'system')) {

		$notif_header = t('System Notifications');
		$notifs = $nm->systemNotifs($show, $startrec, $perpage);

	// Get the personal notifications
	} else if (($a->argc > 1) && ($a->argv[1] == 'personal')) {

		$notif_header = t('Personal Notifications');
		$notifs = $nm->personalNotifs($show, $startrec, $perpage);

	// Get the home notifications
	} else if (($a->argc > 1) && ($a->argv[1] == 'home')) {

		$notif_header = t('Home Notifications');
		$notifs = $nm->homeNotifs($show, $startrec, $perpage);

	}


	// Set the pager
	$a->set_pager_total($notifs['total']);
	$a->set_pager_itemspage($perpage);

	// Add additional informations (needed for json output)
	$notifs['items_page'] = $a->pager['itemspage'];
	$notifs['page'] = $a->pager['page'];

	// Json output
	if(intval($json) === 1)
		json_return_and_die($notifs);

	$notif_tpl = get_markup_template('notifications.tpl');

	// Process the data for template creation
	if($notifs['ident'] === 'introductions') {

		$sugg = get_markup_template('suggestions.tpl');
		$tpl = get_markup_template("intros.tpl");

		// The link to switch between ignored and normal connection requests
		$notif_show_lnk = array(
			'href' => (!$all ? 'notifications/intros/all' : 'notifications/intros' ),
			'text' => (!$all ? t('Show Ignored Requests') : t('Hide Ignored Requests'))
		);

		// Loop through all introduction notifications.This creates an array with the output html for each
		// introduction
		foreach ($notifs['notifications'] as $it) {

			// There are two kind of introduction. Contacts suggested by other contacts and normal connection requests.
			// We have to distinguish between these two because they use different data.
			switch ($it['label']) {
				case 'friend_suggestion':
					$notif_content[] = replace_macros($sugg, array(
						'$str_notifytype' => t('Notification type: '),
						'$notify_type' => $it['notify_type'],
						'$intro_id' => $it['intro_id'],
						'$madeby' => sprintf( t('suggested by %s'),$it['madeby']),
						'$contact_id' => $it['contact-id'],
						'$photo' => $it['photo'],
						'$fullname' => $it['name'],
						'$url' => $it['url'],
						'$hidden' => array('hidden', t('Hide this contact from others'), ($it['hidden'] == 1), ''),
						'$activity' => array('activity', t('Post a new friend activity'), $it['post_newfriend'], t('if applicable')),

						'$knowyou' => $it['knowyou'],
						'$approve' => t('Approve'),
						'$note' => $it['note'],
						'$request' => $it['request'],
						'$ignore' => t('Ignore'),
						'$discard' => t('Discard'),
					));
					break;

				// Normal connection requests
				default:
					$friend_selected = (($it['network'] !== NETWORK_OSTATUS) ? ' checked="checked" ' : ' disabled ');
					$fan_selected = (($it['network'] === NETWORK_OSTATUS) ? ' checked="checked" disabled ' : '');
					$dfrn_tpl = get_markup_template('netfriend.tpl');

					$knowyou   = '';
					$dfrn_text = '';

					if($it['network'] === NETWORK_DFRN || $it['network'] === NETWORK_DIASPORA) {
						if($it['network'] === NETWORK_DFRN) {
							$lbl_knowyou = t('Claims to be known to you: ');
							$knowyou = (($it['knowyou']) ? t('yes') : t('no'));
							$helptext = t('Shall your connection be bidirectional or not? "Friend" implies that you allow to read and you subscribe to their posts. "Fan/Admirer" means that you allow to read but you do not want to read theirs. Approve as: ');
						} else {
							$knowyou = '';
							$helptext = t('Shall your connection be bidirectional or not? "Friend" implies that you allow to read and you subscribe to their posts. "Sharer" means that you allow to read but you do not want to read theirs. Approve as: ');
						}
					}

					$dfrn_text = replace_macros($dfrn_tpl,array(
						'$intro_id' => $it['intro_id'],
						'$friend_selected' => $friend_selected,
						'$fan_selected' => $fan_selected,
						'$approve_as' => $helptext,
						'$as_friend' => t('Friend'),
						'$as_fan' => (($it['network'] == NETWORK_DIASPORA) ? t('Sharer') : t('Fan/Admirer'))
					));

					$header = $it["name"];

					if ($it["addr"] != "")
						$header .= " <".$it["addr"].">";

					$header .= " (".network_to_name($it['network'], $it['url']).")";

					$notif_content[] = replace_macros($tpl, array(
						'$header' => htmlentities($header),
						'$str_notifytype' => t('Notification type: '),
						'$notify_type' => $it['notify_type'],
						'$dfrn_text' => $dfrn_text,
						'$dfrn_id' => $it['dfrn_id'],
						'$uid' => $it['uid'],
						'$intro_id' => $it['intro_id'],
						'$contact_id' => $it['contact_id'],
						'$photo' => $it['photo'],
						'$fullname' => $it['name'],
						'$location' => $it['location'],
						'$lbl_location' => t('Location:'),
						'$about' => $it['about'],
						'$lbl_about' => t('About:'),
						'$keywords' => $it['keywords'],
						'$lbl_keywords' => t('Tags:'),
						'$gender' => $it['gender'],
						'$lbl_gender' => t('Gender:'),
						'$hidden' => array('hidden', t('Hide this contact from others'), ($it['hidden'] == 1), ''),
						'$activity' => array('activity', t('Post a new friend activity'), $it['post_newfriend'], t('if applicable')),
						'$url' => $it['url'],
						'$zrl' => $it['zrl'],
						'$lbl_url' => t('Profile URL'),
						'$addr' => $it['addr'],
						'$lbl_knowyou' => $lbl_knowyou,
						'$lbl_network' => t('Network:'),
						'$network' => network_to_name($it['network'], $it['url']),
						'$knowyou' => $knowyou,
						'$approve' => t('Approve'),
						'$note' => $it['note'],
						'$ignore' => t('Ignore'),
						'$discard' => t('Discard'),

					));
					break;
			}
		}

		if($notifs['total'] == 0)
			info( t('No introductions.') . EOL);

	// Normal notifications (no introductions)
	} else {

		// The template files we need in different cases for formatting the content
		$tpl_item_like = 'notifications_likes_item.tpl';
		$tpl_item_dislike = 'notifications_dislikes_item.tpl';
		$tpl_item_attend = 'notifications_attend_item.tpl';
		$tpl_item_attendno = 'notifications_attend_item.tpl';
		$tpl_item_attendmaybe = 'notifications_attend_item.tpl';
		$tpl_item_friend = 'notifications_friends_item.tpl';
		$tpl_item_comment = 'notifications_comments_item.tpl';
		$tpl_item_post = 'notifications_posts_item.tpl';
		$tpl_item_notify = 'notify.tpl';

		// Loop trough ever notification This creates an array with the output html for each
		// notification and apply the correct template according to the notificationtype (label).
		foreach ($notifs['notifications'] as $it) {

			// We use the notification label to get the correct template file
			$tpl_var_name = 'tpl_item_'.$it['label'];
			$tpl_notif = get_markup_template($$tpl_var_name);

			$notif_content[] = replace_macros($tpl_notif,array(
				'$item_label' => $it['label'],
				'$item_link' => $it['link'],
				'$item_image' => $it['image'],
				'$item_url' => $it['url'],
				'$item_text' => htmlentities($it['text']),
				'$item_when' => $it['when'],
				'$item_ago' => $it['ago'],
				'$item_seen' => $it['seen'],
			));
		}

		// It doesn't make sense to show the Show unread / Show all link visible if the user is on the
		// "Show all" page and there are no notifications. So we will hide it.
		if($show == 0 || intval($show) && $notifs['total'] > 0) {
			$notif_show_lnk = array(
				'href' => ($show ? 'notifications/'.$notifs['ident'] : 'notifications/'.$notifs['ident'].'?show=all' ),
				'text' => ($show ? t('Show unread') : t('Show all')),
			);
		}

		// Output if there aren't any notifications available
		if($notifs['total'] == 0)
			$notif_nocontent = sprintf( t('No more %s notifications.'), $notifs['ident']);
	}

	$o .= replace_macros($notif_tpl, array(
		'$notif_header' => $notif_header,
		'$tabs' => $tabs,
		'$notif_content' => $notif_content,
		'$notif_nocontent' => $notif_nocontent,
		'$notif_show_lnk' => $notif_show_lnk,
		'$notif_paginate' => paginate($a)
	));

	return $o;
}
