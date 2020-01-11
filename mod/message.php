<?php
/**
 * @file mod/message.php
 */

use Friendica\App;
use Friendica\Content\Nav;
use Friendica\Content\Pager;
use Friendica\Content\Text\BBCode;
use Friendica\Core\ACL;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Database\DBA;
use Friendica\Model\Contact;
use Friendica\Model\Mail;
use Friendica\Module\Security\Login;
use Friendica\Registry\App as AppR;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Proxy as ProxyUtils;
use Friendica\Util\Strings;
use Friendica\Util\Temporal;

function message_init(App $a)
{
	$tabs = '';

	if ($a->argc > 1 && is_numeric($a->argv[1])) {
		$tabs = render_messages($a, get_messages(local_user(), 0, 5), 'mail_list.tpl');
	}

	$new = [
		'label' => L10n::t('New Message'),
		'url' => 'message/new',
		'sel' => $a->argc > 1 && $a->argv[1] == 'new',
		'accesskey' => 'm',
	];

	$tpl                      = Renderer::getMarkupTemplate('message_side.tpl');
	AppR::page()['aside'] = Renderer::replaceMacros($tpl, [
		'$tabs' => $tabs,
		'$new' => $new,
	]);
	$base                     = AppR::baseUrl();

	$head_tpl                    = Renderer::getMarkupTemplate('message-head.tpl');
	AppR::page()['htmlhead'] .= Renderer::replaceMacros($head_tpl, [
		'$baseurl' => AppR::baseUrl()->get(true),
		'$base' => $base
	]);
}

function message_post(App $a)
{
	if (!local_user()) {
		notice(L10n::t('Permission denied.') . EOL);
		return;
	}

	$replyto   = !empty($_REQUEST['replyto'])   ? Strings::escapeTags(trim($_REQUEST['replyto'])) : '';
	$subject   = !empty($_REQUEST['subject'])   ? Strings::escapeTags(trim($_REQUEST['subject'])) : '';
	$body      = !empty($_REQUEST['body'])      ? Strings::escapeHtml(trim($_REQUEST['body']))    : '';
	$recipient = !empty($_REQUEST['messageto']) ? intval($_REQUEST['messageto'])                  : 0;

	$ret = Mail::send($recipient, $body, $subject, $replyto);
	$norecip = false;

	switch ($ret) {
		case -1:
			notice(L10n::t('No recipient selected.') . EOL);
			$norecip = true;
			break;
		case -2:
			notice(L10n::t('Unable to locate contact information.') . EOL);
			break;
		case -3:
			notice(L10n::t('Message could not be sent.') . EOL);
			break;
		case -4:
			notice(L10n::t('Message collection failure.') . EOL);
			break;
		default:
			info(L10n::t('Message sent.') . EOL);
	}

	// fake it to go back to the input form if no recipient listed
	if ($norecip) {
		$a->argc = 2;
		$a->argv[1] = 'new';
	} else {
		AppR::baseUrl()->redirect(AppR::args()->getCommand() . '/' . $ret);
	}
}

function message_content(App $a)
{
	$o = '';
	Nav::setSelected('messages');

	if (!local_user()) {
		notice(L10n::t('Permission denied.') . EOL);
		return Login::form();
	}

	$myprofile = AppR::baseUrl() . '/profile/' . $a->user['nickname'];

	$tpl = Renderer::getMarkupTemplate('mail_head.tpl');
	if ($a->argc > 1 && $a->argv[1] == 'new') {
		$button = [
			'label' => L10n::t('Discard'),
			'url' => '/message',
			'sel' => 'close',
		];
	} else {
		$button = [
			'label' => L10n::t('New Message'),
			'url' => '/message/new',
			'sel' => 'new',
			'accesskey' => 'm',
		];
	}
	$header = Renderer::replaceMacros($tpl, [
		'$messages' => L10n::t('Messages'),
		'$button' => $button,
	]);

	if (($a->argc == 3) && ($a->argv[1] === 'drop' || $a->argv[1] === 'dropconv')) {
		if (!intval($a->argv[2])) {
			return;
		}

		// Check if we should do HTML-based delete confirmation
		if (!empty($_REQUEST['confirm'])) {
			// <form> can't take arguments in its "action" parameter
			// so add any arguments as hidden inputs
			$query = explode_querystring(AppR::args()->getQueryString());
			$inputs = [];
			foreach ($query['args'] as $arg) {
				if (strpos($arg, 'confirm=') === false) {
					$arg_parts = explode('=', $arg);
					$inputs[] = ['name' => $arg_parts[0], 'value' => $arg_parts[1]];
				}
			}

			//App::page()['aside'] = '';
			return Renderer::replaceMacros(Renderer::getMarkupTemplate('confirm.tpl'), [
				'$method' => 'get',
				'$message' => L10n::t('Do you really want to delete this message?'),
				'$extra_inputs' => $inputs,
				'$confirm' => L10n::t('Yes'),
				'$confirm_url' => $query['base'],
				'$confirm_name' => 'confirmed',
				'$cancel' => L10n::t('Cancel'),
			]);
		}

		// Now check how the user responded to the confirmation query
		if (!empty($_REQUEST['canceled'])) {
			AppR::baseUrl()->redirect('message');
		}

		$cmd = $a->argv[1];
		if ($cmd === 'drop') {
			$message = DBA::selectFirst('mail', ['convid'], ['id' => $a->argv[2], 'uid' => local_user()]);
			if(!DBA::isResult($message)){
				info(L10n::t('Conversation not found.') . EOL);
				AppR::baseUrl()->redirect('message');
			}

			if (DBA::delete('mail', ['id' => $a->argv[2], 'uid' => local_user()])) {
				info(L10n::t('Message deleted.') . EOL);
			}

			$conversation = DBA::selectFirst('mail', ['id'], ['convid' => $message['convid'], 'uid' => local_user()]);
			if(!DBA::isResult($conversation)){
				info(L10n::t('Conversation removed.') . EOL);
				AppR::baseUrl()->redirect('message');
			}

			AppR::baseUrl()->redirect('message/' . $conversation['id'] );
		} else {
			$r = q("SELECT `parent-uri`,`convid` FROM `mail` WHERE `id` = %d AND `uid` = %d LIMIT 1",
				intval($a->argv[2]),
				intval(local_user())
			);
			if (DBA::isResult($r)) {
				$parent = $r[0]['parent-uri'];

				if (DBA::delete('mail', ['parent-uri' => $parent, 'uid' => local_user()])) {
					info(L10n::t('Conversation removed.') . EOL);
				}
			}
			AppR::baseUrl()->redirect('message');
		}
	}

	if (($a->argc > 1) && ($a->argv[1] === 'new')) {
		$o .= $header;

		$tpl                         = Renderer::getMarkupTemplate('msg-header.tpl');
		AppR::page()['htmlhead'] .= Renderer::replaceMacros($tpl, [
			'$baseurl' => AppR::baseUrl()->get(true),
			'$nickname' => $a->user['nickname'],
			'$linkurl' => L10n::t('Please enter a link URL:')
		]);

		$preselect = isset($a->argv[2]) ? [$a->argv[2]] : [];

		$prename = $preurl = $preid = '';

		if ($preselect) {
			$r = q("SELECT `name`, `url`, `id` FROM `contact` WHERE `uid` = %d AND `id` = %d LIMIT 1",
				intval(local_user()),
				intval($a->argv[2])
			);
			if (!DBA::isResult($r)) {
				$r = q("SELECT `name`, `url`, `id` FROM `contact` WHERE `uid` = %d AND `nurl` = '%s' LIMIT 1",
					intval(local_user()),
					DBA::escape(Strings::normaliseLink(base64_decode($a->argv[2])))
				);
			}

			if (!DBA::isResult($r)) {
				$r = q("SELECT `name`, `url`, `id` FROM `contact` WHERE `uid` = %d AND `addr` = '%s' LIMIT 1",
					intval(local_user()),
					DBA::escape(base64_decode($a->argv[2]))
				);
			}

			if (DBA::isResult($r)) {
				$prename = $r[0]['name'];
				$preid = $r[0]['id'];
				$preselect = [$preid];
			} else {
				$preselect = [];
			}
		}

		$prefill = $preselect ? $prename : '';

		// the ugly select box
		$select = ACL::getMessageContactSelectHTML('messageto', 'message-to-select', $preselect, 4, 10);

		$tpl = Renderer::getMarkupTemplate('prv_message.tpl');
		$o .= Renderer::replaceMacros($tpl, [
			'$header'     => L10n::t('Send Private Message'),
			'$to'         => L10n::t('To:'),
			'$showinputs' => 'true',
			'$prefill'    => $prefill,
			'$preid'      => $preid,
			'$subject'    => L10n::t('Subject:'),
			'$subjtxt'    => $_REQUEST['subject'] ?? '',
			'$text'       => $_REQUEST['body'] ?? '',
			'$readonly'   => '',
			'$yourmessage'=> L10n::t('Your message:'),
			'$select'     => $select,
			'$parent'     => '',
			'$upload'     => L10n::t('Upload photo'),
			'$insert'     => L10n::t('Insert web link'),
			'$wait'       => L10n::t('Please wait'),
			'$submit'     => L10n::t('Submit')
		]);
		return $o;
	}


	$_SESSION['return_path'] = AppR::args()->getQueryString();

	if ($a->argc == 1) {

		// List messages

		$o .= $header;

		$total = 0;
		$r = q("SELECT count(*) AS `total`, ANY_VALUE(`created`) AS `created` FROM `mail`
			WHERE `mail`.`uid` = %d GROUP BY `parent-uri` ORDER BY `created` DESC",
			intval(local_user())
		);
		if (DBA::isResult($r)) {
			$total = $r[0]['total'];
		}

		$pager = new Pager(AppR::args()->getQueryString());

		$r = get_messages(local_user(), $pager->getStart(), $pager->getItemsPerPage());

		if (!DBA::isResult($r)) {
			info(L10n::t('No messages.') . EOL);
			return $o;
		}

		$o .= render_messages($a, $r, 'mail_list.tpl');

		$o .= $pager->renderFull($total);

		return $o;
	}

	if (($a->argc > 1) && (intval($a->argv[1]))) {

		$o .= $header;

		$message = DBA::fetchFirst("
			SELECT `mail`.*, `contact`.`name`, `contact`.`url`, `contact`.`thumb`
			FROM `mail`
			LEFT JOIN `contact` ON `mail`.`contact-id` = `contact`.`id`
			WHERE `mail`.`uid` = ? AND `mail`.`id` = ?
			LIMIT 1",
			local_user(),
			$a->argv[1]
		);
		if (DBA::isResult($message)) {
			$contact_id = $message['contact-id'];

			$params = [
				local_user(),
				$message['parent-uri']
			];

			if ($message['convid']) {
				$sql_extra = "AND (`mail`.`parent-uri` = ? OR `mail`.`convid` = ?)";
				$params[] = $message['convid'];
			} else {
				$sql_extra = "AND `mail`.`parent-uri` = ?";
			}
			$messages_stmt = DBA::p("
				SELECT `mail`.*, `contact`.`name`, `contact`.`url`, `contact`.`thumb`
				FROM `mail`
				LEFT JOIN `contact` ON `mail`.`contact-id` = `contact`.`id`
				WHERE `mail`.`uid` = ?
				$sql_extra
				ORDER BY `mail`.`created` ASC",
				...$params
			);

			$messages = DBA::toArray($messages_stmt);

			DBA::update('mail', ['seen' => 1], ['parent-uri' => $message['parent-uri'], 'uid' => local_user()]);

			if ($message['convid']) {
				// Clear Diaspora private message notifications
				DBA::update('notify', ['seen' => 1], ['type' => NOTIFY_MAIL, 'parent' => $message['convid'], 'uid' => local_user()]);
			}
			// Clear DFRN private message notifications
			DBA::update('notify', ['seen' => 1], ['type' => NOTIFY_MAIL, 'parent' => $message['parent-uri'], 'uid' => local_user()]);
		} else {
			$messages = false;
		}

		if (!DBA::isResult($messages)) {
			notice(L10n::t('Message not available.') . EOL);
			return $o;
		}

		$tpl                         = Renderer::getMarkupTemplate('msg-header.tpl');
		AppR::page()['htmlhead'] .= Renderer::replaceMacros($tpl, [
			'$baseurl' => AppR::baseUrl()->get(true),
			'$nickname' => $a->user['nickname'],
			'$linkurl' => L10n::t('Please enter a link URL:')
		]);

		$mails = [];
		$seen = 0;
		$unknown = false;

		foreach ($messages as $message) {
			if ($message['unknown']) {
				$unknown = true;
			}

			if ($message['from-url'] == $myprofile) {
				$from_url = $myprofile;
				$sparkle = '';
			} else {
				$from_url = Contact::magicLink($message['from-url']);
				$sparkle = ' sparkle';
			}

			$extracted = item_extract_images($message['body']);
			if ($extracted['images']) {
				$message['body'] = item_redir_and_replace_images($extracted['body'], $extracted['images'], $message['contact-id']);
			}

			$from_name_e = $message['from-name'];
			$subject_e = $message['title'];
			$body_e = BBCode::convert($message['body']);
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
				'from_photo' => ProxyUtils::proxifyUrl($from_photo, false, ProxyUtils::SIZE_THUMB),
				'subject' => $subject_e,
				'body' => $body_e,
				'delete' => L10n::t('Delete message'),
				'to_name' => $to_name_e,
				'date' => DateTimeFormat::local($message['created'], L10n::t('D, d M Y - g:i A')),
				'ago' => Temporal::getRelativeDate($message['created']),
			];

			$seen = $message['seen'];
		}

		$select = $message['name'] . '<input type="hidden" name="messageto" value="' . $contact_id . '" />';
		$parent = '<input type="hidden" name="replyto" value="' . $message['parent-uri'] . '" />';

		$tpl = Renderer::getMarkupTemplate('mail_display.tpl');
		$o = Renderer::replaceMacros($tpl, [
			'$thread_id' => $a->argv[1],
			'$thread_subject' => $message['title'],
			'$thread_seen' => $seen,
			'$delete' => L10n::t('Delete conversation'),
			'$canreply' => (($unknown) ? false : '1'),
			'$unknown_text' => L10n::t("No secure communications available. You <strong>may</strong> be able to respond from the sender's profile page."),
			'$mails' => $mails,

			// reply
			'$header' => L10n::t('Send Reply'),
			'$to' => L10n::t('To:'),
			'$showinputs' => '',
			'$subject' => L10n::t('Subject:'),
			'$subjtxt' => $message['title'],
			'$readonly' => ' readonly="readonly" style="background: #BBBBBB;" ',
			'$yourmessage' => L10n::t('Your message:'),
			'$text' => '',
			'$select' => $select,
			'$parent' => $parent,
			'$upload' => L10n::t('Upload photo'),
			'$insert' => L10n::t('Insert web link'),
			'$submit' => L10n::t('Submit'),
			'$wait' => L10n::t('Please wait')
		]);

		return $o;
	}
}

/**
 * @param int $uid
 * @param int $start
 * @param int $limit
 * @return array
 */
function get_messages($uid, $start, $limit)
{
	return DBA::toArray(DBA::p('SELECT
			m.`id`,
			m.`uid`,
			m.`guid`,
			m.`from-name`,
			m.`from-photo`,
			m.`from-url`,
			m.`contact-id`,
			m.`convid`,
			m.`title`,
			m.`body`,
			m.`seen`,
			m.`reply`,
			m.`replied`,
			m.`unknown`,
			m.`uri`,
			m.`parent-uri`,
			m.`created`,
			c.`name`,
			c.`url`,
			c.`thumb`,
			c.`network`,
       		m2.`count`,
       		m2.`mailcreated`,
       		m2.`mailseen`
       	FROM `mail` m
       	JOIN (
       		SELECT
       			`parent-uri`,
       		    MIN(`id`)      AS `id`,
       		    COUNT(*)       AS `count`,
       		    MAX(`created`) AS `mailcreated`,
       		    MIN(`seen`)    AS `mailseen`
       		FROM `mail`
       		WHERE `uid` = ?
       		GROUP BY `parent-uri`
       	) m2 ON m.`parent-uri` = m2.`parent-uri` AND m.`id` = m2.`id`
		LEFT JOIN `contact` c ON m.`contact-id` = c.`id`
		WHERE m.`uid` = ?
		ORDER BY m2.`mailcreated` DESC
		LIMIT ?, ?'
		, $uid, $uid, $start, $limit));
}

function render_messages(App $a, array $msg, $t)
{
	$tpl = Renderer::getMarkupTemplate($t);
	$rslt = '';

	$myprofile = AppR::baseUrl() . '/profile/' . $a->user['nickname'];

	foreach ($msg as $rr) {
		if ($rr['unknown']) {
			$participants = L10n::t("Unknown sender - %s", $rr['from-name']);
		} elseif (Strings::compareLink($rr['from-url'], $myprofile)) {
			$participants = L10n::t("You and %s", $rr['name']);
		} else {
			$participants = L10n::t("%s and You", $rr['from-name']);
		}

		$body_e = $rr['body'];
		$to_name_e = $rr['name'];

		$contact = Contact::getDetailsByURL($rr['url']);
		if (isset($contact["thumb"])) {
			$from_photo = $contact["thumb"];
		} else {
			$from_photo = (($rr['thumb']) ? $rr['thumb'] : $rr['from-photo']);
		}

		$rslt .= Renderer::replaceMacros($tpl, [
			'$id' => $rr['id'],
			'$from_name' => $participants,
			'$from_url' => Contact::magicLink($rr['url']),
			'$from_addr' => $contact['addr'] ?? '',
			'$sparkle' => ' sparkle',
			'$from_photo' => ProxyUtils::proxifyUrl($from_photo, false, ProxyUtils::SIZE_THUMB),
			'$subject' => $rr['title'],
			'$delete' => L10n::t('Delete conversation'),
			'$body' => $body_e,
			'$to_name' => $to_name_e,
			'$date' => DateTimeFormat::local($rr['mailcreated'], L10n::t('D, d M Y - g:i A')),
			'$ago' => Temporal::getRelativeDate($rr['mailcreated']),
			'$seen' => $rr['mailseen'],
			'$count' => L10n::tt('%d message', '%d messages', $rr['count']),
		]);
	}

	return $rslt;
}
