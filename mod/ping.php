<?php
require_once("include/datetime.php");
require_once('include/bbcode.php');
require_once("mod/proxy.php");

function ping_init(&$a) {

	header("Content-type: text/xml");

	echo "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
		<result>";

	$xmlhead="<"."?xml version='1.0' encoding='UTF-8' ?".">";

	if(local_user()){

		// Different login session than the page that is calling us.

		if(intval($_GET['uid']) && intval($_GET['uid']) != local_user()) {
			echo '<invalid>1</invalid></result>';
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

		$home = 0;
		$network = 0;
		$network_group = array();

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

		if(count($r)) {

			$arr = array('items' => $r);
			call_hooks('network_ping', $arr);

			foreach ($r as $it) {

				if($it['wall'])
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
							if(! $it['wall'])
								$posts[] = $it;
						}
				}
			}
		}

		if ( $network )
		{
			# Find out how unseen network posts are spread across groups
			$sql = "SELECT g.id, g.name, count(i.id) count " .
				"FROM `group` g, group_member gm, item i " .
				"WHERE g.uid = %d " .
				"AND i.uid = %d " .
				"AND i.unseen AND i.visible " .
				"AND NOT i.deleted " .
				"AND i.`contact-id` = gm.`contact-id` " .
				"AND gm.gid = g.id GROUP BY g.id";
			#echo '<SQL id="' . intval(local_user()) . '">' . $sql . '</SQL>';
			$network_group = q($sql, intval(local_user()), intval(local_user()));
			#echo '<COUNT R="' . count($network_group) . '"/>';
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
			if($regs)
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

		if($ev && count($ev)) {
			$all_events = intval($ev[0]['total']);

			if($all_events) {
				$str_now = datetime_convert('UTC',$a->timezone,'now','Y-m-d');
				foreach($ev as $x) {
					$bd = false;
					if($x['type'] === 'birthday') {
						$birthdays ++;
						$bd = true;
					}
					else {
						$events ++;
					}
					if(datetime_convert('UTC',((intval($x['adjust'])) ? $a->timezone : 'UTC'), $x['start'],'Y-m-d') === $str_now) {
						$all_events_today ++;
						if($bd)
							$birthdays_today ++;
						else
							$events_today ++;
					}
				}
			}
		}


		/**
		 * return xml from notification array
		 *
		 * @param array $n Notification array:
		 *		'href' => notification link
		 *		'name' => subject name
		 *		'url' => subject url
		 *		'photo' => subject photo
		 *		'date' => notification date
		 *		'seen' => bool true/false
		 *		'message' => notification message. "{0}" will be replaced by subject name
		 **/
		function xmlize($n){
			$n['photo'] = proxy_url($n['photo'], false, PROXY_SIZE_MICRO);

			$n['message'] = html_entity_decode($n['message'], ENT_COMPAT | ENT_HTML401, "UTF-8");
			$n['name'] = html_entity_decode($n['name'], ENT_COMPAT | ENT_HTML401, "UTF-8");

			// Are the nofications calles from the regular process or via the friendica app?
			$regularnotifications = (intval($_GET['uid']) AND intval($_GET['_']));

			$a = get_app();

			if ($a->is_friendica_app() OR !$regularnotifications)
				$n['message'] = str_replace("{0}", $n['name'], $n['message']);

			$local_time = datetime_convert('UTC',date_default_timezone_get(),$n['date']);

			call_hooks('ping_xmlize', $n);
			$notsxml = '<note href="%s" name="%s" url="%s" photo="%s" date="%s" seen="%s" timestamp="%s" >%s</note>'."\n";
			return sprintf ( $notsxml,
				xmlify($n['href']), xmlify($n['name']), xmlify($n['url']), xmlify($n['photo']),
				xmlify(relative_date($n['date'])), xmlify($n['seen']), xmlify(strtotime($local_time)),
				xmlify($n['message'])
			);
		}

		echo "<intro>$intro</intro>
				<mail>$mail</mail>
				<net>$network</net>
				<home>$home</home>\r\n";
		if ($register!=0) echo "<register>$register</register>";
		if ( count($network_group) ) {
			echo '<groups>';
			foreach ($network_group as $it) {
				echo '<group id="' . $it['id'] . '">' . $it['count'] . "</group>";
			}
			echo "</groups>";
		}

		echo "<all-events>$all_events</all-events>
			<all-events-today>$all_events_today</all-events-today>
			<events>$events</events>
			<events-today>$events_today</events-today>
			<birthdays>$birthdays</birthdays>
			<birthdays-today>$birthdays_today</birthdays-today>\r\n";


		if(count($notifs) && (! $sysnotify)) {
			foreach($notifs as $zz) {
				if($zz['seen'] == 0)
					$sysnotify ++;
			}
		}

		echo '	<notif count="'. ($sysnotify + $intro + $mail + $register) .'">';

		// merge all notification types in one array
		if ($intro>0){
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

		if ($mail>0){
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

		if ($register>0){
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

		if(count($notifs)) {
			foreach($notifs as $n) {
				echo xmlize($n);
			}
		}


		echo "  </notif>";
	}
	echo " <sysmsgs>";

	if(x($_SESSION,'sysmsg')){
		foreach ($_SESSION['sysmsg'] as $m){
			echo "<notice>".xmlify($m)."</notice>";
		}
		unset($_SESSION['sysmsg']);
	}
	if(x($_SESSION,'sysmsg_info')){
		foreach ($_SESSION['sysmsg_info'] as $m){
			echo "<info>".xmlify($m)."</info>";
		}
		unset($_SESSION['sysmsg_info']);
	}

	echo " </sysmsgs>";
	echo"</result>
	";

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

			$pos = strpos($notification["message"],$notification['name']);
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
