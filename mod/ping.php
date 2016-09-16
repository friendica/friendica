<?php
require_once("include/datetime.php");
require_once('include/bbcode.php');
require_once('include/ForumManager.php');
require_once('include/group.php');
require_once("mod/proxy.php");
require_once('include/xml.php');

function ping_init(&$a) {

	$xmlhead = "<"."?xml version='1.0' encoding='UTF-8' ?".">";

	if (local_user()){
		// Different login session than the page that is calling us.
		if (intval($_GET['uid']) && intval($_GET['uid']) != local_user()) {
		        $data = array("invalid" => 1);
			header("Content-type: text/xml");
			echo xml::from_array(array("result" => $data), $xml);
			killme();
		}

		$notifs = ping_get_notifications(local_user());
		$sysnotify = 0; // we will update this in a moment

		$tags = array();
		$comments = array();
		$likes = array();
		$dislikes = array();
		$friends = array();
		$posts = array();
		$regs = array();
		$mails = array();

		$home = 0;
		$network = 0;
		$groups_unseen = array();
		$forums_unseen = array();

		$r = q("SELECT `item`.`id`,`item`.`parent`, `item`.`verb`, `item`.`wall`, `item`.`author-name`,
				`item`.`contact-id`, `item`.`author-link`, `item`.`author-avatar`, `item`.`created`, `item`.`object`,
				`pitem`.`author-name` as `pname`, `pitem`.`author-link` as `plink`
				FROM `item` INNER JOIN `item` as `pitem` ON  `pitem`.`id`=`item`.`parent`
				WHERE `item`.`unseen` = 1 AND `item`.`visible` = 1 AND
				 `item`.`deleted` = 0 AND `item`.`uid` = %d AND `pitem`.`parent` != 0
				AND `item`.`contact-id` != %d
				ORDER BY `item`.`created` DESC",
			intval(local_user()), intval(local_user())
		);

		if (dbm::is_result($r)) {

			$arr = array('items' => $r);
			call_hooks('network_ping', $arr);

			foreach ($r as $it) {

				if ($it['wall'])
					$home ++;
				else
					$network ++;

				switch($it['verb']){
					case ACTIVITY_TAG:
						$obj = parse_xml_string($xmlhead.$it['object']);
						$it['tname'] = $obj->content;
						$tags[] = $it;
						break;
					case ACTIVITY_LIKE:
						$likes[] = $it;
						break;
					case ACTIVITY_DISLIKE:
						$dislikes[] = $it;
						break;
					case ACTIVITY_FRIEND:
						$obj = parse_xml_string($xmlhead.$it['object']);
						$it['fname'] = $obj->title;
						$friends[] = $it;
						break;
					default:
						if ($it['parent']!=$it['id']) {
							$comments[] = $it;
						} else {
							if (!$it['wall'])
								$posts[] = $it;
						}
				}
			}
		}

		if ($network) {
			if (intval(feature_enabled(local_user(),'groups'))) {
				// Find out how unseen network posts are spread across groups
				$groups_unseen = groups_count_unseen();
			}

			if (intval(feature_enabled(local_user(),'forumlist_widget'))) {
				$forums_unseen = ForumManager::count_unseen_items();
			}
		}

		$intros1 = q("SELECT  `intro`.`id`, `intro`.`datetime`,
			`fcontact`.`name`, `fcontact`.`url`, `fcontact`.`photo`
			FROM `intro` LEFT JOIN `fcontact` ON `intro`.`fid` = `fcontact`.`id`
			WHERE `intro`.`uid` = %d  AND `intro`.`blocked` = 0 AND `intro`.`ignore` = 0 AND `intro`.`fid`!=0",
			intval(local_user())
		);
		$intros2 = q("SELECT `intro`.`id`, `intro`.`datetime`,
			`contact`.`name`, `contact`.`url`, `contact`.`photo`
			FROM `intro` LEFT JOIN `contact` ON `intro`.`contact-id` = `contact`.`id`
			WHERE `intro`.`uid` = %d  AND `intro`.`blocked` = 0 AND `intro`.`ignore` = 0 AND `intro`.`contact-id`!=0",
			intval(local_user())
		);

		$intro = count($intros1) + count($intros2);
		$intros = $intros1+$intros2;

		$myurl = $a->get_baseurl() . '/profile/' . $a->user['nickname'] ;
		$mails = q("SELECT * FROM `mail`
			WHERE `uid` = %d AND `seen` = 0 AND `from-url` != '%s' ",
			intval(local_user()),
			dbesc($myurl)
		);
		$mail = count($mails);

		if ($a->config['register_policy'] == REGISTER_APPROVE && is_site_admin()){
			$regs = q("SELECT `contact`.`name`, `contact`.`url`, `contact`.`micro`, `register`.`created`, COUNT(*) as `total` FROM `contact` RIGHT JOIN `register` ON `register`.`uid`=`contact`.`uid` WHERE `contact`.`self`=1");
			if ($regs)
				$register = $regs[0]['total'];
		} else {
			$register = "0";
		}

		$all_events = 0;
		$all_events_today = 0;
		$events = 0;
		$events_today = 0;
		$birthdays = 0;
		$birthdays_today = 0;


		$ev = q("SELECT count(`event`.`id`) as total, type, start, adjust FROM `event`
			WHERE `event`.`uid` = %d AND `start` < '%s' AND `finish` > '%s' and `ignore` = 0
			ORDER BY `start` ASC ",
			intval(local_user()),
			dbesc(datetime_convert('UTC','UTC','now + 7 days')),
			dbesc(datetime_convert('UTC','UTC','now'))
		);

		if (dbm::is_result($ev)) {
			$all_events = intval($ev[0]['total']);

			if ($all_events) {
				$str_now = datetime_convert('UTC',$a->timezone,'now','Y-m-d');
				foreach($ev as $x) {
					$bd = false;
					if ($x['type'] === 'birthday') {
						$birthdays ++;
						$bd = true;
					}
					else {
						$events ++;
					}
					if (datetime_convert('UTC',((intval($x['adjust'])) ? $a->timezone : 'UTC'), $x['start'],'Y-m-d') === $str_now) {
						$all_events_today ++;
						if ($bd)
							$birthdays_today ++;
						else
							$events_today ++;
					}
				}
			}
		}

	        $data = array();
	        $data["intro"] = $intro;
	        $data["mail"] = $mail;
	        $data["net"] = $network;
	        $data["home"] = $home;

		if ($register!=0)
		        $data["register"] = $register;

		$groups = array();

		if (dbm::is_result($groups_unseen)) {
			$count = 0;
			foreach ($groups_unseen as $it)
				if ($it['count'] > 0) {
					$count++;
					$groups[$count.":group"] = $it['count'];
					$groups[$count.":@attributes"] = array("id" => $it['id']);
				}
			$data["groups"] = $groups;
		}

		$forums = array();

		if (dbm::is_result($forums_unseen)) {
			$count = 0;
			foreach ($forums_unseen as $it)
				if ($it['count'] > 0) {
					$count++;
					$forums[$count.":forum"] = $it['count'];
					$forums[$count.":@attributes"] = array("id" => $it['id']);
				}
			$data["forums"] = $forums;
		}

		$data["all-events"] = $all_events;
		$data["all-events-today"] = $all_events_today;
		$data["events"] = $events;
		$data["events-today"] = $events_today;
		$data["birthdays"] = $birthdays;
		$data["birthdays-today"] = $birthdays_today;


		if (dbm::is_result($notifs) && !$sysnotify) {
			foreach ($notifs as $zz) {
				if ($zz['seen'] == 0)
					$sysnotify ++;
			}
		}

		// merge all notification types in one array
		if (dbm::is_result($intros)) {
			foreach ($intros as $i) {
				$n = array(
					'href' => $a->get_baseurl().'/notifications/intros/'.$i['id'],
					'name' => $i['name'],
					'url' => $i['url'],
					'photo' => $i['photo'],
					'date' => $i['datetime'],
					'seen' => false,
					'message' => t("{0} wants to be your friend"),
				);
				$notifs[] = $n;
			}
		}

		if (dbm::is_result($mails)) {
			foreach ($mails as $i) {
				$n = array(
					'href' => $a->get_baseurl().'/message/'.$i['id'],
					'name' => $i['from-name'],
					'url' => $i['from-url'],
					'photo' => $i['from-photo'],
					'date' => $i['created'],
					'seen' => false,
					'message' => t("{0} sent you a message"),
				);
				$notifs[] = $n;
			}
		}

		if (dbm::is_result($regs)) {
			foreach ($regs as $i) {
				$n = array(
					'href' => $a->get_baseurl().'/admin/users/',
					'name' => $i['name'],
					'url' => $i['url'],
					'photo' => $i['micro'],
					'date' => $i['created'],
					'seen' => false,
					'message' => t("{0} requested registration"),
				);
				$notifs[] = $n;
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

			// Are the nofications calles from the regular process or via the friendica app?
			$regularnotifications = (intval($_GET['uid']) AND intval($_GET['_']));

			$count = 0;
			foreach($notifs as $n) {
				$count++;
				if ($a->is_friendica_app() OR !$regularnotifications)
					$n['message'] = str_replace("{0}", $n['name'], $n['message']);

				$notifications[$count.":note"] = $n['message'];

				$contact = get_contact_details_by_url($n['url']);
				if (isset($contact["micro"]))
					$n['photo'] = proxy_url($contact["micro"], false, PROXY_SIZE_MICRO);
				else
					$n['photo'] = proxy_url($n['photo'], false, PROXY_SIZE_MICRO);

				$local_time = datetime_convert('UTC',date_default_timezone_get(),$n['date']);

				call_hooks('ping_xmlize', $n);

				$notifications[$count.":@attributes"] = array("id" => $n["id"],
										"href" => $n['href'],
										"name" => $n['name'],
										"url" => $n['url'],
										"photo" => $n['photo'],
										"date" => relative_date($n['date']),
										"seen" => $n['seen'],
										"timestamp" => strtotime($local_time));

			}
		}

		$data["notif"] = $notifications;
		$data["@attributes"] = array("count" => $sysnotify + $intro + $mail + $register);
	}

	$sysmsg = array();

	if (x($_SESSION,'sysmsg')){
		$count = 0;
		foreach ($_SESSION['sysmsg'] as $m){
			$count++;
			$sysmsg[$count.":notice"] = $m;
		}
		unset($_SESSION['sysmsg']);
	}

	if (x($_SESSION,'sysmsg_info')){
		$count = 0;
		foreach ($_SESSION['sysmsg_info'] as $m){
			$count++;
			$sysmsg[$count.":info"] = $m;
		}
		unset($_SESSION['sysmsg_info']);
	}

	$data["sysmsgs"] = $sysmsg;

	header("Content-type: text/xml");
	echo xml::from_array(array("result" => $data), $xml);
	killme();
}

function ping_get_notifications($uid) {

	$result = array();
	$offset = 0;
	$seen = false;
	$seensql = "NOT";
	$order = "DESC";
	$quit = false;

	$a = get_app();

	do {
		$r = q("SELECT `notify`.*, `item`.`visible`, `item`.`spam`, `item`.`deleted`
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
		} elseif (!$r)
			$quit = true;
		else
			$offset += 50;


		foreach ($r AS $notification) {
			if (is_null($notification["visible"]))
				$notification["visible"] = true;

			if (is_null($notification["spam"]))
				$notification["spam"] = 0;

			if (is_null($notification["deleted"]))
				$notification["deleted"] = 0;

			$notification["message"] = strip_tags(bbcode($notification["msg"]));
			$notification["name"] = strip_tags(bbcode($notification["name"]));

			// Replace the name with {0} but ensure to make that only once
			// The {0} is used later and prints the name in bold.

			if ($notification['name'] != "")
				$pos = strpos($notification["message"],$notification['name']);
			else
				$pos = false;

			if ($pos !== false)
				$notification["message"] = substr_replace($notification["message"],"{0}",$pos,strlen($notification["name"]));

			$notification['href'] = $a->get_baseurl() . '/notify/view/' . $notification['id'];

			if ($notification["visible"] AND !$notification["spam"] AND
				!$notification["deleted"] AND !is_array($result[$notification["parent"]])) {
				$result[$notification["parent"]] = $notification;
			}
		}

	} while ((count($result) < 50) AND !$quit);


	return($result);
}
