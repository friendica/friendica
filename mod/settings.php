<?php
/**
 * @file mod/settings.php
 */

use Friendica\App;
use Friendica\BaseModule;
use Friendica\Content\Feature;
use Friendica\Content\Nav;
use Friendica\Core\ACL;
use Friendica\Core\Config;
use Friendica\Core\Hook;
use Friendica\Core\L10n;
use Friendica\Core\Logger;
use Friendica\Core\PConfig;
use Friendica\Core\Renderer;
use Friendica\Core\Session;
use Friendica\Core\Theme;
use Friendica\Core\Worker;
use Friendica\Database\DBA;
use Friendica\Model\Contact;
use Friendica\Model\GContact;
use Friendica\Model\Group;
use Friendica\Model\User;
use Friendica\Module\Security\Login;
use Friendica\Protocol\Email;
use Friendica\Registry\App as A;
use Friendica\Registry\Util;
use Friendica\Util\Strings;
use Friendica\Util\Temporal;
use Friendica\Worker\Delivery;

function get_theme_config_file(App $a, $theme)
{
	$theme = Strings::sanitizeFilePathItem($theme);

	$base_theme = $a->theme_info['extends'] ?? '';

	if (file_exists("view/theme/$theme/config.php")) {
		return "view/theme/$theme/config.php";
	}
	if ($base_theme && file_exists("view/theme/$base_theme/config.php")) {
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

	$tpl                         = Renderer::getMarkupTemplate('settings/head.tpl');
	A::page()['htmlhead'] .= Renderer::replaceMacros($tpl, [
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

	$tabs[] = [
		'label' => L10n::t('Two-factor authentication'),
		'url' => 'settings/2fa',
		'selected' => (($a->argc > 1) && ($a->argv[1] === '2fa') ? 'active' : ''),
		'accesskey' => 'o',
	];

	$tabs[] =	[
		'label'	=> L10n::t('Profiles'),
		'url' 	=> 'profiles',
		'selected'	=> (($a->argc == 1) && ($a->argv[0] === 'profiles')?'active':''),
		'accesskey' => 'p',
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
		'url' 	=> 'settings/delegation',
		'selected'	=> (($a->argc > 1) && ($a->argv[1] === 'delegation')?'active':''),
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
		'url' => 'settings/userexport',
		'selected' => (($a->argc > 1) && ($a->argv[1] === 'userexport')?'active':''),
		'accesskey' => 'e',
	];

	$tabs[] =	[
		'label' => L10n::t('Remove account'),
		'url' => 'removeme',
		'selected' => (($a->argc == 1) && ($a->argv[0] === 'removeme')?'active':''),
		'accesskey' => 'r',
	];


	$tabtpl                   = Renderer::getMarkupTemplate("generic_links_widget.tpl");
	A::page()['aside'] = Renderer::replaceMacros($tabtpl, [
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

	if (!empty($_SESSION['submanage'])) {
		return;
	}

	if (count($a->user) && !empty($a->user['uid']) && $a->user['uid'] != local_user()) {
		notice(L10n::t('Permission denied.') . EOL);
		return;
	}

	$old_page_flags = $a->user['page-flags'];

	if (($a->argc > 1) && ($a->argv[1] === 'oauth') && !empty($_POST['remove'])) {
		BaseModule::checkFormSecurityTokenRedirectOnError('/settings/oauth', 'settings_oauth');

		$key = $_POST['remove'];
		DBA::delete('tokens', ['id' => $key, 'uid' => local_user()]);
		A::baseUrl()->redirect('settings/oauth/', true);
		return;
	}

	if (($a->argc > 2) && ($a->argv[1] === 'oauth')  && ($a->argv[2] === 'edit'||($a->argv[2] === 'add')) && !empty($_POST['submit'])) {
		BaseModule::checkFormSecurityTokenRedirectOnError('/settings/oauth', 'settings_oauth');

		$name     = $_POST['name']     ?? '';
		$key      = $_POST['key']      ?? '';
		$secret   = $_POST['secret']   ?? '';
		$redirect = $_POST['redirect'] ?? '';
		$icon     = $_POST['icon']     ?? '';

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
					DBA::escape($key),
					DBA::escape($secret),
					DBA::escape($name),
					DBA::escape($redirect),
					DBA::escape($icon),
					local_user(),
					DBA::escape($key)
				);
			} else {
				q("INSERT INTO clients
							(client_id, pw, name, redirect_uri, icon, uid)
						VALUES ('%s', '%s', '%s', '%s', '%s',%d)",
					DBA::escape($key),
					DBA::escape($secret),
					DBA::escape($name),
					DBA::escape($redirect),
					DBA::escape($icon),
					local_user()
				);
			}
		}
		A::baseUrl()->redirect('settings/oauth/', true);
		return;
	}

	if (($a->argc > 1) && ($a->argv[1] == 'addon')) {
		BaseModule::checkFormSecurityTokenRedirectOnError('/settings/addon', 'settings_addon');

		Hook::callAll('addon_settings_post', $_POST);
		return;
	}

	if (($a->argc > 1) && ($a->argv[1] == 'connectors')) {
		BaseModule::checkFormSecurityTokenRedirectOnError('/settings/connectors', 'settings_connectors');

		if (!empty($_POST['general-submit'])) {
			PConfig::set(local_user(), 'system', 'accept_only_sharer', intval($_POST['accept_only_sharer']));
			PConfig::set(local_user(), 'system', 'disable_cw', intval($_POST['disable_cw']));
			PConfig::set(local_user(), 'system', 'no_intelligent_shortening', intval($_POST['no_intelligent_shortening']));
			PConfig::set(local_user(), 'system', 'attach_link_title', intval($_POST['attach_link_title']));
			PConfig::set(local_user(), 'system', 'ostatus_autofriend', intval($_POST['snautofollow']));
			PConfig::set(local_user(), 'ostatus', 'default_group', $_POST['group-selection']);
			PConfig::set(local_user(), 'ostatus', 'legacy_contact', $_POST['legacy_contact']);
		} elseif (!empty($_POST['imap-submit'])) {
			$mail_server       =                 $_POST['mail_server']       ?? '';
			$mail_port         =                 $_POST['mail_port']         ?? '';
			$mail_ssl          = strtolower(trim($_POST['mail_ssl']          ?? ''));
			$mail_user         =                 $_POST['mail_user']         ?? '';
			$mail_pass         =            trim($_POST['mail_pass']         ?? '');
			$mail_action       =            trim($_POST['mail_action']       ?? '');
			$mail_movetofolder =            trim($_POST['mail_movetofolder'] ?? '');
			$mail_replyto      =                 $_POST['mail_replyto']      ?? '';
			$mail_pubmail      =                 $_POST['mail_pubmail']      ?? '';

			if (
				!Config::get('system', 'dfrn_only')
				&& function_exists('imap_open')
				&& !Config::get('system', 'imap_disabled')
			) {
				$failed = false;
				$r = q("SELECT * FROM `mailacct` WHERE `uid` = %d LIMIT 1",
					intval(local_user())
				);
				if (!DBA::isResult($r)) {
					DBA::insert('mailacct', ['uid' => local_user()]);
				}
				if (strlen($mail_pass)) {
					$pass = '';
					openssl_public_encrypt($mail_pass, $pass, $a->user['pubkey']);
					DBA::update('mailacct', ['pass' => bin2hex($pass)], ['uid' => local_user()]);
				}
				$r = q("UPDATE `mailacct` SET `server` = '%s', `port` = %d, `ssltype` = '%s', `user` = '%s',
					`action` = %d, `movetofolder` = '%s',
					`mailbox` = 'INBOX', `reply_to` = '%s', `pubmail` = %d WHERE `uid` = %d",
					DBA::escape($mail_server),
					intval($mail_port),
					DBA::escape($mail_ssl),
					DBA::escape($mail_user),
					intval($mail_action),
					DBA::escape($mail_movetofolder),
					DBA::escape($mail_replyto),
					intval($mail_pubmail),
					intval(local_user())
				);
				Logger::log("mail: updating mailaccount. Response: ".print_r($r, true));
				$r = q("SELECT * FROM `mailacct` WHERE `uid` = %d LIMIT 1",
					intval(local_user())
				);
				if (DBA::isResult($r)) {
					$eacct = $r[0];
					$mb = Email::constructMailboxName($eacct);

					if (strlen($eacct['server'])) {
						$dcrpass = '';
						openssl_private_decrypt(hex2bin($eacct['pass']), $dcrpass, $a->user['prvkey']);
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

		Hook::callAll('connector_settings_post', $_POST);
		return;
	}

	if (($a->argc > 1) && ($a->argv[1] === 'features')) {
		BaseModule::checkFormSecurityTokenRedirectOnError('/settings/features', 'settings_features');
		foreach ($_POST as $k => $v) {
			if (strpos($k, 'feature_') === 0) {
				PConfig::set(local_user(), 'feature', substr($k, 8), ((intval($v)) ? 1 : 0));
			}
		}
		info(L10n::t('Features updated') . EOL);
		return;
	}

	if (($a->argc > 1) && ($a->argv[1] === 'display')) {
		BaseModule::checkFormSecurityTokenRedirectOnError('/settings/display', 'settings_display');

		$theme              = !empty($_POST['theme'])              ? Strings::escapeTags(trim($_POST['theme']))        : $a->user['theme'];
		$mobile_theme       = !empty($_POST['mobile_theme'])       ? Strings::escapeTags(trim($_POST['mobile_theme'])) : '';
		$nosmile            = !empty($_POST['nosmile'])            ? intval($_POST['nosmile'])            : 0;
		$first_day_of_week  = !empty($_POST['first_day_of_week'])  ? intval($_POST['first_day_of_week'])  : 0;
		$noinfo             = !empty($_POST['noinfo'])             ? intval($_POST['noinfo'])             : 0;
		$infinite_scroll    = !empty($_POST['infinite_scroll'])    ? intval($_POST['infinite_scroll'])    : 0;
		$no_auto_update     = !empty($_POST['no_auto_update'])     ? intval($_POST['no_auto_update'])     : 0;
		$bandwidth_saver    = !empty($_POST['bandwidth_saver'])    ? intval($_POST['bandwidth_saver'])    : 0;
		$no_smart_threading = !empty($_POST['no_smart_threading']) ? intval($_POST['no_smart_threading']) : 0;
		$nowarn_insecure    = !empty($_POST['nowarn_insecure'])    ? intval($_POST['nowarn_insecure'])    : 0;
		$browser_update     = !empty($_POST['browser_update'])     ? intval($_POST['browser_update'])     : 0;
		if ($browser_update != -1) {
			$browser_update = $browser_update * 1000;
			if ($browser_update < 10000) {
				$browser_update = 10000;
			}
		}

		$itemspage_network = !empty($_POST['itemspage_network'])  ? intval($_POST['itemspage_network'])  : 40;
		if ($itemspage_network > 100) {
			$itemspage_network = 100;
		}
		$itemspage_mobile_network = !empty($_POST['itemspage_mobile_network']) ? intval($_POST['itemspage_mobile_network']) : 20;
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
		PConfig::set(local_user(), 'system', 'no_smart_threading'      , $no_smart_threading);

		if (in_array($theme, Theme::getAllowedList())) {
			if ($theme == $a->user['theme']) {
				// call theme_post only if theme has not been changed
				if (($themeconfigfile = get_theme_config_file($a, $theme)) !== null) {
					require_once $themeconfigfile;
					theme_post($a);
				}
			} else {
				DBA::update('user', ['theme' => $theme], ['uid' => local_user()]);
			}
		} else {
			notice(L10n::t('The theme you chose isn\'t available.'));
		}

		Hook::callAll('display_settings_post', $_POST);
		A::baseUrl()->redirect('settings/display');
		return; // NOTREACHED
	}

	BaseModule::checkFormSecurityTokenRedirectOnError('/settings', 'settings');

	// Import Contacts from CSV file
	if (!empty($_POST['importcontact-submit'])) {
		if (isset($_FILES['importcontact-filename'])) {
			// was there an error
			if ($_FILES['importcontact-filename']['error'] > 0) {
				Logger::notice('Contact CSV file upload error');
				info(L10n::t('Contact CSV file upload error'));
			} else {
				$csvArray = array_map('str_getcsv', file($_FILES['importcontact-filename']['tmp_name']));
				// import contacts
				foreach ($csvArray as $csvRow) {
					// The 1st row may, or may not contain the headers of the table
					// We expect the 1st field of the row to contain either the URL
					// or the handle of the account, therefore we check for either
					// "http" or "@" to be present in the string.
					// All other fields from the row will be ignored
					if ((strpos($csvRow[0],'@') !== false) || (strpos($csvRow[0],'http') !== false)) {
						$arr = Contact::createFromProbe($_SESSION['uid'], $csvRow[0], '', false);
					}
				}
				info(L10n::t('Importing Contacts done'));
				// delete temp file
				unlink($filename);
			}
		}
	}

	if (!empty($_POST['resend_relocate'])) {
		Worker::add(PRIORITY_HIGH, 'Notifier', Delivery::RELOCATION, local_user());
		info(L10n::t("Relocate message has been send to your contacts"));
		A::baseUrl()->redirect('settings');
	}

	Hook::callAll('settings_post', $_POST);

	if (!empty($_POST['password']) || !empty($_POST['confirm'])) {
		$newpass = $_POST['password'];
		$confirm = $_POST['confirm'];

		try {
			if ($newpass != $confirm) {
				throw new Exception(L10n::t('Passwords do not match.'));
			}

			//  check if the old password was supplied correctly before changing it to the new value
			User::getIdFromPasswordAuthentication(local_user(), $_POST['opassword']);

			$result = User::updatePassword(local_user(), $newpass);
			if (!DBA::isResult($result)) {
				throw new Exception(L10n::t('Password update failed. Please try again.'));
			}

			info(L10n::t('Password changed.'));
		} catch (Exception $e) {
			notice($e->getMessage());
			notice(L10n::t('Password unchanged.'));
		}
	}

	$username         = (!empty($_POST['username'])   ? Strings::escapeTags(trim($_POST['username']))     : '');
	$email            = (!empty($_POST['email'])      ? Strings::escapeTags(trim($_POST['email']))        : '');
	$timezone         = (!empty($_POST['timezone'])   ? Strings::escapeTags(trim($_POST['timezone']))     : '');
	$language         = (!empty($_POST['language'])   ? Strings::escapeTags(trim($_POST['language']))     : '');

	$defloc           = (!empty($_POST['defloc'])     ? Strings::escapeTags(trim($_POST['defloc']))       : '');
	$maxreq           = (!empty($_POST['maxreq'])     ? intval($_POST['maxreq'])             : 0);
	$expire           = (!empty($_POST['expire'])     ? intval($_POST['expire'])             : 0);
	$def_gid          = (!empty($_POST['group-selection']) ? intval($_POST['group-selection']) : 0);


	$expire_items     = (!empty($_POST['expire_items']) ? intval($_POST['expire_items'])	 : 0);
	$expire_notes     = (!empty($_POST['expire_notes']) ? intval($_POST['expire_notes'])	 : 0);
	$expire_starred   = (!empty($_POST['expire_starred']) ? intval($_POST['expire_starred']) : 0);
	$expire_photos    = (!empty($_POST['expire_photos'])? intval($_POST['expire_photos'])	 : 0);
	$expire_network_only    = (!empty($_POST['expire_network_only'])? intval($_POST['expire_network_only'])	 : 0);

	$delete_openid    = ((!empty($_POST['delete_openid']) && (intval($_POST['delete_openid']) == 1)) ? 1: 0);

	$allow_location   = ((!empty($_POST['allow_location']) && (intval($_POST['allow_location']) == 1)) ? 1: 0);
	$publish          = ((!empty($_POST['profile_in_directory']) && (intval($_POST['profile_in_directory']) == 1)) ? 1: 0);
	$net_publish      = ((!empty($_POST['profile_in_netdirectory']) && (intval($_POST['profile_in_netdirectory']) == 1)) ? 1: 0);
	$old_visibility   = ((!empty($_POST['visibility']) && (intval($_POST['visibility']) == 1)) ? 1 : 0);
	$account_type     = ((!empty($_POST['account-type']) && (intval($_POST['account-type']))) ? intval($_POST['account-type']) : 0);
	$page_flags       = ((!empty($_POST['page-flags']) && (intval($_POST['page-flags']))) ? intval($_POST['page-flags']) : 0);
	$blockwall        = ((!empty($_POST['blockwall']) && (intval($_POST['blockwall']) == 1)) ? 0: 1); // this setting is inverted!
	$blocktags        = ((!empty($_POST['blocktags']) && (intval($_POST['blocktags']) == 1)) ? 0: 1); // this setting is inverted!
	$unkmail          = ((!empty($_POST['unkmail']) && (intval($_POST['unkmail']) == 1)) ? 1: 0);
	$cntunkmail       = (!empty($_POST['cntunkmail']) ? intval($_POST['cntunkmail']) : 0);
	$suggestme        = (!empty($_POST['suggestme']) ? intval($_POST['suggestme'])  : 0);
	$hide_friends     = (($_POST['hide-friends'] == 1) ? 1: 0);
	$hidewall         = (($_POST['hidewall'] == 1) ? 1: 0);

	$email_textonly   = (($_POST['email_textonly'] == 1) ? 1 : 0);
	$detailed_notif   = (($_POST['detailed_notif'] == 1) ? 1 : 0);

	$notify = 0;

	if (!empty($_POST['notify1'])) {
		$notify += intval($_POST['notify1']);
	}
	if (!empty($_POST['notify2'])) {
		$notify += intval($_POST['notify2']);
	}
	if (!empty($_POST['notify3'])) {
		$notify += intval($_POST['notify3']);
	}
	if (!empty($_POST['notify4'])) {
		$notify += intval($_POST['notify4']);
	}
	if (!empty($_POST['notify5'])) {
		$notify += intval($_POST['notify5']);
	}
	if (!empty($_POST['notify6'])) {
		$notify += intval($_POST['notify6']);
	}
	if (!empty($_POST['notify7'])) {
		$notify += intval($_POST['notify7']);
	}
	if (!empty($_POST['notify8'])) {
		$notify += intval($_POST['notify8']);
	}

	// Adjust the page flag if the account type doesn't fit to the page flag.
	if (($account_type == User::ACCOUNT_TYPE_PERSON) && !in_array($page_flags, [User::PAGE_FLAGS_NORMAL, User::PAGE_FLAGS_SOAPBOX, User::PAGE_FLAGS_FREELOVE])) {
		$page_flags = User::PAGE_FLAGS_NORMAL;
	} elseif (($account_type == User::ACCOUNT_TYPE_ORGANISATION) && !in_array($page_flags, [User::PAGE_FLAGS_SOAPBOX])) {
		$page_flags = User::PAGE_FLAGS_SOAPBOX;
	} elseif (($account_type == User::ACCOUNT_TYPE_NEWS) && !in_array($page_flags, [User::PAGE_FLAGS_SOAPBOX])) {
		$page_flags = User::PAGE_FLAGS_SOAPBOX;
	} elseif (($account_type == User::ACCOUNT_TYPE_COMMUNITY) && !in_array($page_flags, [User::PAGE_FLAGS_COMMUNITY, User::PAGE_FLAGS_PRVGROUP])) {
		$page_flags = User::PAGE_FLAGS_COMMUNITY;
	}

	$err = '';

	if ($username != $a->user['username']) {
		if (strlen($username) > 40) {
			$err .= L10n::t(' Please use a shorter name.');
		}
		if (strlen($username) < 3) {
			$err .= L10n::t(' Name too short.');
		}
	}

	if ($email != $a->user['email']) {
		//  check for the correct password
		if (!User::authenticate(intval(local_user()), $_POST['mpassword'])) {
			$err .= L10n::t('Wrong Password') . EOL;
			$email = $a->user['email'];
		}
		//  check the email is valid
		if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
			$err .= L10n::t('Invalid email.');
		}
		//  ensure new email is not the admin mail
		if (Config::get('config', 'admin_email')) {
			$adminlist = explode(",", str_replace(" ", "", strtolower(Config::get('config', 'admin_email'))));
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

	$aclFormatter = Util::aclFormatter();

	$str_group_allow   = !empty($_POST['group_allow'])   ? $aclFormatter->toString($_POST['group_allow'])   : '';
	$str_contact_allow = !empty($_POST['contact_allow']) ? $aclFormatter->toString($_POST['contact_allow']) : '';
	$str_group_deny    = !empty($_POST['group_deny'])    ? $aclFormatter->toString($_POST['group_deny'])    : '';
	$str_contact_deny  = !empty($_POST['contact_deny'])  ? $aclFormatter->toString($_POST['contact_deny'])  : '';

	PConfig::set(local_user(), 'expire', 'items', $expire_items);
	PConfig::set(local_user(), 'expire', 'notes', $expire_notes);
	PConfig::set(local_user(), 'expire', 'starred', $expire_starred);
	PConfig::set(local_user(), 'expire', 'photos', $expire_photos);
	PConfig::set(local_user(), 'expire', 'network_only', $expire_network_only);

	PConfig::set(local_user(), 'system', 'suggestme', $suggestme);

	PConfig::set(local_user(), 'system', 'email_textonly', $email_textonly);
	PConfig::set(local_user(), 'system', 'detailed_notif', $detailed_notif);

	if ($page_flags == User::PAGE_FLAGS_PRVGROUP) {
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

	$fields = ['username' => $username, 'email' => $email, 'timezone' => $timezone,
		'allow_cid' => $str_contact_allow, 'allow_gid' => $str_group_allow, 'deny_cid' => $str_contact_deny, 'deny_gid' => $str_group_deny,
		'notify-flags' => $notify, 'page-flags' => $page_flags, 'account-type' => $account_type, 'default-location' => $defloc,
		'allow_location' => $allow_location, 'maxreq' => $maxreq, 'expire' => $expire, 'def_gid' => $def_gid, 'blockwall' => $blockwall,
		'hidewall' => $hidewall, 'blocktags' => $blocktags, 'unkmail' => $unkmail, 'cntunkmail' => $cntunkmail, 'language' => $language];

	if ($delete_openid) {
		$fields['openid'] = '';
		$fields['openidserver'] = '';
	}

	if (DBA::update('user', $fields, ['uid' => local_user()])) {
		info(L10n::t('Settings updated.') . EOL);
	}

	// clear session language
	unset($_SESSION['language']);

	q("UPDATE `profile`
		SET `publish` = %d,
		`name` = '%s',
		`net-publish` = %d,
		`hide-friends` = %d
		WHERE `is-default` = 1 AND `uid` = %d",
		intval($publish),
		DBA::escape($username),
		intval($net_publish),
		intval($hide_friends),
		intval(local_user())
	);

	Contact::updateSelfFromUserID(local_user());

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

	A::baseUrl()->redirect('settings');
	return; // NOTREACHED
}


function settings_content(App $a)
{
	$o = '';
	Nav::setSelected('settings');

	if (!local_user()) {
		//notice(L10n::t('Permission denied.') . EOL);
		return Login::form();
	}

	if (!empty($_SESSION['submanage'])) {
		notice(L10n::t('Permission denied.') . EOL);
		return;
	}

	if (($a->argc > 1) && ($a->argv[1] === 'oauth')) {
		if (($a->argc > 2) && ($a->argv[2] === 'add')) {
			$tpl = Renderer::getMarkupTemplate('settings/oauth_edit.tpl');
			$o .= Renderer::replaceMacros($tpl, [
				'$form_security_token' => BaseModule::getFormSecurityToken("settings_oauth"),
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
					DBA::escape($a->argv[3]),
					local_user());

			if (!DBA::isResult($r)) {
				notice(L10n::t("You can't edit this application."));
				return;
			}
			$app = $r[0];

			$tpl = Renderer::getMarkupTemplate('settings/oauth_edit.tpl');
			$o .= Renderer::replaceMacros($tpl, [
				'$form_security_token' => BaseModule::getFormSecurityToken("settings_oauth"),
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
			BaseModule::checkFormSecurityTokenRedirectOnError('/settings/oauth', 'settings_oauth', 't');

			DBA::delete('clients', ['client_id' => $a->argv[3], 'uid' => local_user()]);
			A::baseUrl()->redirect('settings/oauth/', true);
			return;
		}

		/// @TODO validate result with DBA::isResult()
		$r = q("SELECT clients.*, tokens.id as oauth_token, (clients.uid=%d) AS my
				FROM clients
				LEFT JOIN tokens ON clients.client_id=tokens.client_id
				WHERE clients.uid IN (%d, 0)",
				local_user(),
				local_user());


		$tpl = Renderer::getMarkupTemplate('settings/oauth.tpl');
		$o .= Renderer::replaceMacros($tpl, [
			'$form_security_token' => BaseModule::getFormSecurityToken("settings_oauth"),
			'$baseurl'	=> A::baseUrl()->get(true),
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
		if (!DBA::isResult($r)) {
			$settings_addons = L10n::t('No Addon settings configured');
		}

		Hook::callAll('addon_settings', $settings_addons);


		$tpl = Renderer::getMarkupTemplate('settings/addons.tpl');
		$o .= Renderer::replaceMacros($tpl, [
			'$form_security_token' => BaseModule::getFormSecurityToken("settings_addon"),
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

		$tpl = Renderer::getMarkupTemplate('settings/features.tpl');
		$o .= Renderer::replaceMacros($tpl, [
			'$form_security_token' => BaseModule::getFormSecurityToken("settings_features"),
			'$title'               => L10n::t('Additional Features'),
			'$features'            => $arr,
			'$submit'              => L10n::t('Save Settings'),
		]);
		return $o;
	}

	if (($a->argc > 1) && ($a->argv[1] === 'connectors')) {
		$accept_only_sharer        = intval(PConfig::get(local_user(), 'system', 'accept_only_sharer'));
		$disable_cw                = intval(PConfig::get(local_user(), 'system', 'disable_cw'));
		$no_intelligent_shortening = intval(PConfig::get(local_user(), 'system', 'no_intelligent_shortening'));
		$attach_link_title         = intval(PConfig::get(local_user(), 'system', 'attach_link_title'));
		$ostatus_autofriend        = intval(PConfig::get(local_user(), 'system', 'ostatus_autofriend'));
		$default_group             = PConfig::get(local_user(), 'ostatus', 'default_group');
		$legacy_contact            = PConfig::get(local_user(), 'ostatus', 'legacy_contact');

		if (!empty($legacy_contact)) {
			/// @todo Isn't it supposed to be a $a->internalRedirect() call?
			A::page()['htmlhead'] = '<meta http-equiv="refresh" content="0; URL=' . A::baseUrl() . '/ostatus_subscribe?url=' . urlencode($legacy_contact) . '">';
		}

		$settings_connectors = '';
		Hook::callAll('connector_settings', $settings_connectors);

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

		$mail_server       = ((DBA::isResult($r)) ? $r[0]['server'] : '');
		$mail_port         = ((DBA::isResult($r) && intval($r[0]['port'])) ? intval($r[0]['port']) : '');
		$mail_ssl          = ((DBA::isResult($r)) ? $r[0]['ssltype'] : '');
		$mail_user         = ((DBA::isResult($r)) ? $r[0]['user'] : '');
		$mail_replyto      = ((DBA::isResult($r)) ? $r[0]['reply_to'] : '');
		$mail_pubmail      = ((DBA::isResult($r)) ? $r[0]['pubmail'] : 0);
		$mail_action       = ((DBA::isResult($r)) ? $r[0]['action'] : 0);
		$mail_movetofolder = ((DBA::isResult($r)) ? $r[0]['movetofolder'] : '');
		$mail_chk          = ((DBA::isResult($r)) ? $r[0]['last_check'] : DBA::NULL_DATETIME);


		$tpl = Renderer::getMarkupTemplate('settings/connectors.tpl');

		$mail_disabled_message = ($mail_disabled ? L10n::t('Email access is disabled on this site.') : '');

		$ssl_options = ['TLS' => 'TLS', 'SSL' => 'SSL'];

		if (Config::get('system', 'insecure_imap')) {
			$ssl_options['notls'] = L10n::t('None');
		}

		$o .= Renderer::replaceMacros($tpl, [
			'$form_security_token' => BaseModule::getFormSecurityToken("settings_connectors"),

			'$title'	=> L10n::t('Social Networks'),

			'$diasp_enabled' => $diasp_enabled,
			'$ostat_enabled' => $ostat_enabled,

			'$general_settings' => L10n::t('General Social Media Settings'),
			'$accept_only_sharer' => ['accept_only_sharer', L10n::t('Accept only top level posts by contacts you follow'), $accept_only_sharer, L10n::t('The system does an auto completion of threads when a comment arrives. This has got the side effect that you can receive posts that had been started by a non-follower but had been commented by someone you follow. This setting deactivates this behaviour. When activated, you strictly only will receive posts from people you really do follow.')],
			'$disable_cw' => ['disable_cw', L10n::t('Disable Content Warning'), $disable_cw, L10n::t('Users on networks like Mastodon or Pleroma are able to set a content warning field which collapse their post by default. This disables the automatic collapsing and sets the content warning as the post title. Doesn\'t affect any other content filtering you eventually set up.')],
			'$no_intelligent_shortening' => ['no_intelligent_shortening', L10n::t('Disable intelligent shortening'), $no_intelligent_shortening, L10n::t('Normally the system tries to find the best link to add to shortened posts. If this option is enabled then every shortened post will always point to the original friendica post.')],
			'$attach_link_title' => ['attach_link_title', L10n::t('Attach the link title'), $attach_link_title, L10n::t('When activated, the title of the attached link will be added as a title on posts to Diaspora. This is mostly helpful with "remote-self" contacts that share feed content.')],
			'$ostatus_autofriend' => ['snautofollow', L10n::t("Automatically follow any GNU Social \x28OStatus\x29 followers/mentioners"), $ostatus_autofriend, L10n::t('If you receive a message from an unknown OStatus user, this option decides what to do. If it is checked, a new contact will be created for every unknown user.')],
			'$default_group' => Group::displayGroupSelection(local_user(), $default_group, L10n::t("Default group for OStatus contacts")),
			'$legacy_contact' => ['legacy_contact', L10n::t('Your legacy GNU Social account'), $legacy_contact, L10n::t("If you enter your old GNU Social/Statusnet account name here \x28in the format user@domain.tld\x29, your contacts will be added automatically. The field will be emptied when done.")],

			'$repair_ostatus_url' => A::baseUrl() . '/repair_ostatus',
			'$repair_ostatus_text' => L10n::t('Repair OStatus subscriptions'),

			'$settings_connectors' => $settings_connectors,

			'$h_imap' => L10n::t('Email/Mailbox Setup'),
			'$imap_desc' => L10n::t("If you wish to communicate with email contacts using this service \x28optional\x29, please specify how to connect to your mailbox."),
			'$imap_lastcheck' => ['imap_lastcheck', L10n::t('Last successful email check:'), $mail_chk, ''],
			'$mail_disabled' => $mail_disabled_message,
			'$mail_server'	=> ['mail_server',	L10n::t('IMAP server name:'), $mail_server, ''],
			'$mail_port'	=> ['mail_port', 	L10n::t('IMAP port:'), $mail_port, ''],
			'$mail_ssl'	=> ['mail_ssl',		L10n::t('Security:'), strtoupper($mail_ssl), '', $ssl_options],
			'$mail_user'	=> ['mail_user',	L10n::t('Email login name:'), $mail_user, ''],
			'$mail_pass'	=> ['mail_pass',	L10n::t('Email password:'), '', ''],
			'$mail_replyto'	=> ['mail_replyto',	L10n::t('Reply-to address:'), $mail_replyto, 'Optional'],
			'$mail_pubmail'	=> ['mail_pubmail',	L10n::t('Send public posts to all email contacts:'), $mail_pubmail, ''],
			'$mail_action'	=> ['mail_action',	L10n::t('Action after import:'), $mail_action, '', [0 => L10n::t('None'), 1 => L10n::t('Delete'), 2 => L10n::t('Mark as seen'), 3 => L10n::t('Move to folder')]],
			'$mail_movetofolder' => ['mail_movetofolder', L10n::t('Move to folder:'), $mail_movetofolder, ''],
			'$submit' => L10n::t('Save Settings'),
		]);

		Hook::callAll('display_settings', $o);
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

		$allowed_themes = Theme::getAllowedList();

		$themes = [];
		$mobile_themes = ["---" => L10n::t('No special theme for mobile devices')];
		foreach ($allowed_themes as $theme) {
			$is_experimental = file_exists('view/theme/' . $theme . '/experimental');
			$is_unsupported  = file_exists('view/theme/' . $theme . '/unsupported');
			$is_mobile       = file_exists('view/theme/' . $theme . '/mobile');
			if (!$is_experimental || ($is_experimental && (Config::get('experimentals', 'exp_themes')==1 || is_null(Config::get('experimentals', 'exp_themes'))))) {
				$theme_name = ucfirst($theme);
				if ($is_unsupported) {
					$theme_name = L10n::t('%s - (Unsupported)', $theme_name);
				} elseif ($is_experimental) {
					$theme_name = L10n::t('%s - (Experimental)', $theme_name);
				}

				if ($is_mobile) {
					$mobile_themes[$theme] = $theme_name;
				} else {
					$themes[$theme] = $theme_name;
				}
			}
		}

		$theme_selected        = $a->user['theme'] ?: $default_theme;
		$mobile_theme_selected = Session::get('mobile-theme', $default_mobile_theme);

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
		$no_smart_threading = PConfig::get(local_user(), 'system', 'no_smart_threading', 0);

		$theme_config = "";
		if (($themeconfigfile = get_theme_config_file($a, $theme_selected)) !== null) {
			require_once $themeconfigfile;
			$theme_config = theme_content($a);
		}

		$tpl = Renderer::getMarkupTemplate('settings/display.tpl');
		$o = Renderer::replaceMacros($tpl, [
			'$ptitle' 	=> L10n::t('Display Settings'),
			'$form_security_token' => BaseModule::getFormSecurityToken("settings_display"),
			'$submit' 	=> L10n::t('Save Settings'),
			'$baseurl' => A::baseUrl()->get(true),
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
			'$bandwidth_saver' => ['bandwidth_saver', L10n::t('Bandwidth Saver Mode'), $bandwidth_saver, L10n::t('When enabled, embedded content is not displayed on automatic updates, they only show on page reload.')],
			'$no_smart_threading' => ['no_smart_threading', L10n::t('Disable Smart Threading'), $no_smart_threading, L10n::t('Disable the automatic suppression of extraneous thread indentation.')],

			'$d_tset' => L10n::t('General Theme Settings'),
			'$d_ctset' => L10n::t('Custom Theme Settings'),
			'$d_cset' => L10n::t('Content Settings'),
			'stitle' => L10n::t('Theme settings'),
			'$theme_config' => $theme_config,
		]);

		return $o;
	}


	/*
	 * ACCOUNT SETTINGS
	 */

	$profile = DBA::selectFirst('profile', [], ['is-default' => true, 'uid' => local_user()]);
	if (!DBA::isResult($profile)) {
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

	// nowarn_insecure

	if (!strlen($a->user['timezone'])) {
		$timezone = date_default_timezone_get();
	}

	// Set the account type to "Community" when the page is a community page but the account type doesn't fit
	// This is only happening on the first visit after the update
	if (in_array($a->user['page-flags'], [User::PAGE_FLAGS_COMMUNITY, User::PAGE_FLAGS_PRVGROUP]) &&
		($a->user['account-type'] != User::ACCOUNT_TYPE_COMMUNITY))
		$a->user['account-type'] = User::ACCOUNT_TYPE_COMMUNITY;

	$pageset_tpl = Renderer::getMarkupTemplate('settings/pagetypes.tpl');

	$pagetype = Renderer::replaceMacros($pageset_tpl, [
		'$account_types'	=> L10n::t("Account Types"),
		'$user' 		=> L10n::t("Personal Page Subtypes"),
		'$community'		=> L10n::t("Community Forum Subtypes"),
		'$account_type'		=> $a->user['account-type'],
		'$type_person'		=> User::ACCOUNT_TYPE_PERSON,
		'$type_organisation' 	=> User::ACCOUNT_TYPE_ORGANISATION,
		'$type_news'		=> User::ACCOUNT_TYPE_NEWS,
		'$type_community' 	=> User::ACCOUNT_TYPE_COMMUNITY,

		'$account_person' 	=> ['account-type', L10n::t('Personal Page'), User::ACCOUNT_TYPE_PERSON,
									L10n::t('Account for a personal profile.'),
									($a->user['account-type'] == User::ACCOUNT_TYPE_PERSON)],

		'$account_organisation'	=> ['account-type', L10n::t('Organisation Page'), User::ACCOUNT_TYPE_ORGANISATION,
									L10n::t('Account for an organisation that automatically approves contact requests as "Followers".'),
									($a->user['account-type'] == User::ACCOUNT_TYPE_ORGANISATION)],

		'$account_news'		=> ['account-type', L10n::t('News Page'), User::ACCOUNT_TYPE_NEWS,
									L10n::t('Account for a news reflector that automatically approves contact requests as "Followers".'),
									($a->user['account-type'] == User::ACCOUNT_TYPE_NEWS)],

		'$account_community' 	=> ['account-type', L10n::t('Community Forum'), User::ACCOUNT_TYPE_COMMUNITY,
									L10n::t('Account for community discussions.'),
									($a->user['account-type'] == User::ACCOUNT_TYPE_COMMUNITY)],

		'$page_normal'		=> ['page-flags', L10n::t('Normal Account Page'), User::PAGE_FLAGS_NORMAL,
									L10n::t('Account for a regular personal profile that requires manual approval of "Friends" and "Followers".'),
									($a->user['page-flags'] == User::PAGE_FLAGS_NORMAL)],

		'$page_soapbox' 	=> ['page-flags', L10n::t('Soapbox Page'), User::PAGE_FLAGS_SOAPBOX,
									L10n::t('Account for a public profile that automatically approves contact requests as "Followers".'),
									($a->user['page-flags'] == User::PAGE_FLAGS_SOAPBOX)],

		'$page_community'	=> ['page-flags', L10n::t('Public Forum'), User::PAGE_FLAGS_COMMUNITY,
									L10n::t('Automatically approves all contact requests.'),
									($a->user['page-flags'] == User::PAGE_FLAGS_COMMUNITY)],

		'$page_freelove' 	=> ['page-flags', L10n::t('Automatic Friend Page'), User::PAGE_FLAGS_FREELOVE,
									L10n::t('Account for a popular profile that automatically approves contact requests as "Friends".'),
									($a->user['page-flags'] == User::PAGE_FLAGS_FREELOVE)],

		'$page_prvgroup' 	=> ['page-flags', L10n::t('Private Forum [Experimental]'), User::PAGE_FLAGS_PRVGROUP,
									L10n::t('Requires manual approval of contact requests.'),
									($a->user['page-flags'] == User::PAGE_FLAGS_PRVGROUP)],


	]);

	$noid = Config::get('system', 'no_openid');

	if ($noid) {
		$openid_field = false;
	} else {
		$openid_field = ['openid_url', L10n::t('OpenID:'), $openid, L10n::t("\x28Optional\x29 Allow this OpenID to login to this account."), "", "readonly", "url"];
	}

	$opt_tpl = Renderer::getMarkupTemplate("field_yesno.tpl");
	if (Config::get('system', 'publish_all')) {
		$profile_in_dir = '<input type="hidden" name="profile_in_directory" value="1" />';
	} else {
		$profile_in_dir = Renderer::replaceMacros($opt_tpl, [
			'$field' => ['profile_in_directory', L10n::t('Publish your default profile in your local site directory?'), $profile['publish'], L10n::t('Your profile will be published in this node\'s <a href="%s">local directory</a>. Your profile details may be publicly visible depending on the system settings.', A::baseUrl() . '/directory'), [L10n::t('No'), L10n::t('Yes')]]
		]);
	}

	if (strlen(Config::get('system', 'directory'))) {
		$profile_in_net_dir = Renderer::replaceMacros($opt_tpl, [
			'$field' => ['profile_in_netdirectory', L10n::t('Publish your default profile in the global social directory?'), $profile['net-publish'], L10n::t('Your profile will be published in the global friendica directories (e.g. <a href="%s">%s</a>). Your profile will be visible in public.', Config::get('system', 'directory'), Config::get('system', 'directory'))	. " " . L10n::t("This setting also determines whether Friendica will inform search engines that your profile should be indexed or not. Third-party search engines may or may not respect this setting."), [L10n::t('No'), L10n::t('Yes')]]
		]);
	} else {
		$profile_in_net_dir = '';
	}

	$hide_friends = Renderer::replaceMacros($opt_tpl, [
		'$field' => ['hide-friends', L10n::t('Hide your contact/friend list from viewers of your default profile?'), $profile['hide-friends'], L10n::t('Your contact list won\'t be shown in your default profile page. You can decide to show your contact list separately for each additional profile you create'), [L10n::t('No'), L10n::t('Yes')]],
	]);

	$hide_wall = Renderer::replaceMacros($opt_tpl, [
		'$field' => ['hidewall', L10n::t('Hide your profile details from anonymous viewers?'), $a->user['hidewall'], L10n::t('Anonymous visitors will only see your profile picture, your display name and the nickname you are using on your profile page. Your public posts and replies will still be accessible by other means.'), [L10n::t('No'), L10n::t('Yes')]],
	]);

	$blockwall = Renderer::replaceMacros($opt_tpl, [
		'$field' => ['blockwall', L10n::t('Allow friends to post to your profile page?'), (intval($a->user['blockwall']) ? '0' : '1'), L10n::t('Your contacts may write posts on your profile wall. These posts will be distributed to your contacts'), [L10n::t('No'), L10n::t('Yes')]],
	]);

	$blocktags = Renderer::replaceMacros($opt_tpl, [
		'$field' => ['blocktags', L10n::t('Allow friends to tag your posts?'), (intval($a->user['blocktags']) ? '0' : '1'), L10n::t('Your contacts can add additional tags to your posts.'), [L10n::t('No'), L10n::t('Yes')]],
	]);

	$suggestme = Renderer::replaceMacros($opt_tpl, [
		'$field' => ['suggestme', L10n::t('Allow us to suggest you as a potential friend to new members?'), $suggestme, L10n::t('If you like, Friendica may suggest new members to add you as a contact.'), [L10n::t('No'), L10n::t('Yes')]],
	]);

	$unkmail = Renderer::replaceMacros($opt_tpl, [
		'$field' => ['unkmail', L10n::t('Permit unknown people to send you private mail?'), $unkmail, L10n::t('Friendica network users may send you private messages even if they are not in your contact list.'), [L10n::t('No'), L10n::t('Yes')]],
	]);

	if (!$profile['publish'] && !$profile['net-publish']) {
		info(L10n::t('Profile is <strong>not published</strong>.') . EOL);
	}

	$tpl_addr = Renderer::getMarkupTemplate('settings/nick_set.tpl');

	$prof_addr = Renderer::replaceMacros($tpl_addr,[
		'$desc' => L10n::t("Your Identity Address is <strong>'%s'</strong> or '%s'.", $nickname . '@' . A::baseUrl()->getHostname() . A::baseUrl()->getUrlPath(), A::baseUrl() . '/profile/' . $nickname),
		'$basepath' => A::baseUrl()->getHostname()
	]);

	$stpl = Renderer::getMarkupTemplate('settings/settings.tpl');

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
	if (!empty($_REQUEST['public']) && !$_REQUEST['public']) {
		$private_post = 0;
	}

	$query_str = A::args()->getQueryString();
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
	$o .= Renderer::replaceMacros($stpl, [
		'$ptitle' 	=> L10n::t('Account Settings'),

		'$submit' 	=> L10n::t('Save Settings'),
		'$baseurl' => A::baseUrl()->get(true),
		'$uid' => local_user(),
		'$form_security_token' => BaseModule::getFormSecurityToken("settings"),
		'$nickname_block' => $prof_addr,

		'$h_pass' 	=> L10n::t('Password Settings'),
		'$password1'=> ['password', L10n::t('New Password:'), '', L10n::t('Allowed characters are a-z, A-Z, 0-9 and special characters except white spaces, accentuated letters and colon (:).')],
		'$password2'=> ['confirm', L10n::t('Confirm:'), '', L10n::t('Leave password fields blank unless changing')],
		'$password3'=> ['opassword', L10n::t('Current Password:'), '', L10n::t('Your current password to confirm the changes')],
		'$password4'=> ['mpassword', L10n::t('Password:'), '', L10n::t('Your current password to confirm the changes')],
		'$oid_enable' => (!Config::get('system', 'no_openid')),
		'$openid'	=> $openid_field,
		'$delete_openid' => ['delete_openid', L10n::t('Delete OpenID URL'), false, ''],

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
		'$aclselect' => ACL::getFullSelectorHTML(A::page(), $a->user),
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

		'$importcontact' => L10n::t('Import Contacts'),
		'$importcontact_text' => L10n::t('Upload a CSV file that contains the handle of your followed accounts in the first column you exported from the old account.'),
		'$importcontact_button' => L10n::t('Upload File'),
		'$importcontact_maxsize' => Config::get('system', 'max_csv_file_size', 30720), 
		'$relocate' => L10n::t('Relocate'),
		'$relocate_text' => L10n::t("If you have moved this profile from another server, and some of your contacts don't receive your updates, try pushing this button."),
		'$relocate_button' => L10n::t("Resend relocate message to contacts"),

	]);

	Hook::callAll('settings_form', $o);

	$o .= '</form>' . "\r\n";

	return $o;

}
