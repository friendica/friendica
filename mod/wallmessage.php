<?php
/**
 * @file mod/wallmessage.php
 */
use Friendica\App;
use Friendica\Core\L10n;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\Model\Mail;
use Friendica\Model\Profile;

function wallmessage_post(App $a) {

	$replyto = Profile::getMyURL();
	if (!$replyto) {
		notice(L10n::t('Permission denied.') . EOL);
		return;
	}

	$subject   = ((x($_REQUEST,'subject'))   ? notags(trim($_REQUEST['subject']))   : '');
	$body      = ((x($_REQUEST,'body'))      ? escape_tags(trim($_REQUEST['body'])) : '');

	$recipient = (($a->argc > 1) ? notags($a->argv[1]) : '');
	if ((! $recipient) || (! $body)) {
		return;
	}

	$r = q("select * from user where nickname = '%s' limit 1",
		DBA::escape($recipient)
	);

	if (! DBA::isResult($r)) {
		logger('wallmessage: no recipient');
		return;
	}

	$user = $r[0];

	if (! intval($user['unkmail'])) {
		notice(L10n::t('Permission denied.') . EOL);
		return;
	}

	$r = q("select count(*) as total from mail where uid = %d and created > UTC_TIMESTAMP() - INTERVAL 1 day and unknown = 1",
			intval($user['uid'])
	);

	if ($r[0]['total'] > $user['cntunkmail']) {
		notice(L10n::t('Number of daily wall messages for %s exceeded. Message failed.', $user['username']));
		return;
	}

	$ret = Mail::sendWall($user, $body, $subject, $replyto);

	switch ($ret) {
		case -1:
			notice(L10n::t('No recipient selected.') . EOL);
			break;
		case -2:
			notice(L10n::t('Unable to check your home location.') . EOL);
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

	$a->internalRedirect('profile/'.$user['nickname']);
}


function wallmessage_content(App $a) {

	if (!Profile::getMyURL()) {
		notice(L10n::t('Permission denied.') . EOL);
		return;
	}

	$recipient = (($a->argc > 1) ? $a->argv[1] : '');

	if (!$recipient) {
		notice(L10n::t('No recipient.') . EOL);
		return;
	}

	$r = q("select * from user where nickname = '%s' limit 1",
		DBA::escape($recipient)
	);

	if (! DBA::isResult($r)) {
		notice(L10n::t('No recipient.') . EOL);
		logger('wallmessage: no recipient');
		return;
	}

	$user = $r[0];

	if (!intval($user['unkmail'])) {
		notice(L10n::t('Permission denied.') . EOL);
		return;
	}

	$r = q("select count(*) as total from mail where uid = %d and created > UTC_TIMESTAMP() - INTERVAL 1 day and unknown = 1",
			intval($user['uid'])
	);

	if ($r[0]['total'] > $user['cntunkmail']) {
		notice(L10n::t('Number of daily wall messages for %s exceeded. Message failed.', $user['username']));
		return;
	}

	$tpl = get_markup_template('wallmsg-header.tpl');
	$a->page['htmlhead'] .= replace_macros($tpl, [
		'$baseurl' => System::baseUrl(true),
		'$nickname' => $user['nickname'],
		'$linkurl' => L10n::t('Please enter a link URL:')
	]);

	$tpl = get_markup_template('wallmessage.tpl');
	$o = replace_macros($tpl, [
		'$header' => L10n::t('Send Private Message'),
		'$subheader' => L10n::t('If you wish for %s to respond, please check that the privacy settings on your site allow private mail from unknown senders.', $user['username']),
		'$to' => L10n::t('To:'),
		'$subject' => L10n::t('Subject:'),
		'$recipname' => $user['username'],
		'$nickname' => $user['nickname'],
		'$subjtxt' => ((x($_REQUEST, 'subject')) ? strip_tags($_REQUEST['subject']) : ''),
		'$text' => ((x($_REQUEST, 'body')) ? escape_tags(htmlspecialchars($_REQUEST['body'])) : ''),
		'$readonly' => '',
		'$yourmessage' => L10n::t('Your message:'),
		'$parent' => '',
		'$upload' => L10n::t('Upload photo'),
		'$insert' => L10n::t('Insert web link'),
		'$wait' => L10n::t('Please wait')
	]);

	return $o;
}
