<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
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
use Friendica\Module\Security\Login;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Strings;
use Friendica\Util\Temporal;

function message_init(App $a)
{
	$tabs = '';

	if (DI::args()->getArgc() > 1 && is_numeric(DI::args()->getArgv()[1])) {
		$tabs = render_messages(get_messages(DI::userSession()->getLocalUserId(), 0, 5), 'mail_list.tpl');
	}

	$new = [
		'label'     => DI::l10n()->t('New Message'),
		'url'       => 'message/new',
		'sel'       => DI::args()->getArgc() > 1 && DI::args()->getArgv()[1] == 'new',
		'accesskey' => 'm',
	];

	$tpl = Renderer::getMarkupTemplate('message_side.tpl');
	DI::page()['aside'] = Renderer::replaceMacros($tpl, [
		'$tabs' => $tabs,
		'$new'  => $new,
	]);
	$base = DI::baseUrl();

	$head_tpl = Renderer::getMarkupTemplate('message-head.tpl');
	DI::page()['htmlhead'] .= Renderer::replaceMacros($head_tpl, [
		'$base'    => $base
	]);
}

function message_post(App $a)
{
	if (!DI::userSession()->getLocalUserId()) {
		DI::sysmsg()->addNotice(DI::l10n()->t('Permission denied.'));
		return;
	}

	$replyto   = !empty($_REQUEST['replyto'])   ? trim($_REQUEST['replyto'])                   : '';
	$subject   = !empty($_REQUEST['subject'])   ? trim($_REQUEST['subject'])                   : '';
	$body      = !empty($_REQUEST['body'])      ? Strings::escapeHtml(trim($_REQUEST['body'])) : '';
	$recipient = !empty($_REQUEST['recipient']) ? intval($_REQUEST['recipient'])               : 0;

	$ret = Mail::send($recipient, $body, $subject, $replyto);
	$norecip = false;

	switch ($ret) {
		case -1:
			DI::sysmsg()->addNotice(DI::l10n()->t('No recipient selected.'));
			$norecip = true;
			break;

		case -2:
			DI::sysmsg()->addNotice(DI::l10n()->t('Unable to locate contact information.'));
			break;

		case -3:
			DI::sysmsg()->addNotice(DI::l10n()->t('Message could not be sent.'));
			break;

		case -4:
			DI::sysmsg()->addNotice(DI::l10n()->t('Message collection failure.'));
			break;
	}

	// fake it to go back to the input form if no recipient listed
	if ($norecip) {
		DI::args()->setArgv(['message', 'new']);
	} else {
		DI::baseUrl()->redirect(DI::args()->getCommand() . '/' . $ret);
	}
}

function message_content(App $a)
{
	$o = '';
	Nav::setSelected('messages');

	if (!DI::userSession()->getLocalUserId()) {
		DI::sysmsg()->addNotice(DI::l10n()->t('Permission denied.'));
		return Login::form();
	}

	$myprofile = DI::baseUrl() . '/profile/' . $a->getLoggedInUserNickname();

	$tpl = Renderer::getMarkupTemplate('mail_head.tpl');
	if (DI::args()->getArgc() > 1 && DI::args()->getArgv()[1] == 'new') {
		$button = [
			'label' => DI::l10n()->t('Discard'),
			'url'   => '/message',
			'sel'   => 'close',
		];
	} else {
		$button = [
			'label'     => DI::l10n()->t('New Message'),
			'url'       => '/message/new',
			'sel'       => 'new',
			'accesskey' => 'm',
		];
	}
	$header = Renderer::replaceMacros($tpl, [
		'$messages' => DI::l10n()->t('Messages'),
		'$button'   => $button,
	]);

	if ((DI::args()->getArgc() == 3) && (DI::args()->getArgv()[1] === 'drop' || DI::args()->getArgv()[1] === 'dropconv')) {
		if (!intval(DI::args()->getArgv()[2])) {
			return;
		}

		$cmd = DI::args()->getArgv()[1];
		if ($cmd === 'drop') {
			$message = DBA::selectFirst('mail', ['convid'], ['id' => DI::args()->getArgv()[2], 'uid' => DI::userSession()->getLocalUserId()]);
			if(!DBA::isResult($message)){
				DI::sysmsg()->addNotice(DI::l10n()->t('Conversation not found.'));
				DI::baseUrl()->redirect('message');
			}

			if (!DBA::delete('mail', ['id' => DI::args()->getArgv()[2], 'uid' => DI::userSession()->getLocalUserId()])) {
				DI::sysmsg()->addNotice(DI::l10n()->t('Message was not deleted.'));
			}

			$conversation = DBA::selectFirst('mail', ['id'], ['convid' => $message['convid'], 'uid' => DI::userSession()->getLocalUserId()]);
			if(!DBA::isResult($conversation)){
				DI::baseUrl()->redirect('message');
			}

			DI::baseUrl()->redirect('message/' . $conversation['id'] );
		} else {
			$parentmail = DBA::selectFirst('mail', ['parent-uri'], ['id' => DI::args()->getArgv()[2], 'uid' => DI::userSession()->getLocalUserId()]);
			if (DBA::isResult($parentmail)) {
				$parent = $parentmail['parent-uri'];

				if (!DBA::delete('mail', ['parent-uri' => $parent, 'uid' => DI::userSession()->getLocalUserId()])) {
					DI::sysmsg()->addNotice(DI::l10n()->t('Conversation was not removed.'));
				}
			}
			DI::baseUrl()->redirect('message');
		}
	}

	if ((DI::args()->getArgc() > 1) && (DI::args()->getArgv()[1] === 'new')) {
		$o .= $header;

		$tpl = Renderer::getMarkupTemplate('msg-header.tpl');
		DI::page()['htmlhead'] .= Renderer::replaceMacros($tpl, [
			'$nickname' => $a->getLoggedInUserNickname(),
			'$linkurl' => DI::l10n()->t('Please enter a link URL:')
		]);

		$recipientId = DI::args()->getArgv()[2] ?? null;

		$select = ACL::getMessageContactSelectHTML($recipientId);

		$tpl = Renderer::getMarkupTemplate('prv_message.tpl');
		$o .= Renderer::replaceMacros($tpl, [
			'$header'      => DI::l10n()->t('Send Private Message'),
			'$to'          => DI::l10n()->t('To:'),
			'$subject'     => DI::l10n()->t('Subject:'),
			'$subjtxt'     => $_REQUEST['subject'] ?? '',
			'$text'        => $_REQUEST['body'] ?? '',
			'$readonly'    => '',
			'$yourmessage' => DI::l10n()->t('Your message:'),
			'$select'      => $select,
			'$parent'      => '',
			'$upload'      => DI::l10n()->t('Upload photo'),
			'$insert'      => DI::l10n()->t('Insert web link'),
			'$wait'        => DI::l10n()->t('Please wait'),
			'$submit'      => DI::l10n()->t('Submit')
		]);
		return $o;
	}


	$_SESSION['return_path'] = DI::args()->getQueryString();

	if (DI::args()->getArgc() == 1) {

		// List messages

		$o .= $header;

		$total = DBA::count('mail', ['uid' => DI::userSession()->getLocalUserId()], ['distinct' => true, 'expression' => 'parent-uri']);

		$pager = new Pager(DI::l10n(), DI::args()->getQueryString());

		$r = get_messages(DI::userSession()->getLocalUserId(), $pager->getStart(), $pager->getItemsPerPage());

		if (!DBA::isResult($r)) {
			DI::sysmsg()->addNotice(DI::l10n()->t('No messages.'));
			return $o;
		}

		$o .= render_messages($r, 'mail_list.tpl');

		$o .= $pager->renderFull($total);

		return $o;
	}

	if ((DI::args()->getArgc() > 1) && (intval(DI::args()->getArgv()[1]))) {

		$o .= $header;

		$message = DBA::fetchFirst("
			SELECT `mail`.*, `contact`.`name`, `contact`.`url`, `contact`.`thumb`
			FROM `mail`
			LEFT JOIN `contact` ON `mail`.`contact-id` = `contact`.`id`
			WHERE `mail`.`uid` = ? AND `mail`.`id` = ?
			LIMIT 1",
			DI::userSession()->getLocalUserId(),
			DI::args()->getArgv()[1]
		);
		if (DBA::isResult($message)) {
			$contact_id = $message['contact-id'];

			$params = [
				DI::userSession()->getLocalUserId(),
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

			DBA::update('mail', ['seen' => 1], ['parent-uri' => $message['parent-uri'], 'uid' => DI::userSession()->getLocalUserId()]);
		} else {
			$messages = false;
		}

		if (!DBA::isResult($messages)) {
			DI::sysmsg()->addNotice(DI::l10n()->t('Message not available.'));
			return $o;
		}

		$tpl = Renderer::getMarkupTemplate('msg-header.tpl');
		DI::page()['htmlhead'] .= Renderer::replaceMacros($tpl, [
			'$nickname' => $a->getLoggedInUserNickname(),
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

			$from_name_e = $message['from-name'];
			$subject_e = $message['title'];
			$body_e = BBCode::convertForUriId($message['uri-id'], $message['body']);
			$to_name_e = $message['name'];

			$contact = Contact::getByURL($message['from-url'], false, ['thumb', 'addr', 'id', 'avatar', 'url']);
			$from_photo = Contact::getThumb($contact);

			$mails[] = [
				'id'         => $message['id'],
				'from_name'  => $from_name_e,
				'from_url'   => $from_url,
				'from_addr'  => $contact['addr'] ?? $from_url,
				'sparkle'    => $sparkle,
				'from_photo' => $from_photo,
				'subject'    => $subject_e,
				'body'       => $body_e,
				'delete'     => DI::l10n()->t('Delete message'),
				'to_name'    => $to_name_e,
				'date'       => DateTimeFormat::local($message['created'], DI::l10n()->t('D, d M Y - g:i A')),
				'ago'        => Temporal::getRelativeDate($message['created']),
			];

			$seen = $message['seen'];
		}

		$select = $message['name'] . '<input type="hidden" name="recipient" value="' . $contact_id . '" />';
		$parent = '<input type="hidden" name="replyto" value="' . $message['parent-uri'] . '" />';

		$tpl = Renderer::getMarkupTemplate('mail_display.tpl');
		$o = Renderer::replaceMacros($tpl, [
			'$thread_id'      => DI::args()->getArgv()[1],
			'$thread_subject' => $message['title'],
			'$thread_seen'    => $seen,
			'$delete'         => DI::l10n()->t('Delete conversation'),
			'$canreply'       => (($unknown) ? false : '1'),
			'$unknown_text'   => DI::l10n()->t("No secure communications available. You <strong>may</strong> be able to respond from the sender's profile page."),
			'$mails'          => $mails,
			// reply
			'$header'         => DI::l10n()->t('Send Reply'),
			'$to'             => DI::l10n()->t('To:'),
			'$subject'        => DI::l10n()->t('Subject:'),
			'$subjtxt'        => $message['title'],
			'$readonly'       => ' readonly="readonly" style="background: #BBBBBB;" ',
			'$yourmessage'    => DI::l10n()->t('Your message:'),
			'$text'           => '',
			'$select'         => $select,
			'$parent'         => $parent,
			'$upload'         => DI::l10n()->t('Upload photo'),
			'$insert'         => DI::l10n()->t('Insert web link'),
			'$submit'         => DI::l10n()->t('Submit'),
			'$wait'           => DI::l10n()->t('Please wait')
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
function get_messages(int $uid, int $start, int $limit): array
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

function render_messages(array $msg, string $t): string
{
	$a = DI::app();

	$tpl = Renderer::getMarkupTemplate($t);
	$rslt = '';

	$myprofile = DI::baseUrl() . '/profile/' . $a->getLoggedInUserNickname();

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

		if (is_null($rr['url'])) {
			// contact-id is pointing to a non existing contact
			continue;
		}

		$contact = Contact::getByURL($rr['url'], false, ['thumb', 'addr', 'id', 'avatar', 'url']);
		$from_photo = Contact::getThumb($contact);

		$rslt .= Renderer::replaceMacros($tpl, [
			'$id'         => $rr['id'],
			'$from_name'  => $participants,
			'$from_url'   => Contact::magicLink($rr['url']),
			'$from_addr'  => $contact['addr'] ?? '',
			'$sparkle'    => ' sparkle',
			'$from_photo' => $from_photo,
			'$subject'    => $rr['title'],
			'$delete'     => DI::l10n()->t('Delete conversation'),
			'$body'       => $body_e,
			'$to_name'    => $to_name_e,
			'$date'       => DateTimeFormat::local($rr['mailcreated'], DI::l10n()->t('D, d M Y - g:i A')),
			'$ago'        => Temporal::getRelativeDate($rr['mailcreated']),
			'$seen'       => $rr['mailseen'],
			'$count'      => DI::l10n()->tt('%d message', '%d messages', $rr['count']),
		]);
	}

	return $rslt;
}
