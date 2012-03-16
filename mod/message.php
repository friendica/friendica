<?php

require_once('include/acl_selectors.php');
require_once('include/message.php');

function message_post(&$a) {

	if(! local_user()) {
		notice( t('Permission denied.') . EOL);
		return;
	}

	$replyto   = ((x($_REQUEST,'replyto'))   ? notags(trim($_REQUEST['replyto']))   : '');
	$subject   = ((x($_REQUEST,'subject'))   ? notags(trim($_REQUEST['subject']))   : '');
	$body      = ((x($_REQUEST,'body'))      ? escape_tags(trim($_REQUEST['body'])) : '');
	$recipient = ((x($_REQUEST,'messageto')) ? intval($_REQUEST['messageto'])       : 0 );

	
	$ret = send_message($recipient, $body, $subject, $replyto);
	$norecip = false;

	switch($ret){
		case -1:
			notice( t('No recipient selected.') . EOL );
			$norecip = true;
			break;
		case -2:
			notice( t('Unable to locate contact information.') . EOL );
			break;
		case -3:
			notice( t('Message could not be sent.') . EOL );
			break;
		case -4:
			notice( t('Message collection failure.') . EOL );
			break;
		default:
			info( t('Message sent.') . EOL );
	}

	// fake it to go back to the input form if no recipient listed

	if($norecip) {
		$a->argc = 2;
		$a->argv[1] = 'new';
	}

}

function message_content(&$a) {

	$o = '';
	nav_set_selected('messages');

	if(! local_user()) {
		notice( t('Permission denied.') . EOL);
		return;
	}

	$myprofile = $a->get_baseurl(true) . '/profile/' . $a->user['nickname'];


	$tabs = array(
		array(
			'label' => t('Inbox'),
			'url'=> $a->get_baseurl(true) . '/message',
			'sel'=> (($a->argc == 1) ? 'active' : ''),
		),
		array(
			'label' => t('Outbox'),
			'url' => $a->get_baseurl(true) . '/message/sent',
			'sel'=> (($a->argv[1] == 'sent') ? 'active' : ''),
		),
		array(
			'label' => t('New Message'),
			'url' => $a->get_baseurl(true) . '/message/new',
			'sel'=> (($a->argv[1] == 'new') ? 'active' : ''),
		),
	);
	$tpl = get_markup_template('common_tabs.tpl');
	$tab_content = replace_macros($tpl, array('$tabs'=>$tabs));


	$tpl = get_markup_template('mail_head.tpl');
	$header = replace_macros($tpl, array(
		'$messages' => t('Messages'),
		'$tab_content' => $tab_content
	));


	if(($a->argc == 3) && ($a->argv[1] === 'drop' || $a->argv[1] === 'dropconv')) {
		if(! intval($a->argv[2]))
			return;
		$cmd = $a->argv[1];
		if($cmd === 'drop') {
			$r = q("DELETE FROM `mail` WHERE `id` = %d AND `uid` = %d LIMIT 1",
				intval($a->argv[2]),
				intval(local_user())
			);
			if($r) {
				info( t('Message deleted.') . EOL );
			}
			goaway($a->get_baseurl(true) . '/message' );
		}
		else {
			$r = q("SELECT `parent-uri`,`convid` FROM `mail` WHERE `id` = %d AND `uid` = %d LIMIT 1",
				intval($a->argv[2]),
				intval(local_user())
			);
			if(count($r)) {
				$parent = $r[0]['parent-uri'];
				$convid = $r[0]['convid'];

				$r = q("DELETE FROM `mail` WHERE `parent-uri` = '%s' AND `uid` = %d ",
					dbesc($parent),
					intval(local_user())
				);

				// remove diaspora conversation pointer
				// Actually if we do this, we can never receive another reply to that conversation,
				// as we will never again have the info we need to re-create it. 
				// We'll just have to orphan it. 

				//if($convid) {
				//	q("delete from conv where id = %d limit 1",
				//		intval($convid)
				//	);
				//}

				if($r)
					info( t('Conversation removed.') . EOL );
			} 
			goaway($a->get_baseurl(true) . '/message' );
		}	
	
	}

	if(($a->argc > 1) && ($a->argv[1] === 'new')) {
		
		$o .= $header;
		
		$plaintext = false;
		if(intval(get_pconfig(local_user(),'system','plaintext')))
			$plaintext = true;


		$tpl = get_markup_template('msg-header.tpl');

		$a->page['htmlhead'] .= replace_macros($tpl, array(
			'$baseurl' => $a->get_baseurl(true),
			'$editselect' => (($plaintext) ? 'none' : '/(profile-jot-text|prvmail-text)/'),
			'$nickname' => $a->user['nickname'],
			'$linkurl' => t('Please enter a link URL:')
		));
	
		$preselect = (isset($a->argv[2])?array($a->argv[2]):false);
	
		$select = contact_select('messageto','message-to-select', $preselect, 4, true, false, false, 10);
		$tpl = get_markup_template('prv_message.tpl');
		$o .= replace_macros($tpl,array(
			'$header' => t('Send Private Message'),
			'$to' => t('To:'),
			'$subject' => t('Subject:'),
			'$subjtxt' => ((x($_REQUEST,'subject')) ? strip_tags($_REQUEST['subject']) : ''),
			'$text' => ((x($_REQUEST,'body')) ? escape_tags(htmlspecialchars($_REQUEST['body'])) : ''),
			'$readonly' => '',
			'$yourmessage' => t('Your message:'),
			'$select' => $select,
			'$parent' => '',
			'$upload' => t('Upload photo'),
			'$insert' => t('Insert web link'),
			'$wait' => t('Please wait')
		));

		return $o;
	}

	if(($a->argc == 1) || ($a->argc == 2 && $a->argv[1] === 'sent')) {

		$o .= $header;
		
		if($a->argc == 2)
			$eq = '='; // I'm not going to bother escaping this.
		else
			$eq = '!='; // or this.

		$r = q("SELECT count(*) AS `total` FROM `mail` 
			WHERE `mail`.`uid` = %d AND `from-url` $eq '%s' GROUP BY `parent-uri` ORDER BY `created` DESC",
			intval(local_user()),
			dbesc($myprofile)
		);
		if(count($r))
			$a->set_pager_total($r[0]['total']);
	
		$r = q("SELECT max(`mail`.`created`) AS `mailcreated`, min(`mail`.`seen`) AS `mailseen`, 
			`mail`.* , `contact`.`name`, `contact`.`url`, `contact`.`thumb` , `contact`.`network`  
			FROM `mail` LEFT JOIN `contact` ON `mail`.`contact-id` = `contact`.`id` 
			WHERE `mail`.`uid` = %d AND `from-url` $eq '%s' GROUP BY `parent-uri` ORDER BY `mailcreated` DESC  LIMIT %d , %d ",
			intval(local_user()),
			dbesc($myprofile),
			intval($a->pager['start']),
			intval($a->pager['itemspage'])
		);
		if(! count($r)) {
			info( t('No messages.') . EOL);
			return $o;
		}

		$tpl = get_markup_template('mail_list.tpl');
		foreach($r as $rr) {
			$o .= replace_macros($tpl, array(
				'$id' => $rr['id'],
				'$from_name' =>$rr['from-name'],
				'$from_url' => (($rr['network'] === NETWORK_DFRN) ? $a->get_baseurl(true) . '/redir/' . $rr['contact-id'] : $rr['url']),
				'$sparkle' => ' sparkle',
				'$from_photo' => $rr['thumb'],
				'$subject' => template_escape((($rr['mailseen']) ? $rr['title'] : '<strong>' . $rr['title'] . '</strong>')),
				'$delete' => t('Delete conversation'),
				'$body' => template_escape($rr['body']),
				'$to_name' => template_escape($rr['name']),
				'$date' => datetime_convert('UTC',date_default_timezone_get(),$rr['mailcreated'], t('D, d M Y - g:i A'))
			));
		}
		$o .= paginate($a);	
		return $o;
	}

	if(($a->argc > 1) && (intval($a->argv[1]))) {

		$o .= $header;

		$r = q("SELECT `mail`.*, `contact`.`name`, `contact`.`url`, `contact`.`thumb` 
			FROM `mail` LEFT JOIN `contact` ON `mail`.`contact-id` = `contact`.`id` 
			WHERE `mail`.`uid` = %d AND `mail`.`id` = %d LIMIT 1",
			intval(local_user()),
			intval($a->argv[1])
		);
		if(count($r)) { 
			$contact_id = $r[0]['contact-id'];
			$convid = $r[0]['convid'];

			$sql_extra = sprintf(" and `mail`.`parent-uri` = '%s' ", dbesc($r[0]['parent-uri']));
			if($convid)
				$sql_extra = sprintf(" and ( `mail`.`parent-uri` = '%s' OR `mail`.`convid` = '%d' ) ",
					dbesc($r[0]['parent-uri']),
					intval($convid)
				);  

			$messages = q("SELECT `mail`.*, `contact`.`name`, `contact`.`url`, `contact`.`thumb` 
				FROM `mail` LEFT JOIN `contact` ON `mail`.`contact-id` = `contact`.`id` 
				WHERE `mail`.`uid` = %d $sql_extra ORDER BY `mail`.`created` ASC",
				intval(local_user())
			);
		}
		if(! count($messages)) {
			notice( t('Message not available.') . EOL );
			return $o;
		}

		$r = q("UPDATE `mail` SET `seen` = 1 WHERE `parent-uri` = '%s' AND `uid` = %d",
			dbesc($r[0]['parent-uri']),
			intval(local_user())
		);

		require_once("include/bbcode.php");

		$tpl = get_markup_template('msg-header.tpl');
	
		$a->page['htmlhead'] .= replace_macros($tpl, array(
			'$nickname' => $a->user['nickname'],
			'$baseurl' => $a->get_baseurl(true)
		));


		$tpl = get_markup_template('mail_conv.tpl');
		foreach($messages as $message) {
			if($message['from-url'] == $myprofile) {
				$from_url = $myprofile;
				$sparkle = '';
			}
			else {
				$from_url = $a->get_baseurl(true) . '/redir/' . $message['contact-id'];
				$sparkle = ' sparkle';
			}
			$o .= replace_macros($tpl, array(
				'$id' => $message['id'],
				'$from_name' => template_escape($message['from-name']),
				'$from_url' => $from_url,
				'$sparkle' => $sparkle,
				'$from_photo' => $message['from-photo'],
				'$subject' => template_escape($message['title']),
				'$body' => template_escape(smilies(bbcode($message['body']))),
				'$delete' => t('Delete message'),
				'$to_name' => template_escape($message['name']),
				'$date' => datetime_convert('UTC',date_default_timezone_get(),$message['created'],'D, d M Y - g:i A')
			));
				
		}
		$select = $message['name'] . '<input type="hidden" name="messageto" value="' . $contact_id . '" />';
		$parent = '<input type="hidden" name="replyto" value="' . $message['parent-uri'] . '" />';
		$tpl = get_markup_template('prv_message.tpl');
		$o .= replace_macros($tpl,array(
			'$header' => t('Send Reply'),
			'$to' => t('To:'),
			'$subject' => t('Subject:'),
			'$subjtxt' => template_escape($message['title']),
			'$readonly' => ' readonly="readonly" style="background: #BBBBBB;" ',
			'$yourmessage' => t('Your message:'),
			'$text' => '',
			'$select' => $select,
			'$parent' => $parent,
			'$upload' => t('Upload photo'),
			'$insert' => t('Insert web link'),
			'$wait' => t('Please wait')
		));

		return $o;
	}

}
