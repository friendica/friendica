<?php
require_once("include/datetime.php");
require_once('include/bbcode.php');
require_once('include/ForumManager.php');
require_once('include/group.php');
require_once('mod/proxy.php');
require_once('include/xml.php');
require_once('include/cache.php');

/**
 * @brief Outputs the counts and the lists of various notifications
 *
 * The output format can be controlled via the GET parameter 'format'. It can be
 * - xml (deprecated legacy default)
 * - json (outputs JSONP with the 'callback' GET parameter)
 *
 * Expected JSON structure:
 * {
 *		"result": {
 *			"intro": 0,
 *			"mail": 0,
 *			"net": 0,
 *			"home": 0,
 *			"register": 0,
 *			"all-events": 0,
 *			"all-events-today": 0,
 *			"events": 0,
 *			"events-today": 0,
 *			"birthdays": 0,
 *			"birthdays-today": 0,
 *			"groups": [ ],
 *			"forums": [ ],
 *			"notify": 0,
 *			"notifications": [ ],
 *			"sysmsgs": {
 *				"notice": [ ],
 *				"info": [ ]
 *			}
 *		}
 *	}
 *
 * @param App $a The Friendica App instance
 */
function ping_init(App $a)
{
	$format = 'xml';

	if (isset($_GET['format']) && $_GET['format'] == 'json') {
		$format = 'json';
	}

	$tags          = array();
	$comments      = array();
	$likes         = array();
	$dislikes      = array();
	$friends       = array();
	$posts         = array();
	$regs          = array();
	$mails         = array();
	$notifications = array();

	$intro_count    = 0;
	$mail_count     = 0;
	$home_count     = 0;
	$network_count  = 0;
	$register_count = 0;
	$sysnotify_count = 0;
	$groups_unseen  = array();
	$forums_unseen  = array();

	$all_events       = 0;
	$all_events_today = 0;
	$events           = 0;
	$events_today     = 0;
	$birthdays        = 0;
	$birthdays_today  = 0;

	$data = array();
	$data['intro']    = $intro_count;
	$data['mail']     = $mail_count;
	$data['net']      = $network_count;
	$data['home']     = $home_count;
	$data['register'] = $register_count;

	$data['all-events']       = $all_events;
	$data['all-events-today'] = $all_events_today;
	$data['events']           = $events;
	$data['events-today']     = $events_today;
	$data['birthdays']        = $birthdays;
	$data['birthdays-today']  = $birthdays_today;

	if (local_user()){
		// Different login session than the page that is calling us.
		if (intval($_GET['uid']) && intval($_GET['uid']) != local_user()) {

			$data = array('result' => array('invalid' => 1));

			if ($format == 'json') {
				if (isset($_GET['callback'])) {
					// JSONP support
					header("Content-type: application/javascript");
					echo $_GET['callback'] . '(' . json_encode($data) . ')';
				} else {
					header("Content-type: application/json");
					echo json_encode($data);
				}
			} else {
				header("Content-type: text/xml");
				echo xml::from_array($data, $xml);
			}
			killme();
		}

		$notifs = ping_get_notifications(local_user());

		$items_unseen = qu("SELECT `item`.`id`, `item`.`parent`, `item`.`verb`, `item`.`wall`, `item`.`author-name`,
				`item`.`contact-id`, `item`.`author-link`, `item`.`author-avatar`, `item`.`created`, `item`.`object`,
				`pitem`.`author-name` AS `pname`, `pitem`.`author-link` AS `plink`
				FROM `item` INNER JOIN `item` AS `pitem` ON  `pitem`.`id` = `item`.`parent`
				WHERE `item`.`unseen` = 1 AND `item`.`visible` = 1 AND
				 `item`.`deleted` = 0 AND `item`.`uid` = %d AND `pitem`.`parent` != 0
				AND `item`.`contact-id` != %d
				ORDER BY `item`.`created` DESC",
			intval(local_user()), intval(local_user())
		);

		if (dbm::is_result($items_unseen)) {
			$arr = array('items' => $items_unseen);
			call_hooks('network_ping', $arr);

			foreach ($items_unseen as $item) {
				if ($item['wall']) {
					$home_count++;
				} else {
					$network_count++;
				}
			}
		}

		if ($network_count) {
			if (intval(feature_enabled(local_user(), 'groups'))) {
				// Find out how unseen network posts are spread across groups
				$group_counts = groups_count_unseen();
				if (dbm::is_result($group_counts)) {
					foreach ($group_counts as $group_count) {
						if ($group_count['count'] > 0) {
							$groups_unseen[] = $group_count;
						}
					}
				}
			}

			if (intval(feature_enabled(local_user(), 'forumlist_widget'))) {
				$forum_counts = ForumManager::count_unseen_items();
				if (dbm::is_result($forums_counts)) {
					foreach ($forums_counts as $forum_count) {
						if ($forum_count['count'] > 0) {
							$forums_unseen[] = $forum_count;
						}
					}
				}
			}
		}

		$intros1 = qu("SELECT  `intro`.`id`, `intro`.`datetime`,
			`fcontact`.`name`, `fcontact`.`url`, `fcontact`.`photo`
			FROM `intro` LEFT JOIN `fcontact` ON `intro`.`fid` = `fcontact`.`id`
			WHERE `intro`.`uid` = %d  AND `intro`.`blocked` = 0 AND `intro`.`ignore` = 0 AND `intro`.`fid` != 0",
			intval(local_user())
		);
		$intros2 = qu("SELECT `intro`.`id`, `intro`.`datetime`,
			`contact`.`name`, `contact`.`url`, `contact`.`photo`
			FROM `intro` LEFT JOIN `contact` ON `intro`.`contact-id` = `contact`.`id`
			WHERE `intro`.`uid` = %d  AND `intro`.`blocked` = 0 AND `intro`.`ignore` = 0 AND `intro`.`contact-id` != 0",
			intval(local_user())
		);

		$intro_count = count($intros1) + count($intros2);
		$intros = $intros1 + $intros2;

		$myurl = App::get_baseurl() . '/profile/' . $a->user['nickname'] ;
		$mails = qu("SELECT `id`, `from-name`, `from-url`, `from-photo`, `created` FROM `mail`
			WHERE `uid` = %d AND `seen` = 0 AND `from-url` != '%s' ",
			intval(local_user()),
			dbesc($myurl)
		);
		$mail_count = count($mails);

		if ($a->config['register_policy'] == REGISTER_APPROVE && is_site_admin()){
			$regs = qu("SELECT `contact`.`name`, `contact`.`url`, `contact`.`micro`, `register`.`created`, COUNT(*) AS `total`
				FROM `contact` RIGHT JOIN `register` ON `register`.`uid` = `contact`.`uid`
				WHERE `contact`.`self` = 1");

			if (dbm::is_result($regs)) {
				$register_count = $regs[0]['total'];
			}
		}

		$cachekey = "ping_init:".local_user();
		$ev = Cache::get($cachekey);
		if (is_null($ev)) {
			$ev = qu("SELECT count(`event`.`id`) AS total, type, start, adjust FROM `event`
				WHERE `event`.`uid` = %d AND `start` < '%s' AND `finish` > '%s' and `ignore` = 0
				ORDER BY `start` ASC ",
				intval(local_user()),
				dbesc(datetime_convert('UTC', 'UTC', 'now + 7 days')),
				dbesc(datetime_convert('UTC', 'UTC', 'now'))
			);
			if (dbm::is_result($ev)) {
				Cache::set($cachekey, $ev, CACHE_HOUR);
			}
		}

		if (dbm::is_result($ev)) {
			$all_events = intval($ev[0]['total']);

			if ($all_events) {
				$str_now = datetime_convert('UTC', $a->timezone, 'now', 'Y-m-d');
				foreach($ev as $x) {
					$bd = false;
					if ($x['type'] === 'birthday') {
						$birthdays ++;
						$bd = true;
					}
					else {
						$events ++;
					}
					if (datetime_convert('UTC', ((intval($x['adjust'])) ? $a->timezone : 'UTC'), $x['start'], 'Y-m-d') === $str_now) {
						$all_events_today ++;
						if ($bd)
							$birthdays_today ++;
						else
							$events_today ++;
					}
				}
			}
		}

		$data['intro']    = $intro_count;
		$data['mail']     = $mail_count;
		$data['net']      = $network_count;
		$data['home']     = $home_count;
		$data['register'] = $register_count;

		$data['all-events']       = $all_events;
		$data['all-events-today'] = $all_events_today;
		$data['events']           = $events;
		$data['events-today']     = $events_today;
		$data['birthdays']        = $birthdays;
		$data['birthdays-today']  = $birthdays_today;

		if (dbm::is_result($notifs)) {
			foreach ($notifs as $notif) {
				if ($notif['seen'] == 0) {
					$sysnotify_count ++;
				}
			}
		}

		// merge all notification types in one array
		if (dbm::is_result($intros)) {
			foreach ($intros as $intro) {
				$notif = array(
					'href'    => App::get_baseurl() . '/notifications/intros/' . $intro['id'],
					'name'    => $intro['name'],
					'url'     => $intro['url'],
					'photo'   => $intro['photo'],
					'date'    => $intro['datetime'],
					'seen'    => false,
					'message' => t('{0} wants to be your friend'),
				);
				$notifs[] = $notif;
			}
		}

		if (dbm::is_result($mails)) {
			foreach ($mails as $mail) {
				$notif = array(
					'href'    => App::get_baseurl() . '/message/' . $mail['id'],
					'name'    => $mail['from-name'],
					'url'     => $mail['from-url'],
					'photo'   => $mail['from-photo'],
					'date'    => $mail['created'],
					'seen'    => false,
					'message' => t('{0} sent you a message'),
				);
				$notifs[] = $notif;
			}
		}

		if (dbm::is_result($regs)) {
			foreach ($regs as $reg) {
				$notif = array(
					'href'    => App::get_baseurl() . '/admin/users/',
					'name'    => $reg['name'],
					'url'     => $reg['url'],
					'photo'   => $reg['micro'],
					'date'    => $reg['created'],
					'seen'    => false,
					'message' => t('{0} requested registration'),
				);
				$notifs[] = $notif;
			}
		}

		// sort notifications by $[]['date']
		$sort_function = function($a, $b) {
			$adate = date($a['date']);
			$bdate = date($b['date']);
			if ($adate == $bdate) {
				return 0;
			}
			return ($adate < $bdate) ? 1 : -1;
		};
		usort($notifs, $sort_function);

		if (dbm::is_result($notifs)) {
			// Are the nofications called from the regular process or via the friendica app?
			$regularnotifications = (intval($_GET['uid']) AND intval($_GET['_']));

			foreach ($notifs as $notif) {
				if ($a->is_friendica_app() OR !$regularnotifications) {
					$notif['message'] = str_replace("{0}", $notif['name'], $notif['message']);
				}

				$contact = get_contact_details_by_url($notif['url']);
				if (isset($contact['micro'])) {
					$notif['photo'] = proxy_url($contact['micro'], false, PROXY_SIZE_MICRO);
				} else {
					$notif['photo'] = proxy_url($notif['photo'], false, PROXY_SIZE_MICRO);
				}

				$local_time = datetime_convert('UTC', date_default_timezone_get(), $notif['date']);

				$notifications[] = array(
					'id'        => $notif['id'],
					'href'      => $notif['href'],
					'name'      => $notif['name'],
					'url'       => $notif['url'],
					'photo'     => $notif['photo'],
					'date'      => relative_date($notif['date']),
					'message'   => $notif['message'],
					'seen'      => $notif['seen'],
					'timestamp' => strtotime($local_time)
				);
			}
		}
	}

	$sysmsgs = array();
	$sysmsgs_info = array();

	if (x($_SESSION, 'sysmsg')) {
		$sysmsgs = $_SESSION['sysmsg'];
		unset($_SESSION['sysmsg']);
	}

	if (x($_SESSION, 'sysmsg_info')) {
		$sysmsgs_info = $_SESSION['sysmsg_info'];
		unset($_SESSION['sysmsg_info']);
	}

	if ($format == 'json') {
		$data['groups'] = $groups_unseen;
		$data['forums'] = $forums_unseen;
		$data['notify'] = $sysnotify_count + $intro_count + $mail_count + $register_count;
		$data['notifications'] = $notifications;
		$data['sysmsgs'] = array(
			'notice' => $sysmsgs,
			'info' => $sysmsgs_info
		);

		$json_payload = json_encode(array("result" => $data));

		if (isset($_GET['callback'])) {
			// JSONP support
			header("Content-type: application/javascript");
			echo $_GET['callback'] . '(' . $json_payload . ')';
		} else {
			header("Content-type: application/json");
			echo $json_payload;
		}
	} else {
		// Legacy slower XML format output
		$data = ping_format_xml_data($data, $sysnotify_count, $notifications, $sysmsgs, $sysmsgs_info, $groups_unseen, $forums_unseen);

		header("Content-type: text/xml");
		echo xml::from_array(array("result" => $data), $xml);
	}

	killme();
}

/**
 * @brief Retrieves the notifications array for the given user ID
 *
 * @param int $uid User id
 * @return array Associative array of notifications
 */
function ping_get_notifications($uid)
{
	$result  = array();
	$offset  = 0;
	$seen    = false;
	$seensql = "NOT";
	$order   = "DESC";
	$quit    = false;

	$a = get_app();

	do {
		$r = qu("SELECT `notify`.*, `item`.`visible`, `item`.`spam`, `item`.`deleted`
			FROM `notify` LEFT JOIN `item` ON `item`.`id` = `notify`.`iid`
			WHERE `notify`.`uid` = %d AND `notify`.`msg` != ''
			AND NOT (`notify`.`type` IN (%d, %d))
			AND $seensql `notify`.`seen` ORDER BY `notify`.`date` $order LIMIT %d, 50",
			intval($uid),
			intval(NOTIFY_INTRO),
			intval(NOTIFY_MAIL),
			intval($offset)
		);

		if (!$r AND !$seen) {
			$seen = true;
			$seensql = "";
			$order = "DESC";
			$offset = 0;
		} elseif (!$r) {
			$quit = true;
		} else {
			$offset += 50;
		}

		foreach ($r AS $notification) {
			if (is_null($notification["visible"])) {
				$notification["visible"] = true;
			}

			if (is_null($notification["spam"])) {
				$notification["spam"] = 0;
			}

			if (is_null($notification["deleted"])) {
				$notification["deleted"] = 0;
			}

			if ($notification["msg_cache"]) {
				$notification["name"] = $notification["name_cache"];
				$notification["message"] = $notification["msg_cache"];
			} else {
				$notification["name"] = strip_tags(bbcode($notification["name"]));
				$notification["message"] = format_notification_message($notification["name"], strip_tags(bbcode($notification["msg"])));

				q("UPDATE `notify` SET `name_cache` = '%s', `msg_cache` = '%s' WHERE `id` = %d",
					dbesc($notification["name"]),
					dbesc($notification["message"]),
					intval($notification["id"])
				);
			}

			$notification["href"] = App::get_baseurl() . "/notify/view/" . $notification["id"];

			if ($notification["visible"] AND !$notification["spam"] AND
				!$notification["deleted"] AND !is_array($result[$notification["parent"]])) {
				$result[$notification["parent"]] = $notification;
			}
		}
	} while ((count($result) < 50) AND !$quit);

	return($result);
}

/**
 * @brief Backward-compatible XML formatting for ping.php output
 * @deprecated
 *
 * @param array $data The initial ping data array
 * @param int $sysnotify_count Number of unseen system notifications
 * @param array $notifs Complete list of notification
 * @param array $sysmsgs List of system notice messages
 * @param array $sysmsgs_info List of system info messages
 * @param int $groups_unseen Number of unseen group items
 * @param int $forums_unseen Number of unseen forum items
 * @return array XML-transform ready data array
 */
function ping_format_xml_data($data, $sysnotify, $notifs, $sysmsgs, $sysmsgs_info, $groups_unseen, $forums_unseen)
{
	$notifications = array();
	foreach($notifs as $key => $notif) {
		$notifications[$key . ':note'] = $notif['message'];

		$notifications[$key . ':@attributes'] = array(
			'id'        => $notif['id'],
			'href'      => $notif['href'],
			'name'      => $notif['name'],
			'url'       => $notif['url'],
			'photo'     => $notif['photo'],
			'date'      => $notif['date'],
			'seen'      => $notif['seen'],
			'timestamp' => $notif['timestamp']
		);
	}

	$sysmsg = array();
	foreach ($sysmsgs as $key => $m){
		$sysmsg[$key . ':notice'] = $m;
	}
	foreach ($sysmsgs_info as $key => $m){
		$sysmsg[$key . ':info'] = $m;
	}

	$data['notif'] = $notifications;
	$data['@attributes'] = array('count' => $sysnotify_count + $data['intro'] + $data['mail'] + $data['register']);
	$data['sysmsgs'] = $sysmsg;

	if ($data['register'] == 0) {
		unset($data['register']);
	}

	$groups = array();
	if (count($groups_unseen)) {
		foreach ($groups_unseen as $key => $item) {
			$groups[$key . ':group'] = $item['count'];
			$groups[$key . ':@attributes'] = array('id' => $item['id']);
		}
		$data['groups'] = $groups;
	}

	$forums = array();
	if (count($forums_unseen)) {
		foreach ($forums_unseen as $key => $item) {
			$forums[$count . ':forum'] = $item['count'];
			$forums[$count . ':@attributes'] = array('id' => $item['id']);
		}
		$data['forums'] = $forums;
	}

	return $data;
}
