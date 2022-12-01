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

namespace Friendica\Module\Settings;

use Exception;
use Friendica\Core\ACL;
use Friendica\Core\Logger;
use Friendica\Core\Renderer;
use Friendica\Core\Search;
use Friendica\Core\Worker;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Group;
use Friendica\Model\Notification;
use Friendica\Model\Post\UserNotification;
use Friendica\Model\Profile;
use Friendica\Model\User;
use Friendica\Model\Verb;
use Friendica\Module\BaseSettings;
use Friendica\Network\HTTPException;
use Friendica\Protocol\Activity;
use Friendica\Util\Network;
use Friendica\Util\Temporal;
use Friendica\Worker\Delivery;

class Account extends BaseSettings
{
	protected function post(array $request = [])
	{
		if (!DI::app()->isLoggedIn()) {
			throw new HTTPException\ForbiddenException(DI::l10n()->t('Permission denied.'));
		}

		$redirectUrl = '/settings' . (isset($this->parameters['open']) ? '/account/' . $this->parameters['open'] : '');

		self::checkFormSecurityTokenRedirectOnError($redirectUrl, 'settings');

		$a = DI::app();

		$user = User::getById($a->getLoggedInUserId());

		if (!empty($request['password-submit'])) {
			$newpass = $request['password'];
			$confirm = $request['confirm'];

			try {
				if ($newpass != $confirm) {
					throw new Exception(DI::l10n()->t('Passwords do not match.'));
				}

				//  check if the old password was supplied correctly before changing it to the new value
				User::getIdFromPasswordAuthentication(DI::userSession()->getLocalUserId(), $request['opassword']);

				$result = User::updatePassword(DI::userSession()->getLocalUserId(), $newpass);
				if (!DBA::isResult($result)) {
					throw new Exception(DI::l10n()->t('Password update failed. Please try again.'));
				}

				DI::sysmsg()->addInfo(DI::l10n()->t('Password changed.'));
			} catch (Exception $e) {
				DI::sysmsg()->addNotice($e->getMessage());
				DI::sysmsg()->addNotice(DI::l10n()->t('Password unchanged.'));
			}

			DI::baseUrl()->redirect($redirectUrl);
		}

		if (!empty($request['basic-submit'])) {
			$username = trim($request['username'] ?? '');
			$email    = trim($request['email'] ?? '');
			$timezone = trim($request['timezone'] ?? '');


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
					User::getIdFromPasswordAuthentication(DI::userSession()->getLocalUserId(), $request['mpassword']);
				} catch (Exception $ex) {
					$err .= DI::l10n()->t('Wrong Password.');
					$email = $user['email'];
				}
				//  check the email is valid
				if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
					$err .= DI::l10n()->t('Invalid email.');
				}
				//  ensure new email is not the admin mail
				if (in_array(strtolower($email), User::getAdminEmailList())) {
					$err .= DI::l10n()->t('Cannot change to that email.');
					$email = $user['email'];
				}
			}

			if (strlen($err)) {
				DI::sysmsg()->addNotice($err);
				return;
			}

			if (strlen($timezone) && $timezone != $user['timezone']) {
				$a->setTimeZone($timezone);
			}

			$fields = [
				'username'         => $username,
				'email'            => $email,
				'timezone'         => $timezone,
				'default-location' => trim($request['default_location'] ?? ''),
				'allow_location'   => !empty($request['allow_location']),
				'language'         => trim($request['language'] ?? ''),
			];

			if (!empty($request['delete_openid'])) {
				$fields['openid']       = '';
				$fields['openidserver'] = '';
			}

			if (!User::update($fields, DI::userSession()->getLocalUserId())) {
				DI::sysmsg()->addNotice(DI::l10n()->t('Settings were not updated.'));
			}

			// clear session language
			unset($_SESSION['language']);

			DI::baseUrl()->redirect($redirectUrl);
		}

		if (!empty($request['privacy-submit'])) {
			$maxreq       = intval($request['maxreq'] ?? 0);
			$publish      = !empty($request['profile_in_directory']);
			$net_publish  = !empty($request['profile_in_netdirectory']);
			$hide_friends = !empty($request['hide-friends']);
			$hidewall     = !empty($request['hidewall']);
			$blockwall    = empty($request['blockwall']); // this setting is inverted!
			$blocktags    = empty($request['blocktags']); // this setting is inverted!
			$unkmail      = !empty($request['unkmail']);
			$cntunkmail   = intval($request['cntunkmail'] ?? 0);
			$def_gid      = intval($request['group-selection'] ?? 0);

			$aclFormatter = DI::aclFormatter();

			$str_group_allow   = !empty($request['group_allow']) ? $aclFormatter->toString($request['group_allow']) : '';
			$str_contact_allow = !empty($request['contact_allow']) ? $aclFormatter->toString($request['contact_allow']) : '';
			$str_group_deny    = !empty($request['group_deny']) ? $aclFormatter->toString($request['group_deny']) : '';
			$str_contact_deny  = !empty($request['contact_deny']) ? $aclFormatter->toString($request['contact_deny']) : '';

			DI::pConfig()->set(DI::userSession()->getLocalUserId(), 'system', 'unlisted', !empty($request['unlisted']));
			DI::pConfig()->set(DI::userSession()->getLocalUserId(), 'system', 'accessible-photos', !empty($request['accessible-photos']));

			$fields = [
				'allow_cid'  => $str_contact_allow,
				'allow_gid'  => $str_group_allow,
				'deny_cid'   => $str_contact_deny,
				'deny_gid'   => $str_group_deny,
				'maxreq'     => $maxreq,
				'def_gid'    => $def_gid,
				'blockwall'  => $blockwall,
				'hidewall'   => $hidewall,
				'blocktags'  => $blocktags,
				'unkmail'    => $unkmail,
				'cntunkmail' => $cntunkmail,
			];

			$profile_fields = [
				'publish'      => $publish,
				'net-publish'  => $net_publish,
				'hide-friends' => $hide_friends
			];

			if (!User::update($fields, DI::userSession()->getLocalUserId()) || !Profile::update($profile_fields, DI::userSession()->getLocalUserId())) {
				DI::sysmsg()->addNotice(DI::l10n()->t('Settings were not updated.'));
			}

			DI::baseUrl()->redirect($redirectUrl);
		}

		if (!empty($request['expire-submit'])) {
			$expire = intval($request['expire'] ?? 0);

			$expire_items        = !empty($request['expire_items']);
			$expire_notes        = !empty($request['expire_notes']);
			$expire_starred      = !empty($request['expire_starred']);
			$expire_network_only = !empty($request['expire_network_only']);

			DI::pConfig()->set(DI::userSession()->getLocalUserId(), 'expire', 'items', $expire_items);
			DI::pConfig()->set(DI::userSession()->getLocalUserId(), 'expire', 'notes', $expire_notes);
			DI::pConfig()->set(DI::userSession()->getLocalUserId(), 'expire', 'starred', $expire_starred);
			DI::pConfig()->set(DI::userSession()->getLocalUserId(), 'expire', 'network_only', $expire_network_only);

			if (!User::update(['expire' => $expire], DI::userSession()->getLocalUserId())) {
				DI::sysmsg()->addNotice(DI::l10n()->t('Settings were not updated.'));
			}

			DI::baseUrl()->redirect($redirectUrl);
		}

		if (!empty($request['notification-submit'])) {
			$notify = 0;

			if (!empty($request['notify1'])) {
				$notify += intval($request['notify1']);
			}
			if (!empty($request['notify2'])) {
				$notify += intval($request['notify2']);
			}
			if (!empty($request['notify3'])) {
				$notify += intval($request['notify3']);
			}
			if (!empty($request['notify4'])) {
				$notify += intval($request['notify4']);
			}
			if (!empty($request['notify5'])) {
				$notify += intval($request['notify5']);
			}
			if (!empty($request['notify6'])) {
				$notify += intval($request['notify6']);
			}
			if (!empty($request['notify7'])) {
				$notify += intval($request['notify7']);
			}
			if (!empty($request['notify8'])) {
				$notify += intval($request['notify8']);
			}

			$notify_like     = !empty($request['notify_like']);
			$notify_announce = !empty($request['notify_announce']);

			$notify_type = 0;

			if (!empty($request['notify_tagged'])) {
				$notify_type = $notify_type | UserNotification::TYPE_EXPLICIT_TAGGED;
			}
			if (!empty($request['notify_direct_comment'])) {
				$notify_type = $notify_type | (UserNotification::TYPE_IMPLICIT_TAGGED + UserNotification::TYPE_DIRECT_COMMENT + UserNotification::TYPE_DIRECT_THREAD_COMMENT);
			}
			if (!empty($request['notify_thread_comment'])) {
				$notify_type = $notify_type | UserNotification::TYPE_THREAD_COMMENT;
			}
			if (!empty($request['notify_comment_participation'])) {
				$notify_type = $notify_type | UserNotification::TYPE_COMMENT_PARTICIPATION;
			}
			if (!empty($request['notify_activity_participation'])) {
				$notify_type = $notify_type | UserNotification::TYPE_ACTIVITY_PARTICIPATION;
			}
			DI::pConfig()->set(DI::userSession()->getLocalUserId(), 'system', 'notify_type', $notify_type);

			if (!($notify_type & (UserNotification::TYPE_DIRECT_COMMENT + UserNotification::TYPE_DIRECT_THREAD_COMMENT))) {
				$notify_like     = false;
				$notify_announce = false;
			}

			// Reset like notifications when they are going to be shown again
			if (!DI::pConfig()->get(DI::userSession()->getLocalUserId(), 'system', 'notify_like') && $notify_like) {
				DI::notification()->setAllSeenForUser(DI::userSession()->getLocalUserId(), ['vid' => Verb::getID(Activity::LIKE)]);
			}

			DI::pConfig()->set(DI::userSession()->getLocalUserId(), 'system', 'notify_like', $notify_like);

			// Reset share notifications when they are going to be shown again
			if (!DI::pConfig()->get(DI::userSession()->getLocalUserId(), 'system', 'notify_announce') && $notify_announce) {
				DI::notification()->setAllSeenForUser(DI::userSession()->getLocalUserId(), ['vid' => Verb::getID(Activity::ANNOUNCE)]);
			}

			DI::pConfig()->set(DI::userSession()->getLocalUserId(), 'system', 'notify_announce', $notify_announce);

			DI::pConfig()->set(DI::userSession()->getLocalUserId(), 'system', 'email_textonly', !empty($request['email_textonly']));
			DI::pConfig()->set(DI::userSession()->getLocalUserId(), 'system', 'detailed_notif', !empty($request['detailed_notif']));
			DI::pConfig()->set(DI::userSession()->getLocalUserId(), 'system', 'notify_ignored', !empty($request['notify_ignored']));

			$fields = [
				'notify-flags' => $notify,
			];

			if (!User::update($fields, DI::userSession()->getLocalUserId())) {
				DI::sysmsg()->addNotice(DI::l10n()->t('Settings were not updated.'));
			}

			DI::baseUrl()->redirect($redirectUrl);
		}

		if (!empty($request['advanced-submit'])) {
			$account_type = intval($request['account-type'] ?? 0);
			$page_flags   = intval($request['page-flags'] ?? 0);

			// Adjust the page flag if the account type doesn't fit to the page flag.
			if ($account_type == User::ACCOUNT_TYPE_PERSON && !in_array($page_flags, [User::PAGE_FLAGS_NORMAL, User::PAGE_FLAGS_SOAPBOX, User::PAGE_FLAGS_FREELOVE])) {
				$page_flags = User::PAGE_FLAGS_NORMAL;
			} elseif ($account_type == User::ACCOUNT_TYPE_ORGANISATION && $page_flags != User::PAGE_FLAGS_SOAPBOX) {
				$page_flags = User::PAGE_FLAGS_SOAPBOX;
			} elseif ($account_type == User::ACCOUNT_TYPE_NEWS && $page_flags != User::PAGE_FLAGS_SOAPBOX) {
				$page_flags = User::PAGE_FLAGS_SOAPBOX;
			} elseif ($account_type == User::ACCOUNT_TYPE_COMMUNITY && !in_array($page_flags, [User::PAGE_FLAGS_COMMUNITY, User::PAGE_FLAGS_PRVGROUP])) {
				$page_flags = User::PAGE_FLAGS_COMMUNITY;
			}

			$fields         = [];
			$profile_fields = [];

			if ($account_type == User::ACCOUNT_TYPE_COMMUNITY) {
				DI::pConfig()->set(DI::userSession()->getLocalUserId(), 'system', 'unlisted', true);

				$fields = [
					'allow_cid' => '',
					'allow_gid' => $page_flags == User::PAGE_FLAGS_PRVGROUP ?
							'<' . Group::FOLLOWERS . '>'
							: '',
					'deny_cid'  => '',
					'deny_gid'  => '',
					'blockwall' => true,
					'blocktags' => true,
				];

				$profile_fields = [
					'hide-friends' => true,
				];
			}

			$fields = array_merge($fields, [
				'page-flags'   => $page_flags,
				'account-type' => $account_type,
			]);

			if (!User::update($fields, DI::userSession()->getLocalUserId()) || !empty($profile_fields) && !Profile::update($profile_fields, DI::userSession()->getLocalUserId())) {
				DI::sysmsg()->addNotice(DI::l10n()->t('Settings were not updated.'));
			}

			DI::baseUrl()->redirect($redirectUrl);
		}

		// Import Contacts from CSV file
		if (!empty($request['importcontact-submit'])) {
			if (isset($_FILES['importcontact-filename'])) {
				// was there an error
				if ($_FILES['importcontact-filename']['error'] > 0) {
					Logger::notice('Contact CSV file upload error', ['error' => $_FILES['importcontact-filename']['error']]);
					DI::sysmsg()->addNotice(DI::l10n()->t('Contact CSV file upload error'));
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
						if ((strpos($csvRow[0], '@') !== false) || Network::isValidHttpUrl($csvRow[0])) {
							Worker::add(Worker::PRIORITY_MEDIUM, 'AddContact', DI::userSession()->getLocalUserId(), trim($csvRow[0], '@'));
						} else {
							Logger::notice('Invalid account', ['url' => $csvRow[0]]);
						}
					}
					Logger::notice('Import done');

					DI::sysmsg()->addInfo(DI::l10n()->t('Importing Contacts done'));
					// delete temp file
					unlink($_FILES['importcontact-filename']['tmp_name']);
				}
			} else {
				Logger::notice('Import triggered, but no import file was found.');
			}

			DI::baseUrl()->redirect($redirectUrl);
		}

		if (!empty($request['relocate-submit'])) {
			Worker::add(Worker::PRIORITY_HIGH, 'Notifier', Delivery::RELOCATION, DI::userSession()->getLocalUserId());
			DI::sysmsg()->addInfo(DI::l10n()->t("Relocate message has been send to your contacts"));
			DI::baseUrl()->redirect($redirectUrl);
		}

		DI::baseUrl()->redirect($redirectUrl);
	}

	protected function content(array $request = []): string
	{
		parent::content();

		if (!DI::userSession()->getLocalUserId()) {
			throw new HTTPException\ForbiddenException(DI::l10n()->t('Permission denied.'));
		}

		$profile = DBA::selectFirst('profile', [], ['uid' => DI::userSession()->getLocalUserId()]);
		if (!DBA::isResult($profile)) {
			DI::sysmsg()->addNotice(DI::l10n()->t('Unable to find your profile. Please contact your admin.'));
			return '';
		}

		$a = DI::app();

		$user = User::getById($a->getLoggedInUserId());

		$username         = $user['username'];
		$email            = $user['email'];
		$nickname         = $a->getLoggedInUserNickname();
		$timezone         = $user['timezone'];
		$language         = $user['language'];
		$notify           = $user['notify-flags'];
		$default_location = $user['default-location'];
		$openid           = $user['openid'];
		$maxreq           = $user['maxreq'];
		$expire           = $user['expire'] ?: '';
		$unkmail          = $user['unkmail'];
		$cntunkmail       = $user['cntunkmail'];

		$expire_items        = DI::pConfig()->get(DI::userSession()->getLocalUserId(), 'expire', 'items', true);
		$expire_notes        = DI::pConfig()->get(DI::userSession()->getLocalUserId(), 'expire', 'notes', true);
		$expire_starred      = DI::pConfig()->get(DI::userSession()->getLocalUserId(), 'expire', 'starred', true);
		$expire_network_only = DI::pConfig()->get(DI::userSession()->getLocalUserId(), 'expire', 'network_only', false);

		if (!strlen($user['timezone'])) {
			$timezone = $a->getTimeZone();
		}

		// Set the account type to "Community" when the page is a community page but the account type doesn't fit
		// This is only happening on the first visit after the update
		if (
			in_array($user['page-flags'], [User::PAGE_FLAGS_COMMUNITY, User::PAGE_FLAGS_PRVGROUP])
			&& $user['account-type'] != User::ACCOUNT_TYPE_COMMUNITY
		) {
			$user['account-type'] = User::ACCOUNT_TYPE_COMMUNITY;
		}

		$pageset_tpl = Renderer::getMarkupTemplate('settings/pagetypes.tpl');
		$pagetype    = Renderer::replaceMacros($pageset_tpl, [
			'$account_types'     => DI::l10n()->t("Account Types"),
			'$user'              => DI::l10n()->t("Personal Page Subtypes"),
			'$community'         => DI::l10n()->t("Community Forum Subtypes"),
			'$account_type'      => $user['account-type'],
			'$type_person'       => User::ACCOUNT_TYPE_PERSON,
			'$type_organisation' => User::ACCOUNT_TYPE_ORGANISATION,
			'$type_news'         => User::ACCOUNT_TYPE_NEWS,
			'$type_community'    => User::ACCOUNT_TYPE_COMMUNITY,
			'$account_person'    => [
				'account-type',
				DI::l10n()->t('Personal Page'),
				User::ACCOUNT_TYPE_PERSON,
				DI::l10n()->t('Account for a personal profile.'),
				$user['account-type'] == User::ACCOUNT_TYPE_PERSON
			],
			'$account_organisation' => [
				'account-type',
				DI::l10n()->t('Organisation Page'),
				User::ACCOUNT_TYPE_ORGANISATION,
				DI::l10n()->t('Account for an organisation that automatically approves contact requests as "Followers".'),
				$user['account-type'] == User::ACCOUNT_TYPE_ORGANISATION
			],
			'$account_news' => [
				'account-type',
				DI::l10n()->t('News Page'),
				User::ACCOUNT_TYPE_NEWS,
				DI::l10n()->t('Account for a news reflector that automatically approves contact requests as "Followers".'),
				$user['account-type'] == User::ACCOUNT_TYPE_NEWS
			],
			'$account_community' => [
				'account-type',
				DI::l10n()->t('Community Forum'),
				User::ACCOUNT_TYPE_COMMUNITY,
				DI::l10n()->t('Account for community discussions.'),
				$user['account-type'] == User::ACCOUNT_TYPE_COMMUNITY
			],
			'$page_normal' => [
				'page-flags',
				DI::l10n()->t('Normal Account Page'),
				User::PAGE_FLAGS_NORMAL,
				DI::l10n()->t('Account for a regular personal profile that requires manual approval of "Friends" and "Followers".'),
				$user['page-flags'] == User::PAGE_FLAGS_NORMAL
			],
			'$page_soapbox' => [
				'page-flags',
				DI::l10n()->t('Soapbox Page'),
				User::PAGE_FLAGS_SOAPBOX,
				DI::l10n()->t('Account for a public profile that automatically approves contact requests as "Followers".'),
				$user['page-flags'] == User::PAGE_FLAGS_SOAPBOX
			],
			'$page_community' => [
				'page-flags',
				DI::l10n()->t('Public Forum'),
				User::PAGE_FLAGS_COMMUNITY,
				DI::l10n()->t('Automatically approves all contact requests.'),
				$user['page-flags'] == User::PAGE_FLAGS_COMMUNITY
			],
			'$page_freelove' => [
				'page-flags',
				DI::l10n()->t('Automatic Friend Page'),
				User::PAGE_FLAGS_FREELOVE,
				DI::l10n()->t('Account for a popular profile that automatically approves contact requests as "Friends".'),
				$user['page-flags'] == User::PAGE_FLAGS_FREELOVE
			],
			'$page_prvgroup' => [
				'page-flags',
				DI::l10n()->t('Private Forum [Experimental]'),
				User::PAGE_FLAGS_PRVGROUP,
				DI::l10n()->t('Requires manual approval of contact requests.'),
				$user['page-flags'] == User::PAGE_FLAGS_PRVGROUP
			],
		]);

		$noid = DI::config()->get('system', 'no_openid');
		if ($noid) {
			$openid_field = false;
		} else {
			$openid_field = ['openid_url', DI::l10n()->t('OpenID:'), $openid, DI::l10n()->t("(Optional) Allow this OpenID to login to this account."), "", "readonly", "url"];
		}

		if (DI::config()->get('system', 'publish_all')) {
			$profile_in_dir = '<input type="hidden" name="profile_in_directory" value="1" />';
		} else {
			$opt_tpl        = Renderer::getMarkupTemplate("field_checkbox.tpl");
			$profile_in_dir = Renderer::replaceMacros($opt_tpl, [
				'$field' => ['profile_in_directory', DI::l10n()->t('Publish your profile in your local site directory?'), $profile['publish'], DI::l10n()->t('Your profile will be published in this node\'s <a href="%s">local directory</a>. Your profile details may be publicly visible depending on the system settings.', DI::baseUrl() . '/directory')]
			]);
		}

		$net_pub_desc = '';
		if (Search::getGlobalDirectory()) {
			$net_pub_desc = ' ' . DI::l10n()->t('Your profile will also be published in the global friendica directories (e.g. <a href="%s">%s</a>).', Search::getGlobalDirectory(), Search::getGlobalDirectory());
		}

		/* Installed langs */
		$lang_choices = DI::l10n()->getAvailableLanguages();

		$notify_type = DI::pConfig()->get(DI::userSession()->getLocalUserId(), 'system', 'notify_type');

		$passwordRules = DI::l10n()->t('Allowed characters are a-z, A-Z, 0-9 and special characters except white spaces, accentuated letters and colon (:).')
			. (PASSWORD_DEFAULT === PASSWORD_BCRYPT ? ' ' . DI::l10n()->t('Password length is limited to 72 characters.') : '');

		$tpl = Renderer::getMarkupTemplate('settings/account.tpl');
		$o   = Renderer::replaceMacros($tpl, [
			'$ptitle' => DI::l10n()->t('Account Settings'),
			'$desc'   => DI::l10n()->t("Your Identity Address is <strong>'%s'</strong> or '%s'.", $nickname . '@' . DI::baseUrl()->getHostname() . DI::baseUrl()->getUrlPath(), DI::baseUrl() . '/profile/' . $nickname),

			'$submit'              => DI::l10n()->t('Save Settings'),
			'$baseurl'             => DI::baseUrl()->get(true),
			'$uid'                 => DI::userSession()->getLocalUserId(),
			'$form_security_token' => self::getFormSecurityToken('settings'),
			'$open'                => $this->parameters['open'] ?? 'password',

			'$h_pass'        => DI::l10n()->t('Password Settings'),
			'$password1'     => ['password', DI::l10n()->t('New Password:'), '', $passwordRules, false, 'autocomplete="off"', User::getPasswordRegExp()],
			'$password2'     => ['confirm', DI::l10n()->t('Confirm:'), '', DI::l10n()->t('Leave password fields blank unless changing'), false, 'autocomplete="off"'],
			'$password3'     => ['opassword', DI::l10n()->t('Current Password:'), '', DI::l10n()->t('Your current password to confirm the changes'), false, 'autocomplete="off"'],
			'$password4'     => ['mpassword', DI::l10n()->t('Password:'), '', DI::l10n()->t('Your current password to confirm the changes of the email address'), false, 'autocomplete="off"'],
			'$oid_enable'    => (!DI::config()->get('system', 'no_openid')),
			'$openid'        => $openid_field,
			'$delete_openid' => ['delete_openid', DI::l10n()->t('Delete OpenID URL'), false, ''],

			'$h_basic'          => DI::l10n()->t('Basic Settings'),
			'$username'         => ['username', DI::l10n()->t('Full Name:'), $username, '', false, 'autocomplete="off"'],
			'$email'            => ['email', DI::l10n()->t('Email Address:'), $email, '', '', 'autocomplete="off"', 'email'],
			'$timezone'         => ['timezone_select', DI::l10n()->t('Your Timezone:'), Temporal::getTimezoneSelect($timezone), ''],
			'$language'         => ['language', DI::l10n()->t('Your Language:'), $language, DI::l10n()->t('Set the language we use to show you friendica interface and to send you emails'), $lang_choices],
			'$default_location' => ['default_location', DI::l10n()->t('Default Post Location:'), $default_location, ''],
			'$allow_location'   => ['allow_location', DI::l10n()->t('Use Browser Location:'), ($user['allow_location'] == 1), ''],

			'$h_prv'              => DI::l10n()->t('Security and Privacy Settings'),
			'$is_community'       => ($user['account-type'] == User::ACCOUNT_TYPE_COMMUNITY),
			'$maxreq'             => ['maxreq', DI::l10n()->t('Maximum Friend Requests/Day:'), $maxreq, DI::l10n()->t("(to prevent spam abuse)")],
			'$profile_in_dir'     => $profile_in_dir,
			'$profile_in_net_dir' => ['profile_in_netdirectory', DI::l10n()->t('Allow your profile to be searchable globally?'), $profile['net-publish'], DI::l10n()->t("Activate this setting if you want others to easily find and follow you. Your profile will be searchable on remote systems. This setting also determines whether Friendica will inform search engines that your profile should be indexed or not.") . $net_pub_desc],
			'$hide_friends'       => ['hide-friends', DI::l10n()->t('Hide your contact/friend list from viewers of your profile?'), $profile['hide-friends'], DI::l10n()->t('A list of your contacts is displayed on your profile page. Activate this option to disable the display of your contact list.')],
			'$hide_wall'          => ['hidewall', $this->t('Hide your public content from anonymous viewers'), $user['hidewall'], $this->t('Anonymous visitors will only see your basic profile details. Your public posts and replies will still be freely accessible on the remote servers of your followers and through relays.')],
			'$unlisted'           => ['unlisted', DI::l10n()->t('Make public posts unlisted'), DI::pConfig()->get(DI::userSession()->getLocalUserId(), 'system', 'unlisted'), DI::l10n()->t('Your public posts will not appear on the community pages or in search results, nor be sent to relay servers. However they can still appear on public feeds on remote servers.')],
			'$accessiblephotos'   => ['accessible-photos', DI::l10n()->t('Make all posted pictures accessible'), DI::pConfig()->get(DI::userSession()->getLocalUserId(), 'system', 'accessible-photos'), DI::l10n()->t("This option makes every posted picture accessible via the direct link. This is a workaround for the problem that most other networks can't handle permissions on pictures. Non public pictures still won't be visible for the public on your photo albums though.")],
			'$blockwall'          => ['blockwall', DI::l10n()->t('Allow friends to post to your profile page?'), (intval($user['blockwall']) ? '0' : '1'), DI::l10n()->t('Your contacts may write posts on your profile wall. These posts will be distributed to your contacts')],
			'$blocktags'          => ['blocktags', DI::l10n()->t('Allow friends to tag your posts?'), (intval($user['blocktags']) ? '0' : '1'), DI::l10n()->t('Your contacts can add additional tags to your posts.')],
			'$unkmail'            => ['unkmail', DI::l10n()->t('Permit unknown people to send you private mail?'), $unkmail, DI::l10n()->t('Friendica network users may send you private messages even if they are not in your contact list.')],
			'$cntunkmail'         => ['cntunkmail', DI::l10n()->t('Maximum private messages per day from unknown people:'), $cntunkmail, DI::l10n()->t("(to prevent spam abuse)")],
			'$group_select'       => Group::displayGroupSelection(DI::userSession()->getLocalUserId(), $user['def_gid']),
			'$permissions'        => DI::l10n()->t('Default Post Permissions'),
			'$aclselect'          => ACL::getFullSelectorHTML(DI::page(), $a->getLoggedInUserId()),

			'$expire' => [
				'label'        => DI::l10n()->t('Expiration settings'),
				'days'         => ['expire', DI::l10n()->t("Automatically expire posts after this many days:"), $expire, DI::l10n()->t('If empty, posts will not expire. Expired posts will be deleted')],
				'items'        => ['expire_items', DI::l10n()->t('Expire posts'), $expire_items, DI::l10n()->t('When activated, posts and comments will be expired.')],
				'notes'        => ['expire_notes', DI::l10n()->t('Expire personal notes'), $expire_notes, DI::l10n()->t('When activated, the personal notes on your profile page will be expired.')],
				'starred'      => ['expire_starred', DI::l10n()->t('Expire starred posts'), $expire_starred, DI::l10n()->t('Starring posts keeps them from being expired. That behaviour is overwritten by this setting.')],
				'network_only' => ['expire_network_only', DI::l10n()->t('Only expire posts by others'), $expire_network_only, DI::l10n()->t('When activated, your own posts never expire. Then the settings above are only valid for posts you received.')],
			],

			'$h_not'   => DI::l10n()->t('Notification Settings'),
			'$lbl_not' => DI::l10n()->t('Send a notification email when:'),
			'$notify1' => ['notify1', DI::l10n()->t('You receive an introduction'), ($notify & Notification\Type::INTRO), Notification\Type::INTRO, ''],
			'$notify2' => ['notify2', DI::l10n()->t('Your introductions are confirmed'), ($notify & Notification\Type::CONFIRM), Notification\Type::CONFIRM, ''],
			'$notify3' => ['notify3', DI::l10n()->t('Someone writes on your profile wall'), ($notify & Notification\Type::WALL), Notification\Type::WALL, ''],
			'$notify4' => ['notify4', DI::l10n()->t('Someone writes a followup comment'), ($notify & Notification\Type::COMMENT), Notification\Type::COMMENT, ''],
			'$notify5' => ['notify5', DI::l10n()->t('You receive a private message'), ($notify & Notification\Type::MAIL), Notification\Type::MAIL, ''],
			'$notify6' => ['notify6', DI::l10n()->t('You receive a friend suggestion'), ($notify & Notification\Type::SUGGEST), Notification\Type::SUGGEST, ''],
			'$notify7' => ['notify7', DI::l10n()->t('You are tagged in a post'), ($notify & Notification\Type::TAG_SELF), Notification\Type::TAG_SELF, ''],

			'$lbl_notify'                    => DI::l10n()->t('Create a desktop notification when:'),
			'$notify_tagged'                 => ['notify_tagged', DI::l10n()->t('Someone tagged you'), is_null($notify_type) || $notify_type & UserNotification::TYPE_EXPLICIT_TAGGED, ''],
			'$notify_direct_comment'         => ['notify_direct_comment', DI::l10n()->t('Someone directly commented on your post'), is_null($notify_type) || $notify_type & (UserNotification::TYPE_IMPLICIT_TAGGED + UserNotification::TYPE_DIRECT_COMMENT + UserNotification::TYPE_DIRECT_THREAD_COMMENT), ''],
			'$notify_like'                   => ['notify_like', DI::l10n()->t('Someone liked your content'), DI::pConfig()->get(DI::userSession()->getLocalUserId(), 'system', 'notify_like'), DI::l10n()->t('Can only be enabled, when the direct comment notification is enabled.')],
			'$notify_announce'               => ['notify_announce', DI::l10n()->t('Someone shared your content'), DI::pConfig()->get(DI::userSession()->getLocalUserId(), 'system', 'notify_announce'), DI::l10n()->t('Can only be enabled, when the direct comment notification is enabled.')],
			'$notify_thread_comment'         => ['notify_thread_comment', DI::l10n()->t('Someone commented in your thread'), is_null($notify_type) || $notify_type & UserNotification::TYPE_THREAD_COMMENT, ''],
			'$notify_comment_participation'  => ['notify_comment_participation', DI::l10n()->t('Someone commented in a thread where you commented'), is_null($notify_type) || $notify_type & UserNotification::TYPE_COMMENT_PARTICIPATION, ''],
			'$notify_activity_participation' => ['notify_activity_participation', DI::l10n()->t('Someone commented in a thread where you interacted'), is_null($notify_type) || $notify_type & UserNotification::TYPE_ACTIVITY_PARTICIPATION, ''],

			'$desktop_notifications' => ['desktop_notifications', DI::l10n()->t('Activate desktop notifications'), false, DI::l10n()->t('Show desktop popup on new notifications')],

			'$email_textonly' => [
				'email_textonly',
				DI::l10n()->t('Text-only notification emails'),
				DI::pConfig()->get(DI::userSession()->getLocalUserId(), 'system', 'email_textonly'),
				DI::l10n()->t('Send text only notification emails, without the html part')
			],
			'$detailed_notif' => [
				'detailed_notif',
				DI::l10n()->t('Show detailled notifications'),
				DI::pConfig()->get(DI::userSession()->getLocalUserId(), 'system', 'detailed_notif'),
				DI::l10n()->t('Per default, notifications are condensed to a single notification per item. When enabled every notification is displayed.')
			],
			'$notify_ignored' => [
				'notify_ignored',
				DI::l10n()->t('Show notifications of ignored contacts'),
				DI::pConfig()->get(DI::userSession()->getLocalUserId(), 'system', 'notify_ignored', true),
				DI::l10n()->t("You don't see posts from ignored contacts. But you still see their comments. This setting controls if you want to still receive regular notifications that are caused by ignored contacts or not.")
			],

			'$h_advn'     => DI::l10n()->t('Advanced Account/Page Type Settings'),
			'$h_descadvn' => DI::l10n()->t('Change the behaviour of this account for special situations'),
			'$pagetype'   => $pagetype,

			'$importcontact'         => DI::l10n()->t('Import Contacts'),
			'$importcontact_text'    => DI::l10n()->t('Upload a CSV file that contains the handle of your followed accounts in the first column you exported from the old account.'),
			'$importcontact_button'  => DI::l10n()->t('Upload File'),
			'$importcontact_maxsize' => DI::config()->get('system', 'max_csv_file_size', 30720),

			'$relocate'        => DI::l10n()->t('Relocate'),
			'$relocate_text'   => DI::l10n()->t("If you have moved this profile from another server, and some of your contacts don't receive your updates, try pushing this button."),
			'$relocate_button' => DI::l10n()->t("Resend relocate message to contacts"),
		]);

		return $o;
	}
}
