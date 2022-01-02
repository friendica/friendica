<?php
/**
 * @copyright Copyright (C) 2010-2022, the Friendica project
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
use Friendica\BaseModule;
use Friendica\Content\Feature;
use Friendica\Content\Nav;
use Friendica\Core\ACL;
use Friendica\Core\Hook;
use Friendica\Core\Logger;
use Friendica\Core\Renderer;
use Friendica\Core\Worker;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Group;
use Friendica\Model\Notification;
use Friendica\Model\Profile;
use Friendica\Model\User;
use Friendica\Module\BaseSettings;
use Friendica\Module\Security\Login;
use Friendica\Protocol\Email;
use Friendica\Util\Temporal;
use Friendica\Worker\Delivery;

function settings_init(App $a)
{
	if (!local_user()) {
		notice(DI::l10n()->t('Permission denied.'));
		return;
	}

	BaseSettings::createAside();
}

function settings_post(App $a)
{
	if (!$a->isLoggedIn()) {
		notice(DI::l10n()->t('Permission denied.'));
		return;
	}

	if (!empty($_SESSION['submanage'])) {
		return;
	}

	if ((DI::args()->getArgc() > 1) && (DI::args()->getArgv()[1] == 'addon')) {
		BaseModule::checkFormSecurityTokenRedirectOnError(DI::args()->getQueryString(), 'settings_addon');

		Hook::callAll('addon_settings_post', $_POST);
		DI::baseUrl()->redirect(DI::args()->getQueryString());
		return;
	}

	$user = User::getById($a->getLoggedInUserId());

	if ((DI::args()->getArgc() > 1) && (DI::args()->getArgv()[1] == 'connectors')) {
		BaseModule::checkFormSecurityTokenRedirectOnError(DI::args()->getQueryString(), 'settings_connectors');

		if (!empty($_POST['general-submit'])) {
			DI::pConfig()->set(local_user(), 'system', 'accept_only_sharer', intval($_POST['accept_only_sharer']));
			DI::pConfig()->set(local_user(), 'system', 'disable_cw', !intval($_POST['enable_cw']));
			DI::pConfig()->set(local_user(), 'system', 'no_intelligent_shortening', !intval($_POST['enable_smart_shortening']));
			DI::pConfig()->set(local_user(), 'system', 'simple_shortening', intval($_POST['simple_shortening']));
			DI::pConfig()->set(local_user(), 'system', 'attach_link_title', intval($_POST['attach_link_title']));
			DI::pConfig()->set(local_user(), 'ostatus', 'legacy_contact', $_POST['legacy_contact']);
		} elseif (!empty($_POST['mail-submit'])) {
			$mail_server       =                 $_POST['mail_server']       ?? '';
			$mail_port         =                 $_POST['mail_port']         ?? '';
			$mail_ssl          = strtolower(trim($_POST['mail_ssl']          ?? ''));
			$mail_user         =                 $_POST['mail_user']         ?? '';
			$mail_pass         =            trim($_POST['mail_pass']         ?? '');
			$mail_action       =            trim($_POST['mail_action']       ?? '');
			$mail_movetofolder =            trim($_POST['mail_movetofolder'] ?? '');
			$mail_replyto      =                 $_POST['mail_replyto']      ?? '';
			$mail_pubmail      =                 $_POST['mail_pubmail']      ?? '';

			if (function_exists('imap_open') && !DI::config()->get('system', 'imap_disabled')) {
				if (!DBA::exists('mailacct', ['uid' => local_user()])) {
					DBA::insert('mailacct', ['uid' => local_user()]);
				}
				if (strlen($mail_pass)) {
					$pass = '';
					openssl_public_encrypt($mail_pass, $pass, $user['pubkey']);
					DBA::update('mailacct', ['pass' => bin2hex($pass)], ['uid' => local_user()]);
				}

				$r = DBA::update('mailacct', [
					'server'       => $mail_server,
					'port'         => $mail_port,
					'ssltype'      => $mail_ssl,
					'user'         => $mail_user,
					`action`       => $mail_action,
					'movetofolder' => $mail_movetofolder,
					'mailbox'      => 'INBOX',
					'reply_to'     => $mail_replyto,
					'pubmail'      => $mail_pubmail
				], ['uid' => local_user()]);

				Logger::notice('updating mailaccount', ['response' => $r]);
				$mailacct = DBA::selectFirst('mailacct', [], ['uid' => local_user()]);
				if (DBA::isResult($mailacct)) {
					$mb = Email::constructMailboxName($mailacct);

					if (strlen($mailacct['server'])) {
						$dcrpass = '';
						openssl_private_decrypt(hex2bin($mailacct['pass']), $dcrpass, $user['prvkey']);
						$mbox = Email::connect($mb, $mail_user, $dcrpass);
						unset($dcrpass);
						if (!$mbox) {
							notice(DI::l10n()->t('Failed to connect with email account using the settings provided.'));
						}
					}
				}
			}
		}

		Hook::callAll('connector_settings_post', $_POST);
		DI::baseUrl()->redirect(DI::args()->getQueryString());
		return;
	}

	if ((DI::args()->getArgc() > 1) && (DI::args()->getArgv()[1] === 'features')) {
		BaseModule::checkFormSecurityTokenRedirectOnError('/settings/features', 'settings_features');
		foreach ($_POST as $k => $v) {
			if (strpos($k, 'feature_') === 0) {
				DI::pConfig()->set(local_user(), 'feature', substr($k, 8), ((intval($v)) ? 1 : 0));
			}
		}
		return;
	}

	BaseModule::checkFormSecurityTokenRedirectOnError('/settings', 'settings');

	// Import Contacts from CSV file
	if (!empty($_POST['importcontact-submit'])) {
		if (isset($_FILES['importcontact-filename'])) {
			// was there an error
			if ($_FILES['importcontact-filename']['error'] > 0) {
				Logger::notice('Contact CSV file upload error', ['error' => $_FILES['importcontact-filename']['error']]);
				notice(DI::l10n()->t('Contact CSV file upload error'));
			} else {
				$csvArray = array_map('str_getcsv', file($_FILES['importcontact-filename']['tmp_name']));
				Logger::notice('Import started', ['lines' => count($csvArray)]);
				// import contacts
				foreach ($csvArray as $csvRow) {
					// The 1st row may, or may not contain the headers of the table
					// We expect the 1st field of the row to contain either the URL
					// or the handle of the account, therefore we check for either
					// "http" or "@" to be present in the string.
					// All other fields from the row will be ignored
					if ((strpos($csvRow[0],'@') !== false) || in_array(parse_url($csvRow[0], PHP_URL_SCHEME), ['http', 'https'])) {
						Worker::add(PRIORITY_LOW, 'AddContact', $_SESSION['uid'], $csvRow[0]);
					} else {
						Logger::notice('Invalid account', ['url' => $csvRow[0]]);
					}
				}
				Logger::notice('Import done');

				info(DI::l10n()->t('Importing Contacts done'));
				// delete temp file
				unlink($_FILES['importcontact-filename']['tmp_name']);
			}
		} else {
			Logger::notice('Import triggered, but no import file was found.');
		}

		return;
	}

	if (!empty($_POST['resend_relocate'])) {
		Worker::add(PRIORITY_HIGH, 'Notifier', Delivery::RELOCATION, local_user());
		info(DI::l10n()->t("Relocate message has been send to your contacts"));
		DI::baseUrl()->redirect('settings');
	}

	Hook::callAll('settings_post', $_POST);

	if (!empty($_POST['password']) || !empty($_POST['confirm'])) {
		$newpass = $_POST['password'];
		$confirm = $_POST['confirm'];

		try {
			if ($newpass != $confirm) {
				throw new Exception(DI::l10n()->t('Passwords do not match.'));
			}

			//  check if the old password was supplied correctly before changing it to the new value
			User::getIdFromPasswordAuthentication(local_user(), $_POST['opassword']);

			$result = User::updatePassword(local_user(), $newpass);
			if (!DBA::isResult($result)) {
				throw new Exception(DI::l10n()->t('Password update failed. Please try again.'));
			}

			info(DI::l10n()->t('Password changed.'));
		} catch (Exception $e) {
			notice($e->getMessage());
			notice(DI::l10n()->t('Password unchanged.'));
		}
	}

	$username         = (!empty($_POST['username'])        ? trim($_POST['username'])          : '');
	$email            = (!empty($_POST['email'])           ? trim($_POST['email'])             : '');
	$timezone         = (!empty($_POST['timezone'])        ? trim($_POST['timezone'])          : '');
	$language         = (!empty($_POST['language'])        ? trim($_POST['language'])          : '');

	$defloc           = (!empty($_POST['defloc'])          ? trim($_POST['defloc'])            : '');
	$maxreq           = (!empty($_POST['maxreq'])          ? intval($_POST['maxreq'])          : 0);
	$expire           = (!empty($_POST['expire'])          ? intval($_POST['expire'])          : 0);
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
	$hide_friends     = (($_POST['hide-friends'] == 1) ? 1: 0);
	$hidewall         = (($_POST['hidewall'] == 1) ? 1: 0);
	$unlisted         = (($_POST['unlisted'] == 1) ? 1: 0);
	$accessiblephotos = (($_POST['accessible-photos'] == 1) ? 1: 0);

	$notify_like      = (($_POST['notify_like'] == 1) ? 1 : 0);
	$notify_announce  = (($_POST['notify_announce'] == 1) ? 1 : 0);

	$email_textonly   = (($_POST['email_textonly'] == 1) ? 1 : 0);
	$detailed_notif   = (($_POST['detailed_notif'] == 1) ? 1 : 0);

	$notify_ignored   = (($_POST['notify_ignored'] == 1) ? 1 : 0);

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

	if ($username != $user['username']) {
		if (strlen($username) > 40) {
			$err .= DI::l10n()->t('Please use a shorter name.');
		}
		if (strlen($username) < 3) {
			$err .= DI::l10n()->t('Name too short.');
		}
	}

	if ($email != $user['email']) {
		//  check for the correct password
		try {
			User::getIdFromPasswordAuthentication(local_user(), $_POST['mpassword']);
		} catch (Exception $ex) {
			$err .= DI::l10n()->t('Wrong Password.');
			$email = $user['email'];
		}
		//  check the email is valid
		if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
			$err .= DI::l10n()->t('Invalid email.');
		}
		//  ensure new email is not the admin mail
		if (DI::config()->get('config', 'admin_email')) {
			$adminlist = explode(",", str_replace(" ", "", strtolower(DI::config()->get('config', 'admin_email'))));
			if (in_array(strtolower($email), $adminlist)) {
				$err .= DI::l10n()->t('Cannot change to that email.');
				$email = $user['email'];
			}
		}
	}

	if (strlen($err)) {
		notice($err);
		return;
	}

	if (($timezone != $user['timezone']) && strlen($timezone)) {
		$a->setTimeZone($timezone);
	}

	$aclFormatter = DI::aclFormatter();

	$str_group_allow   = !empty($_POST['group_allow'])   ? $aclFormatter->toString($_POST['group_allow'])   : '';
	$str_contact_allow = !empty($_POST['contact_allow']) ? $aclFormatter->toString($_POST['contact_allow']) : '';
	$str_group_deny    = !empty($_POST['group_deny'])    ? $aclFormatter->toString($_POST['group_deny'])    : '';
	$str_contact_deny  = !empty($_POST['contact_deny'])  ? $aclFormatter->toString($_POST['contact_deny'])  : '';

	DI::pConfig()->set(local_user(), 'expire', 'items', $expire_items);
	DI::pConfig()->set(local_user(), 'expire', 'notes', $expire_notes);
	DI::pConfig()->set(local_user(), 'expire', 'starred', $expire_starred);
	DI::pConfig()->set(local_user(), 'expire', 'photos', $expire_photos);
	DI::pConfig()->set(local_user(), 'expire', 'network_only', $expire_network_only);

	DI::pConfig()->set(local_user(), 'system', 'notify_like', $notify_like);
	DI::pConfig()->set(local_user(), 'system', 'notify_announce', $notify_announce);

	DI::pConfig()->set(local_user(), 'system', 'email_textonly', $email_textonly);
	DI::pConfig()->set(local_user(), 'system', 'detailed_notif', $detailed_notif);
	DI::pConfig()->set(local_user(), 'system', 'notify_ignored', $notify_ignored);
	DI::pConfig()->set(local_user(), 'system', 'unlisted', $unlisted);
	DI::pConfig()->set(local_user(), 'system', 'accessible-photos', $accessiblephotos);

	if ($page_flags == User::PAGE_FLAGS_PRVGROUP) {
		$hidewall = 1;
		if (!$str_contact_allow && !$str_group_allow && !$str_contact_deny && !$str_group_deny) {
			if ($def_gid) {
				info(DI::l10n()->t('Private forum has no privacy permissions. Using default privacy group.'));
				$str_group_allow = '<' . $def_gid . '>';
			} else {
				notice(DI::l10n()->t('Private forum has no privacy permissions and no default privacy group.'));
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

	$profile_fields = ['publish' => $publish, 'net-publish' => $net_publish, 'hide-friends' => $hide_friends];

	if (!User::update($fields, local_user()) || !Profile::update($profile_fields, local_user())) {
		notice(DI::l10n()->t('Settings were not updated.'));
	}

	// clear session language
	unset($_SESSION['language']);

	DI::baseUrl()->redirect('settings');
	return; // NOTREACHED
}


function settings_content(App $a)
{
	$o = '';
	Nav::setSelected('settings');

	if (!local_user()) {
		//notice(DI::l10n()->t('Permission denied.'));
		return Login::form();
	}

	if (!empty($_SESSION['submanage'])) {
		notice(DI::l10n()->t('Permission denied.'));
		return '';
	}

	if ((DI::args()->getArgc() > 1) && (DI::args()->getArgv()[1] === 'oauth')) {
		if ((DI::args()->getArgc() > 3) && (DI::args()->getArgv()[2] === 'delete')) {
			BaseModule::checkFormSecurityTokenRedirectOnError('/settings/oauth', 'settings_oauth', 't');

			DBA::delete('application-token', ['application-id' => DI::args()->getArgv()[3], 'uid' => local_user()]);
			DI::baseUrl()->redirect('settings/oauth/', true);
			return '';
		}

		$applications = DBA::selectToArray('application-view', ['id', 'uid', 'name', 'website', 'scopes', 'created_at'], ['uid' => local_user()]);

		$tpl = Renderer::getMarkupTemplate('settings/oauth.tpl');
		$o .= Renderer::replaceMacros($tpl, [
			'$form_security_token' => BaseModule::getFormSecurityToken("settings_oauth"),
			'$baseurl'             => DI::baseUrl()->get(true),
			'$title'               => DI::l10n()->t('Connected Apps'),
			'$name'                => DI::l10n()->t('Name'),
			'$website'             => DI::l10n()->t('Home Page'),
			'$created_at'          => DI::l10n()->t('Created'),
			'$delete'              => DI::l10n()->t('Remove authorization'),
			'$apps'                => $applications,
		]);
		return $o;
	}

	if ((DI::args()->getArgc() > 1) && (DI::args()->getArgv()[1] === 'addon')) {
		$addon_settings_forms = [];
		foreach (DI::dba()->selectToArray('hook', ['file', 'function'], ['hook' => 'addon_settings']) as $hook) {
			$data = [];
			Hook::callSingle(DI::app(), 'addon_settings', [$hook['file'], $hook['function']], $data);

			if (!empty($data['href'])) {
				$tpl = Renderer::getMarkupTemplate('settings/addon/link.tpl');
				$addon_settings_forms[] = Renderer::replaceMacros($tpl, [
					'$addon' => $data['addon'],
					'$title' => $data['title'],
					'$href'  => $data['href'],
				]);
			} elseif(!empty($data['addon'])) {
				$tpl = Renderer::getMarkupTemplate('settings/addon/panel.tpl');
				$addon_settings_forms[$data['addon']] = Renderer::replaceMacros($tpl, [
					'$addon'  => $data['addon'],
					'$title'  => $data['title'],
					'$open'   => (DI::args()->getArgv()[2] ?? '') === $data['addon'],
					'$html'   => $data['html'] ?? '',
					'$submit' => $data['submit'] ?? DI::l10n()->t('Save Settings'),
				]);
			}
		}

		$tpl = Renderer::getMarkupTemplate('settings/addons.tpl');
		$o .= Renderer::replaceMacros($tpl, [
			'$form_security_token' => BaseModule::getFormSecurityToken("settings_addon"),
			'$title'	=> DI::l10n()->t('Addon Settings'),
			'$no_addons_settings_configured' => DI::l10n()->t('No Addon settings configured'),
			'$addon_settings_forms' => $addon_settings_forms,
		]);
		return $o;
	}

	if ((DI::args()->getArgc() > 1) && (DI::args()->getArgv()[1] === 'features')) {

		$arr = [];
		$features = Feature::get();
		foreach ($features as $fname => $fdata) {
			$arr[$fname] = [];
			$arr[$fname][0] = $fdata[0];
			foreach (array_slice($fdata,1) as $f) {
				$arr[$fname][1][] = ['feature_' . $f[0], $f[1], Feature::isEnabled(local_user(), $f[0]), $f[2]];
			}
		}

		$tpl = Renderer::getMarkupTemplate('settings/features.tpl');
		$o .= Renderer::replaceMacros($tpl, [
			'$form_security_token' => BaseModule::getFormSecurityToken("settings_features"),
			'$title'               => DI::l10n()->t('Additional Features'),
			'$features'            => $arr,
			'$submit'              => DI::l10n()->t('Save Settings'),
		]);
		return $o;
	}

	if ((DI::args()->getArgc() > 1) && (DI::args()->getArgv()[1] === 'connectors')) {
		$accept_only_sharer        = intval(DI::pConfig()->get(local_user(), 'system', 'accept_only_sharer'));
		$enable_cw                 = !intval(DI::pConfig()->get(local_user(), 'system', 'disable_cw'));
		$enable_smart_shortening   = !intval(DI::pConfig()->get(local_user(), 'system', 'no_intelligent_shortening'));
		$simple_shortening         = intval(DI::pConfig()->get(local_user(), 'system', 'simple_shortening'));
		$attach_link_title         = intval(DI::pConfig()->get(local_user(), 'system', 'attach_link_title'));
		$legacy_contact            = DI::pConfig()->get(local_user(), 'ostatus', 'legacy_contact');

		if (!empty($legacy_contact)) {
			/// @todo Isn't it supposed to be a $a->internalRedirect() call?
			DI::page()['htmlhead'] = '<meta http-equiv="refresh" content="0; URL=' . DI::baseUrl().'/ostatus_subscribe?url=' . urlencode($legacy_contact) . '">';
		}

		$connector_settings_forms = [];
		foreach (DI::dba()->selectToArray('hook', ['file', 'function'], ['hook' => 'connector_settings']) as $hook) {
			$data = [];
			Hook::callSingle(DI::app(), 'connector_settings', [$hook['file'], $hook['function']], $data);

			$tpl = Renderer::getMarkupTemplate('settings/addon/connector.tpl');
			$connector_settings_forms[$data['connector']] = Renderer::replaceMacros($tpl, [
				'$connector' => $data['connector'],
				'$title'     => $data['title'],
				'$image'     => $data['image'] ?? '',
				'$enabled'   => $data['enabled'] ?? true,
				'$open'      => (DI::args()->getArgv()[2] ?? '') === $data['connector'],
				'$html'      => $data['html'] ?? '',
				'$submit'    => $data['submit'] ?? DI::l10n()->t('Save Settings'),
			]);
		}

		if ($a->isSiteAdmin()) {
			$diasp_enabled = DI::l10n()->t('Built-in support for %s connectivity is %s', DI::l10n()->t('Diaspora (Socialhome, Hubzilla)'), ((DI::config()->get('system', 'diaspora_enabled')) ? DI::l10n()->t('enabled') : DI::l10n()->t('disabled')));
			$ostat_enabled = DI::l10n()->t('Built-in support for %s connectivity is %s', DI::l10n()->t('OStatus (GNU Social)'), ((DI::config()->get('system', 'ostatus_disabled')) ? DI::l10n()->t('disabled') : DI::l10n()->t('enabled')));
		} else {
			$diasp_enabled = "";
			$ostat_enabled = "";
		}

		$mail_disabled = ((function_exists('imap_open') && (!DI::config()->get('system', 'imap_disabled'))) ? 0 : 1);
		if (!$mail_disabled) {
			$mailacct = DBA::selectFirst('mailacct', [], ['uid' => local_user()]);
		} else {
			$mailacct = null;
		}

		$mail_server       = $mailacct['server'] ?? '';
		$mail_port         = (!empty($mailacct['port']) && is_numeric($mailacct['port'])) ? (int)$mailacct['port'] : '';
		$mail_ssl          = $mailacct['ssltype'] ?? '';
		$mail_user         = $mailacct['user'] ?? '';
		$mail_replyto      = $mailacct['reply_to'] ?? '';
		$mail_pubmail      = $mailacct['pubmail'] ?? 0;
		$mail_action       = $mailacct['action'] ?? 0;
		$mail_movetofolder = $mailacct['movetofolder'] ?? '';
		$mail_chk          = $mailacct['last_check'] ?? DBA::NULL_DATETIME;


		$tpl = Renderer::getMarkupTemplate('settings/connectors.tpl');

		$mail_disabled_message = ($mail_disabled ? DI::l10n()->t('Email access is disabled on this site.') : '');

		$ssl_options = ['TLS' => 'TLS', 'SSL' => 'SSL'];

		if (DI::config()->get('system', 'insecure_imap')) {
			$ssl_options['notls'] = DI::l10n()->t('None');
		}

		$o .= Renderer::replaceMacros($tpl, [
			'$form_security_token' => BaseModule::getFormSecurityToken("settings_connectors"),

			'$title'	=> DI::l10n()->t('Social Networks'),

			'$diasp_enabled' => $diasp_enabled,
			'$ostat_enabled' => $ostat_enabled,

			'$general_settings' => DI::l10n()->t('General Social Media Settings'),
			'$accept_only_sharer' => ['accept_only_sharer', DI::l10n()->t('Accept only top level posts by contacts you follow'), $accept_only_sharer, DI::l10n()->t('The system does an auto completion of threads when a comment arrives. This has got the side effect that you can receive posts that had been started by a non-follower but had been commented by someone you follow. This setting deactivates this behaviour. When activated, you strictly only will receive posts from people you really do follow.')],
			'$enable_cw' => ['enable_cw', DI::l10n()->t('Enable Content Warning'), $enable_cw, DI::l10n()->t('Users on networks like Mastodon or Pleroma are able to set a content warning field which collapse their post by default. This enables the automatic collapsing instead of setting the content warning as the post title. Doesn\'t affect any other content filtering you eventually set up.')],
			'$enable_smart_shortening' => ['enable_smart_shortening', DI::l10n()->t('Enable intelligent shortening'), $enable_smart_shortening, DI::l10n()->t('Normally the system tries to find the best link to add to shortened posts. If disabled, every shortened post will always point to the original friendica post.')],
			'$simple_shortening' => ['simple_shortening', DI::l10n()->t('Enable simple text shortening'), $simple_shortening, DI::l10n()->t('Normally the system shortens posts at the next line feed. If this option is enabled then the system will shorten the text at the maximum character limit.')],
			'$attach_link_title' => ['attach_link_title', DI::l10n()->t('Attach the link title'), $attach_link_title, DI::l10n()->t('When activated, the title of the attached link will be added as a title on posts to Diaspora. This is mostly helpful with "remote-self" contacts that share feed content.')],
			'$legacy_contact' => ['legacy_contact', DI::l10n()->t('Your legacy ActivityPub/GNU Social account'), $legacy_contact, DI::l10n()->t("If you enter your old account name from an ActivityPub based system or your GNU Social/Statusnet account name here (in the format user@domain.tld), your contacts will be added automatically. The field will be emptied when done.")],

			'$repair_ostatus_url' => DI::baseUrl() . '/repair_ostatus',
			'$repair_ostatus_text' => DI::l10n()->t('Repair OStatus subscriptions'),

			'$connector_settings_forms' => $connector_settings_forms,

			'$h_mail' => DI::l10n()->t('Email/Mailbox Setup'),
			'$mail_desc' => DI::l10n()->t("If you wish to communicate with email contacts using this service \x28optional\x29, please specify how to connect to your mailbox."),
			'$mail_lastcheck' => ['mail_lastcheck', DI::l10n()->t('Last successful email check:'), $mail_chk, ''],
			'$mail_disabled' => $mail_disabled_message,
			'$mail_server'	=> ['mail_server',	DI::l10n()->t('IMAP server name:'), $mail_server, ''],
			'$mail_port'	=> ['mail_port', 	DI::l10n()->t('IMAP port:'), $mail_port, ''],
			'$mail_ssl'	=> ['mail_ssl',		DI::l10n()->t('Security:'), strtoupper($mail_ssl), '', $ssl_options],
			'$mail_user'	=> ['mail_user',	DI::l10n()->t('Email login name:'), $mail_user, ''],
			'$mail_pass'	=> ['mail_pass',	DI::l10n()->t('Email password:'), '', ''],
			'$mail_replyto'	=> ['mail_replyto',	DI::l10n()->t('Reply-to address:'), $mail_replyto, 'Optional'],
			'$mail_pubmail'	=> ['mail_pubmail',	DI::l10n()->t('Send public posts to all email contacts:'), $mail_pubmail, ''],
			'$mail_action'	=> ['mail_action',	DI::l10n()->t('Action after import:'), $mail_action, '', [0 => DI::l10n()->t('None'), 1 => DI::l10n()->t('Delete'), 2 => DI::l10n()->t('Mark as seen'), 3 => DI::l10n()->t('Move to folder')]],
			'$mail_movetofolder' => ['mail_movetofolder', DI::l10n()->t('Move to folder:'), $mail_movetofolder, ''],
			'$submit' => DI::l10n()->t('Save Settings'),
		]);

		Hook::callAll('display_settings', $o);
		return $o;
	}

	/*
	 * ACCOUNT SETTINGS
	 */

	$profile = DBA::selectFirst('profile', [], ['uid' => local_user()]);
	if (!DBA::isResult($profile)) {
		notice(DI::l10n()->t('Unable to find your profile. Please contact your admin.'));
		return '';
	}

	$user = User::getById($a->getLoggedInUserId());

	$username   = $user['username'];
	$email      = $user['email'];
	$nickname   = $a->getLoggedInUserNickname();
	$timezone   = $user['timezone'];
	$language   = $user['language'];
	$notify     = $user['notify-flags'];
	$defloc     = $user['default-location'];
	$openid     = $user['openid'];
	$maxreq     = $user['maxreq'];
	$expire     =  ((intval($user['expire'])) ? $user['expire'] : '');
	$unkmail    = $user['unkmail'];
	$cntunkmail = $user['cntunkmail'];

	$expire_items = DI::pConfig()->get(local_user(), 'expire', 'items', true);
	$expire_notes = DI::pConfig()->get(local_user(), 'expire', 'notes', true);
	$expire_starred = DI::pConfig()->get(local_user(), 'expire', 'starred', true);
	$expire_photos = DI::pConfig()->get(local_user(), 'expire', 'photos', false);
	$expire_network_only = DI::pConfig()->get(local_user(), 'expire', 'network_only', false);

	if (!strlen($user['timezone'])) {
		$timezone = $a->getTimeZone();
	}

	// Set the account type to "Community" when the page is a community page but the account type doesn't fit
	// This is only happening on the first visit after the update
	if (in_array($user['page-flags'], [User::PAGE_FLAGS_COMMUNITY, User::PAGE_FLAGS_PRVGROUP]) &&
		($user['account-type'] != User::ACCOUNT_TYPE_COMMUNITY))
		$user['account-type'] = User::ACCOUNT_TYPE_COMMUNITY;

	$pageset_tpl = Renderer::getMarkupTemplate('settings/pagetypes.tpl');

	$pagetype = Renderer::replaceMacros($pageset_tpl, [
		'$account_types'	=> DI::l10n()->t("Account Types"),
		'$user' 		=> DI::l10n()->t("Personal Page Subtypes"),
		'$community'		=> DI::l10n()->t("Community Forum Subtypes"),
		'$account_type'		=> $user['account-type'],
		'$type_person'		=> User::ACCOUNT_TYPE_PERSON,
		'$type_organisation' 	=> User::ACCOUNT_TYPE_ORGANISATION,
		'$type_news'		=> User::ACCOUNT_TYPE_NEWS,
		'$type_community' 	=> User::ACCOUNT_TYPE_COMMUNITY,

		'$account_person' 	=> ['account-type', DI::l10n()->t('Personal Page'), User::ACCOUNT_TYPE_PERSON,
									DI::l10n()->t('Account for a personal profile.'),
									($user['account-type'] == User::ACCOUNT_TYPE_PERSON)],

		'$account_organisation'	=> ['account-type', DI::l10n()->t('Organisation Page'), User::ACCOUNT_TYPE_ORGANISATION,
									DI::l10n()->t('Account for an organisation that automatically approves contact requests as "Followers".'),
									($user['account-type'] == User::ACCOUNT_TYPE_ORGANISATION)],

		'$account_news'		=> ['account-type', DI::l10n()->t('News Page'), User::ACCOUNT_TYPE_NEWS,
									DI::l10n()->t('Account for a news reflector that automatically approves contact requests as "Followers".'),
									($user['account-type'] == User::ACCOUNT_TYPE_NEWS)],

		'$account_community' 	=> ['account-type', DI::l10n()->t('Community Forum'), User::ACCOUNT_TYPE_COMMUNITY,
									DI::l10n()->t('Account for community discussions.'),
									($user['account-type'] == User::ACCOUNT_TYPE_COMMUNITY)],

		'$page_normal'		=> ['page-flags', DI::l10n()->t('Normal Account Page'), User::PAGE_FLAGS_NORMAL,
									DI::l10n()->t('Account for a regular personal profile that requires manual approval of "Friends" and "Followers".'),
									($user['page-flags'] == User::PAGE_FLAGS_NORMAL)],

		'$page_soapbox' 	=> ['page-flags', DI::l10n()->t('Soapbox Page'), User::PAGE_FLAGS_SOAPBOX,
									DI::l10n()->t('Account for a public profile that automatically approves contact requests as "Followers".'),
									($user['page-flags'] == User::PAGE_FLAGS_SOAPBOX)],

		'$page_community'	=> ['page-flags', DI::l10n()->t('Public Forum'), User::PAGE_FLAGS_COMMUNITY,
									DI::l10n()->t('Automatically approves all contact requests.'),
									($user['page-flags'] == User::PAGE_FLAGS_COMMUNITY)],

		'$page_freelove' 	=> ['page-flags', DI::l10n()->t('Automatic Friend Page'), User::PAGE_FLAGS_FREELOVE,
									DI::l10n()->t('Account for a popular profile that automatically approves contact requests as "Friends".'),
									($user['page-flags'] == User::PAGE_FLAGS_FREELOVE)],

		'$page_prvgroup' 	=> ['page-flags', DI::l10n()->t('Private Forum [Experimental]'), User::PAGE_FLAGS_PRVGROUP,
									DI::l10n()->t('Requires manual approval of contact requests.'),
									($user['page-flags'] == User::PAGE_FLAGS_PRVGROUP)],


	]);

	$noid = DI::config()->get('system', 'no_openid');

	if ($noid) {
		$openid_field = false;
	} else {
		$openid_field = ['openid_url', DI::l10n()->t('OpenID:'), $openid, DI::l10n()->t("\x28Optional\x29 Allow this OpenID to login to this account."), "", "readonly", "url"];
	}

	$opt_tpl = Renderer::getMarkupTemplate("field_checkbox.tpl");
	if (DI::config()->get('system', 'publish_all')) {
		$profile_in_dir = '<input type="hidden" name="profile_in_directory" value="1" />';
	} else {
		$profile_in_dir = Renderer::replaceMacros($opt_tpl, [
			'$field' => ['profile_in_directory', DI::l10n()->t('Publish your profile in your local site directory?'), $profile['publish'], DI::l10n()->t('Your profile will be published in this node\'s <a href="%s">local directory</a>. Your profile details may be publicly visible depending on the system settings.', DI::baseUrl().'/directory')]
		]);
	}

	$net_pub_desc = '';
	if (strlen(DI::config()->get('system', 'directory'))) {
		$net_pub_desc = ' ' . DI::l10n()->t('Your profile will also be published in the global friendica directories (e.g. <a href="%s">%s</a>).', DI::config()->get('system', 'directory'), DI::config()->get('system', 'directory'));
	}

	$tpl_addr = Renderer::getMarkupTemplate('settings/nick_set.tpl');

	$prof_addr = Renderer::replaceMacros($tpl_addr,[
		'$desc' => DI::l10n()->t("Your Identity Address is <strong>'%s'</strong> or '%s'.", $nickname . '@' . DI::baseUrl()->getHostname() . DI::baseUrl()->getUrlPath(), DI::baseUrl() . '/profile/' . $nickname),
		'$basepath' => DI::baseUrl()->getHostname()
	]);

	$stpl = Renderer::getMarkupTemplate('settings/settings.tpl');

	/* Installed langs */
	$lang_choices = DI::l10n()->getAvailableLanguages();

	/// @TODO Fix indending (or so)
	$o .= Renderer::replaceMacros($stpl, [
		'$ptitle' 	=> DI::l10n()->t('Account Settings'),

		'$submit' 	=> DI::l10n()->t('Save Settings'),
		'$baseurl' => DI::baseUrl()->get(true),
		'$uid' => local_user(),
		'$form_security_token' => BaseModule::getFormSecurityToken("settings"),
		'$nickname_block' => $prof_addr,

		'$h_pass' 	=> DI::l10n()->t('Password Settings'),
		'$password1'=> ['password', DI::l10n()->t('New Password:'), '', DI::l10n()->t('Allowed characters are a-z, A-Z, 0-9 and special characters except white spaces, accentuated letters and colon (:).')],
		'$password2'=> ['confirm', DI::l10n()->t('Confirm:'), '', DI::l10n()->t('Leave password fields blank unless changing')],
		'$password3'=> ['opassword', DI::l10n()->t('Current Password:'), '', DI::l10n()->t('Your current password to confirm the changes')],
		'$password4'=> ['mpassword', DI::l10n()->t('Password:'), '', DI::l10n()->t('Your current password to confirm the changes of the email address')],
		'$oid_enable' => (!DI::config()->get('system', 'no_openid')),
		'$openid'	=> $openid_field,
		'$delete_openid' => ['delete_openid', DI::l10n()->t('Delete OpenID URL'), false, ''],

		'$h_basic' 	=> DI::l10n()->t('Basic Settings'),
		'$username' => ['username',  DI::l10n()->t('Full Name:'), $username, ''],
		'$email' 	=> ['email', DI::l10n()->t('Email Address:'), $email, '', '', '', 'email'],
		'$timezone' => ['timezone_select' , DI::l10n()->t('Your Timezone:'), Temporal::getTimezoneSelect($timezone), ''],
		'$language' => ['language', DI::l10n()->t('Your Language:'), $language, DI::l10n()->t('Set the language we use to show you friendica interface and to send you emails'), $lang_choices],
		'$defloc'	=> ['defloc', DI::l10n()->t('Default Post Location:'), $defloc, ''],
		'$allowloc' => ['allow_location', DI::l10n()->t('Use Browser Location:'), ($user['allow_location'] == 1), ''],

		'$h_prv' 	          => DI::l10n()->t('Security and Privacy Settings'),
		'$visibility'         => $profile['net-publish'],
		'$maxreq' 	          => ['maxreq', DI::l10n()->t('Maximum Friend Requests/Day:'), $maxreq , DI::l10n()->t("\x28to prevent spam abuse\x29")],
		'$profile_in_dir'     => $profile_in_dir,
		'$profile_in_net_dir' => ['profile_in_netdirectory', DI::l10n()->t('Allow your profile to be searchable globally?'), $profile['net-publish'], DI::l10n()->t("Activate this setting if you want others to easily find and follow you. Your profile will be searchable on remote systems. This setting also determines whether Friendica will inform search engines that your profile should be indexed or not.") . $net_pub_desc],
		'$hide_friends'       => ['hide-friends', DI::l10n()->t('Hide your contact/friend list from viewers of your profile?'), $profile['hide-friends'], DI::l10n()->t('A list of your contacts is displayed on your profile page. Activate this option to disable the display of your contact list.')],
		'$hide_wall'          => ['hidewall', DI::l10n()->t('Hide your profile details from anonymous viewers?'), $user['hidewall'], DI::l10n()->t('Anonymous visitors will only see your profile picture, your display name and the nickname you are using on your profile page. Your public posts and replies will still be accessible by other means.')],
		'$unlisted'           => ['unlisted', DI::l10n()->t('Make public posts unlisted'), DI::pConfig()->get(local_user(), 'system', 'unlisted'), DI::l10n()->t('Your public posts will not appear on the community pages or in search results, nor be sent to relay servers. However they can still appear on public feeds on remote servers.')],
		'$accessiblephotos'   => ['accessible-photos', DI::l10n()->t('Make all posted pictures accessible'), DI::pConfig()->get(local_user(), 'system', 'accessible-photos'), DI::l10n()->t("This option makes every posted picture accessible via the direct link. This is a workaround for the problem that most other networks can't handle permissions on pictures. Non public pictures still won't be visible for the public on your photo albums though.")],
		'$blockwall'          => ['blockwall', DI::l10n()->t('Allow friends to post to your profile page?'), (intval($user['blockwall']) ? '0' : '1'), DI::l10n()->t('Your contacts may write posts on your profile wall. These posts will be distributed to your contacts')], // array('blockwall', DI::l10n()->t('Allow friends to post to your profile page:'), !$blockwall, ''),
		'$blocktags'          => ['blocktags', DI::l10n()->t('Allow friends to tag your posts?'), (intval($user['blocktags']) ? '0' : '1'), DI::l10n()->t('Your contacts can add additional tags to your posts.')], // array('blocktags', DI::l10n()->t('Allow friends to tag your posts:'), !$blocktags, ''),
		'$unkmail'            => ['unkmail', DI::l10n()->t('Permit unknown people to send you private mail?'), $unkmail, DI::l10n()->t('Friendica network users may send you private messages even if they are not in your contact list.')],
		'$cntunkmail' 	      => ['cntunkmail', DI::l10n()->t('Maximum private messages per day from unknown people:'), $cntunkmail , DI::l10n()->t("\x28to prevent spam abuse\x29")],
		'$group_select'       => Group::displayGroupSelection(local_user(), $user['def_gid']),
		'$permissions'        => DI::l10n()->t('Default Post Permissions'),
		'$aclselect'          => ACL::getFullSelectorHTML(DI::page(), $a->getLoggedInUserId()),

		'$expire' => [
			'label'        => DI::l10n()->t('Expiration settings'),
			'days'         => ['expire',  DI::l10n()->t("Automatically expire posts after this many days:"), $expire, DI::l10n()->t('If empty, posts will not expire. Expired posts will be deleted')],
			'items'        => ['expire_items', DI::l10n()->t('Expire posts'), $expire_items, DI::l10n()->t('When activated, posts and comments will be expired.')],
			'notes'        => ['expire_notes', DI::l10n()->t('Expire personal notes'), $expire_notes, DI::l10n()->t('When activated, the personal notes on your profile page will be expired.')],
			'starred'      => ['expire_starred', DI::l10n()->t('Expire starred posts'), $expire_starred, DI::l10n()->t('Starring posts keeps them from being expired. That behaviour is overwritten by this setting.')],
			'photos'       => ['expire_photos', DI::l10n()->t('Expire photos'), $expire_photos, DI::l10n()->t('When activated, photos will be expired.')],
			'network_only' => ['expire_network_only', DI::l10n()->t('Only expire posts by others'), $expire_network_only, DI::l10n()->t('When activated, your own posts never expire. Then the settings above are only valid for posts you received.')],
		],

		'$h_not' 	=> DI::l10n()->t('Notification Settings'),
		'$lbl_not' 	=> DI::l10n()->t('Send a notification email when:'),
		'$notify1'	=> ['notify1', DI::l10n()->t('You receive an introduction'), ($notify & Notification\Type::INTRO), Notification\Type::INTRO, ''],
		'$notify2'	=> ['notify2', DI::l10n()->t('Your introductions are confirmed'), ($notify & Notification\Type::CONFIRM), Notification\Type::CONFIRM, ''],
		'$notify3'	=> ['notify3', DI::l10n()->t('Someone writes on your profile wall'), ($notify & Notification\Type::WALL), Notification\Type::WALL, ''],
		'$notify4'	=> ['notify4', DI::l10n()->t('Someone writes a followup comment'), ($notify & Notification\Type::COMMENT), Notification\Type::COMMENT, ''],
		'$notify5'	=> ['notify5', DI::l10n()->t('You receive a private message'), ($notify & Notification\Type::MAIL), Notification\Type::MAIL, ''],
		'$notify6'  => ['notify6', DI::l10n()->t('You receive a friend suggestion'), ($notify & Notification\Type::SUGGEST), Notification\Type::SUGGEST, ''],
		'$notify7'  => ['notify7', DI::l10n()->t('You are tagged in a post'), ($notify & Notification\Type::TAG_SELF), Notification\Type::TAG_SELF, ''],
		'$notify8'  => ['notify8', DI::l10n()->t('You are poked/prodded/etc. in a post'), ($notify & Notification\Type::POKE), Notification\Type::POKE, ''],

		'$lbl_notify'      => DI::l10n()->t('Create a desktop notification when:'),
		'$notify_like'     => ['notify_like', DI::l10n()->t('Someone liked your content'), DI::pConfig()->get(local_user(), 'system', 'notify_like'), ''],
		'$notify_announce' => ['notify_announce', DI::l10n()->t('Someone shared your content'), DI::pConfig()->get(local_user(), 'system', 'notify_announce'), ''],

		'$desktop_notifications' => ['desktop_notifications', DI::l10n()->t('Activate desktop notifications') , false, DI::l10n()->t('Show desktop popup on new notifications')],

		'$email_textonly' => ['email_textonly', DI::l10n()->t('Text-only notification emails'),
									DI::pConfig()->get(local_user(), 'system', 'email_textonly'),
									DI::l10n()->t('Send text only notification emails, without the html part')],

		'$detailed_notif' => ['detailed_notif', DI::l10n()->t('Show detailled notifications'),
									DI::pConfig()->get(local_user(), 'system', 'detailed_notif'),
									DI::l10n()->t('Per default, notifications are condensed to a single notification per item. When enabled every notification is displayed.')],

		'$notify_ignored' => ['notify_ignored', DI::l10n()->t('Show notifications of ignored contacts') ,
									DI::pConfig()->get(local_user(), 'system', 'notify_ignored', true),
									DI::l10n()->t("You don't see posts from ignored contacts. But you still see their comments. This setting controls if you want to still receive regular notifications that are caused by ignored contacts or not.")],

									'$h_advn' => DI::l10n()->t('Advanced Account/Page Type Settings'),
		'$h_descadvn' => DI::l10n()->t('Change the behaviour of this account for special situations'),
		'$pagetype' => $pagetype,

		'$importcontact' => DI::l10n()->t('Import Contacts'),
		'$importcontact_text' => DI::l10n()->t('Upload a CSV file that contains the handle of your followed accounts in the first column you exported from the old account.'),
		'$importcontact_button' => DI::l10n()->t('Upload File'),
		'$importcontact_maxsize' => DI::config()->get('system', 'max_csv_file_size', 30720),
		'$relocate' => DI::l10n()->t('Relocate'),
		'$relocate_text' => DI::l10n()->t("If you have moved this profile from another server, and some of your contacts don't receive your updates, try pushing this button."),
		'$relocate_button' => DI::l10n()->t("Resend relocate message to contacts"),

	]);

	Hook::callAll('settings_form', $o);

	$o .= '</form>' . "\r\n";

	return $o;

}
