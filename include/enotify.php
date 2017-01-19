<?php
require_once('include/Emailer.php');
require_once('include/email.php');
require_once('include/bbcode.php');
require_once('include/html2bbcode.php');

/**
 * @brief Creates a notification entry and possibly sends a mail
 *
 * @param array $params Array with the elements:
			uid, item, parent, type, otype, verb, event,
			link, subject, body, to_name, to_email, source_name,
			source_link, activity, preamble, notify_flags,
			language, show_in_notification_page
 */
function notification($params) {

	$a = get_app();

	// from here on everything is in the recipients language

	push_lang($params['language']);

	$banner = t('Friendica Notification');
	$product = FRIENDICA_PLATFORM;
	$siteurl = App::get_baseurl(true);
	$thanks = t('Thank You,');
	$sitename = $a->config['sitename'];
	if (!x($a->config['admin_name']))
	    $site_admin = sprintf(t('%s Administrator'), $sitename);
	else
	    $site_admin = sprintf(t('%1$s, %2$s Administrator'), $a->config['admin_name'], $sitename);

	$nickname = "";

	$sender_name = $sitename;
	$hostname = $a->get_hostname();
	if (strpos($hostname, ':'))
		$hostname = substr($hostname, 0, strpos($hostname, ':'));

	$sender_email = $a->config['sender_email'];
	if (empty($sender_email))
		$sender_email = t('noreply').'@'.$hostname;

	$user = q("SELECT `nickname` FROM `user` WHERE `uid` = %d", intval($params['uid']));
	if ($user)
		$nickname = $user[0]["nickname"];

	// with $params['show_in_notification_page'] == false, the notification isn't inserted into
	// the database, and an email is sent if applicable.
	// default, if not specified: true
	$show_in_notification_page = ((x($params, 'show_in_notification_page'))	? $params['show_in_notification_page']:true);

	$additional_mail_header = "";
	$additional_mail_header .= "Precedence: list\n";
	$additional_mail_header .= "X-Friendica-Host: ".$hostname."\n";
	$additional_mail_header .= "X-Friendica-Account: <".$nickname."@".$hostname.">\n";
	$additional_mail_header .= "X-Friendica-Platform: ".FRIENDICA_PLATFORM."\n";
	$additional_mail_header .= "X-Friendica-Version: ".FRIENDICA_VERSION."\n";
	$additional_mail_header .= "List-ID: <notification.".$hostname.">\n";
	$additional_mail_header .= "List-Archive: <".App::get_baseurl()."/notifications/system>\n";

	if (array_key_exists('item', $params)) {
		$title = $params['item']['title'];
		$body = $params['item']['body'];
	} else
		$title = $body = '';

	// e.g. "your post", "David's photo", etc.
	$possess_desc = t('%s <!item_type!>');

	if (isset($params['item']['id']))
		$item_id = $params['item']['id'];
	else
		$item_id = 0;

	if (isset($params['parent']))
		$parent_id = $params['parent'];
	else
		$parent_id = 0;

	if ($params['type'] == NOTIFY_MAIL) {
		$subject = sprintf(t('[Friendica:Notify] New mail received at %s'), $sitename);

		$preamble = sprintf(t('%1$s sent you a new private message at %2$s.'), $params['source_name'], $sitename);
		$epreamble = sprintf(t('%1$s sent you %2$s.'), '[url='.$params['source_link'].']'.$params['source_name'].'[/url]', '[url=$itemlink]'.t('a private message').'[/url]');

		$sitelink = t('Please visit %s to view and/or reply to your private messages.');
		$tsitelink = sprintf($sitelink, $siteurl.'/message/'.$params['item']['id']);
		$hsitelink = sprintf($sitelink, '<a href="'.$siteurl.'/message/'.$params['item']['id'].'">'.$sitename.'</a>');
		$itemlink = $siteurl.'/message/'.$params['item']['id'];
	}

	if ($params['type'] == NOTIFY_COMMENT) {
		$p = q("SELECT `ignored` FROM `thread` WHERE `iid` = %d AND `uid` = %d LIMIT 1",
			intval($parent_id),
			intval($params['uid'])
		);
		if ($p AND count($p) AND ($p[0]["ignored"])) {
			logger("Thread ".$parent_id." will be ignored", LOGGER_DEBUG);
			return;
		}

		// Check to see if there was already a tag notify or comment notify for this post.
		// If so don't create a second notification

		$p = null;
		$p = q("SELECT `id` FROM `notify` WHERE `type` IN (%d, %d, %d) AND `link` = '%s' AND `uid` = %d LIMIT 1",
			intval(NOTIFY_TAGSELF),
			intval(NOTIFY_COMMENT),
			intval(NOTIFY_SHARE),
			dbesc($params['link']),
			intval($params['uid'])
		);
		if ($p and count($p)) {
			pop_lang();
			return;
		}

		// if it's a post figure out who's post it is.

		$p = null;

		if ($params['otype'] === 'item' && $parent_id) {
			$p = q("SELECT * FROM `item` WHERE `id` = %d AND `uid` = %d LIMIT 1",
				intval($parent_id),
				intval($params['uid'])
			);
		}

		$item_post_type = item_post_type($p[0]);

		// "a post"
		$dest_str = sprintf(t('%1$s commented on [url=%2$s]a %3$s[/url]'),
								'[url='.$params['source_link'].']'.$params['source_name'].'[/url]',
								$itemlink,
								$item_post_type);

		// "George Bull's post"
		if ($p)
			$dest_str = sprintf(t('%1$s commented on [url=%2$s]%3$s\'s %4$s[/url]'),
						'[url='.$params['source_link'].']'.$params['source_name'].'[/url]',
						$itemlink,
						$p[0]['author-name'],
						$item_post_type);

		// "your post"
		if ($p[0]['owner-name'] == $p[0]['author-name'] && $p[0]['wall'])
			$dest_str = sprintf(t('%1$s commented on [url=%2$s]your %3$s[/url]'),
								'[url='.$params['source_link'].']'.$params['source_name'].'[/url]',
								$itemlink,
								$item_post_type);

		// Some mail softwares relies on subject field for threading.
		// So, we cannot have different subjects for notifications of the same thread.
		// Before this we have the name of the replier on the subject rendering
		// differents subjects for messages on the same thread.

		$subject = sprintf(t('[Friendica:Notify] Comment to conversation #%1$d by %2$s'), $parent_id, $params['source_name']);

		$preamble = sprintf(t('%s commented on an item/conversation you have been following.'), $params['source_name']);
		$epreamble = $dest_str;

		$sitelink = t('Please visit %s to view and/or reply to the conversation.');
		$tsitelink = sprintf($sitelink, $siteurl);
		$hsitelink = sprintf($sitelink, '<a href="'.$siteurl.'">'.$sitename.'</a>');
		$itemlink =  $params['link'];
	}

	if ($params['type'] == NOTIFY_WALL) {
		$subject = sprintf(t('[Friendica:Notify] %s posted to your profile wall'), $params['source_name']);

		$preamble = sprintf(t('%1$s posted to your profile wall at %2$s'), $params['source_name'], $sitename);
		$epreamble = sprintf(t('%1$s posted to [url=%2$s]your wall[/url]'),
					'[url='.$params['source_link'].']'.$params['source_name'].'[/url]',
					$params['link']);

		$sitelink = t('Please visit %s to view and/or reply to the conversation.');
		$tsitelink = sprintf($sitelink, $siteurl);
		$hsitelink = sprintf($sitelink, '<a href="'.$siteurl.'">'.$sitename.'</a>');
		$itemlink =  $params['link'];
	}

	if ($params['type'] == NOTIFY_TAGSELF) {
		$subject = sprintf(t('[Friendica:Notify] %s tagged you'), $params['source_name']);

		$preamble = sprintf(t('%1$s tagged you at %2$s'), $params['source_name'], $sitename);
		$epreamble = sprintf(t('%1$s [url=%2$s]tagged you[/url].'),
					'[url='.$params['source_link'].']'.$params['source_name'].'[/url]',
					$params['link']);

		$sitelink = t('Please visit %s to view and/or reply to the conversation.');
		$tsitelink = sprintf($sitelink, $siteurl);
		$hsitelink = sprintf($sitelink, '<a href="'.$siteurl.'">'.$sitename.'</a>');
		$itemlink =  $params['link'];
	}

	if ($params['type'] == NOTIFY_SHARE) {
		$subject = sprintf(t('[Friendica:Notify] %s shared a new post'), $params['source_name']);

		$preamble = sprintf(t('%1$s shared a new post at %2$s'), $params['source_name'], $sitename);
		$epreamble = sprintf(t('%1$s [url=%2$s]shared a post[/url].'),
					'[url='.$params['source_link'].']'.$params['source_name'].'[/url]',
					$params['link']);

		$sitelink = t('Please visit %s to view and/or reply to the conversation.');
		$tsitelink = sprintf($sitelink, $siteurl);
		$hsitelink = sprintf($sitelink, '<a href="'.$siteurl.'">'.$sitename.'</a>');
		$itemlink =  $params['link'];
	}

	if ($params['type'] == NOTIFY_POKE) {
		$subject = sprintf(t('[Friendica:Notify] %1$s poked you'), $params['source_name']);

		$preamble = sprintf(t('%1$s poked you at %2$s'), $params['source_name'], $sitename);
		$epreamble = sprintf(t('%1$s [url=%2$s]poked you[/url].'),
					'[url='.$params['source_link'].']'.$params['source_name'].'[/url]',
					$params['link']);

		$subject = str_replace('poked', t($params['activity']), $subject);
		$preamble = str_replace('poked', t($params['activity']), $preamble);
		$epreamble = str_replace('poked', t($params['activity']), $epreamble);

		$sitelink = t('Please visit %s to view and/or reply to the conversation.');
		$tsitelink = sprintf($sitelink, $siteurl);
		$hsitelink = sprintf($sitelink, '<a href="'.$siteurl.'">'.$sitename.'</a>');
		$itemlink =  $params['link'];
	}

	if ($params['type'] == NOTIFY_TAGSHARE) {
		$subject = sprintf(t('[Friendica:Notify] %s tagged your post'), $params['source_name']);

		$preamble = sprintf(t('%1$s tagged your post at %2$s'), $params['source_name'], $sitename);
		$epreamble = sprintf(t('%1$s tagged [url=%2$s]your post[/url]'),
					'[url='.$params['source_link'].']'.$params['source_name'].'[/url]',
					$itemlink);

		$sitelink = t('Please visit %s to view and/or reply to the conversation.');
		$tsitelink = sprintf($sitelink, $siteurl);
		$hsitelink = sprintf($sitelink, '<a href="'.$siteurl.'">'.$sitename.'</a>');
		$itemlink =  $params['link'];
	}

	if ($params['type'] == NOTIFY_INTRO) {
		$subject = sprintf(t('[Friendica:Notify] Introduction received'));

		$preamble = sprintf(t('You\'ve received an introduction from \'%1$s\' at %2$s'), $params['source_name'], $sitename);
		$epreamble = sprintf(t('You\'ve received [url=%1$s]an introduction[/url] from %2$s.'),
					$itemlink,
					'[url='.$params['source_link'].']'.$params['source_name'].'[/url]');

		$body = sprintf(t('You may visit their profile at %s'), $params['source_link']);

		$sitelink = t('Please visit %s to approve or reject the introduction.');
		$tsitelink = sprintf($sitelink, $siteurl);
		$hsitelink = sprintf($sitelink, '<a href="'.$siteurl.'">'.$sitename.'</a>');
		$itemlink =  $params['link'];

		switch ($params['verb']) {
			case ACTIVITY_FRIEND:
				// someone started to share with user (mostly OStatus)
				$subject = sprintf(t('[Friendica:Notify] A new person is sharing with you'));

				$preamble = sprintf(t('%1$s is sharing with you at %2$s'), $params['source_name'], $sitename);
				$epreamble = sprintf(t('%1$s is sharing with you at %2$s'),
							'[url='.$params['source_link'].']'.$params['source_name'].'[/url]',
							$sitename);
				break;
			case ACTIVITY_FOLLOW:
				// someone started to follow the user (mostly OStatus)
				$subject = sprintf(t('[Friendica:Notify] You have a new follower'));

				$preamble = sprintf(t('You have a new follower at %2$s : %1$s'), $params['source_name'], $sitename);
				$epreamble = sprintf(t('You have a new follower at %2$s : %1$s'),
							'[url='.$params['source_link'].']'.$params['source_name'].'[/url]',
							$sitename);
				break;
			default:
				// ACTIVITY_REQ_FRIEND is default activity for notifications
				break;
		}
	}

	if ($params['type'] == NOTIFY_SUGGEST) {
		$subject = sprintf(t('[Friendica:Notify] Friend suggestion received'));

		$preamble = sprintf(t('You\'ve received a friend suggestion from \'%1$s\' at %2$s'), $params['source_name'], $sitename);
		$epreamble = sprintf(t('You\'ve received [url=%1$s]a friend suggestion[/url] for %2$s from %3$s.'),
					$itemlink,
					'[url='.$params['item']['url'].']'.$params['item']['name'].'[/url]',
					'[url='.$params['source_link'].']'.$params['source_name'].'[/url]');

		$body = t('Name:').' '.$params['item']['name']."\n";
		$body .= t('Photo:').' '.$params['item']['photo']."\n";
		$body .= sprintf(t('You may visit their profile at %s'), $params['item']['url']);

		$sitelink = t('Please visit %s to approve or reject the suggestion.');
		$tsitelink = sprintf($sitelink, $siteurl);
		$hsitelink = sprintf($sitelink, '<a href="'.$siteurl.'">'.$sitename.'</a>');
		$itemlink =  $params['link'];
	}

	if ($params['type'] == NOTIFY_CONFIRM) {
		if ($params['verb'] == ACTIVITY_FRIEND) { // mutual connection
			$subject = sprintf(t('[Friendica:Notify] Connection accepted'));

			$preamble = sprintf(t('\'%1$s\' has accepted your connection request at %2$s'), $params['source_name'], $sitename);
			$epreamble = sprintf(t('%2$s has accepted your [url=%1$s]connection request[/url].'),
						$itemlink,
						'[url='.$params['source_link'].']'.$params['source_name'].'[/url]');

			$body =  t('You are now mutual friends and may exchange status updates, photos, and email without restriction.');

			$sitelink = t('Please visit %s if you wish to make any changes to this relationship.');
			$tsitelink = sprintf($sitelink, $siteurl);
			$hsitelink = sprintf($sitelink, '<a href="'.$siteurl.'">'.$sitename.'</a>');
			$itemlink =  $params['link'];
		} else { // ACTIVITY_FOLLOW
			$subject = sprintf(t('[Friendica:Notify] Connection accepted'));

			$preamble = sprintf(t('\'%1$s\' has accepted your connection request at %2$s'), $params['source_name'], $sitename);
			$epreamble = sprintf(t('%2$s has accepted your [url=%1$s]connection request[/url].'),
						$itemlink,
						'[url='.$params['source_link'].']'.$params['source_name'].'[/url]');

			$body =  sprintf(t('\'%1$s\' has chosen to accept you a "fan", which restricts some forms of communication - such as private messaging and some profile interactions. If this is a celebrity or community page, these settings were applied automatically.'), $params['source_name']);
			$body .= "\n\n";
			$body .= sprintf(t('\'%1$s\' may choose to extend this into a two-way or more permissive relationship in the future.'), $params['source_name']);

			$sitelink = t('Please visit %s  if you wish to make any changes to this relationship.');
			$tsitelink = sprintf($sitelink, $siteurl);
			$hsitelink = sprintf($sitelink, '<a href="'.$siteurl.'">'.$sitename.'</a>');
			$itemlink =  $params['link'];
		}
	}

	if ($params['type'] == NOTIFY_SYSTEM) {
		switch($params['event']) {
			case "SYSTEM_REGISTER_REQUEST":
				$subject = sprintf(t('[Friendica System:Notify] registration request'));

				$preamble = sprintf(t('You\'ve received a registration request from \'%1$s\' at %2$s'), $params['source_name'], $sitename);
				$epreamble = sprintf(t('You\'ve received a [url=%1$s]registration request[/url] from %2$s.'),
							$itemlink,
							'[url='.$params['source_link'].']'.$params['source_name'].'[/url]');

				$body = sprintf(t('Full Name:	%1$s\nSite Location:	%2$s\nLogin Name:	%3$s (%4$s)'),
									$params['source_name'], $siteurl, $params['source_mail'], $params['source_nick']);

				$sitelink = t('Please visit %s to approve or reject the request.');
				$tsitelink = sprintf($sitelink, $params['link']);
				$hsitelink = sprintf($sitelink, '<a href="'.$params['link'].'">'.$sitename.'</a><br><br>');
				$itemlink =  $params['link'];
				break;
			case "SYSTEM_DB_UPDATE_FAIL":
				break;
		}
	}

	if ($params['type'] == "SYSTEM_EMAIL") {
		// not part of the notifications.
		// it just send a mail to the user.
		// It will be used by the system to send emails to users (like
		// password reset, invitations and so) using one look (but without
		// add a notification to the user, with could be inexistent)
		$subject = $params['subject'];

		$preamble = $params['preamble'];

		$body =  $params['body'];

		$sitelink = "";
		$tsitelink = "";
		$hsitelink = "";
		$itemlink =  "";
		$show_in_notification_page = false;
	}

	$subject .= " (".$nickname."@".$hostname.")";

	$h = array(
		'params'    => $params,
		'subject'   => $subject,
		'preamble'  => $preamble,
		'epreamble' => $epreamble,
		'body'      => $body,
		'sitelink'  => $sitelink,
		'tsitelink' => $tsitelink,
		'hsitelink' => $hsitelink,
		'itemlink'  => $itemlink
	);

	call_hooks('enotify', $h);

	$subject   = $h['subject'];

	$preamble  = $h['preamble'];
	$epreamble = $h['epreamble'];

	$body      = $h['body'];

	$sitelink  = $h['sitelink'];
	$tsitelink = $h['tsitelink'];
	$hsitelink = $h['hsitelink'];
	$itemlink  = $h['itemlink'];

	if ($show_in_notification_page) {
		logger("adding notification entry", LOGGER_DEBUG);
		do {
			$dups = false;
			$hash = random_string();
			$r = q("SELECT `id` FROM `notify` WHERE `hash` = '%s' LIMIT 1",
				dbesc($hash));
			if (dbm::is_result($r))
				$dups = true;
		} while($dups == true);

		$datarray = array();
		$datarray['hash']  = $hash;
		$datarray['name']  = $params['source_name'];
		$datarray['name_cache'] = strip_tags(bbcode($params['source_name']));
		$datarray['url']   = $params['source_link'];
		$datarray['photo'] = $params['source_photo'];
		$datarray['date']  = datetime_convert();
		$datarray['uid']   = $params['uid'];
		$datarray['link']  = $itemlink;
		$datarray['iid']   = $item_id;
		$datarray['parent'] = $parent_id;
		$datarray['type']  = $params['type'];
		$datarray['verb']  = $params['verb'];
		$datarray['otype'] = $params['otype'];
		$datarray['abort'] = false;

		call_hooks('enotify_store', $datarray);

		if ($datarray['abort']) {
			pop_lang();
			return False;
		}

		// create notification entry in DB

		$r = q("INSERT INTO `notify` (`hash`, `name`, `url`, `photo`, `date`, `uid`, `link`, `iid`, `parent`, `type`, `verb`, `otype`, `name_cache`)
			values('%s', '%s', '%s', '%s', '%s', %d, '%s', %d, %d, %d, '%s', '%s', '%s')",
			dbesc($datarray['hash']),
			dbesc($datarray['name']),
			dbesc($datarray['url']),
			dbesc($datarray['photo']),
			dbesc($datarray['date']),
			intval($datarray['uid']),
			dbesc($datarray['link']),
			intval($datarray['iid']),
			intval($datarray['parent']),
			intval($datarray['type']),
			dbesc($datarray['verb']),
			dbesc($datarray['otype']),
			dbesc($datarray["name_cache"])
		);

		$r = q("SELECT `id` FROM `notify` WHERE `hash` = '%s' AND `uid` = %d LIMIT 1",
			dbesc($hash),
			intval($params['uid'])
		);
		if ($r)
			$notify_id = $r[0]['id'];
		else {
			pop_lang();
			return False;
		}

		// we seem to have a lot of duplicate comment notifications due to race conditions, mostly from forums
		// After we've stored everything, look again to see if there are any duplicates and if so remove them

		$p = null;
		$p = q("SELECT `id` FROM `notify` WHERE `type` IN (%d, %d) AND `link` = '%s' AND `uid` = %d ORDER BY `id`",
			intval(NOTIFY_TAGSELF),
			intval(NOTIFY_COMMENT),
			dbesc($params['link']),
			intval($params['uid'])
		);
		if ($p && (count($p) > 1)) {
			for ($d = 1; $d < count($p); $d ++) {
				q("DELETE FROM `notify` WHERE `id` = %d",
					intval($p[$d]['id'])
				);
			}

			// only continue on if we stored the first one

			if ($notify_id != $p[0]['id']) {
				pop_lang();
				return False;
			}
		}


		$itemlink = App::get_baseurl().'/notify/view/'.$notify_id;
		$msg = replace_macros($epreamble, array('$itemlink' => $itemlink));
		$msg_cache = format_notification_message($datarray['name_cache'], strip_tags(bbcode($msg)));
		$r = q("UPDATE `notify` SET `msg` = '%s', `msg_cache` = '%s' WHERE `id` = %d AND `uid` = %d",
			dbesc($msg),
			dbesc($msg_cache),
			intval($notify_id),
			intval($params['uid'])
		);
	}

	// send email notification if notification preferences permit
	if ((intval($params['notify_flags']) & intval($params['type']))
		|| $params['type'] == NOTIFY_SYSTEM
		|| $params['type'] == "SYSTEM_EMAIL") {

		logger('sending notification email');

		if (isset($params['parent']) AND (intval($params['parent']) != 0)) {
			$id_for_parent = $params['parent']."@".$hostname;

			// Is this the first email notification for this parent item and user?

			$r = q("SELECT `id` FROM `notify-threads` WHERE `master-parent-item` = %d AND `receiver-uid` = %d LIMIT 1",
				intval($params['parent']),
				intval($params['uid']));

			// If so, create the record of it and use a message-id smtp header.

			if (!$r) {
				logger("notify_id:".intval($notify_id).", parent: ".intval($params['parent'])."uid: ".intval($params['uid']), LOGGER_DEBUG);
				$r = q("INSERT INTO `notify-threads` (`notify-id`, `master-parent-item`, `receiver-uid`, `parent-item`)
					values(%d, %d, %d, %d)",
					intval($notify_id),
					intval($params['parent']),
					intval($params['uid']),
					0);

				$additional_mail_header .= "Message-ID: <${id_for_parent}>\n";
				$log_msg = "include/enotify: No previous notification found for this parent:\n".
						"  parent: ${params['parent']}\n"."  uid   : ${params['uid']}\n";
				logger($log_msg, LOGGER_DEBUG);
			} else {
				// If not, just "follow" the thread.
				$additional_mail_header .= "References: <${id_for_parent}>\nIn-Reply-To: <${id_for_parent}>\n";
				logger("There's already a notification for this parent:\n".print_r($r, true), LOGGER_DEBUG);
			}
		}

		// textversion keeps linebreaks
		$textversion = strip_tags(str_replace("<br>", "\n", html_entity_decode(bbcode(stripslashes(str_replace(array("\\r\\n", "\\r", "\\n"), "\n",
			$body))),ENT_QUOTES, 'UTF-8')));
		$htmlversion = html_entity_decode(bbcode(stripslashes(str_replace(array("\\r\\n", "\\r", "\\n\\n", "\\n"),
			"<br />\n", $body))), ENT_QUOTES, 'UTF-8');

		$datarray = array();
		$datarray['banner'] = $banner;
		$datarray['product'] = $product;
		$datarray['preamble'] = $preamble;
		$datarray['sitename'] = $sitename;
		$datarray['siteurl'] = $siteurl;
		$datarray['type'] = $params['type'];
		$datarray['parent'] = $params['parent'];
		$datarray['source_name'] = $params['source_name'];
		$datarray['source_link'] = $params['source_link'];
		$datarray['source_photo'] = $params['source_photo'];
		$datarray['uid'] = $params['uid'];
		$datarray['username'] = $params['to_name'];
		$datarray['hsitelink'] = $hsitelink;
		$datarray['tsitelink'] = $tsitelink;
		$datarray['hitemlink'] = '<a href="'.$itemlink.'">'.$itemlink.'</a>';
		$datarray['titemlink'] = $itemlink;
		$datarray['thanks'] = $thanks;
		$datarray['site_admin'] = $site_admin;
		$datarray['title'] = stripslashes($title);
		$datarray['htmlversion'] = $htmlversion;
		$datarray['textversion'] = $textversion;
		$datarray['subject'] = $subject;
		$datarray['headers'] = $additional_mail_header;

		call_hooks('enotify_mail', $datarray);

		// check whether sending post content in email notifications is allowed
		// always true for "SYSTEM_EMAIL"
		$content_allowed = ((!get_config('system', 'enotify_no_content')) || ($params['type'] == "SYSTEM_EMAIL"));

		// load the template for private message notifications
		$tpl = get_markup_template('email_notify_html.tpl');
		$email_html_body = replace_macros($tpl, array(
			'$banner'       => $datarray['banner'],
			'$product'      => $datarray['product'],
			'$preamble'     => str_replace("\n", "<br>\n", $datarray['preamble']),
			'$sitename'     => $datarray['sitename'],
			'$siteurl'      => $datarray['siteurl'],
			'$source_name'  => $datarray['source_name'],
			'$source_link'  => $datarray['source_link'],
			'$source_photo' => $datarray['source_photo'],
			'$username'     => $datarray['to_name'],
			'$hsitelink'    => $datarray['hsitelink'],
			'$hitemlink'    => $datarray['hitemlink'],
			'$thanks'       => $datarray['thanks'],
			'$site_admin'   => $datarray['site_admin'],
			'$title'	=> $datarray['title'],
			'$htmlversion'	=> $datarray['htmlversion'],
			'$content_allowed'	=> $content_allowed,
		));

		// load the template for private message notifications
		$tpl = get_markup_template('email_notify_text.tpl');
		$email_text_body = replace_macros($tpl, array(
			'$banner'       => $datarray['banner'],
			'$product'      => $datarray['product'],
			'$preamble'     => $datarray['preamble'],
			'$sitename'     => $datarray['sitename'],
			'$siteurl'      => $datarray['siteurl'],
			'$source_name'  => $datarray['source_name'],
			'$source_link'  => $datarray['source_link'],
			'$source_photo' => $datarray['source_photo'],
			'$username'     => $datarray['to_name'],
			'$tsitelink'    => $datarray['tsitelink'],
			'$titemlink'    => $datarray['titemlink'],
			'$thanks'       => $datarray['thanks'],
			'$site_admin'   => $datarray['site_admin'],
			'$title'	=> $datarray['title'],
			'$textversion'	=> $datarray['textversion'],
			'$content_allowed'	=> $content_allowed,
		));

		// use the Emailer class to send the message

		return Emailer::send(array(
			'uid' => $params['uid'],
			'fromName' => $sender_name,
			'fromEmail' => $sender_email,
			'replyTo' => $sender_email,
			'toEmail' => $params['to_email'],
			'messageSubject' => $datarray['subject'],
			'htmlVersion' => $email_html_body,
			'textVersion' => $email_text_body,
			'additionalMailHeader' => $datarray['headers'],
		));
	}

    return False;
}

/**
 * @brief Checks for item related notifications and sends them
 *
 * @param int $itemid ID of the item for which the check should be done
 * @param int $uid User ID
 * @param str $defaulttype (Optional) Forces a notification with this type.
 */
function check_item_notification($itemid, $uid, $defaulttype = "") {
	$notification_data = array("uid" => $uid, "profiles" => array());
	call_hooks('check_item_notification', $notification_data);

	$profiles = $notification_data["profiles"];

	$user = q("SELECT `notify-flags`, `language`, `username`, `email`, `nickname` FROM `user` WHERE `uid` = %d", intval($uid));
	if (!$user)
		return false;

	$owner = q("SELECT `id`, `url` FROM `contact` WHERE `self` AND `uid` = %d LIMIT 1", intval($uid));
	if (!$owner)
		return false;

	// This is our regular URL format
	$profiles[] = $owner[0]["url"];

	// Notifications from Diaspora are often with an URL in the Diaspora format
	$profiles[] = App::get_baseurl()."/u/".$user[0]["nickname"];

	$profiles2 = array();

	foreach ($profiles AS $profile) {
		// Check for invalid profile urls. 13 should be the shortest possible profile length:
		// http://a.bc/d
		// Additionally check for invalid urls that would return the normalised value "http:"
		if ((strlen($profile) >= 13) AND (normalise_link($profile) != "http:")) {
			if (!in_array($profile, $profiles2))
				$profiles2[] = $profile;

			$profile = normalise_link($profile);
			if (!in_array($profile, $profiles2))
				$profiles2[] = $profile;

			$profile = str_replace("http://", "https://", $profile);
			if (!in_array($profile, $profiles2))
				$profiles2[] = $profile;
		}
	}

	$profiles = $profiles2;

	$profile_list = "";

	foreach ($profiles AS $profile) {
		if ($profile_list != "")
			$profile_list .= "', '";

		$profile_list .= dbesc($profile);
	}

	$profile_list = "'".$profile_list."'";

	// Only act if it is a "real" post
	// We need the additional check for the "local_profile" because of mixed situations on connector networks
	$item = q("SELECT `id`, `mention`, `tag`,`parent`, `title`, `body`, `author-name`, `author-link`, `author-avatar`, `guid`,
			`parent-uri`, `uri`, `contact-id`
			FROM `item` WHERE `id` = %d AND `verb` IN ('%s', '') AND `type` != 'activity' AND
				NOT (`author-link` IN ($profile_list))  LIMIT 1",
		intval($itemid), dbesc(ACTIVITY_POST));
	if (!$item)
		return false;

	// Generate the notification array
	$params = array();
	$params["uid"] = $uid;
	$params["notify_flags"] = $user[0]["notify-flags"];
	$params["language"] = $user[0]["language"];
	$params["to_name"] = $user[0]["username"];
	$params["to_email"] = $user[0]["email"];
	$params["item"] = $item[0];
	$params["parent"] = $item[0]["parent"];
	$params["link"] = App::get_baseurl().'/display/'.urlencode($item[0]["guid"]);
	$params["otype"] = 'item';
	$params["source_name"] = $item[0]["author-name"];
	$params["source_link"] = $item[0]["author-link"];
	$params["source_photo"] = $item[0]["author-avatar"];

	if ($item[0]["parent-uri"] === $item[0]["uri"]) {
		// Send a notification for every new post?
		$r = q("SELECT `notify_new_posts` FROM `contact` WHERE `id` = %d AND `uid` = %d AND `notify_new_posts` LIMIT 1",
			intval($item[0]['contact-id']),
			intval($uid)
		);
		$send_notification = dbm::is_result($r);

		if (!$send_notification) {
			$tags = q("SELECT `url` FROM `term` WHERE `otype` = %d AND `oid` = %d AND `type` = %d AND `uid` = %d",
				intval(TERM_OBJ_POST), intval($itemid), intval(TERM_MENTION), intval($uid));

			if (dbm::is_result($tags)) {
				foreach ($tags AS $tag) {
					$r = q("SELECT `id` FROM `contact` WHERE `nurl` = '%s' AND `uid` = %d AND `notify_new_posts`",
						normalise_link($tag["url"]), intval($uid));
					if (dbm::is_result($r))
						$send_notification = true;
				}
			}
		}

		if ($send_notification) {
			$params["type"] = NOTIFY_SHARE;
			$params["verb"] = ACTIVITY_TAG;
		}
	}

	// Is the user mentioned in this post?
	$tagged = false;

	foreach ($profiles AS $profile) {
		if (strpos($item[0]["tag"], "=".$profile."]") OR strpos($item[0]["body"], "=".$profile."]"))
			$tagged = true;
	}

	if ($item[0]["mention"] OR $tagged OR ($defaulttype == NOTIFY_TAGSELF)) {
		$params["type"] = NOTIFY_TAGSELF;
		$params["verb"] = ACTIVITY_TAG;
	}

	// Is it a post that the user had started or where he interacted?
	$parent = q("SELECT `thread`.`iid` FROM `thread` INNER JOIN `item` ON `item`.`parent` = `thread`.`iid`
			WHERE `thread`.`iid` = %d AND `thread`.`uid` = %d AND NOT `thread`.`ignored` AND
				(`thread`.`mention` OR `item`.`author-link` IN ($profile_list))
			LIMIT 1",
			intval($item[0]["parent"]), intval($uid));

	if ($parent AND !isset($params["type"])) {
		$params["type"] = NOTIFY_COMMENT;
		$params["verb"] = ACTIVITY_POST;
	}

	if (isset($params["type"]))
		notification($params);
}

/**
 * @brief Formats a notification message with the notification author
 *
 * Replace the name with {0} but ensure to make that only once. The {0} is used
 * later and prints the name in bold.
 *
 * @param string $name
 * @param string $message
 * @return string Formatted message
 */
function format_notification_message($name, $message) {
	if ($name != '') {
		$pos = strpos($message, $name);
	} else {
		$pos = false;
	}

	if ($pos !== false) {
		$message = substr_replace($message, '{0}', $pos, strlen($name));
	}

	return $message;
}
