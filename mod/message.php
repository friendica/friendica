<?php
/**
 * @copyright Copyright (C) 2020, Friendica
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

use Friendica\App;
use Friendica\Content\Nav;
use Friendica\Content\Pager;
use Friendica\Content\Text\BBCode;
use Friendica\Core\ACL;
use Friendica\Core\Renderer;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Mail;
use Friendica\Domain\Model\Notify\Type;
use Friendica\Module\Security\Login;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Strings;
use Friendica\Util\Temporal;

function message_init(App $a)
{
	$tabs = '';

	if ($a->argc > 1 && is_numeric($a->argv[1])) {
		$tabs = render_messages(get_messages(local_user(), 0, 5), 'mail_list.tpl');
	}

	$new = [
		'label' => DI::l10n()->t('New Message'),
		'url' => 'message/new',
		'sel' => $a->argc > 1 && $a->argv[1] == 'new',
		'accesskey' => 'm',
	];

	$tpl = Renderer::getMarkupTemplate('message_side.tpl');
	DI::page()['aside'] = Renderer::replaceMacros($tpl, [
		'$tabs' => $tabs,
		'$new' => $new,
	]);
	$base = DI::baseUrl();

	$head_tpl = Renderer::getMarkupTemplate('message-head.tpl');
	DI::page()['htmlhead'] .= Renderer::replaceMacros($head_tpl, [
		'$baseurl' => DI::baseUrl()->get(true),
		'$base' => $base
	]);
}

function message_post(App $a)
{
	if (!local_user()) {
		notice(DI::l10n()->t('Permission denied.'));
		return;
	}

	$replyto   = !empty($_REQUEST['replyto'])   ? Strings::escapeTags(trim($_REQUEST['replyto'])) : '';
	$subject   = !empty($_REQUEST['subject'])   ? Strings::escapeTags(trim($_REQUEST['subject'])) : '';
	$body      = !empty($_REQUEST['body'])      ? Strings::escapeHtml(trim($_REQUEST['body']))    : '';
	$recipient = !empty($_REQUEST['recipient']) ? intval($_REQUEST['recipient'])                  : 0;

	$ret = Mail::send($recipient, $body, $subject, $replyto);
	$norecip = false;

	switch ($ret) {
		case -1:
			notice(DI::l10n()->t('No recipient selected.'));
			$norecip = true;
			break;
		case -2:
			notice(DI::l10n()->t('Unable to locate contact information.'));
			break;
		case -3:
			notice(DI::l10n()->t('Message could not be sent.'));
			break;
		case -4:
			notice(DI::l10n()->t('Message collection failure.'));
			break;
	}

	// fake it to go back to the input form if no recipient listed
	if ($norecip) {
		$a->argc = 2;
		$a->argv[1] = 'new';
	} else {
		DI::baseUrl()->redirect(DI::args()->getCommand() . '/' . $ret);
	}
}

function message_content(App $a)
{
	$o = '';
	Nav::setSelected('messages');

	if (!local_user()) {
		notice(DI::l10n()->t('Permission denied.'));
		return Login::form();
	}

	$myprofile = DI::baseUrl() . '/profile/' . $a->user['nickname'];

	$tpl = Renderer::getMarkupTemplate('mail_head.tpl');
	if ($a->argc > 1 && $a->argv[1] == 'new') {
		$button = [
			'label' => DI::l10n()->t('Discard'),
			'url' => '/message',
			'sel' => 'close',
		];
	} else {
		$button = [
			'label' => DI::l10n()->t('New Message'),
			'url' => '/message/new',
			'sel' => 'new',
			'accesskey' => 'm',
		];
	}
	$header = Renderer::replaceMacros($tpl, [
		'$messages' => DI::l10n()->t('Messages'),
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
			$query = explode_querystring(DI::args()->getQueryString());
			$inputs = [];
			foreach ($query['args'] as $arg) {
				if (strpos($arg, 'confirm=') === false) {
					$arg_parts = explode('=', $arg);
					$inputs[] = ['name' => $arg_parts[0], 'value' => $arg_parts[1]];
				}
			}

			//DI::page()['aside'] = '';
			return Renderer::replaceMacros(Renderer::getMarkupTemplate('confirm.tpl'), [
				'$method' => 'get',
				'$message' => DI::l10n()->t('Do you really want to delete this message?'),
				'$extra_inputs' => $inputs,
				'$confirm' => DI::l10n()->t('Yes'),
				'$confirm_url' => $query['base'],
				'$confirm_name' => 'confirmed',
				'$cancel' => DI::l10n()->t('Cancel'),
			]);
		}

		// Now check how the user responded to the confirmation query
		if (!empty($_REQUEST['canceled'])) {
			DI::baseUrl()->redirect('message');
		}

		$cmd = $a->argv[1];
		if ($cmd === 'drop') {
			$message = DBA::selectFirst('mail', ['convid'], ['id' => $a->argv[2], 'uid' => local_user()]);
			if(!DBA::isResult($message)){
				notice(DI::l10n()->t('Conversation not found.'));
				DI::baseUrl()->redirect('message');
			}

			if (!DBA::delete('mail', ['id' => $a->argv[2], 'uid' => local_user()])) {
				notice(DI::l10n()->t('Message was not deleted.'));
			}

			$conversation = DBA::selectFirst('mail', ['id'], ['convid' => $message['convid'], 'uid' => local_user()]);
			if(!DBA::isResult($conversation)){
				DI::baseUrl()->redirect('message');
			}

			DI::baseUrl()->redirect('message/' . $conversation['id'] );
		} else {
			$r = q("SELECT `parent-uri`,`convid` FROM `mail` WHERE `id` = %d AND `uid` = %d LIMIT 1",
				intval($a->argv[2]),
				intval(local_user())
			);
			if (DBA::isResult($r)) {
				$parent = $r[0]['parent-uri'];

				if (!DBA::delete('mail', ['parent-uri' => $parent, 'uid' => local_user()])) {
					notice(DI::l10n()->t('Conversation was not removed.'));
				}
			}
			DI::baseUrl()->redirect('message');
		}
	}

	if (($a->argc > 1) && ($a->argv[1] === 'new')) {
		$o .= $header;

		$tpl = Renderer::getMarkupTemplate('msg-header.tpl');
		DI::page()['htmlhead'] .= Renderer::replaceMacros($tpl, [
			'$baseurl' => DI::baseUrl()->get(true),
			'$nickname' => $a->user['nickname'],
			'$linkurl' => DI::l10n()->t('Please enter a link URL:')
		]);

		$recipientId = $a->argv[2] ?? null;

		$select = ACL::getMessageContactSelectHTML($recipientId);

		$tpl = Renderer::getMarkupTemplate('prv_message.tpl');
		$o .= Renderer::replaceMacros($tpl, [
			'$header'     => DI::l10n()->t('Send Private Message'),
			'$to'         => DI::l10n()->t('To:'),
			'$subject'    => DI::l10n()->t('Subject:'),
			'$subjtxt'    => $_REQUEST['subject'] ?? '',
			'$text'       => $_REQUEST['body'] ?? '',
			'$readonly'   => '',
			'$yourmessage'=> DI::l10n()->t('Your message:'),
			'$select'     => $select,
			'$parent'     => '',
			'$upload'     => DI::l10n()->t('Upload photo'),
			'$insert'     => DI::l10n()->t('Insert web link'),
			'$wait'       => DI::l10n()->t('Please wait'),
			'$submit'     => DI::l10n()->t('Submit')
		]);
		return $o;
	}


	$_SESSION['return_path'] = DI::args()->getQueryString();

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

		$pager = new Pager(DI::l10n(), DI::args()->getQueryString());

		$r = get_messages(local_user(), $pager->getStart(), $pager->getItemsPerPage());

		if (!DBA::isResult($r)) {
			notice(DI::l10n()->t('No messages.'));
			return $o;
		}

		$o .= render_messages($r, 'mail_list.tpl');

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
			DBA::update('notify', ['seen' => 1], ['type' => Type::MAIL, 'parent' => $message['id'], 'uid' => local_user()]);
		} else {
			$messages = false;
		}

		if (!DBA::isResult($messages)) {
			notice(DI::l10n()->t('Message not available.'));
			return $o;
		}

		$tpl = Renderer::getMarkupTemplate('msg-header.tpl');
		DI::page()['htmlhead'] .= Renderer::replaceMacros($tpl, [
			'$baseurl' => DI::baseUrl()->get(true),
			'$nickname' => $a->user['nickname'],
			'$linkurl' => DI::l10n()->t('Please enter a link URL:')
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

			$contact = Contact::getByURL($message['from-url'], false, ['thumb', 'addr', 'id', 'avatar']);
			$from_photo = Contact::getThumb($contact, $message['from-photo']);

			$mails[] = [
				'id' => $message['id'],
				'from_name' => $from_name_e,
				'from_url' => $from_url,
				'from_addr' => $contact['addr'],
				'sparkle' => $sparkle,
				'from_photo' => $from_photo,
				'subject' => $subject_e,
				'body' => $body_e,
				'delete' => DI::l10n()->t('Delete message'),
				'to_name' => $to_name_e,
				'date' => DateTimeFormat::local($message['created'], DI::l10n()->t('D, d M Y - g:i A')),
				'ago' => Temporal::getRelativeDate($message['created']),
			];

			$seen = $message['seen'];
		}

		$select = $message['name'] . '<input type="hidden" name="recipient" value="' . $contact_id . '" />';
		$parent = '<input type="hidden" name="replyto" value="' . $message['parent-uri'] . '" />';

		$tpl = Renderer::getMarkupTemplate('mail_display.tpl');
		$o = Renderer::replaceMacros($tpl, [
			'$thread_id' => $a->argv[1],
			'$thread_subject' => $message['title'],
			'$thread_seen' => $seen,
			'$delete' => DI::l10n()->t('Delete conversation'),
			'$canreply' => (($unknown) ? false : '1'),
			'$unknown_text' => DI::l10n()->t("No secure communications available. You <strong>may</strong> be able to respond from the sender's profile page."),
			'$mails' => $mails,

			// reply
			'$header' => DI::l10n()->t('Send Reply'),
			'$to' => DI::l10n()->t('To:'),
			'$subject' => DI::l10n()->t('Subject:'),
			'$subjtxt' => $message['title'],
			'$readonly' => ' readonly="readonly" style="background: #BBBBBB;" ',
			'$yourmessage' => DI::l10n()->t('Your message:'),
			'$text' => '',
			'$select' => $select,
			'$parent' => $parent,
			'$upload' => DI::l10n()->t('Upload photo'),
			'$insert' => DI::l10n()->t('Insert web link'),
			'$submit' => DI::l10n()->t('Submit'),
			'$wait' => DI::l10n()->t('Please wait')
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

function render_messages(array $msg, $t)
{
	$a = DI::app();

	$tpl = Renderer::getMarkupTemplate($t);
	$rslt = '';

	$myprofile = DI::baseUrl() . '/profile/' . $a->user['nickname'];

	foreach ($msg as $rr) {
		if ($rr['unknown']) {
			$participants = DI::l10n()->t("Unknown sender - %s", $rr['from-name']);
		} elseif (Strings::compareLink($rr['from-url'], $myprofile)) {
			$participants = DI::l10n()->t("You and %s", $rr['name']);
		} else {
			$participants = DI::l10n()->t("%s and You", $rr['from-name']);
		}

		$body_e = $rr['body'];
		$to_name_e = $rr['name'];

		$contact = Contact::getByURL($rr['url'], false, ['thumb', 'addr', 'id', 'avatar']);
		$from_photo = Contact::getThumb($contact, $rr['thumb'] ?: $rr['from-photo']);

		$rslt .= Renderer::replaceMacros($tpl, [
			'$id' => $rr['id'],
			'$from_name' => $participants,
			'$from_url' => Contact::magicLink($rr['url']),
			'$from_addr' => $contact['addr'] ?? '',
			'$sparkle' => ' sparkle',
			'$from_photo' => $from_photo,
			'$subject' => $rr['title'],
			'$delete' => DI::l10n()->t('Delete conversation'),
			'$body' => $body_e,
			'$to_name' => $to_name_e,
			'$date' => DateTimeFormat::local($rr['mailcreated'], DI::l10n()->t('D, d M Y - g:i A')),
			'$ago' => Temporal::getRelativeDate($rr['mailcreated']),
			'$seen' => $rr['mailseen'],
			'$count' => DI::l10n()->tt('%d message', '%d messages', $rr['count']),
		]);
	}

	return $rslt;
}
