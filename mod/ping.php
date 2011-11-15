<?php
require_once("include/datetime.php");


function ping_init(&$a) {

	header("Content-type: text/xml");
	echo "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
		<result>";

	$xmlhead="<"."?xml version='1.0' encoding='UTF-8' ?".">";
	if(local_user()){

		$tags = array();
		$comments = array();
		$likes = array();
		$dislikes = array();
		$friends = array();
		$posts = array();
		
		$r = q("SELECT `item`.`id`,`item`.`parent`, `item`.`verb`, `item`.`author-name`, 
				`item`.`author-link`, `item`.`author-avatar`, `item`.`created`, `item`.`object`, 
				`pitem`.`author-name` as `pname`, `pitem`.`author-link` as `plink` 
				FROM `item` INNER JOIN `item` as `pitem` ON  `pitem`.`id`=`item`.`parent`
				WHERE `item`.`unseen` = 1 AND `item`.`visible` = 1 AND
				 `item`.`deleted` = 0 AND `item`.`uid` = %d AND `item`.`wall` = 0 ORDER BY `item`.`created` DESC",
			intval(local_user())
		);
		
		$network = count($r);
		foreach ($r as $it) {
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
						$posts[] = $it;
					}
			}
		}

		$r = q("SELECT `item`.`id`,`item`.`parent`, `item`.`verb`, `item`.`author-name`, 
				`item`.`author-link`, `item`.`author-avatar`, `item`.`created`, `item`.`object`, 
				`pitem`.`author-name` as `pname`, `pitem`.`author-link` as `plink` 
				FROM `item` INNER JOIN `item` as `pitem` ON  `pitem`.`id`=`item`.`parent`
				WHERE `item`.`unseen` = 1 AND `item`.`visible` = 1 AND
				 `item`.`deleted` = 0 AND `item`.`uid` = %d AND `item`.`wall` = 1",
			intval(local_user())
		);	
		$home = count($r);
		foreach ($r as $it) {
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
					if ($it['parent']!=$it['id']) $comments[] = $it;
			}
		}


		$intros1 = q("SELECT COUNT(`intro`.`id`) AS `total`, `intro`.`id`, `intro`.`datetime`, 
			`fcontact`.`name`, `fcontact`.`url`, `fcontact`.`photo` 
			FROM `intro` LEFT JOIN `fcontact` ON `intro`.`fid` = `fcontact`.`id`
			WHERE `intro`.`uid` = %d  AND `intro`.`blocked` = 0 AND `intro`.`ignore` = 0 AND `intro`.`fid`!=0",
			intval(local_user())
		);
		$intros2 = q("SELECT COUNT(`intro`.`id`) AS `total`, `intro`.`id`, `intro`.`datetime`, 
			`contact`.`name`, `contact`.`url`, `contact`.`photo` 
			FROM `intro` LEFT JOIN `contact` ON `intro`.`contact-id` = `contact`.`id`
			WHERE `intro`.`uid` = %d  AND `intro`.`blocked` = 0 AND `intro`.`ignore` = 0 AND `intro`.`contact-id`!=0",
			intval(local_user())
		);
		
		$intro = $intros1[0]['total'] + $intros2[0]['total'];
		if ($intros1[0]['total']==0) $intros1=Array();
		if ($intros2[0]['total']==0) $intros2=Array();
		$intros = $intros1+$intros2;



		$myurl = $a->get_baseurl() . '/profile/' . $a->user['nickname'] ;
		$mails = q("SELECT *,  COUNT(*) AS `total` FROM `mail`
			WHERE `uid` = %d AND `seen` = 0 AND `from-url` != '%s' ",
			intval(local_user()),
			dbesc($myurl)
		);
		$mail = $mails[0]['total'];
		
		if ($a->config['register_policy'] == REGISTER_APPROVE && is_site_admin()){
			$regs = q("SELECT `contact`.`name`, `contact`.`url`, `contact`.`micro`, `register`.`created`, COUNT(*) as `total` FROM `contact` RIGHT JOIN `register` ON `register`.`uid`=`contact`.`uid` WHERE `contact`.`self`=1");
			$register = $regs[0]['total'];
		} else {
			$register = "0";
		}


		function xmlize($href, $name, $url, $photo, $date, $message){
			$notsxml = '<note href="%s" name="%s" url="%s" photo="%s" date="%s">%s</note>';
			return sprintf ( $notsxml,
					xmlify($href), xmlify($name), xmlify($url), xmlify($photo), xmlify($date), xmlify($message)
				);
		}
		

		echo "<intro>$intro</intro>
				<mail>$mail</mail>
				<net>$network</net>
				<home>$home</home>";
		if ($register!=0) echo "<register>$register</register>";
		
		$tot = $mail+$intro+$register+count($comments)+count($likes)+count($dislikes)+count($friends)+count($posts)+count($tags);
		
		echo '	<notif count="'.$tot.'">';
		if ($intro>0){
			foreach ($intros as $i) { 
				echo xmlize( $a->get_baseurl().'/notifications/'.$i['id'], $i['name'], $i['url'], $i['photo'], relative_date($i['datetime']), t("{0} wants to be your friend") );
			};
		}
		if ($mail>0){
			foreach ($mails as $i) { 
				echo xmlize( $a->get_baseurl().'/message/'.$i['id'], $i['from-name'], $i['from-url'], $i['from-photo'], relative_date($i['created']), t("{0} sent you a message") );
			};
		}
		if ($register>0){
			foreach ($regs as $i) { 
				echo xmlize( $a->get_baseurl().'/admin/users/', $i['name'], $i['url'], $i['micro'], relative_date($i['created']), t("{0} requested registration") );
			};
		}

		if (count($comments)){
			foreach ($comments as $i) {
				echo xmlize( $a->get_baseurl().'/display/'.$a->user['nickname']."/".$i['parent'], $i['author-name'], $i['author-link'], $i['author-avatar'], relative_date($i['created']), sprintf( t("{0} commented %s's post"), $i['pname'] ) );
			};
		}
		if (count($likes)){
			foreach ($likes as $i) {
				echo xmlize( $a->get_baseurl().'/display/'.$a->user['nickname']."/".$i['parent'], $i['author-name'], $i['author-link'], $i['author-avatar'], relative_date($i['created']), sprintf( t("{0} liked %s's post"), $i['pname'] ) );
			};
		}
		if (count($dislikes)){
			foreach ($dislikes as $i) {
				echo xmlize( $a->get_baseurl().'/display/'.$a->user['nickname']."/".$i['parent'], $i['author-name'], $i['author-link'], $i['author-avatar'], relative_date($i['created']), sprintf( t("{0} disliked %s's post"), $i['pname'] ) );
			};
		}
		if (count($friends)){
			foreach ($friends as $i) {
				echo xmlize($a->get_baseurl().'/display/'.$a->user['nickname']."/".$i['parent'],$i['author-name'],$i['author-link'], $i['author-avatar'], relative_date($i['created']), sprintf( t("{0} is now friends with %s"), $i['fname'] ) );
			};
		}
		if (count($posts)){
			foreach ($posts as $i) {
				echo xmlize( $a->get_baseurl().'/display/'.$a->user['nickname']."/".$i['parent'], $i['author-name'], $i['author-link'], $i['author-avatar'], relative_date($i['created']), sprintf( t("{0} posted") ) );
			};
		}
		if (count($tags)){
			foreach ($tags as $i) {
				echo xmlize( $a->get_baseurl().'/display/'.$a->user['nickname']."/".$i['parent'], $i['author-name'], $i['author-link'], $i['author-avatar'], relative_date($i['created']), sprintf( t("{0} tagged %s's post with #%s"), $i['pname'], $i['tname'] ) );
			};
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

