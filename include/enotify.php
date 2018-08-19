<?php
/**
 * @file include/enotify.php
 */

use Friendica\Content\Text\BBCode;
use Friendica\Core\Addon;
use Friendica\Core\Config;
use Friendica\Core\L10n;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\Model\Contact;
use Friendica\Model\Item;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Emailer;

/**
 * @brief Creates a notification entry and possibly sends a mail
 *
 * @param array $params Array with the elements:
 *			uid, item, parent, type, otype, verb, event,
 *			link, subject, body, to_name, to_email, source_name,
 *			source_link, activity, preamble, notify_flags,
 *			language, show_in_notification_page
 */
function notification($params)
{
	$a = get_app();

	// Temporary logging for finding the origin
	if (!isset($params['language']) || !isset($params['uid'])) {
		logger('Missing parameters.' . System::callstack());
	}

	// from here on everything is in the recipients language
	L10n::pushLang($params['language']);

	$banner = L10n::t('Friendica Notification');
	$product = FRIENDICA_PLATFORM;
	$siteurl = System::baseUrl(true);
	$thanks = L10n::t('Thank You,');
	$sitename = Config::get('config', 'sitename');
	if (Config::get('config', 'admin_name')) {
		$site_admin = L10n::t('%1$s, %2$s Administrator', Config::get('config', 'admin_name'), $sitename);
	} else {
		$site_admin = L10n::t('%s Administrator', $sitename);
	}

	$sender_name = $sitename;
	$hostname = $a->get_hostname();
	if (strpos($hostname, ':')) {
		$hostname = substr($hostname, 0, strpos($hostname, ':'));
	}

	$sender_email = $a->getSenderEmailAddress();

	if ($params['type'] != SYSTEM_EMAIL) {
		$user = DBA::selectFirst('user', ['nickname', 'page-flags'],
			['uid' => $params['uid']]);

		// There is no need to create notifications for forum accounts
		if (!DBA::isResult($user) || in_array($user["page-flags"], [Contact::PAGE_COMMUNITY, Contact::PAGE_PRVGROUP])) {
			return;
		}
		$nickname = $user["nickname"];
	} else {
		$nickname = '';
	}

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
	$additional_mail_header .= "List-Archive: <".System::baseUrl()."/notifications/system>\n";

	if (array_key_exists('item', $params)) {
		$title = $params['item']['title'];
		$body = $params['item']['body'];
	} else {
		$title = $body = '';
	}

	if (isset($params['item']['id'])) {
		$item_id = $params['item']['id'];
	} else {
		$item_id = 0;
	}

	if (isset($params['parent'])) {
		$parent_id = $params['parent'];
	} else {
		$parent_id = 0;
	}

	$epreamble = '';

	if ($params['type'] == NOTIFY_MAIL) {
		$itemlink = $siteurl.'/message/'.$params['item']['id'];
		$params["link"] = $itemlink;

		$subject = L10n::t('[Friendica:Notify] New mail received at %s', $sitename);

		$preamble = L10n::t('%1$s sent you a new private message at %2$s.', $params['source_name'], $sitename);
		$epreamble = L10n::t('%1$s sent you %2$s.', '[url='.$params['source_link'].']'.$params['source_name'].'[/url]', '[url=$itemlink]'.L10n::t('a private message').'[/url]');

		$sitelink = L10n::t('Please visit %s to view and/or reply to your private messages.');
		$tsitelink = sprintf($sitelink, $siteurl.'/message/'.$params['item']['id']);
		$hsitelink = sprintf($sitelink, '<a href="'.$siteurl.'/message/'.$params['item']['id'].'">'.$sitename.'</a>');
	}

	if ($params['type'] == NOTIFY_COMMENT) {
		$thread = Item::selectFirstThreadForUser($params['uid'] ,['ignored'], ['iid' => $parent_id]);
		if (DBA::isResult($thread) && $thread["ignored"]) {
			logger("Thread ".$parent_id." will be ignored", LOGGER_DEBUG);
			return;
		}

		// Check to see if there was already a tag notify or comment notify for this post.
		// If so don't create a second notification
		$condition = ['type' => [NOTIFY_TAGSELF, NOTIFY_COMMENT, NOTIFY_SHARE],
			'link' => $params['link'], 'uid' => $params['uid']];
		if (DBA::exists('notify', $condition)) {
			L10n::popLang();
			return;
		}

		// if it's a post figure out who's post it is.

		$item = null;

		if ($params['otype'] === 'item' && $parent_id) {
			$item = Item::selectFirstForUser($params['uid'], Item::ITEM_FIELDLIST, ['id' => $parent_id]);
		}

		$item_post_type = item_post_type($item);
		$itemlink = $item['plink'];

		// "a post"
		$dest_str = L10n::t('%1$s commented on [url=%2$s]a %3$s[/url]',
			'[url='.$params['source_link'].']'.$params['source_name'].'[/url]',
			$itemlink,
			$item_post_type
		);

		// "George Bull's post"
		if ($item) {
			$dest_str = L10n::t('%1$s commented on [url=%2$s]%3$s\'s %4$s[/url]',
				'[url='.$params['source_link'].']'.$params['source_name'].'[/url]',
				$itemlink,
				$item['author-name'],
				$item_post_type
			);
		}

		// "your post"
		if (DBA::isResult($item) && $item['owner-id'] == $item['author-id'] && $item['wall']) {
			$dest_str = L10n::t('%1$s commented on [url=%2$s]your %3$s[/url]',
				'[url='.$params['source_link'].']'.$params['source_name'].'[/url]',
				$itemlink,
				$item_post_type
			);
		}

		// Some mail softwares relies on subject field for threading.
		// So, we cannot have different subjects for notifications of the same thread.
		// Before this we have the name of the replier on the subject rendering
		// differents subjects for messages on the same thread.

		$subject = L10n::t('[Friendica:Notify] Comment to conversation #%1$d by %2$s', $parent_id, $params['source_name']);

		$preamble = L10n::t('%s commented on an item/conversation you have been following.', $params['source_name']);
		$epreamble = $dest_str;

		$sitelink = L10n::t('Please visit %s to view and/or reply to the conversation.');
		$tsitelink = sprintf($sitelink, $siteurl);
		$hsitelink = sprintf($sitelink, '<a href="'.$siteurl.'">'.$sitename.'</a>');
		$itemlink =  $params['link'];
	}

	if ($params['type'] == NOTIFY_WALL) {
		$subject = L10n::t('[Friendica:Notify] %s posted to your profile wall', $params['source_name']);

		$preamble = L10n::t('%1$s posted to your profile wall at %2$s', $params['source_name'], $sitename);
		$epreamble = L10n::t('%1$s posted to [url=%2$s]your wall[/url]',
			'[url='.$params['source_link'].']'.$params['source_name'].'[/url]',
			$params['link']
		);

		$sitelink = L10n::t('Please visit %s to view and/or reply to the conversation.');
		$tsitelink = sprintf($sitelink, $siteurl);
		$hsitelink = sprintf($sitelink, '<a href="'.$siteurl.'">'.$sitename.'</a>');
		$itemlink =  $params['link'];
	}

	if ($params['type'] == NOTIFY_TAGSELF) {
		$subject = L10n::t('[Friendica:Notify] %s tagged you', $params['source_name']);

		$preamble = L10n::t('%1$s tagged you at %2$s', $params['source_name'], $sitename);
		$epreamble = L10n::t('%1$s [url=%2$s]tagged you[/url].',
			'[url='.$params['source_link'].']'.$params['source_name'].'[/url]',
			$params['link']
		);

		$sitelink = L10n::t('Please visit %s to view and/or reply to the conversation.');
		$tsitelink = sprintf($sitelink, $siteurl);
		$hsitelink = sprintf($sitelink, '<a href="'.$siteurl.'">'.$sitename.'</a>');
		$itemlink =  $params['link'];
	}

	if ($params['type'] == NOTIFY_SHARE) {
		$subject = L10n::t('[Friendica:Notify] %s shared a new post', $params['source_name']);

		$preamble = L10n::t('%1$s shared a new post at %2$s', $params['source_name'], $sitename);
		$epreamble = L10n::t('%1$s [url=%2$s]shared a post[/url].',
			'[url='.$params['source_link'].']'.$params['source_name'].'[/url]',
			$params['link']
		);

		$sitelink = L10n::t('Please visit %s to view and/or reply to the conversation.');
		$tsitelink = sprintf($sitelink, $siteurl);
		$hsitelink = sprintf($sitelink, '<a href="'.$siteurl.'">'.$sitename.'</a>');
		$itemlink =  $params['link'];
	}

	if ($params['type'] == NOTIFY_POKE) {
		$subject = L10n::t('[Friendica:Notify] %1$s poked you', $params['source_name']);

		$preamble = L10n::t('%1$s poked you at %2$s', $params['source_name'], $sitename);
		$epreamble = L10n::t('%1$s [url=%2$s]poked you[/url].',
			'[url='.$params['source_link'].']'.$params['source_name'].'[/url]',
			$params['link']
		);

		$subject = str_replace('poked', L10n::t($params['activity']), $subject);
		$preamble = str_replace('poked', L10n::t($params['activity']), $preamble);
		$epreamble = str_replace('poked', L10n::t($params['activity']), $epreamble);

		$sitelink = L10n::t('Please visit %s to view and/or reply to the conversation.');
		$tsitelink = sprintf($sitelink, $siteurl);
		$hsitelink = sprintf($sitelink, '<a href="'.$siteurl.'">'.$sitename.'</a>');
		$itemlink =  $params['link'];
	}

	if ($params['type'] == NOTIFY_TAGSHARE) {
		$itemlink =  $params['link'];
		$subject = L10n::t('[Friendica:Notify] %s tagged your post', $params['source_name']);

		$preamble = L10n::t('%1$s tagged your post at %2$s', $params['source_name'], $sitename);
		$epreamble = L10n::t('%1$s tagged [url=%2$s]your post[/url]',
			'[url='.$params['source_link'].']'.$params['source_name'].'[/url]',
			$itemlink
		);

		$sitelink = L10n::t('Please visit %s to view and/or reply to the conversation.');
		$tsitelink = sprintf($sitelink, $siteurl);
		$hsitelink = sprintf($sitelink, '<a href="'.$siteurl.'">'.$sitename.'</a>');
	}

	if ($params['type'] == NOTIFY_INTRO) {
		$itemlink = $params['link'];
		$subject = L10n::t('[Friendica:Notify] Introduction received');

		$preamble = L10n::t('You\'ve received an introduction from \'%1$s\' at %2$s', $params['source_name'], $sitename);
		$epreamble = L10n::t('You\'ve received [url=%1$s]an introduction[/url] from %2$s.',
			$itemlink,
			'[url='.$params['source_link'].']'.$params['source_name'].'[/url]'
		);

		$body = L10n::t('You may visit their profile at %s', $params['source_link']);

		$sitelink = L10n::t('Please visit %s to approve or reject the introduction.');
		$tsitelink = sprintf($sitelink, $siteurl);
		$hsitelink = sprintf($sitelink, '<a href="'.$siteurl.'">'.$sitename.'</a>');

		switch ($params['verb']) {
			case ACTIVITY_FRIEND:
				// someone started to share with user (mostly OStatus)
				$subject = L10n::t('[Friendica:Notify] A new person is sharing with you');

				$preamble = L10n::t('%1$s is sharing with you at %2$s', $params['source_name'], $sitename);
				$epreamble = L10n::t('%1$s is sharing with you at %2$s',
					'[url='.$params['source_link'].']'.$params['source_name'].'[/url]',
					$sitename
				);
				break;
			case ACTIVITY_FOLLOW:
				// someone started to follow the user (mostly OStatus)
				$subject = L10n::t('[Friendica:Notify] You have a new follower');

				$preamble = L10n::t('You have a new follower at %2$s : %1$s', $params['source_name'], $sitename);
				$epreamble = L10n::t('You have a new follower at %2$s : %1$s',
					'[url='.$params['source_link'].']'.$params['source_name'].'[/url]',
					$sitename
				);
				break;
			default:
				// ACTIVITY_REQ_FRIEND is default activity for notifications
				break;
		}
	}

	if ($params['type'] == NOTIFY_SUGGEST) {
		$itemlink =  $params['link'];
		$subject = L10n::t('[Friendica:Notify] Friend suggestion received');

		$preamble = L10n::t('You\'ve received a friend suggestion from \'%1$s\' at %2$s', $params['source_name'], $sitename);
		$epreamble = L10n::t('You\'ve received [url=%1$s]a friend suggestion[/url] for %2$s from %3$s.',
			$itemlink,
			'[url='.$params['item']['url'].']'.$params['item']['name'].'[/url]',
			'[url='.$params['source_link'].']'.$params['source_name'].'[/url]'
		);

		$body = L10n::t('Name:').' '.$params['item']['name']."\n";
		$body .= L10n::t('Photo:').' '.$params['item']['photo']."\n";
		$body .= L10n::t('You may visit their profile at %s', $params['item']['url']);

		$sitelink = L10n::t('Please visit %s to approve or reject the suggestion.');
		$tsitelink = sprintf($sitelink, $siteurl);
		$hsitelink = sprintf($sitelink, '<a href="'.$siteurl.'">'.$sitename.'</a>');
	}

	if ($params['type'] == NOTIFY_CONFIRM) {
		if ($params['verb'] == ACTIVITY_FRIEND) { // mutual connection
			$itemlink =  $params['link'];
			$subject = L10n::t('[Friendica:Notify] Connection accepted');

			$preamble = L10n::t('\'%1$s\' has accepted your connection request at %2$s', $params['source_name'], $sitename);
			$epreamble = L10n::t('%2$s has accepted your [url=%1$s]connection request[/url].',
				$itemlink,
				'[url='.$params['source_link'].']'.$params['source_name'].'[/url]'
			);

			$body =  L10n::t('You are now mutual friends and may exchange status updates, photos, and email without restriction.');

			$sitelink = L10n::t('Please visit %s if you wish to make any changes to this relationship.');
			$tsitelink = sprintf($sitelink, $siteurl);
			$hsitelink = sprintf($sitelink, '<a href="'.$siteurl.'">'.$sitename.'</a>');
		} else { // ACTIVITY_FOLLOW
			$itemlink =  $params['link'];
			$subject = L10n::t('[Friendica:Notify] Connection accepted');

			$preamble = L10n::t('\'%1$s\' has accepted your connection request at %2$s', $params['source_name'], $sitename);
			$epreamble = L10n::t('%2$s has accepted your [url=%1$s]connection request[/url].',
				$itemlink,
				'[url='.$params['source_link'].']'.$params['source_name'].'[/url]'
			);

			$body =  L10n::t('\'%1$s\' has chosen to accept you a fan, which restricts some forms of communication - such as private messaging and some profile interactions. If this is a celebrity or community page, these settings were applied automatically.', $params['source_name']);
			$body .= "\n\n";
			$body .= L10n::t('\'%1$s\' may choose to extend this into a two-way or more permissive relationship in the future.', $params['source_name']);

			$sitelink = L10n::t('Please visit %s  if you wish to make any changes to this relationship.');
			$tsitelink = sprintf($sitelink, $siteurl);
			$hsitelink = sprintf($sitelink, '<a href="'.$siteurl.'">'.$sitename.'</a>');
		}
	}

	if ($params['type'] == NOTIFY_SYSTEM) {
		switch($params['event']) {
			case "SYSTEM_REGISTER_REQUEST":
				$itemlink =  $params['link'];
				$subject = L10n::t('[Friendica System Notify]') . ' ' . L10n::t('registration request');

				$preamble = L10n::t('You\'ve received a registration request from \'%1$s\' at %2$s', $params['source_name'], $sitename);
				$epreamble = L10n::t('You\'ve received a [url=%1$s]registration request[/url] from %2$s.',
					$itemlink,
					'[url='.$params['source_link'].']'.$params['source_name'].'[/url]'
				);

				$body = L10n::t("Full Name:	%s\nSite Location:	%s\nLogin Name:	%s (%s)",
					$params['source_name'],
					$siteurl, $params['source_mail'],
					$params['source_nick']
				);

				$sitelink = L10n::t('Please visit %s to approve or reject the request.');
				$tsitelink = sprintf($sitelink, $params['link']);
				$hsitelink = sprintf($sitelink, '<a href="'.$params['link'].'">'.$sitename.'</a><br><br>');
				break;
			case "SYSTEM_DB_UPDATE_FAIL":
				break;
		}
	}

	if ($params['type'] == SYSTEM_EMAIL) {
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

	$h = [
		'params'    => $params,
		'subject'   => $subject,
		'preamble'  => $preamble,
		'epreamble' => $epreamble,
		'body'      => $body,
		'sitelink'  => $sitelink,
		'tsitelink' => $tsitelink,
		'hsitelink' => $hsitelink,
		'itemlink'  => $itemlink
	];

	Addon::callHooks('enotify', $h);

	$subject   = $h['subject'];

	$preamble  = $h['preamble'];
	$epreamble = $h['epreamble'];

	$body      = $h['body'];

	$tsitelink = $h['tsitelink'];
	$hsitelink = $h['hsitelink'];
	$itemlink  = $h['itemlink'];

	if ($show_in_notification_page) {
		logger("adding notification entry", LOGGER_DEBUG);
		do {
			$dups = false;
			$hash = random_string();
			if (DBA::exists('notify', ['hash' => $hash])) {
				$dups = true;
			}
		} while ($dups == true);

		/// @TODO One statement is enough
		$datarray = [];
		$datarray['hash']  = $hash;
		$datarray['name']  = $params['source_name'];
		$datarray['name_cache'] = strip_tags(BBCode::convert($params['source_name']));
		$datarray['url']   = $params['source_link'];
		$datarray['photo'] = $params['source_photo'];
		$datarray['date']  = DateTimeFormat::utcNow();
		$datarray['uid']   = $params['uid'];
		$datarray['link']  = $itemlink;
		$datarray['iid']   = $item_id;
		$datarray['parent'] = $parent_id;
		$datarray['type']  = $params['type'];
		$datarray['verb']  = $params['verb'];
		$datarray['otype'] = $params['otype'];
		$datarray['abort'] = false;

		Addon::callHooks('enotify_store', $datarray);

		if ($datarray['abort']) {
			L10n::popLang();
			return False;
		}

		// create notification entry in DB
		$fields = ['hash' => $datarray['hash'], 'name' => $datarray['name'], 'url' => $datarray['url'],
			'photo' => $datarray['photo'], 'date' => $datarray['date'], 'uid' => $datarray['uid'],
			'link' => $datarray['link'], 'iid' => $datarray['iid'], 'parent' => $datarray['parent'],
			'type' => $datarray['type'], 'verb' => $datarray['verb'], 'otype' => $datarray['otype'],
			'name_cache' => $datarray["name_cache"]];
		DBA::insert('notify', $fields);

		$notify_id = DBA::lastInsertId();

		// we seem to have a lot of duplicate comment notifications due to race conditions, mostly from forums
		// After we've stored everything, look again to see if there are any duplicates and if so remove them
		$p = q("SELECT `id` FROM `notify` WHERE `type` IN (%d, %d) AND `link` = '%s' AND `uid` = %d ORDER BY `id`",
			intval(NOTIFY_TAGSELF),
			intval(NOTIFY_COMMENT),
			DBA::escape($params['link']),
			intval($params['uid'])
		);
		if ($p && (count($p) > 1)) {
			for ($d = 1; $d < count($p); $d ++) {
				DBA::delete('notify', ['id' => $p[$d]['id']]);
			}

			// only continue on if we stored the first one
			if ($notify_id != $p[0]['id']) {
				L10n::popLang();
				return false;
			}
		}

		$itemlink = System::baseUrl().'/notify/view/'.$notify_id;
		$msg = replace_macros($epreamble, ['$itemlink' => $itemlink]);
		$msg_cache = format_notification_message($datarray['name_cache'], strip_tags(BBCode::convert($msg)));

		$fields = ['msg' => $msg, 'msg_cache' => $msg_cache];
		$condition = ['id' => $notify_id, 'uid' => $params['uid']];
		DBA::update('notify', $fields, $condition);
	}

	// send email notification if notification preferences permit
	if ((!empty($params['notify_flags']) & intval($params['type']))
		|| $params['type'] == NOTIFY_SYSTEM
		|| $params['type'] == SYSTEM_EMAIL) {

		logger('sending notification email');

		if (isset($params['parent']) && (intval($params['parent']) != 0)) {
			$id_for_parent = $params['parent']."@".$hostname;

			// Is this the first email notification for this parent item and user?
			if (!DBA::exists('notify-threads', ['master-parent-item' => $params['parent'], 'receiver-uid' => $params['uid']])) {
				logger("notify_id:".intval($notify_id).", parent: ".intval($params['parent'])."uid: ".intval($params['uid']), LOGGER_DEBUG);

				$fields = ['notify-id' => $notify_id, 'master-parent-item' => $params['parent'],
					'receiver-uid' => $params['uid'], 'parent-item' => 0];
				DBA::insert('notify-threads', $fields);

				$additional_mail_header .= "Message-ID: <${id_for_parent}>\n";
				$log_msg = "include/enotify: No previous notification found for this parent:\n".
						"  parent: ${params['parent']}\n"."  uid   : ${params['uid']}\n";
				logger($log_msg, LOGGER_DEBUG);
			} else {
				// If not, just "follow" the thread.
				$additional_mail_header .= "References: <${id_for_parent}>\nIn-Reply-To: <${id_for_parent}>\n";
				logger("There's already a notification for this parent.", LOGGER_DEBUG);
			}
		}

		$textversion = BBCode::toPlaintext($body);
		$htmlversion = BBCode::convert($body);

		$datarray = [];
		$datarray['banner'] = $banner;
		$datarray['product'] = $product;
		$datarray['preamble'] = $preamble;
		$datarray['sitename'] = $sitename;
		$datarray['siteurl'] = $siteurl;
		$datarray['type'] = $params['type'];
		$datarray['parent'] = $parent_id;
		$datarray['source_name'] = defaults($params, 'source_name', '');
		$datarray['source_link'] = defaults($params, 'source_link', '');
		$datarray['source_photo'] = defaults($params, 'source_photo', '');
		$datarray['uid'] = $params['uid'];
		$datarray['username'] = defaults($params, 'to_name', '');
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

		Addon::callHooks('enotify_mail', $datarray);

		// check whether sending post content in email notifications is allowed
		// always true for SYSTEM_EMAIL
		$content_allowed = ((!Config::get('system', 'enotify_no_content')) || ($params['type'] == SYSTEM_EMAIL));

		// load the template for private message notifications
		$tpl = get_markup_template('email_notify_html.tpl');
		$email_html_body = replace_macros($tpl, [
			'$banner'       => $datarray['banner'],
			'$product'      => $datarray['product'],
			'$preamble'     => str_replace("\n", "<br>\n", $datarray['preamble']),
			'$sitename'     => $datarray['sitename'],
			'$siteurl'      => $datarray['siteurl'],
			'$source_name'  => $datarray['source_name'],
			'$source_link'  => $datarray['source_link'],
			'$source_photo' => $datarray['source_photo'],
			'$username'     => $datarray['username'],
			'$hsitelink'    => $datarray['hsitelink'],
			'$hitemlink'    => $datarray['hitemlink'],
			'$thanks'       => $datarray['thanks'],
			'$site_admin'   => $datarray['site_admin'],
			'$title'	=> $datarray['title'],
			'$htmlversion'	=> $datarray['htmlversion'],
			'$content_allowed'	=> $content_allowed,
		]);

		// load the template for private message notifications
		$tpl = get_markup_template('email_notify_text.tpl');
		$email_text_body = replace_macros($tpl, [
			'$banner'       => $datarray['banner'],
			'$product'      => $datarray['product'],
			'$preamble'     => $datarray['preamble'],
			'$sitename'     => $datarray['sitename'],
			'$siteurl'      => $datarray['siteurl'],
			'$source_name'  => $datarray['source_name'],
			'$source_link'  => $datarray['source_link'],
			'$source_photo' => $datarray['source_photo'],
			'$username'     => $datarray['username'],
			'$tsitelink'    => $datarray['tsitelink'],
			'$titemlink'    => $datarray['titemlink'],
			'$thanks'       => $datarray['thanks'],
			'$site_admin'   => $datarray['site_admin'],
			'$title'	=> $datarray['title'],
			'$textversion'	=> $datarray['textversion'],
			'$content_allowed'	=> $content_allowed,
		]);

		// use the Emailer class to send the message
		return Emailer::send(
			[
			'uid' => $params['uid'],
			'fromName' => $sender_name,
			'fromEmail' => $sender_email,
			'replyTo' => $sender_email,
			'toEmail' => $params['to_email'],
			'messageSubject' => $datarray['subject'],
			'htmlVersion' => $email_html_body,
			'textVersion' => $email_text_body,
			'additionalMailHeader' => $datarray['headers']]
		);
	}

	return false;
}

/**
 * @brief Checks for users who should be notified
 *
 * @param int $itemid ID of the item for which the check should be done
 */
function check_user_notification($itemid) {
	// fetch all users in the thread
	$users = DBA::p("SELECT DISTINCT(`contact`.`uid`) FROM `item`
			INNER JOIN `contact` ON `contact`.`id` = `item`.`contact-id` AND `contact`.`uid` != 0
			WHERE `parent` IN (SELECT `parent` FROM `item` WHERE `id`=?)", $itemid);
	while ($user = DBA::fetch($users)) {
		check_item_notification($itemid, $user['uid']);
	}
	DBA::close($users);
}

/**
 * @brief Checks for item related notifications and sends them
 *
 * @param int $itemid ID of the item for which the check should be done
 * @param int $uid User ID
 * @param string $defaulttype (Optional) Forces a notification with this type.
 */
function check_item_notification($itemid, $uid, $defaulttype = "") {
	$notification_data = ["uid" => $uid, "profiles" => []];
	Addon::callHooks('check_item_notification', $notification_data);

	$profiles = $notification_data["profiles"];

	$fields = ['notify-flags', 'language', 'username', 'email', 'nickname'];
	$user = DBA::selectFirst('user', $fields, ['uid' => $uid]);
	if (!DBA::isResult($user)) {
		return false;
	}

	$owner = DBA::selectFirst('contact', ['url'], ['self' => true, 'uid' => $uid]);
	if (!DBA::isResult($owner)) {
		return false;
	}

	// This is our regular URL format
	$profiles[] = $owner["url"];

	// Notifications from Diaspora are often with an URL in the Diaspora format
	$profiles[] = System::baseUrl()."/u/".$user["nickname"];

	$profiles2 = [];

	foreach ($profiles AS $profile) {
		// Check for invalid profile urls. 13 should be the shortest possible profile length:
		// http://a.bc/d
		// Additionally check for invalid urls that would return the normalised value "http:"
		if ((strlen($profile) >= 13) && (normalise_link($profile) != "http:")) {
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

	$ret = DBA::select('contact', ['id'], ['uid' => 0, 'nurl' => $profiles]);

	$contacts = [];

	while ($contact = DBA::fetch($ret)) {
		$contacts[] = $contact['id'];
	}

	DBA::close($ret);

	// Only act if it is a "real" post
	// We need the additional check for the "local_profile" because of mixed situations on connector networks
	$fields = ['id', 'mention', 'tag', 'parent', 'title', 'body',
		'author-link', 'author-name', 'author-avatar', 'author-id',
		'guid', 'parent-uri', 'uri', 'contact-id', 'network'];
	$condition = ['id' => $itemid, 'gravity' => [GRAVITY_PARENT, GRAVITY_COMMENT]];
	$item = Item::selectFirst($fields, $condition);
	if (!DBA::isResult($item) || in_array($item['author-id'], $contacts)) {
		return;
	}

	// Generate the notification array
	$params = [];
	$params["uid"] = $uid;
	$params["notify_flags"] = $user["notify-flags"];
	$params["language"] = $user["language"];
	$params["to_name"] = $user["username"];
	$params["to_email"] = $user["email"];
	$params["item"] = $item;
	$params["parent"] = $item["parent"];
	$params["link"] = System::baseUrl().'/display/'.urlencode($item["guid"]);
	$params["otype"] = 'item';
	$params["source_name"] = $item["author-name"];
	$params["source_link"] = $item["author-link"];
	$params["source_photo"] = $item["author-avatar"];

	if ($item["parent-uri"] === $item["uri"]) {
		// Send a notification for every new post?
		$send_notification = DBA::exists('contact', ['id' => $item['contact-id'], 'notify_new_posts' => true]);

		if (!$send_notification) {
			$tags = q("SELECT `url` FROM `term` WHERE `otype` = %d AND `oid` = %d AND `type` = %d AND `uid` = %d",
				intval(TERM_OBJ_POST), intval($itemid), intval(TERM_MENTION), intval($uid));

			if (DBA::isResult($tags)) {
				foreach ($tags AS $tag) {
					$condition = ['nurl' => normalise_link($tag["url"]), 'uid' => $uid, 'notify_new_posts' => true];
					$r = DBA::exists('contact', $condition);
					if ($r) {
						$send_notification = true;
					}
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
		if (strpos($item["tag"], "=".$profile."]") || strpos($item["body"], "=".$profile."]"))
			$tagged = true;
	}

	if ($item["mention"] || $tagged || ($defaulttype == NOTIFY_TAGSELF)) {
		$params["type"] = NOTIFY_TAGSELF;
		$params["verb"] = ACTIVITY_TAG;
	}

	// Is it a post that the user had started?
	$fields = ['ignored', 'mention'];
	$thread = Item::selectFirstThreadForUser($params['uid'], $fields, ['iid' => $item["parent"]]);

	if ($thread['mention'] && !$thread['ignored'] && !isset($params["type"])) {
		$params["type"] = NOTIFY_COMMENT;
		$params["verb"] = ACTIVITY_POST;
	}

	// And now we check for participation of one of our contacts in the thread
	$condition = ['parent' => $item["parent"], 'author-id' => $contacts];

	if (!$thread['ignored'] && !isset($params["type"]) && Item::exists($condition)) {
		$params["type"] = NOTIFY_COMMENT;
		$params["verb"] = ACTIVITY_POST;
	}

	if (isset($params["type"])) {
		notification($params);
	}
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
