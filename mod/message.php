<?php
/**
 * @file mod/message.php
 */
use Friendica\App;
use Friendica\Content\Nav;
use Friendica\Content\Smilies;
use Friendica\Core\System;
use Friendica\Database\DBM;
use Friendica\Model\Contact;
use Friendica\Model\Mail;

require_once 'include/acl_selectors.php';
require_once 'include/conversation.php';

function message_init(App $a)
{
	$tabs = '';

	if ($a->argc > 1 && is_numeric($a->argv[1])) {
		$tabs = render_messages(get_messages(local_user(), 0, 5), 'mail_list.tpl');
	}

	$new = [
		'label' => t('New Message'),
		'url' => 'message/new',
		'sel' => $a->argc > 1 && $a->argv[1] == 'new',
		'accesskey' => 'm',
	];

	$tpl = get_markup_template('message_side.tpl');
	$a->page['aside'] = replace_macros($tpl, [
		'$tabs' => $tabs,
		'$new' => $new,
	]);
	$base = System::baseUrl();

	$head_tpl = get_markup_template('message-head.tpl');
	$a->page['htmlhead'] .= replace_macros($head_tpl, [
		'$baseurl' => System::baseUrl(true),
		'$base' => $base
	]);

	$end_tpl = get_markup_template('message-end.tpl');
	$a->page['end'] .= replace_macros($end_tpl, [
		'$baseurl' => System::baseUrl(true),
		'$base' => $base
	]);
}

function message_post(App $a)
{
	if (!local_user()) {
		notice(t('Permission denied.') . EOL);
		return;
	}

	$replyto   = x($_REQUEST, 'replyto')   ? notags(trim($_REQUEST['replyto']))   : '';
	$subject   = x($_REQUEST, 'subject')   ? notags(trim($_REQUEST['subject']))   : '';
	$body      = x($_REQUEST, 'body')      ? escape_tags(trim($_REQUEST['body'])) : '';
	$recipient = x($_REQUEST, 'messageto') ? intval($_REQUEST['messageto'])       : 0;

	$ret = Mail::send($recipient, $body, $subject, $replyto);
	$norecip = false;

	switch ($ret) {
		case -1:
			notice(t('No recipient selected.') . EOL);
			$norecip = true;
			break;
		case -2:
			notice(t('Unable to locate contact information.') . EOL);
			break;
		case -3:
			notice(t('Message could not be sent.') . EOL);
			break;
		case -4:
			notice(t('Message collection failure.') . EOL);
			break;
		default:
			info(t('Message sent.') . EOL);
	}

	// fake it to go back to the input form if no recipient listed
	if ($norecip) {
		$a->argc = 2;
		$a->argv[1] = 'new';
	} else {
		goaway($_SESSION['return_url']);
	}
}

function message_content(App $a)
{
	$o = '';
	Nav::setSelected('messages');

	if (!local_user()) {
		notice(t('Permission denied.') . EOL);
		return;
	}

	$myprofile = System::baseUrl() . '/profile/' . $a->user['nickname'];

	$tpl = get_markup_template('mail_head.tpl');
	$header = replace_macros($tpl, [
		'$messages' => t('Messages'),
	]);

	if (($a->argc == 3) && ($a->argv[1] === 'drop' || $a->argv[1] === 'dropconv')) {
		if (!intval($a->argv[2])) {
			return;
		}

		// Check if we should do HTML-based delete confirmation
		if ($_REQUEST['confirm']) {
			// <form> can't take arguments in its "action" parameter
			// so add any arguments as hidden inputs
			$query = explode_querystring($a->query_string);
			$inputs = [];
			foreach ($query['args'] as $arg) {
				if (strpos($arg, 'confirm=') === false) {
					$arg_parts = explode('=', $arg);
					$inputs[] = ['name' => $arg_parts[0], 'value' => $arg_parts[1]];
				}
			}

			//$a->page['aside'] = '';
			return replace_macros(get_markup_template('confirm.tpl'), [
				'$method' => 'get',
				'$message' => t('Do you really want to delete this message?'),
				'$extra_inputs' => $inputs,
				'$confirm' => t('Yes'),
				'$confirm_url' => $query['base'],
				'$confirm_name' => 'confirmed',
				'$cancel' => t('Cancel'),
			]);
		}
		// Now check how the user responded to the confirmation query
		if ($_REQUEST['canceled']) {
			goaway($_SESSION['return_url']);
		}

		$cmd = $a->argv[1];
		if ($cmd === 'drop') {
			$r = q("DELETE FROM `mail` WHERE `id` = %d AND `uid` = %d LIMIT 1",
				intval($a->argv[2]),
				intval(local_user())
			);
			if ($r) {
				info(t('Message deleted.') . EOL);
			}
			//goaway(System::baseUrl(true) . '/message' );
			goaway($_SESSION['return_url']);
		} else {
			$r = q("SELECT `parent-uri`,`convid` FROM `mail` WHERE `id` = %d AND `uid` = %d LIMIT 1",
				intval($a->argv[2]),
				intval(local_user())
			);
			if (DBM::isResult($r)) {
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
				//if ($convid) {
				//	q("delete from conv where id = %d limit 1",
				//		intval($convid)
				//	);
				//}

				if ($r) {
					info(t('Conversation removed.') . EOL);
				}
			}
			//goaway(System::baseUrl(true) . '/message' );
			goaway($_SESSION['return_url']);
		}
	}

	if (($a->argc > 1) && ($a->argv[1] === 'new')) {
		$o .= $header;

		$tpl = get_markup_template('msg-header.tpl');
		$a->page['htmlhead'] .= replace_macros($tpl, [
			'$baseurl' => System::baseUrl(true),
			'$nickname' => $a->user['nickname'],
			'$linkurl' => t('Please enter a link URL:')
		]);

		$tpl = get_markup_template('msg-end.tpl');
		$a->page['end'] .= replace_macros($tpl, [
			'$baseurl' => System::baseUrl(true),
			'$nickname' => $a->user['nickname'],
			'$linkurl' => t('Please enter a link URL:')
		]);

		$preselect = isset($a->argv[2]) ? [$a->argv[2]] : false;

		$prename = $preurl = $preid = '';

		if ($preselect) {
			$r = q("SELECT `name`, `url`, `id` FROM `contact` WHERE `uid` = %d AND `id` = %d LIMIT 1",
				intval(local_user()),
				intval($a->argv[2])
			);
			if (!DBM::isResult($r)) {
				$r = q("SELECT `name`, `url`, `id` FROM `contact` WHERE `uid` = %d AND `nurl` = '%s' LIMIT 1",
					intval(local_user()),
					dbesc(normalise_link(base64_decode($a->argv[2])))
				);
			}

			if (!DBM::isResult($r)) {
				$r = q("SELECT `name`, `url`, `id` FROM `contact` WHERE `uid` = %d AND `addr` = '%s' LIMIT 1",
					intval(local_user()),
					dbesc(base64_decode($a->argv[2]))
				);
			}

			if (DBM::isResult($r)) {
				$prename = $r[0]['name'];
				$preurl = $r[0]['url'];
				$preid = $r[0]['id'];
				$preselect = [$preid];
			} else {
				$preselect = false;
			}
		}

		$prefill = $preselect ? $prename : '';

		// the ugly select box
		$select = contact_select('messageto', 'message-to-select', $preselect, 4, true, false, false, 10);

		$tpl = get_markup_template('prv_message.tpl');
		$o .= replace_macros($tpl, [
			'$header' => t('Send Private Message'),
			'$to' => t('To:'),
			'$showinputs' => 'true',
			'$prefill' => $prefill,
			'$autocomp' => $autocomp,
			'$preid' => $preid,
			'$subject' => t('Subject:'),
			'$subjtxt' => x($_REQUEST, 'subject') ? strip_tags($_REQUEST['subject']) : '',
			'$text' => x($_REQUEST, 'body') ? escape_tags(htmlspecialchars($_REQUEST['body'])) : '',
			'$readonly' => '',
			'$yourmessage' => t('Your message:'),
			'$select' => $select,
			'$parent' => '',
			'$upload' => t('Upload photo'),
			'$insert' => t('Insert web link'),
			'$wait' => t('Please wait'),
			'$submit' => t('Submit')
		]);
		return $o;
	}


	$_SESSION['return_url'] = $a->query_string;

	if ($a->argc == 1) {

		// List messages

		$o .= $header;

		$r = q("SELECT count(*) AS `total`, ANY_VALUE(`created`) AS `created` FROM `mail`
			WHERE `mail`.`uid` = %d GROUP BY `parent-uri` ORDER BY `created` DESC",
			intval(local_user())
		);

		if (DBM::isResult($r)) {
			$a->set_pager_total($r[0]['total']);
		}

		$r = get_messages(local_user(), $a->pager['start'], $a->pager['itemspage']);

		if (!DBM::isResult($r)) {
			info(t('No messages.') . EOL);
			return $o;
		}

		$o .= render_messages($r, 'mail_list.tpl');

		$o .= paginate($a);

		return $o;
	}

	if (($a->argc > 1) && (intval($a->argv[1]))) {

		$o .= $header;

		$r = q("SELECT `mail`.*, `contact`.`name`, `contact`.`url`, `contact`.`thumb`
			FROM `mail` LEFT JOIN `contact` ON `mail`.`contact-id` = `contact`.`id`
			WHERE `mail`.`uid` = %d AND `mail`.`id` = %d LIMIT 1",
			intval(local_user()),
			intval($a->argv[1])
		);
		if (DBM::isResult($r)) {
			$contact_id = $r[0]['contact-id'];
			$convid = $r[0]['convid'];

			$sql_extra = sprintf(" and `mail`.`parent-uri` = '%s' ", dbesc($r[0]['parent-uri']));
			if ($convid)
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
		if (!count($messages)) {
			notice(t('Message not available.') . EOL);
			return $o;
		}

		$r = q("UPDATE `mail` SET `seen` = 1 WHERE `parent-uri` = '%s' AND `uid` = %d",
			dbesc($r[0]['parent-uri']),
			intval(local_user())
		);

		require_once("include/bbcode.php");

		$tpl = get_markup_template('msg-header.tpl');
		$a->page['htmlhead'] .= replace_macros($tpl, [
			'$baseurl' => System::baseUrl(true),
			'$nickname' => $a->user['nickname'],
			'$linkurl' => t('Please enter a link URL:')
		]);

		$tpl = get_markup_template('msg-end.tpl');
		$a->page['end'] .= replace_macros($tpl, [
			'$baseurl' => System::baseUrl(true),
			'$nickname' => $a->user['nickname'],
			'$linkurl' => t('Please enter a link URL:')
		]);

		$mails = [];
		$seen = 0;
		$unknown = false;

		foreach ($messages as $message) {
			if ($message['unknown'])
				$unknown = true;
			if ($message['from-url'] == $myprofile) {
				$from_url = $myprofile;
				$sparkle = '';
			} elseif ($message['contact-id'] != 0) {
				$from_url = 'redir/' . $message['contact-id'];
				$sparkle = ' sparkle';
			} else {
				$from_url = $message['from-url'] . "?zrl=" . urlencode($myprofile);
				$sparkle = ' sparkle';
			}

			$extracted = item_extract_images($message['body']);
			if ($extracted['images']) {
				$message['body'] = item_redir_and_replace_images($extracted['body'], $extracted['images'], $message['contact-id']);
			}

			$from_name_e = $message['from-name'];
			$subject_e = $message['title'];
			$body_e = Smilies::replace(bbcode($message['body']));
			$to_name_e = $message['name'];

			$contact = Contact::getDetailsByURL($message['from-url']);
			if (isset($contact["thumb"])) {
				$from_photo = $contact["thumb"];
			} else {
				$from_photo = $message['from-photo'];
			}

			$mails[] = [
				'id' => $message['id'],
				'from_name' => $from_name_e,
				'from_url' => $from_url,
				'from_addr' => $contact['addr'],
				'sparkle' => $sparkle,
				'from_photo' => proxy_url($from_photo, false, PROXY_SIZE_THUMB),
				'subject' => $subject_e,
				'body' => $body_e,
				'delete' => t('Delete message'),
				'to_name' => $to_name_e,
				'date' => datetime_convert('UTC', date_default_timezone_get(), $message['created'], 'D, d M Y - g:i A'),
				'ago' => relative_date($message['created']),
			];

			$seen = $message['seen'];
		}

		$select = $message['name'] . '<input type="hidden" name="messageto" value="' . $contact_id . '" />';
		$parent = '<input type="hidden" name="replyto" value="' . $message['parent-uri'] . '" />';

		$tpl = get_markup_template('mail_display.tpl');
		$o = replace_macros($tpl, [
			'$thread_id' => $a->argv[1],
			'$thread_subject' => $message['title'],
			'$thread_seen' => $seen,
			'$delete' => t('Delete conversation'),
			'$canreply' => (($unknown) ? false : '1'),
			'$unknown_text' => t("No secure communications available. You <strong>may</strong> be able to respond from the sender's profile page."),
			'$mails' => $mails,

			// reply
			'$header' => t('Send Reply'),
			'$to' => t('To:'),
			'$showinputs' => '',
			'$subject' => t('Subject:'),
			'$subjtxt' => $message['title'],
			'$readonly' => ' readonly="readonly" style="background: #BBBBBB;" ',
			'$yourmessage' => t('Your message:'),
			'$text' => '',
			'$select' => $select,
			'$parent' => $parent,
			'$upload' => t('Upload photo'),
			'$insert' => t('Insert web link'),
			'$submit' => t('Submit'),
			'$wait' => t('Please wait')
		]);

		return $o;
	}
}

function get_messages($user, $lstart, $lend)
{
	//TODO: rewritte with a sub-query to get the first message of each private thread with certainty
	return q("SELECT max(`mail`.`created`) AS `mailcreated`, min(`mail`.`seen`) AS `mailseen`,
		ANY_VALUE(`mail`.`id`) AS `id`, ANY_VALUE(`mail`.`uid`) AS `uid`, ANY_VALUE(`mail`.`guid`) AS `guid`,
		ANY_VALUE(`mail`.`from-name`) AS `from-name`, ANY_VALUE(`mail`.`from-photo`) AS `from-photo`,
		ANY_VALUE(`mail`.`from-url`) AS `from-url`, ANY_VALUE(`mail`.`contact-id`) AS `contact-id`,
		ANY_VALUE(`mail`.`convid`) AS `convid`, ANY_VALUE(`mail`.`title`) AS `title`, ANY_VALUE(`mail`.`body`) AS `body`,
		ANY_VALUE(`mail`.`seen`) AS `seen`, ANY_VALUE(`mail`.`reply`) AS `reply`, ANY_VALUE(`mail`.`replied`) AS `replied`,
		ANY_VALUE(`mail`.`unknown`) AS `unknown`, ANY_VALUE(`mail`.`uri`) AS `uri`,
		`mail`.`parent-uri`,
		ANY_VALUE(`mail`.`created`) AS `created`, ANY_VALUE(`contact`.`name`) AS `name`, ANY_VALUE(`contact`.`url`) AS `url`,
		ANY_VALUE(`contact`.`thumb`) AS `thumb`, ANY_VALUE(`contact`.`network`) AS `network`,
		count( * ) as `count`
		FROM `mail` LEFT JOIN `contact` ON `mail`.`contact-id` = `contact`.`id`
		WHERE `mail`.`uid` = %d GROUP BY `parent-uri` ORDER BY `mailcreated` DESC LIMIT %d , %d ",
		intval($user), intval($lstart), intval($lend)
	);
}

function render_messages(array $msg, $t)
{
	$a = get_app();

	$tpl = get_markup_template($t);
	$rslt = '';

	$myprofile = System::baseUrl() . '/profile/' . $a->user['nickname'];

	foreach ($msg as $rr) {
		if ($rr['unknown']) {
			$participants = t("Unknown sender - %s", $rr['from-name']);
		} elseif (link_compare($rr['from-url'], $myprofile)) {
			$participants = t("You and %s", $rr['name']);
		} else {
			$participants = t("%s and You", $rr['from-name']);
		}

		$subject_e = (($rr['mailseen']) ? $rr['title'] : '<strong>' . $rr['title'] . '</strong>');
		$body_e = $rr['body'];
		$to_name_e = $rr['name'];

		$contact = Contact::getDetailsByURL($rr['url']);
		if (isset($contact["thumb"])) {
			$from_photo = $contact["thumb"];
		} else {
			$from_photo = (($rr['thumb']) ? $rr['thumb'] : $rr['from-photo']);
		}

		$rslt .= replace_macros($tpl, [
			'$id' => $rr['id'],
			'$from_name' => $participants,
			'$from_url' => (($rr['network'] === NETWORK_DFRN) ? 'redir/' . $rr['contact-id'] : $rr['url']),
			'$from_addr' => $contact['addr'],
			'$sparkle' => ' sparkle',
			'$from_photo' => proxy_url($from_photo, false, PROXY_SIZE_THUMB),
			'$subject' => $subject_e,
			'$delete' => t('Delete conversation'),
			'$body' => $body_e,
			'$to_name' => $to_name_e,
			'$date' => datetime_convert('UTC', date_default_timezone_get(), $rr['mailcreated'], t('D, d M Y - g:i A')),
			'$ago' => relative_date($rr['mailcreated']),
			'$seen' => $rr['mailseen'],
			'$count' => tt('%d message', '%d messages', $rr['count']),
		]);
	}

	return $rslt;
}
