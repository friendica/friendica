<?php
/**
 * @file mod/settings.php
 */

use Friendica\App;
use Friendica\Content\Feature;
use Friendica\Content\Nav;
use Friendica\Core\ACL;
use Friendica\Core\Addon;
use Friendica\Core\Config;
use Friendica\Core\L10n;
use Friendica\Core\PConfig;
use Friendica\Core\System;
use Friendica\Core\Worker;
use Friendica\Database\DBM;
use Friendica\Model\GContact;
use Friendica\Model\Group;
use Friendica\Model\User;
use Friendica\Protocol\Email;
use Friendica\Util\Crypto;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Network;
use Friendica\Util\Temporal;

function get_theme_config_file($theme)
{
	$a = get_app();
	$base_theme = $a->theme_info['extends'];

	if (file_exists("view/theme/$theme/config.php")) {
		return "view/theme/$theme/config.php";
	}
	if (file_exists("view/theme/$base_theme/config.php")) {
		return "view/theme/$base_theme/config.php";
	}
	return null;
}

function settings_init(App $a)
{
	if (!local_user()) {
		notice(L10n::t('Permission denied.') . EOL);
		return;
	}

	// These lines provide the javascript needed by the acl selector

	$tpl = get_markup_template('settings/head.tpl');
	$a->page['htmlhead'] .= replace_macros($tpl, [
		'$ispublic' => L10n::t('everybody')
	]);

	$tabs = [
		[
			'label'	=> L10n::t('Account'),
			'url' 	=> 'settings',
			'selected'	=>  (($a->argc == 1) && ($a->argv[0] === 'settings')?'active':''),
			'accesskey' => 'o',
		],
	];

	if (Feature::get()) {
		$tabs[] =	[
					'label'	=> L10n::t('Additional features'),
					'url' 	=> 'settings/features',
					'selected'	=> (($a->argc > 1) && ($a->argv[1] === 'features') ? 'active' : ''),
					'accesskey' => 't',
				];
	}

	$tabs[] =	[
		'label'	=> L10n::t('Display'),
		'url' 	=> 'settings/display',
		'selected'	=> (($a->argc > 1) && ($a->argv[1] === 'display')?'active':''),
		'accesskey' => 'i',
	];

	$tabs[] =	[
		'label'	=> L10n::t('Social Networks'),
		'url' 	=> 'settings/connectors',
		'selected'	=> (($a->argc > 1) && ($a->argv[1] === 'connectors')?'active':''),
		'accesskey' => 'w',
	];

	$tabs[] =	[
		'label'	=> L10n::t('Addons'),
		'url' 	=> 'settings/addon',
		'selected'	=> (($a->argc > 1) && ($a->argv[1] === 'addon')?'active':''),
		'accesskey' => 'l',
	];

	$tabs[] =	[
		'label'	=> L10n::t('Delegations'),
		'url' 	=> 'delegate',
		'selected'	=> (($a->argc == 1) && ($a->argv[0] === 'delegate')?'active':''),
		'accesskey' => 'd',
	];

	$tabs[] =	[
		'label' => L10n::t('Connected apps'),
		'url' => 'settings/oauth',
		'selected' => (($a->argc > 1) && ($a->argv[1] === 'oauth')?'active':''),
		'accesskey' => 'b',
	];

	$tabs[] =	[
		'label' => L10n::t('Export personal data'),
		'url' => 'uexport',
		'selected' => (($a->argc == 1) && ($a->argv[0] === 'uexport')?'active':''),
		'accesskey' => 'e',
	];

	$tabs[] =	[
		'label' => L10n::t('Remove account'),
		'url' => 'removeme',
		'selected' => (($a->argc == 1) && ($a->argv[0] === 'removeme')?'active':''),
		'accesskey' => 'r',
	];


	$tabtpl = get_markup_template("generic_links_widget.tpl");
	$a->page['aside'] = replace_macros($tabtpl, [
		'$title' => L10n::t('Settings'),
		'$class' => 'settings-widget',
		'$items' => $tabs,
	]);

}

function settings_post(App $a)
{
	if (!local_user()) {
		return;
	}

	if (x($_SESSION, 'submanage') && intval($_SESSION['submanage'])) {
		return;
	}

	if (count($a->user) && x($a->user, 'uid') && $a->user['uid'] != local_user()) {
		notice(L10n::t('Permission denied.') . EOL);
		return;
	}

	$old_page_flags = $a->user['page-flags'];

	if (($a->argc > 1) && ($a->argv[1] === 'oauth') && x($_POST, 'remove')) {
		check_form_security_token_redirectOnErr('/settings/oauth', 'settings_oauth');

		$key = $_POST['remove'];
		q("DELETE FROM tokens WHERE id='%s' AND uid=%d",
			dbesc($key),
			local_user());
		goaway(System::baseUrl(true)."/settings/oauth/");
		return;
	}

	if (($a->argc > 2) && ($a->argv[1] === 'oauth')  && ($a->argv[2] === 'edit'||($a->argv[2] === 'add')) && x($_POST, 'submit')) {
		check_form_security_token_redirectOnErr('/settings/oauth', 'settings_oauth');

		$name     = defaults($_POST, 'name'    , '');
		$key      = defaults($_POST, 'key'     , '');
		$secret   = defaults($_POST, 'secret'  , '');
		$redirect = defaults($_POST, 'redirect', '');
		$icon     = defaults($_POST, 'icon'    , '');

		if ($name == "" || $key == "" || $secret == "") {
			notice(L10n::t("Missing some important data!"));
		} else {
			if ($_POST['submit'] == L10n::t("Update")) {
				q("UPDATE clients SET
							client_id='%s',
							pw='%s',
							name='%s',
							redirect_uri='%s',
							icon='%s',
							uid=%d
						WHERE client_id='%s'",
					dbesc($key),
					dbesc($secret),
					dbesc($name),
					dbesc($redirect),
					dbesc($icon),
					local_user(),
					dbesc($key)
				);
			} else {
				q("INSERT INTO clients
							(client_id, pw, name, redirect_uri, icon, uid)
						VALUES ('%s', '%s', '%s', '%s', '%s',%d)",
					dbesc($key),
					dbesc($secret),
					dbesc($name),
					dbesc($redirect),
					dbesc($icon),
					local_user()
				);
			}
		}
		goaway(System::baseUrl(true)."/settings/oauth/");
		return;
	}

	if (($a->argc > 1) && ($a->argv[1] == 'addon')) {
		check_form_security_token_redirectOnErr('/settings/addon', 'settings_addon');

		Addon::callHooks('addon_settings_post', $_POST);
		return;
	}

	if (($a->argc > 1) && ($a->argv[1] == 'connectors')) {
		check_form_security_token_redirectOnErr('/settings/connectors', 'settings_connectors');

		if (x($_POST, 'general-submit')) {
			PConfig::set(local_user(), 'system', 'no_intelligent_shortening', intval($_POST['no_intelligent_shortening']));
			PConfig::set(local_user(), 'system', 'ostatus_autofriend', intval($_POST['snautofollow']));
			PConfig::set(local_user(), 'ostatus', 'default_group', $_POST['group-selection']);
			PConfig::set(local_user(), 'ostatus', 'legacy_contact', $_POST['legacy_contact']);
		} elseif (x($_POST, 'imap-submit')) {

			$mail_server       = ((x($_POST, 'mail_server')) ? $_POST['mail_server'] : '');
			$mail_port         = ((x($_POST, 'mail_port')) ? $_POST['mail_port'] : '');
			$mail_ssl          = ((x($_POST, 'mail_ssl')) ? strtolower(trim($_POST['mail_ssl'])) : '');
			$mail_user         = ((x($_POST, 'mail_user')) ? $_POST['mail_user'] : '');
			$mail_pass         = ((x($_POST, 'mail_pass')) ? trim($_POST['mail_pass']) : '');
			$mail_action       = ((x($_POST, 'mail_action')) ? trim($_POST['mail_action']) : '');
			$mail_movetofolder = ((x($_POST, 'mail_movetofolder')) ? trim($_POST['mail_movetofolder']) : '');
			$mail_replyto      = ((x($_POST, 'mail_replyto')) ? $_POST['mail_replyto'] : '');
			$mail_pubmail      = ((x($_POST, 'mail_pubmail')) ? $_POST['mail_pubmail'] : '');


			$mail_disabled = ((function_exists('imap_open') && (!Config::get('system', 'imap_disabled'))) ? 0 : 1);
			if (Config::get('system', 'dfrn_only')) {
				$mail_disabled = 1;
			}

			if (!$mail_disabled) {
				$failed = false;
				$r = q("SELECT * FROM `mailacct` WHERE `uid` = %d LIMIT 1",
					intval(local_user())
				);
				if (!DBM::is_result($r)) {
					dba::insert('mailacct', ['uid' => local_user()]);
				}
				if (strlen($mail_pass)) {
					$pass = '';
					openssl_public_encrypt($mail_pass, $pass, $a->user['pubkey'], OPENSSL_PKCS1_OAEP_PADDING);
					dba::update('mailacct', ['pass' => bin2hex($pass)], ['uid' => local_user()]);
				}
				$r = q("UPDATE `mailacct` SET `server` = '%s', `port` = %d, `ssltype` = '%s', `user` = '%s',
					`action` = %d, `movetofolder` = '%s',
					`mailbox` = 'INBOX', `reply_to` = '%s', `pubmail` = %d WHERE `uid` = %d",
					dbesc($mail_server),
					intval($mail_port),
					dbesc($mail_ssl),
					dbesc($mail_user),
					intval($mail_action),
					dbesc($mail_movetofolder),
					dbesc($mail_replyto),
					intval($mail_pubmail),
					intval(local_user())
				);
				logger("mail: updating mailaccount. Response: ".print_r($r, true));
				$r = q("SELECT * FROM `mailacct` WHERE `uid` = %d LIMIT 1",
					intval(local_user())
				);
				if (DBM::is_result($r)) {
					$eacct = $r[0];
					$mb = Email::constructMailboxName($eacct);

					if (strlen($eacct['server'])) {
						$dcrpass = '';
						Crypto::opensslPrivateDecrypt(hex2bin($eacct['pass']), $dcrpass, $a->user['prvkey']);
						$mbox = Email::connect($mb, $mail_user, $dcrpass);
						unset($dcrpass);
						if (!$mbox) {
							$failed = true;
							notice(L10n::t('Failed to connect with email account using the settings provided.') . EOL);
						}
					}
				}
				if (!$failed) {
					info(L10n::t('Email settings updated.') . EOL);
				}
			}
		}

		Addon::callHooks('connector_settings_post', $_POST);
		return;
	}

	if (($a->argc > 1) && ($a->argv[1] === 'features')) {
		check_form_security_token_redirectOnErr('/settings/features', 'settings_features');
		foreach ($_POST as $k => $v) {
			if (strpos($k, 'feature_') === 0) {
				PConfig::set(local_user(), 'feature', substr($k, 8), ((intval($v)) ? 1 : 0));
			}
		}
		info(L10n::t('Features updated') . EOL);
		return;
	}

	if (($a->argc > 1) && ($a->argv[1] === 'display')) {
		check_form_security_token_redirectOnErr('/settings/display', 'settings_display');

		$theme             = x($_POST, 'theme')             ? notags(trim($_POST['theme']))        : $a->user['theme'];
		$mobile_theme      = x($_POST, 'mobile_theme')      ? notags(trim($_POST['mobile_theme'])) : '';
		$nosmile           = x($_POST, 'nosmile')           ? intval($_POST['nosmile'])            : 0;
		$first_day_of_week = x($_POST, 'first_day_of_week') ? intval($_POST['first_day_of_week'])  : 0;
		$noinfo            = x($_POST, 'noinfo')            ? intval($_POST['noinfo'])             : 0;
		$infinite_scroll   = x($_POST, 'infinite_scroll')   ? intval($_POST['infinite_scroll'])    : 0;
		$no_auto_update    = x($_POST, 'no_auto_update')    ? intval($_POST['no_auto_update'])     : 0;
		$bandwidth_saver   = x($_POST, 'bandwidth_saver')   ? intval($_POST['bandwidth_saver'])    : 0;
		$smart_threading   = x($_POST, 'smart_threading')   ? intval($_POST['smart_threading'])    : 0;
		$nowarn_insecure   = x($_POST, 'nowarn_insecure')   ? intval($_POST['nowarn_insecure'])    : 0;
		$browser_update    = x($_POST, 'browser_update')    ? intval($_POST['browser_update'])     : 0;
		if ($browser_update != -1) {
			$browser_update = $browser_update * 1000;
			if ($browser_update < 10000) {
				$browser_update = 10000;
			}
		}

		$itemspage_network = x($_POST, 'itemspage_network')  ? intval($_POST['itemspage_network'])  : 40;
		if ($itemspage_network > 100) {
			$itemspage_network = 100;
		}
		$itemspage_mobile_network = x($_POST, 'itemspage_mobile_network') ? intval($_POST['itemspage_mobile_network']) : 20;
		if ($itemspage_mobile_network > 100) {
			$itemspage_mobile_network = 100;
		}

		if ($mobile_theme !== '') {
			PConfig::set(local_user(), 'system', 'mobile_theme', $mobile_theme);
		}

		PConfig::set(local_user(), 'system', 'nowarn_insecure'         , $nowarn_insecure);
		PConfig::set(local_user(), 'system', 'update_interval'         , $browser_update);
		PConfig::set(local_user(), 'system', 'itemspage_network'       , $itemspage_network);
		PConfig::set(local_user(), 'system', 'itemspage_mobile_network', $itemspage_mobile_network);
		PConfig::set(local_user(), 'system', 'no_smilies'              , $nosmile);
		PConfig::set(local_user(), 'system', 'first_day_of_week'       , $first_day_of_week);
		PConfig::set(local_user(), 'system', 'ignore_info'             , $noinfo);
		PConfig::set(local_user(), 'system', 'infinite_scroll'         , $infinite_scroll);
		PConfig::set(local_user(), 'system', 'no_auto_update'          , $no_auto_update);
		PConfig::set(local_user(), 'system', 'bandwidth_saver'         , $bandwidth_saver);
		PConfig::set(local_user(), 'system', 'smart_threading'         , $smart_threading);

		if ($theme == $a->user['theme']) {
			// call theme_post only if theme has not been changed
			if (($themeconfigfile = get_theme_config_file($theme)) !== null) {
				require_once $themeconfigfile;
				theme_post($a);
			}
		}

		$r = q("UPDATE `user` SET `theme` = '%s' WHERE `uid` = %d",
				dbesc($theme),
				intval(local_user())
		);

		Addon::callHooks('display_settings_post', $_POST);
		goaway('settings/display');
		return; // NOTREACHED
	}

	check_form_security_token_redirectOnErr('/settings', 'settings');

	if (x($_POST,'resend_relocate')) {
		Worker::add(PRIORITY_HIGH, 'Notifier', 'relocate', local_user());
		info(L10n::t("Relocate message has been send to your contacts"));
		goaway('settings');
	}

	Addon::callHooks('settings_post', $_POST);

	if (x($_POST, 'password') || x($_POST, 'confirm')) {
		$newpass = $_POST['password'];
		$confirm = $_POST['confirm'];

		$err = false;
		if ($newpass != $confirm) {
			notice(L10n::t('Passwords do not match. Password unchanged.') . EOL);
			$err = true;
		}

		if (!x($newpass) || !x($confirm)) {
			notice(L10n::t('Empty passwords are not allowed. Password unchanged.') . EOL);
			$err = true;
		}

		if (!Config::get('system', 'disable_password_exposed', false) && User::isPasswordExposed($newpass)) {
			notice(L10n::t('The new password has been exposed in a public data dump, please choose another.') . EOL);
			$err = true;
		}

		//  check if the old password was supplied correctly before changing it to the new value
		if (!User::authenticate(intval(local_user()), $_POST['opassword'])) {
			notice(L10n::t('Wrong password.') . EOL);
			$err = true;
		}

		if (!$err) {
			$result = User::updatePassword(local_user(), $newpass);
			if (DBM::is_result($result)) {
				info(L10n::t('Password changed.') . EOL);
			} else {
				notice(L10n::t('Password update failed. Please try again.') . EOL);
			}
		}
	}

	$username         = ((x($_POST, 'username'))   ? notags(trim($_POST['username']))     : '');
	$email            = ((x($_POST, 'email'))      ? notags(trim($_POST['email']))        : '');
	$timezone         = ((x($_POST, 'timezone'))   ? notags(trim($_POST['timezone']))     : '');
	$language         = ((x($_POST, 'language'))   ? notags(trim($_POST['language']))     : '');

	$defloc           = ((x($_POST, 'defloc'))     ? notags(trim($_POST['defloc']))       : '');
	$openid           = ((x($_POST, 'openid_url')) ? notags(trim($_POST['openid_url']))   : '');
	$maxreq           = ((x($_POST, 'maxreq'))     ? intval($_POST['maxreq'])             : 0);
	$expire           = ((x($_POST, 'expire'))     ? intval($_POST['expire'])             : 0);
	$def_gid          = ((x($_POST, 'group-selection')) ? intval($_POST['group-selection']) : 0);


	$expire_items     = ((x($_POST, 'expire_items')) ? intval($_POST['expire_items'])	 : 0);
	$expire_notes     = ((x($_POST, 'expire_notes')) ? intval($_POST['expire_notes'])	 : 0);
	$expire_starred   = ((x($_POST, 'expire_starred')) ? intval($_POST['expire_starred']) : 0);
	$expire_photos    = ((x($_POST, 'expire_photos'))? intval($_POST['expire_photos'])	 : 0);
	$expire_network_only    = ((x($_POST, 'expire_network_only'))? intval($_POST['expire_network_only'])	 : 0);

	$allow_location   = (((x($_POST, 'allow_location')) && (intval($_POST['allow_location']) == 1)) ? 1: 0);
	$publish          = (((x($_POST, 'profile_in_directory')) && (intval($_POST['profile_in_directory']) == 1)) ? 1: 0);
	$net_publish      = (((x($_POST, 'profile_in_netdirectory')) && (intval($_POST['profile_in_netdirectory']) == 1)) ? 1: 0);
	$old_visibility   = (((x($_POST, 'visibility')) && (intval($_POST['visibility']) == 1)) ? 1 : 0);
	$account_type     = (((x($_POST, 'account-type')) && (intval($_POST['account-type']))) ? intval($_POST['account-type']) : 0);
	$page_flags       = (((x($_POST, 'page-flags')) && (intval($_POST['page-flags']))) ? intval($_POST['page-flags']) : 0);
	$blockwall        = (((x($_POST, 'blockwall')) && (intval($_POST['blockwall']) == 1)) ? 0: 1); // this setting is inverted!
	$blocktags        = (((x($_POST, 'blocktags')) && (intval($_POST['blocktags']) == 1)) ? 0: 1); // this setting is inverted!
	$unkmail          = (((x($_POST, 'unkmail')) && (intval($_POST['unkmail']) == 1)) ? 1: 0);
	$cntunkmail       = ((x($_POST, 'cntunkmail')) ? intval($_POST['cntunkmail']) : 0);
	$suggestme        = ((x($_POST, 'suggestme')) ? intval($_POST['suggestme'])  : 0);
	$hide_friends     = (($_POST['hide-friends'] == 1) ? 1: 0);
	$hidewall         = (($_POST['hidewall'] == 1) ? 1: 0);
	$post_newfriend   = (($_POST['post_newfriend'] == 1) ? 1: 0);
	$post_joingroup   = (($_POST['post_joingroup'] == 1) ? 1: 0);
	$post_profilechange   = (($_POST['post_profilechange'] == 1) ? 1: 0);

	$email_textonly   = (($_POST['email_textonly'] == 1) ? 1 : 0);
	$detailed_notif   = (($_POST['detailed_notif'] == 1) ? 1 : 0);

	$notify = 0;

	if (x($_POST, 'notify1')) {
		$notify += intval($_POST['notify1']);
	}
	if (x($_POST, 'notify2')) {
		$notify += intval($_POST['notify2']);
	}
	if (x($_POST, 'notify3')) {
		$notify += intval($_POST['notify3']);
	}
	if (x($_POST, 'notify4')) {
		$notify += intval($_POST['notify4']);
	}
	if (x($_POST, 'notify5')) {
		$notify += intval($_POST['notify5']);
	}
	if (x($_POST, 'notify6')) {
		$notify += intval($_POST['notify6']);
	}
	if (x($_POST, 'notify7')) {
		$notify += intval($_POST['notify7']);
	}
	if (x($_POST, 'notify8')) {
		$notify += intval($_POST['notify8']);
	}

	// Adjust the page flag if the account type doesn't fit to the page flag.
	if (($account_type == ACCOUNT_TYPE_PERSON) && !in_array($page_flags, [PAGE_NORMAL, PAGE_SOAPBOX, PAGE_FREELOVE])) {
		$page_flags = PAGE_NORMAL;
	} elseif (($account_type == ACCOUNT_TYPE_ORGANISATION) && !in_array($page_flags, [PAGE_SOAPBOX])) {
		$page_flags = PAGE_SOAPBOX;
	} elseif (($account_type == ACCOUNT_TYPE_NEWS) && !in_array($page_flags, [PAGE_SOAPBOX])) {
		$page_flags = PAGE_SOAPBOX;
	} elseif (($account_type == ACCOUNT_TYPE_COMMUNITY) && !in_array($page_flags, [PAGE_COMMUNITY, PAGE_PRVGROUP])) {
		$page_flags = PAGE_COMMUNITY;
	}

	$email_changed = false;

	$err = '';

	$name_change = false;

	if ($username != $a->user['username']) {
		$name_change = true;
		if (strlen($username) > 40) {
			$err .= L10n::t(' Please use a shorter name.');
		}
		if (strlen($username) < 3) {
			$err .= L10n::t(' Name too short.');
		}
	}

	if ($email != $a->user['email']) {
		$email_changed = true;
		//  check for the correct password
		if (!User::authenticate(intval(local_user()), $_POST['mpassword'])) {
			$err .= L10n::t('Wrong Password') . EOL;
			$email = $a->user['email'];
		}
		//  check the email is valid
		if (!valid_email($email)) {
			$err .= L10n::t('Invalid email.');
		}
		//  ensure new email is not the admin mail
		//if ((x($a->config, 'admin_email')) && (strcasecmp($email, $a->config['admin_email']) == 0)) {
		if (x($a->config, 'admin_email')) {
			$adminlist = explode(",", str_replace(" ", "", strtolower($a->config['admin_email'])));
			if (in_array(strtolower($email), $adminlist)) {
				$err .= L10n::t('Cannot change to that email.');
				$email = $a->user['email'];
			}
		}
	}

	if (strlen($err)) {
		notice($err . EOL);
		return;
	}

	if (($timezone != $a->user['timezone']) && strlen($timezone)) {
		date_default_timezone_set($timezone);
	}

	$str_group_allow   = perms2str($_POST['group_allow']);
	$str_contact_allow = perms2str($_POST['contact_allow']);
	$str_group_deny    = perms2str($_POST['group_deny']);
	$str_contact_deny  = perms2str($_POST['contact_deny']);

	$openidserver = $a->user['openidserver'];
	//$openid = normalise_openid($openid);

	// If openid has changed or if there's an openid but no openidserver, try and discover it.
	if ($openid != $a->user['openid'] || (strlen($openid) && (!strlen($openidserver)))) {
		if (Network::isUrlValid($openid)) {
			logger('updating openidserver');
			$open_id_obj = new LightOpenID;
			$open_id_obj->identity = $openid;
			$openidserver = $open_id_obj->discover($open_id_obj->identity);
		} else {
			$openidserver = '';
		}
	}

	PConfig::set(local_user(), 'expire', 'items', $expire_items);
	PConfig::set(local_user(), 'expire', 'notes', $expire_notes);
	PConfig::set(local_user(), 'expire', 'starred', $expire_starred);
	PConfig::set(local_user(), 'expire', 'photos', $expire_photos);
	PConfig::set(local_user(), 'expire', 'network_only', $expire_network_only);

	PConfig::set(local_user(), 'system', 'suggestme', $suggestme);
	PConfig::set(local_user(), 'system', 'post_newfriend', $post_newfriend);
	PConfig::set(local_user(), 'system', 'post_joingroup', $post_joingroup);
	PConfig::set(local_user(), 'system', 'post_profilechange', $post_profilechange);

	PConfig::set(local_user(), 'system', 'email_textonly', $email_textonly);
	PConfig::set(local_user(), 'system', 'detailed_notif', $detailed_notif);

	if ($page_flags == PAGE_PRVGROUP) {
		$hidewall = 1;
		if (!$str_contact_allow && !$str_group_allow && !$str_contact_deny && !$str_group_deny) {
			if ($def_gid) {
				info(L10n::t('Private forum has no privacy permissions. Using default privacy group.'). EOL);
				$str_group_allow = '<' . $def_gid . '>';
			} else {
				notice(L10n::t('Private forum has no privacy permissions and no default privacy group.') . EOL);
			}
		}
	}


	$r = q("UPDATE `user` SET `username` = '%s', `email` = '%s',
				`openid` = '%s', `timezone` = '%s',
				`allow_cid` = '%s', `allow_gid` = '%s', `deny_cid` = '%s', `deny_gid` = '%s',
				`notify-flags` = %d, `page-flags` = %d, `account-type` = %d, `default-location` = '%s',
				`allow_location` = %d, `maxreq` = %d, `expire` = %d, `openidserver` = '%s',
				`def_gid` = %d, `blockwall` = %d, `hidewall` = %d, `blocktags` = %d,
				`unkmail` = %d, `cntunkmail` = %d, `language` = '%s'
			WHERE `uid` = %d",
			dbesc($username),
			dbesc($email),
			dbesc($openid),
			dbesc($timezone),
			dbesc($str_contact_allow),
			dbesc($str_group_allow),
			dbesc($str_contact_deny),
			dbesc($str_group_deny),
			intval($notify),
			intval($page_flags),
			intval($account_type),
			dbesc($defloc),
			intval($allow_location),
			intval($maxreq),
			intval($expire),
			dbesc($openidserver),
			intval($def_gid),
			intval($blockwall),
			intval($hidewall),
			intval($blocktags),
			intval($unkmail),
			intval($cntunkmail),
			dbesc($language),
			intval(local_user())
	);
	if (DBM::is_result($r)) {
		info(L10n::t('Settings updated.') . EOL);
	}

	// clear session language
	unset($_SESSION['language']);

	$r = q("UPDATE `profile`
		SET `publish` = %d,
		`name` = '%s',
		`net-publish` = %d,
		`hide-friends` = %d
		WHERE `is-default` = 1 AND `uid` = %d",
		intval($publish),
		dbesc($username),
		intval($net_publish),
		intval($hide_friends),
		intval(local_user())
	);


	if ($name_change) {
		q("UPDATE `contact` SET `name` = '%s', `name-date` = '%s' WHERE `uid` = %d AND `self`",
			dbesc($username),
			dbesc(DateTimeFormat::utcNow()),
			intval(local_user())
		);
	}

	if (($old_visibility != $net_publish) || ($page_flags != $old_page_flags)) {
		// Update global directory in background
		$url = $_SESSION['my_url'];
		if ($url && strlen(Config::get('system', 'directory'))) {
			Worker::add(PRIORITY_LOW, "Directory", $url);
		}
	}

	Worker::add(PRIORITY_LOW, 'ProfileUpdate', local_user());

	// Update the global contact for the user
	GContact::updateForUser(local_user());

	goaway('settings');
	return; // NOTREACHED
}


function settings_content(App $a)
{
	$o = '';
	Nav::setSelected('settings');

	if (!local_user()) {
		//notice(L10n::t('Permission denied.') . EOL);
		return;
	}

	if (x($_SESSION, 'submanage') && intval($_SESSION['submanage'])) {
		notice(L10n::t('Permission denied.') . EOL);
		return;
	}

	if (($a->argc > 1) && ($a->argv[1] === 'oauth')) {
		if (($a->argc > 2) && ($a->argv[2] === 'add')) {
			$tpl = get_markup_template('settings/oauth_edit.tpl');
			$o .= replace_macros($tpl, [
				'$form_security_token' => get_form_security_token("settings_oauth"),
				'$title'	=> L10n::t('Add application'),
				'$submit'	=> L10n::t('Save Settings'),
				'$cancel'	=> L10n::t('Cancel'),
				'$name'		=> ['name', L10n::t('Name'), '', ''],
				'$key'		=> ['key', L10n::t('Consumer Key'), '', ''],
				'$secret'	=> ['secret', L10n::t('Consumer Secret'), '', ''],
				'$redirect'	=> ['redirect', L10n::t('Redirect'), '', ''],
				'$icon'		=> ['icon', L10n::t('Icon url'), '', ''],
			]);
			return $o;
		}

		if (($a->argc > 3) && ($a->argv[2] === 'edit')) {
			$r = q("SELECT * FROM clients WHERE client_id='%s' AND uid=%d",
					dbesc($a->argv[3]),
					local_user());

			if (!DBM::is_result($r)) {
				notice(L10n::t("You can't edit this application."));
				return;
			}
			$app = $r[0];

			$tpl = get_markup_template('settings/oauth_edit.tpl');
			$o .= replace_macros($tpl, [
				'$form_security_token' => get_form_security_token("settings_oauth"),
				'$title'	=> L10n::t('Add application'),
				'$submit'	=> L10n::t('Update'),
				'$cancel'	=> L10n::t('Cancel'),
				'$name'		=> ['name', L10n::t('Name'), $app['name'] , ''],
				'$key'		=> ['key', L10n::t('Consumer Key'), $app['client_id'], ''],
				'$secret'	=> ['secret', L10n::t('Consumer Secret'), $app['pw'], ''],
				'$redirect'	=> ['redirect', L10n::t('Redirect'), $app['redirect_uri'], ''],
				'$icon'		=> ['icon', L10n::t('Icon url'), $app['icon'], ''],
			]);
			return $o;
		}

		if (($a->argc > 3) && ($a->argv[2] === 'delete')) {
			check_form_security_token_redirectOnErr('/settings/oauth', 'settings_oauth', 't');

			q("DELETE FROM clients WHERE client_id='%s' AND uid=%d",
					dbesc($a->argv[3]),
					local_user());
			goaway(System::baseUrl(true)."/settings/oauth/");
			return;
		}

		/// @TODO validate result with DBM::is_result()
		$r = q("SELECT clients.*, tokens.id as oauth_token, (clients.uid=%d) AS my
				FROM clients
				LEFT JOIN tokens ON clients.client_id=tokens.client_id
				WHERE clients.uid IN (%d, 0)",
				local_user(),
				local_user());


		$tpl = get_markup_template('settings/oauth.tpl');
		$o .= replace_macros($tpl, [
			'$form_security_token' => get_form_security_token("settings_oauth"),
			'$baseurl'	=> System::baseUrl(true),
			'$title'	=> L10n::t('Connected Apps'),
			'$add'		=> L10n::t('Add application'),
			'$edit'		=> L10n::t('Edit'),
			'$delete'		=> L10n::t('Delete'),
			'$consumerkey' => L10n::t('Client key starts with'),
			'$noname'	=> L10n::t('No name'),
			'$remove'	=> L10n::t('Remove authorization'),
			'$apps'		=> $r,
		]);
		return $o;
	}

	if (($a->argc > 1) && ($a->argv[1] === 'addon')) {
		$settings_addons = "";

		$r = q("SELECT * FROM `hook` WHERE `hook` = 'addon_settings' ");
		if (!DBM::is_result($r)) {
			$settings_addons = L10n::t('No Addon settings configured');
		}

		Addon::callHooks('addon_settings', $settings_addons);


		$tpl = get_markup_template('settings/addons.tpl');
		$o .= replace_macros($tpl, [
			'$form_security_token' => get_form_security_token("settings_addon"),
			'$title'	=> L10n::t('Addon Settings'),
			'$settings_addons' => $settings_addons
		]);
		return $o;
	}

	if (($a->argc > 1) && ($a->argv[1] === 'features')) {

		$arr = [];
		$features = Feature::get();
		foreach ($features as $fname => $fdata) {
			$arr[$fname] = [];
			$arr[$fname][0] = $fdata[0];
			foreach (array_slice($fdata,1) as $f) {
				$arr[$fname][1][] = ['feature_' .$f[0], $f[1],((intval(Feature::isEnabled(local_user(), $f[0]))) ? "1" : ''), $f[2],[L10n::t('Off'), L10n::t('On')]];
			}
		}

		$tpl = get_markup_template('settings/features.tpl');
		$o .= replace_macros($tpl, [
			'$form_security_token' => get_form_security_token("settings_features"),
			'$title'               => L10n::t('Additional Features'),
			'$features'            => $arr,
			'$submit'              => L10n::t('Save Settings'),
		]);
		return $o;
	}

	if (($a->argc > 1) && ($a->argv[1] === 'connectors')) {
		$no_intelligent_shortening = intval(PConfig::get(local_user(), 'system', 'no_intelligent_shortening'));
		$ostatus_autofriend        = intval(PConfig::get(local_user(), 'system', 'ostatus_autofriend'));
		$default_group             = PConfig::get(local_user(), 'ostatus', 'default_group');
		$legacy_contact            = PConfig::get(local_user(), 'ostatus', 'legacy_contact');

		if (x($legacy_contact)) {
			/// @todo Isn't it supposed to be a goaway() call?
			$a->page['htmlhead'] = '<meta http-equiv="refresh" content="0; URL=' . System::baseUrl().'/ostatus_subscribe?url=' . urlencode($legacy_contact) . '">';
		}

		$settings_connectors = '';
		Addon::callHooks('connector_settings', $settings_connectors);

		if (is_site_admin()) {
			$diasp_enabled = L10n::t('Built-in support for %s connectivity is %s', L10n::t('Diaspora'), ((Config::get('system', 'diaspora_enabled')) ? L10n::t('enabled') : L10n::t('disabled')));
			$ostat_enabled = L10n::t('Built-in support for %s connectivity is %s', L10n::t("GNU Social \x28OStatus\x29"), ((Config::get('system', 'ostatus_disabled')) ? L10n::t('disabled') : L10n::t('enabled')));
		} else {
			$diasp_enabled = "";
			$ostat_enabled = "";
		}

		$mail_disabled = ((function_exists('imap_open') && (!Config::get('system', 'imap_disabled'))) ? 0 : 1);
		if (Config::get('system', 'dfrn_only')) {
			$mail_disabled = 1;
		}
		if (!$mail_disabled) {
			$r = q("SELECT * FROM `mailacct` WHERE `uid` = %d LIMIT 1",
				local_user()
			);
		} else {
			$r = null;
		}

		$mail_server       = ((DBM::is_result($r)) ? $r[0]['server'] : '');
		$mail_port         = ((DBM::is_result($r) && intval($r[0]['port'])) ? intval($r[0]['port']) : '');
		$mail_ssl          = ((DBM::is_result($r)) ? $r[0]['ssltype'] : '');
		$mail_user         = ((DBM::is_result($r)) ? $r[0]['user'] : '');
		$mail_replyto      = ((DBM::is_result($r)) ? $r[0]['reply_to'] : '');
		$mail_pubmail      = ((DBM::is_result($r)) ? $r[0]['pubmail'] : 0);
		$mail_action       = ((DBM::is_result($r)) ? $r[0]['action'] : 0);
		$mail_movetofolder = ((DBM::is_result($r)) ? $r[0]['movetofolder'] : '');
		$mail_chk          = ((DBM::is_result($r)) ? $r[0]['last_check'] : NULL_DATE);


		$tpl = get_markup_template('settings/connectors.tpl');

		$mail_disabled_message = (($mail_disabled) ? L10n::t('Email access is disabled on this site.') : '');

		$o .= replace_macros($tpl, [
			'$form_security_token' => get_form_security_token("settings_connectors"),

			'$title'	=> L10n::t('Social Networks'),

			'$diasp_enabled' => $diasp_enabled,
			'$ostat_enabled' => $ostat_enabled,

			'$general_settings' => L10n::t('General Social Media Settings'),
			'$no_intelligent_shortening' => ['no_intelligent_shortening', L10n::t('Disable intelligent shortening'), $no_intelligent_shortening, L10n::t('Normally the system tries to find the best link to add to shortened posts. If this option is enabled then every shortened post will always point to the original friendica post.')],
			'$ostatus_autofriend' => ['snautofollow', L10n::t("Automatically follow any GNU Social \x28OStatus\x29 followers/mentioners"), $ostatus_autofriend, L10n::t('If you receive a message from an unknown OStatus user, this option decides what to do. If it is checked, a new contact will be created for every unknown user.')],
			'$default_group' => Group::displayGroupSelection(local_user(), $default_group, L10n::t("Default group for OStatus contacts")),
			'$legacy_contact' => ['legacy_contact', L10n::t('Your legacy GNU Social account'), $legacy_contact, L10n::t("If you enter your old GNU Social/Statusnet account name here \x28in the format user@domain.tld\x29, your contacts will be added automatically. The field will be emptied when done.")],

			'$repair_ostatus_url' => System::baseUrl() . '/repair_ostatus',
			'$repair_ostatus_text' => L10n::t('Repair OStatus subscriptions'),

			'$settings_connectors' => $settings_connectors,

			'$h_imap' => L10n::t('Email/Mailbox Setup'),
			'$imap_desc' => L10n::t("If you wish to communicate with email contacts using this service \x28optional\x29, please specify how to connect to your mailbox."),
			'$imap_lastcheck' => ['imap_lastcheck', L10n::t('Last successful email check:'), $mail_chk, ''],
			'$mail_disabled' => $mail_disabled_message,
			'$mail_server'	=> ['mail_server',  L10n::t('IMAP server name:'), $mail_server, ''],
			'$mail_port'	=> ['mail_port', 	 L10n::t('IMAP port:'), $mail_port, ''],
			'$mail_ssl'		=> ['mail_ssl', 	 L10n::t('Security:'), strtoupper($mail_ssl), '', ['notls'=>L10n::t('None'), 'TLS'=>'TLS', 'SSL'=>'SSL']],
			'$mail_user'	=> ['mail_user',    L10n::t('Email login name:'), $mail_user, ''],
			'$mail_pass'	=> ['mail_pass', 	 L10n::t('Email password:'), '', ''],
			'$mail_replyto'	=> ['mail_replyto', L10n::t('Reply-to address:'), $mail_replyto, 'Optional'],
			'$mail_pubmail'	=> ['mail_pubmail', L10n::t('Send public posts to all email contacts:'), $mail_pubmail, ''],
			'$mail_action'	=> ['mail_action',	 L10n::t('Action after import:'), $mail_action, '', [0=>L10n::t('None'), /*1=>L10n::t('Delete'),*/ 2=>L10n::t('Mark as seen'), 3=>L10n::t('Move to folder')]],
			'$mail_movetofolder'	=> ['mail_movetofolder',	 L10n::t('Move to folder:'), $mail_movetofolder, ''],
			'$submit' => L10n::t('Save Settings'),
		]);

		Addon::callHooks('display_settings', $o);
		return $o;
	}

	/*
	 * DISPLAY SETTINGS
	 */
	if (($a->argc > 1) && ($a->argv[1] === 'display')) {
		$default_theme = Config::get('system', 'theme');
		if (!$default_theme) {
			$default_theme = 'default';
		}
		$default_mobile_theme = Config::get('system', 'mobile-theme');
		if (!$default_mobile_theme) {
			$default_mobile_theme = 'none';
		}

		$allowed_themes_str = Config::get('system', 'allowed_themes');
		$allowed_themes_raw = explode(',', $allowed_themes_str);
		$allowed_themes = [];
		if (count($allowed_themes_raw)) {
			foreach ($allowed_themes_raw as $x) {
				if (strlen(trim($x)) && is_dir("view/theme/$x")) {
					$allowed_themes[] = trim($x);
				}
			}
		}


		$themes = [];
		$mobile_themes = ["---" => L10n::t('No special theme for mobile devices')];
		if ($allowed_themes) {
			foreach ($allowed_themes as $theme) {
				$is_experimental = file_exists('view/theme/' . $theme . '/experimental');
				$is_unsupported  = file_exists('view/theme/' . $theme . '/unsupported');
				$is_mobile       = file_exists('view/theme/' . $theme . '/mobile');
				if (!$is_experimental || ($is_experimental && (Config::get('experimentals', 'exp_themes')==1 || is_null(Config::get('experimentals', 'exp_themes'))))) {
					$theme_name = ucfirst($theme);
					if ($is_unsupported) {
						$theme_name = L10n::t("%s - \x28Unsupported\x29", $theme_name);
					} elseif ($is_experimental) {
						$theme_name = L10n::t("%s - \x28Experimental\x29", $theme_name);
					}
					if ($is_mobile) {
						$mobile_themes[$theme] = $theme_name;
					} else {
						$themes[$theme] = $theme_name;
					}
				}
			}
		}
		$theme_selected        = defaults($_SESSION, 'theme'       , $default_theme);
		$mobile_theme_selected = defaults($_SESSION, 'mobile-theme', $default_mobile_theme);

		$nowarn_insecure = intval(PConfig::get(local_user(), 'system', 'nowarn_insecure'));

		$browser_update = intval(PConfig::get(local_user(), 'system', 'update_interval'));
		if (intval($browser_update) != -1) {
			$browser_update = (($browser_update == 0) ? 40 : $browser_update / 1000); // default if not set: 40 seconds
		}

		$itemspage_network = intval(PConfig::get(local_user(), 'system', 'itemspage_network'));
		$itemspage_network = (($itemspage_network > 0 && $itemspage_network < 101) ? $itemspage_network : 40); // default if not set: 40 items
		$itemspage_mobile_network = intval(PConfig::get(local_user(), 'system', 'itemspage_mobile_network'));
		$itemspage_mobile_network = (($itemspage_mobile_network > 0 && $itemspage_mobile_network < 101) ? $itemspage_mobile_network : 20); // default if not set: 20 items

		$nosmile = PConfig::get(local_user(), 'system', 'no_smilies', 0);
		$first_day_of_week = PConfig::get(local_user(), 'system', 'first_day_of_week', 0);
		$weekdays = [0 => L10n::t("Sunday"), 1 => L10n::t("Monday")];

		$noinfo = PConfig::get(local_user(), 'system', 'ignore_info', 0);
		$infinite_scroll = PConfig::get(local_user(), 'system', 'infinite_scroll', 0);
		$no_auto_update = PConfig::get(local_user(), 'system', 'no_auto_update', 0);
		$bandwidth_saver = PConfig::get(local_user(), 'system', 'bandwidth_saver', 0);
		$smart_threading = PConfig::get(local_user(), 'system', 'smart_threading', 0);

		$theme_config = "";
		if (($themeconfigfile = get_theme_config_file($theme_selected)) !== null) {
			require_once $themeconfigfile;
			$theme_config = theme_content($a);
		}

		$tpl = get_markup_template('settings/display.tpl');
		$o = replace_macros($tpl, [
			'$ptitle' 	=> L10n::t('Display Settings'),
			'$form_security_token' => get_form_security_token("settings_display"),
			'$submit' 	=> L10n::t('Save Settings'),
			'$baseurl' => System::baseUrl(true),
			'$uid' => local_user(),

			'$theme'	=> ['theme', L10n::t('Display Theme:'), $theme_selected, '', $themes, true],
			'$mobile_theme'	=> ['mobile_theme', L10n::t('Mobile Theme:'), $mobile_theme_selected, '', $mobile_themes, false],
			'$nowarn_insecure' => ['nowarn_insecure',  L10n::t('Suppress warning of insecure networks'), $nowarn_insecure, L10n::t("Should the system suppress the warning that the current group contains members of networks that can't receive non public postings.")],
			'$ajaxint'   => ['browser_update',  L10n::t("Update browser every xx seconds"), $browser_update, L10n::t('Minimum of 10 seconds. Enter -1 to disable it.')],
			'$itemspage_network'   => ['itemspage_network',  L10n::t("Number of items to display per page:"), $itemspage_network, L10n::t('Maximum of 100 items')],
			'$itemspage_mobile_network'   => ['itemspage_mobile_network',  L10n::t("Number of items to display per page when viewed from mobile device:"), $itemspage_mobile_network, L10n::t('Maximum of 100 items')],
			'$nosmile'	=> ['nosmile', L10n::t("Don't show emoticons"), $nosmile, ''],
			'$calendar_title' => L10n::t('Calendar'),
			'$first_day_of_week'	=> ['first_day_of_week', L10n::t('Beginning of week:'), $first_day_of_week, '', $weekdays, false],
			'$noinfo'	=> ['noinfo', L10n::t("Don't show notices"), $noinfo, ''],
			'$infinite_scroll'	=> ['infinite_scroll', L10n::t("Infinite scroll"), $infinite_scroll, ''],
			'$no_auto_update'	=> ['no_auto_update', L10n::t("Automatic updates only at the top of the network page"), $no_auto_update, L10n::t('When disabled, the network page is updated all the time, which could be confusing while reading.')],
			'$bandwidth_saver' => ['bandwidth_saver', L10n::t('Bandwith Saver Mode'), $bandwidth_saver, L10n::t('When enabled, embedded content is not displayed on automatic updates, they only show on page reload.')],
			'$smart_threading' => ['smart_threading', L10n::t('Smart Threading'), $smart_threading, L10n::t('When enabled, suppress extraneous thread indentation while keeping it where it matters. Only works if threading is available and enabled.')],

			'$d_tset' => L10n::t('General Theme Settings'),
			'$d_ctset' => L10n::t('Custom Theme Settings'),
			'$d_cset' => L10n::t('Content Settings'),
			'stitle' => L10n::t('Theme settings'),
			'$theme_config' => $theme_config,
		]);

		$tpl = get_markup_template('settings/display_end.tpl');
		$a->page['end'] .= replace_macros($tpl, [
			'$theme'	=> ['theme', L10n::t('Display Theme:'), $theme_selected, '', $themes]
		]);

		return $o;
	}


	/*
	 * ACCOUNT SETTINGS
	 */

	$profile = dba::selectFirst('profile', [], ['is-default' => true, 'uid' => local_user()]);
	if (!DBM::is_result($profile)) {
		notice(L10n::t('Unable to find your profile. Please contact your admin.') . EOL);
		return;
	}

	$username   = $a->user['username'];
	$email      = $a->user['email'];
	$nickname   = $a->user['nickname'];
	$timezone   = $a->user['timezone'];
	$language   = $a->user['language'];
	$notify     = $a->user['notify-flags'];
	$defloc     = $a->user['default-location'];
	$openid     = $a->user['openid'];
	$maxreq     = $a->user['maxreq'];
	$expire     = ((intval($a->user['expire'])) ? $a->user['expire'] : '');
	$unkmail    = $a->user['unkmail'];
	$cntunkmail = $a->user['cntunkmail'];

	$expire_items = PConfig::get(local_user(), 'expire', 'items', true);
	$expire_notes = PConfig::get(local_user(), 'expire', 'notes', true);
	$expire_starred = PConfig::get(local_user(), 'expire', 'starred', true);
	$expire_photos = PConfig::get(local_user(), 'expire', 'photos', false);
	$expire_network_only = PConfig::get(local_user(), 'expire', 'network_only', false);
	$suggestme = PConfig::get(local_user(), 'system', 'suggestme', false);
	$post_newfriend = PConfig::get(local_user(), 'system', 'post_newfriend', false);
	$post_joingroup = PConfig::get(local_user(), 'system', 'post_joingroup', false);
	$post_profilechange = PConfig::get(local_user(), 'system', 'post_profilechange', false);

	// nowarn_insecure

	if (!strlen($a->user['timezone'])) {
		$timezone = date_default_timezone_get();
	}

	// Set the account type to "Community" when the page is a community page but the account type doesn't fit
	// This is only happening on the first visit after the update
	if (in_array($a->user['page-flags'], [PAGE_COMMUNITY, PAGE_PRVGROUP]) &&
		($a->user['account-type'] != ACCOUNT_TYPE_COMMUNITY))
		$a->user['account-type'] = ACCOUNT_TYPE_COMMUNITY;

	$pageset_tpl = get_markup_template('settings/pagetypes.tpl');

	$pagetype = replace_macros($pageset_tpl, [
		'$account_types'	=> L10n::t("Account Types"),
		'$user' 		=> L10n::t("Personal Page Subtypes"),
		'$community'		=> L10n::t("Community Forum Subtypes"),
		'$account_type'		=> $a->user['account-type'],
		'$type_person'		=> ACCOUNT_TYPE_PERSON,
		'$type_organisation' 	=> ACCOUNT_TYPE_ORGANISATION,
		'$type_news'		=> ACCOUNT_TYPE_NEWS,
		'$type_community' 	=> ACCOUNT_TYPE_COMMUNITY,

		'$account_person' 	=> ['account-type', L10n::t('Personal Page'), ACCOUNT_TYPE_PERSON,
									L10n::t('Account for a personal profile.'),
									($a->user['account-type'] == ACCOUNT_TYPE_PERSON)],

		'$account_organisation'	=> ['account-type', L10n::t('Organisation Page'), ACCOUNT_TYPE_ORGANISATION,
									L10n::t('Account for an organisation that automatically approves contact requests as "Followers".'),
									($a->user['account-type'] == ACCOUNT_TYPE_ORGANISATION)],

		'$account_news'		=> ['account-type', L10n::t('News Page'), ACCOUNT_TYPE_NEWS,
									L10n::t('Account for a news reflector that automatically approves contact requests as "Followers".'),
									($a->user['account-type'] == ACCOUNT_TYPE_NEWS)],

		'$account_community' 	=> ['account-type', L10n::t('Community Forum'), ACCOUNT_TYPE_COMMUNITY,
									L10n::t('Account for community discussions.'),
									($a->user['account-type'] == ACCOUNT_TYPE_COMMUNITY)],

		'$page_normal'		=> ['page-flags', L10n::t('Normal Account Page'), PAGE_NORMAL,
									L10n::t('Account for a regular personal profile that requires manual approval of "Friends" and "Followers".'),
									($a->user['page-flags'] == PAGE_NORMAL)],

		'$page_soapbox' 	=> ['page-flags', L10n::t('Soapbox Page'), PAGE_SOAPBOX,
									L10n::t('Account for a public profile that automatically approves contact requests as "Followers".'),
									($a->user['page-flags'] == PAGE_SOAPBOX)],

		'$page_community'	=> ['page-flags', L10n::t('Public Forum'), PAGE_COMMUNITY,
									L10n::t('Automatically approves all contact requests.'),
									($a->user['page-flags'] == PAGE_COMMUNITY)],

		'$page_freelove' 	=> ['page-flags', L10n::t('Automatic Friend Page'), PAGE_FREELOVE,
									L10n::t('Account for a popular profile that automatically approves contact requests as "Friends".'),
									($a->user['page-flags'] == PAGE_FREELOVE)],

		'$page_prvgroup' 	=> ['page-flags', L10n::t('Private Forum [Experimental]'), PAGE_PRVGROUP,
									L10n::t('Requires manual approval of contact requests.'),
									($a->user['page-flags'] == PAGE_PRVGROUP)],


	]);

	$noid = Config::get('system', 'no_openid');

	if ($noid) {
		$openid_field = false;
	} else {
		$openid_field = ['openid_url', L10n::t('OpenID:'), $openid, L10n::t("\x28Optional\x29 Allow this OpenID to login to this account."), "", "", "url"];
	}

	$opt_tpl = get_markup_template("field_yesno.tpl");
	if (Config::get('system', 'publish_all')) {
		$profile_in_dir = '<input type="hidden" name="profile_in_directory" value="1" />';
	} else {
		$profile_in_dir = replace_macros($opt_tpl, [
			'$field' => ['profile_in_directory', L10n::t('Publish your default profile in your local site directory?'), $profile['publish'], L10n::t('Your profile will be published in the global friendica directories (e.g. <a href="%s">%s</a>). Your profile will be visible in public.', Config::get('system', 'directory'), Config::get('system', 'directory')), [L10n::t('No'), L10n::t('Yes')]]
		]);
	}

	if (strlen(Config::get('system', 'directory'))) {
		$profile_in_net_dir = replace_macros($opt_tpl, [
			'$field' => ['profile_in_netdirectory', L10n::t('Publish your default profile in the global social directory?'), $profile['net-publish'], L10n::t('Your profile will be published in this node\'s <a href="%s">local directory</a>. Your profile details may be publicly visible depending on the system settings.', System::baseUrl().'/directory'), [L10n::t('No'), L10n::t('Yes')]]
		]);
	} else {
		$profile_in_net_dir = '';
	}

	$hide_friends = replace_macros($opt_tpl, [
		'$field' => ['hide-friends', L10n::t('Hide your contact/friend list from viewers of your default profile?'), $profile['hide-friends'], L10n::t('Your contact list won\'t be shown in your default profile page. You can decide to show your contact list separately for each additional profile you create'), [L10n::t('No'), L10n::t('Yes')]],
	]);

	$hide_wall = replace_macros($opt_tpl, [
		'$field' => ['hidewall', L10n::t('Hide your profile details from anonymous viewers?'), $a->user['hidewall'], L10n::t('Anonymous visitors will only see your profile picture, your display name and the nickname you are using on your profile page. Disables posting public messages to Diaspora and other networks.'), [L10n::t('No'), L10n::t('Yes')]],
	]);

	$blockwall = replace_macros($opt_tpl, [
		'$field' => ['blockwall', L10n::t('Allow friends to post to your profile page?'), (intval($a->user['blockwall']) ? '0' : '1'), L10n::t('Your contacts may write posts on your profile wall. These posts will be distributed to your contacts'), [L10n::t('No'), L10n::t('Yes')]],
	]);

	$blocktags = replace_macros($opt_tpl, [
		'$field' => ['blocktags', L10n::t('Allow friends to tag your posts?'), (intval($a->user['blocktags']) ? '0' : '1'), L10n::t('Your contacts can add additional tags to your posts.'), [L10n::t('No'), L10n::t('Yes')]],
	]);

	$suggestme = replace_macros($opt_tpl, [
		'$field' => ['suggestme', L10n::t('Allow us to suggest you as a potential friend to new members?'), $suggestme, L10n::t('If you like, Friendica may suggest new members to add you as a contact.'), [L10n::t('No'), L10n::t('Yes')]],
	]);

	$unkmail = replace_macros($opt_tpl, [
		'$field' => ['unkmail', L10n::t('Permit unknown people to send you private mail?'), $unkmail, L10n::t('Friendica network users may send you private messages even if they are not in your contact list.'), [L10n::t('No'), L10n::t('Yes')]],
	]);

	if (!$profile['publish'] && !$profile['net-publish']) {
		info(L10n::t('Profile is <strong>not published</strong>.') . EOL);
	}

	$tpl_addr = get_markup_template('settings/nick_set.tpl');

	$prof_addr = replace_macros($tpl_addr,[
		'$desc' => L10n::t("Your Identity Address is <strong>'%s'</strong> or '%s'.", $nickname . '@' . $a->get_hostname() . $a->get_path(), System::baseUrl() . '/profile/' . $nickname),
		'$basepath' => $a->get_hostname()
	]);

	$stpl = get_markup_template('settings/settings.tpl');

	$expire_arr = [
		'days' => ['expire',  L10n::t("Automatically expire posts after this many days:"), $expire, L10n::t('If empty, posts will not expire. Expired posts will be deleted')],
		'advanced' => L10n::t('Advanced expiration settings'),
		'label' => L10n::t('Advanced Expiration'),
		'items' => ['expire_items',  L10n::t("Expire posts:"), $expire_items, '', [L10n::t('No'), L10n::t('Yes')]],
		'notes' => ['expire_notes',  L10n::t("Expire personal notes:"), $expire_notes, '', [L10n::t('No'), L10n::t('Yes')]],
		'starred' => ['expire_starred',  L10n::t("Expire starred posts:"), $expire_starred, '', [L10n::t('No'), L10n::t('Yes')]],
		'photos' => ['expire_photos',  L10n::t("Expire photos:"), $expire_photos, '', [L10n::t('No'), L10n::t('Yes')]],
		'network_only' => ['expire_network_only',  L10n::t("Only expire posts by others:"), $expire_network_only, '', [L10n::t('No'), L10n::t('Yes')]],
	];

	$group_select = Group::displayGroupSelection(local_user(), $a->user['def_gid']);

	// Private/public post links for the non-JS ACL form
	$private_post = 1;
	if ($_REQUEST['public']) {
		$private_post = 0;
	}

	$query_str = $a->query_string;
	if (strpos($query_str, 'public=1') !== false) {
		$query_str = str_replace(['?public=1', '&public=1'], ['', ''], $query_str);
	}

	// I think $a->query_string may never have ? in it, but I could be wrong
	// It looks like it's from the index.php?q=[etc] rewrite that the web
	// server does, which converts any ? to &, e.g. suggest&ignore=61 for suggest?ignore=61
	if (strpos($query_str, '?') === false) {
		$public_post_link = '?public=1';
	} else {
		$public_post_link = '&public=1';
	}

	/* Installed langs */
	$lang_choices = L10n::getAvailableLanguages();

	/// @TODO Fix indending (or so)
	$o .= replace_macros($stpl, [
		'$ptitle' 	=> L10n::t('Account Settings'),

		'$submit' 	=> L10n::t('Save Settings'),
		'$baseurl' => System::baseUrl(true),
		'$uid' => local_user(),
		'$form_security_token' => get_form_security_token("settings"),
		'$nickname_block' => $prof_addr,

		'$h_pass' 	=> L10n::t('Password Settings'),
		'$password1'=> ['password', L10n::t('New Password:'), '', ''],
		'$password2'=> ['confirm', L10n::t('Confirm:'), '', L10n::t('Leave password fields blank unless changing')],
		'$password3'=> ['opassword', L10n::t('Current Password:'), '', L10n::t('Your current password to confirm the changes')],
		'$password4'=> ['mpassword', L10n::t('Password:'), '', L10n::t('Your current password to confirm the changes')],
		'$oid_enable' => (!Config::get('system', 'no_openid')),
		'$openid'	=> $openid_field,

		'$h_basic' 	=> L10n::t('Basic Settings'),
		'$username' => ['username',  L10n::t('Full Name:'), $username, ''],
		'$email' 	=> ['email', L10n::t('Email Address:'), $email, '', '', '', 'email'],
		'$timezone' => ['timezone_select' , L10n::t('Your Timezone:'), Temporal::getTimezoneSelect($timezone), ''],
		'$language' => ['language', L10n::t('Your Language:'), $language, L10n::t('Set the language we use to show you friendica interface and to send you emails'), $lang_choices],
		'$defloc'	=> ['defloc', L10n::t('Default Post Location:'), $defloc, ''],
		'$allowloc' => ['allow_location', L10n::t('Use Browser Location:'), ($a->user['allow_location'] == 1), ''],


		'$h_prv' 	=> L10n::t('Security and Privacy Settings'),

		'$maxreq' 	=> ['maxreq', L10n::t('Maximum Friend Requests/Day:'), $maxreq , L10n::t("\x28to prevent spam abuse\x29")],
		'$permissions' => L10n::t('Default Post Permissions'),
		'$permdesc' => L10n::t("\x28click to open/close\x29"),
		'$visibility' => $profile['net-publish'],
		'$aclselect' => ACL::getFullSelectorHTML($a->user),
		'$suggestme' => $suggestme,
		'$blockwall'=> $blockwall, // array('blockwall', L10n::t('Allow friends to post to your profile page:'), !$blockwall, ''),
		'$blocktags'=> $blocktags, // array('blocktags', L10n::t('Allow friends to tag your posts:'), !$blocktags, ''),

		// ACL permissions box
		'$group_perms' => L10n::t('Show to Groups'),
		'$contact_perms' => L10n::t('Show to Contacts'),
		'$private' => L10n::t('Default Private Post'),
		'$public' => L10n::t('Default Public Post'),
		'$is_private' => $private_post,
		'$return_path' => $query_str,
		'$public_link' => $public_post_link,
		'$settings_perms' => L10n::t('Default Permissions for New Posts'),

		'$group_select' => $group_select,


		'$expire'	=> $expire_arr,

		'$profile_in_dir' => $profile_in_dir,
		'$profile_in_net_dir' => $profile_in_net_dir,
		'$hide_friends' => $hide_friends,
		'$hide_wall' => $hide_wall,
		'$unkmail' => $unkmail,
		'$cntunkmail' 	=> ['cntunkmail', L10n::t('Maximum private messages per day from unknown people:'), $cntunkmail , L10n::t("\x28to prevent spam abuse\x29")],


		'$h_not' 	=> L10n::t('Notification Settings'),
		'$activity_options' => L10n::t('By default post a status message when:'),
		'$post_newfriend' => ['post_newfriend',  L10n::t('accepting a friend request'), $post_newfriend, ''],
		'$post_joingroup' => ['post_joingroup',  L10n::t('joining a forum/community'), $post_joingroup, ''],
		'$post_profilechange' => ['post_profilechange',  L10n::t('making an <em>interesting</em> profile change'), $post_profilechange, ''],
		'$lbl_not' 	=> L10n::t('Send a notification email when:'),
		'$notify1'	=> ['notify1', L10n::t('You receive an introduction'), ($notify & NOTIFY_INTRO), NOTIFY_INTRO, ''],
		'$notify2'	=> ['notify2', L10n::t('Your introductions are confirmed'), ($notify & NOTIFY_CONFIRM), NOTIFY_CONFIRM, ''],
		'$notify3'	=> ['notify3', L10n::t('Someone writes on your profile wall'), ($notify & NOTIFY_WALL), NOTIFY_WALL, ''],
		'$notify4'	=> ['notify4', L10n::t('Someone writes a followup comment'), ($notify & NOTIFY_COMMENT), NOTIFY_COMMENT, ''],
		'$notify5'	=> ['notify5', L10n::t('You receive a private message'), ($notify & NOTIFY_MAIL), NOTIFY_MAIL, ''],
		'$notify6'  => ['notify6', L10n::t('You receive a friend suggestion'), ($notify & NOTIFY_SUGGEST), NOTIFY_SUGGEST, ''],
		'$notify7'  => ['notify7', L10n::t('You are tagged in a post'), ($notify & NOTIFY_TAGSELF), NOTIFY_TAGSELF, ''],
		'$notify8'  => ['notify8', L10n::t('You are poked/prodded/etc. in a post'), ($notify & NOTIFY_POKE), NOTIFY_POKE, ''],

		'$desktop_notifications' => ['desktop_notifications', L10n::t('Activate desktop notifications') , false, L10n::t('Show desktop popup on new notifications')],

		'$email_textonly' => ['email_textonly', L10n::t('Text-only notification emails'),
									PConfig::get(local_user(), 'system', 'email_textonly'),
									L10n::t('Send text only notification emails, without the html part')],

		'$detailed_notif' => ['detailed_notif', L10n::t('Show detailled notifications'),
									PConfig::get(local_user(), 'system', 'detailed_notif'),
									L10n::t('Per default, notifications are condensed to a single notification per item. When enabled every notification is displayed.')],

		'$h_advn' => L10n::t('Advanced Account/Page Type Settings'),
		'$h_descadvn' => L10n::t('Change the behaviour of this account for special situations'),
		'$pagetype' => $pagetype,

		'$relocate' => L10n::t('Relocate'),
		'$relocate_text' => L10n::t("If you have moved this profile from another server, and some of your contacts don't receive your updates, try pushing this button."),
		'$relocate_button' => L10n::t("Resend relocate message to contacts"),

	]);

	Addon::callHooks('settings_form', $o);

	$o .= '</form>' . "\r\n";

	return $o;

}
