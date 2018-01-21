<?php
/**
 * @file mod/wallmessage.php
 */
use Friendica\App;
use Friendica\Core\System;
use Friendica\Database\DBM;
use Friendica\Model\Mail;
use Friendica\Model\Profile;

function wallmessage_post(App $a) {

	$replyto = Profile::getMyURL();
	if(! $replyto) {
		notice( t('Permission denied.') . EOL);
		return;
	}

	$subject   = ((x($_REQUEST,'subject'))   ? notags(trim($_REQUEST['subject']))   : '');
	$body      = ((x($_REQUEST,'body'))      ? escape_tags(trim($_REQUEST['body'])) : '');

	$recipient = (($a->argc > 1) ? notags($a->argv[1]) : '');
	if((! $recipient) || (! $body)) {
		return;
	}

	$r = q("select * from user where nickname = '%s' limit 1",
		dbesc($recipient)
	);

	if (! DBM::isResult($r)) {
		logger('wallmessage: no recipient');
		return;
	}

	$user = $r[0];

	if(! intval($user['unkmail'])) {
		notice( t('Permission denied.') . EOL);
		return;
	}

	$r = q("select count(*) as total from mail where uid = %d and created > UTC_TIMESTAMP() - INTERVAL 1 day and unknown = 1",
			intval($user['uid'])
	);

	if($r[0]['total'] > $user['cntunkmail']) {
		notice( sprintf( t('Number of daily wall messages for %s exceeded. Message failed.', $user['username'])));
		return;
	}

	$ret = Mail::sendWall($user, $body, $subject, $replyto);

	switch($ret){
		case -1:
			notice( t('No recipient selected.') . EOL );
			break;
		case -2:
			notice( t('Unable to check your home location.') . EOL );
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

	goaway('profile/'.$user['nickname']);

}


function wallmessage_content(App $a) {

	if (!Profile::getMyURL()) {
		notice(t('Permission denied.') . EOL);
		return;
	}

	$recipient = (($a->argc > 1) ? $a->argv[1] : '');

	if(! $recipient) {
		notice( t('No recipient.') . EOL);
		return;
	}

	$r = q("select * from user where nickname = '%s' limit 1",
		dbesc($recipient)
	);

	if (! DBM::isResult($r)) {
		notice( t('No recipient.') . EOL);
		logger('wallmessage: no recipient');
		return;
	}

	$user = $r[0];

	if(! intval($user['unkmail'])) {
		notice( t('Permission denied.') . EOL);
		return;
	}

	$r = q("select count(*) as total from mail where uid = %d and created > UTC_TIMESTAMP() - INTERVAL 1 day and unknown = 1",
			intval($user['uid'])
	);

	if($r[0]['total'] > $user['cntunkmail']) {
		notice( sprintf( t('Number of daily wall messages for %s exceeded. Message failed.', $user['username'])));
		return;
	}

	$tpl = get_markup_template('wallmsg-header.tpl');
	$a->page['htmlhead'] .= replace_macros($tpl, [
		'$baseurl' => System::baseUrl(true),
		'$nickname' => $user['nickname'],
		'$linkurl' => t('Please enter a link URL:')
	]);

	$tpl = get_markup_template('wallmsg-end.tpl');
	$a->page['end'] .= replace_macros($tpl, [
		'$baseurl' => System::baseUrl(true),
		'$nickname' => $user['nickname'],
		'$linkurl' => t('Please enter a link URL:')
	]);

	$tpl = get_markup_template('wallmessage.tpl');
	$o .= replace_macros($tpl,[
		'$header' => t('Send Private Message'),
		'$subheader' => sprintf( t('If you wish for %s to respond, please check that the privacy settings on your site allow private mail from unknown senders.'), $user['username']),
		'$to' => t('To:'),
		'$subject' => t('Subject:'),
		'$recipname' => $user['username'],
		'$nickname' => $user['nickname'],
		'$subjtxt' => ((x($_REQUEST,'subject')) ? strip_tags($_REQUEST['subject']) : ''),
		'$text' => ((x($_REQUEST,'body')) ? escape_tags(htmlspecialchars($_REQUEST['body'])) : ''),
		'$readonly' => '',
		'$yourmessage' => t('Your message:'),
		'$select' => $select,
		'$parent' => '',
		'$upload' => t('Upload photo'),
		'$insert' => t('Insert web link'),
		'$wait' => t('Please wait')
	]);

	return $o;
}
