<?php
/**
 * View for user import
 * @TODO This file has DOS line endings!
 */

require_once("include/uimport.php");

function uimport_post(App $a) {
	switch($a->config['register_policy']) {
	case REGISTER_OPEN:
		$blocked = 0;
		$verified = 1;
		break;

	case REGISTER_APPROVE:
		$blocked = 1;
		$verified = 0;
		break;

	default:
	case REGISTER_CLOSED:
		if ((! x($_SESSION,'authenticated') && (! x($_SESSION,'administrator')))) {
			notice( t('Permission denied.') . EOL );
			return;
		}
		$blocked = 1;
		$verified = 0;
		break;
	}

	if (x($_FILES,'accountfile')){
		/// @TODO Pass $blocked / $verified, send email to admin on REGISTER_APPROVE
		import_account($a, $_FILES['accountfile']);
		return;
	}
}

function uimport_content(App $a) {

	if ((! local_user()) && ($a->config['register_policy'] == REGISTER_CLOSED)) {
		notice("Permission denied." . EOL);
		return;
	}

	$max_dailies = intval(get_config('system','max_daily_registrations'));
	if ($max_dailies) {
		$r = q("select count(*) as total from user where register_date > UTC_TIMESTAMP - INTERVAL 1 day");
		if ($r && $r[0]['total'] >= $max_dailies) {
			logger('max daily registrations exceeded.');
			notice( t('This site has exceeded the number of allowed daily account registrations. Please try again tomorrow.') . EOL);
			return;
		}
	}


	if (x($_SESSION,'theme')) {
		unset($_SESSION['theme']);
	}
	if (x($_SESSION,'mobile-theme')) {
		unset($_SESSION['mobile-theme']);
	}

	$tpl = get_markup_template("uimport.tpl");
	return replace_macros($tpl, array(
		'$regbutt' => t('Import'),
		'$import' => array(
		'title' => t("Move account"),
		'intro' => t("You can import an account from another Friendica server."),
		'instruct' => t("You need to export your account from the old server and upload it here. We will recreate your old account here with all your contacts. We will try also to inform your friends that you moved here."),
		'warn' => t("This feature is experimental. We can't import contacts from the OStatus network (GNU Social/Statusnet) or from Diaspora"),
		'field' => array('accountfile', t('Account file'),'<input id="id_accountfile" name="accountfile" type="file">', t('To export your account, go to "Settings->Export your personal data" and select "Export account"')),
		),
	));
}
