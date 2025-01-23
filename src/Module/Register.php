<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module;

use Friendica\App\Arguments;
use Friendica\App\BaseURL;
use Friendica\BaseModule;
use Friendica\Content\Text\BBCode;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\Hook;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Core\Worker;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model;
use Friendica\Model\User;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Profiler;
use Friendica\Util\Proxy;
use Psr\Log\LoggerInterface;

/**
 * @author Hypolite Petovan <hypolite@mrpetovan.com>
 */
class Register extends BaseModule
{
	const CLOSED  = 0;
	const APPROVE = 1;
	const OPEN    = 2;

	/** @var Tos */
	protected $tos;

	/** @var IHandleUserSessions */
	private $session;

	public function __construct(IHandleUserSessions $session, L10n $l10n, BaseURL $baseUrl, Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, IManageConfigValues $config, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->tos = new Tos($l10n, $baseUrl, $args, $logger, $profiler, $response, $config, $server, $parameters);

		$this->session = $session;
	}

	/**
	 * Module GET method to display any content
	 *
	 * Extend this method if the module is supposed to return any display
	 * through a GET request. It can be an HTML page through templating or a
	 * XML feed or a JSON output.
	 *
	 * @return string
	 */
	protected function content(array $request = []): string
	{
		// logged in users can register others (people/pages/groups)
		// even with closed registrations, unless specifically prohibited by site policy.
		// 'block_extended_register' blocks all registrations, period.
		$block = DI::config()->get('system', 'block_extended_register');

		if (DI::userSession()->getLocalUserId() && $block) {
			DI::sysmsg()->addNotice(DI::l10n()->t('Permission denied.'));
			return '';
		}

		if (DI::userSession()->getLocalUserId()) {
			$user = DBA::selectFirst('user', ['parent-uid'], ['uid' => DI::userSession()->getLocalUserId()]);
			if (!empty($user['parent-uid'])) {
				DI::sysmsg()->addNotice(DI::l10n()->t('Only parent users can create additional accounts.'));
				return '';
			}
		}

		if (!DI::userSession()->getLocalUserId() && self::getPolicy() === self::CLOSED) {
			DI::sysmsg()->addNotice(DI::l10n()->t('Permission denied.'));
			return '';
		}

		$max_dailies = intval(DI::config()->get('system', 'max_daily_registrations'));
		if ($max_dailies) {
			$count = DBA::count('user', ['`register_date` > UTC_TIMESTAMP - INTERVAL 1 day']);
			if ($count >= $max_dailies) {
				$this->logger->notice('max daily registrations exceeded.');
				DI::sysmsg()->addNotice(DI::l10n()->t('This site has exceeded the number of allowed daily account registrations. Please try again tomorrow.'));
				return '';
			}
		}

		$username   = $_REQUEST['username']   ?? '';
		$email      = $_REQUEST['email']      ?? '';
		$openid_url = $_REQUEST['openid_url'] ?? '';
		$nickname   = $_REQUEST['nickname']   ?? '';
		$photo      = $_REQUEST['photo']      ?? '';
		$invite_id  = $_REQUEST['invite_id']  ?? '';

		if (DI::userSession()->getLocalUserId() || DI::config()->get('system', 'no_openid')) {
			$fillwith = '';
			$fillext  = '';
			$oidlabel = '';
		} else {
			$fillwith = DI::l10n()->t('You may (optionally) fill in this form via OpenID by supplying your OpenID and clicking "Register".');
			$fillext  = DI::l10n()->t('If you are not familiar with OpenID, please leave that field blank and fill in the rest of the items.');
			$oidlabel = DI::l10n()->t('Your OpenID (optional): ');
		}

		if (DI::config()->get('system', 'publish_all')) {
			$profile_publish = '<input type="hidden" name="profile_publish_reg" value="1" />';
		} else {
			$publish_tpl     = Renderer::getMarkupTemplate('profile/publish.tpl');
			$profile_publish = Renderer::replaceMacros($publish_tpl, [
				'$instance'     => 'reg',
				'$pubdesc'      => DI::l10n()->t('Include your profile in member directory?'),
				'$yes_selected' => '',
				'$no_selected'  => ' checked="checked"',
				'$str_yes'      => DI::l10n()->t('Yes'),
				'$str_no'       => DI::l10n()->t('No'),
			]);
		}

		$ask_password = !DBA::count('contact');

		$tpl = Renderer::getMarkupTemplate('register.tpl');

		$arr = ['template' => $tpl];

		Hook::callAll('register_form', $arr);

		$tpl = $arr['template'];

		$o = Renderer::replaceMacros($tpl, [
			'$invitations'           => DI::config()->get('system', 'invitation_only'),
			'$permonly'              => self::getPolicy() === self::APPROVE,
			'$permonlybox'           => ['permonlybox', DI::l10n()->t('Note for the admin'), '', DI::l10n()->t('Leave a message for the admin, why you want to join this node'), DI::l10n()->t('Required')],
			'$invite_desc'           => DI::l10n()->t('Membership on this site is by invitation only.'),
			'$invite_label'          => DI::l10n()->t('Your invitation code: '),
			'$invite_id'             => $invite_id,
			'$regtitle'              => DI::l10n()->t('Registration'),
			'$registertext'          => BBCode::convertForUriId(User::getSystemUriId(), DI::config()->get('config', 'register_text', '')),
			'$fillwith'              => $fillwith,
			'$fillext'               => $fillext,
			'$oidlabel'              => $oidlabel,
			'$openid'                => $openid_url,
			'$namelabel'             => DI::l10n()->t('Your Display Name (as you would like it to be displayed on this system'),
			'$addrlabel'             => DI::l10n()->t('Your Email Address: (Initial information will be send there, so this has to be an existing address.)'),
			'$addrlabel2'            => DI::l10n()->t('Please repeat your e-mail address:'),
			'$ask_password'          => $ask_password,
			'$password1'             => ['password1', DI::l10n()->t('New Password:'), '', DI::l10n()->t('Leave empty for an auto generated password.')],
			'$password2'             => ['confirm', DI::l10n()->t('Confirm:'), '', ''],
			'$nickdesc'              => DI::l10n()->t('Choose a profile nickname. This must begin with a text character. Your profile address on this site will then be "<strong>nickname@%s</strong>".', DI::baseUrl()->getHost()),
			'$nicklabel'             => DI::l10n()->t('Choose a nickname: '),
			'$photo'                 => $photo,
			'$publish'               => $profile_publish,
			'$regbutt'               => DI::l10n()->t('Register'),
			'$username'              => $username,
			'$email'                 => $email,
			'$nickname'              => $nickname,
			'$sitename'              => DI::baseUrl()->getHost(),
			'$importh'               => DI::l10n()->t('Import'),
			'$importt'               => DI::l10n()->t('Import your profile to this friendica instance'),
			'$showtoslink'           => DI::config()->get('system', 'tosdisplay'),
			'$tostext'               => DI::l10n()->t('Terms of Service'),
			'$showprivstatement'     => DI::config()->get('system', 'tosprivstatement'),
			'$privstatement'         => $this->tos->privacy_complete,
			'$form_security_token'   => BaseModule::getFormSecurityToken('register'),
			'$explicit_content'      => DI::config()->get('system', 'explicit_content', false),
			'$explicit_content_note' => DI::l10n()->t('Note: This node explicitly contains adult content'),
			'$additional'            => !empty(DI::userSession()->getLocalUserId()),
			'$parent_password'       => ['parent_password', DI::l10n()->t('Parent Password:'), '', DI::l10n()->t('Please enter the password of the parent account to legitimize your request.')]

		]);

		return $o;
	}

	/**
	 * Module POST method to process submitted data
	 *
	 * Extend this method if the module is supposed to process POST requests.
	 * Doesn't display any content
	 */
	protected function post(array $request = [])
	{
		BaseModule::checkFormSecurityTokenRedirectOnError('/register', 'register');

		$arr = ['post' => $_POST];
		Hook::callAll('register_post', $arr);

		$additional_account = false;

		if (!DI::userSession()->getLocalUserId() && !empty($arr['post']['parent_password'])) {
			DI::sysmsg()->addNotice(DI::l10n()->t('Permission denied.'));
			return;
		} elseif (DI::userSession()->getLocalUserId() && !empty($arr['post']['parent_password'])) {
			try {
				Model\User::getIdFromPasswordAuthentication(DI::userSession()->getLocalUserId(), $arr['post']['parent_password']);
			} catch (\Exception $ex) {
				DI::sysmsg()->addNotice(DI::l10n()->t("Password doesn't match."));
				$regdata = ['nickname' => $arr['post']['nickname'], 'username' => $arr['post']['username']];
				DI::baseUrl()->redirect('register?' . http_build_query($regdata));
			}
			$additional_account = true;
		} elseif (DI::userSession()->getLocalUserId()) {
			DI::sysmsg()->addNotice(DI::l10n()->t('Please enter your password.'));
			$regdata = ['nickname' => $arr['post']['nickname'], 'username' => $arr['post']['username']];
			DI::baseUrl()->redirect('register?' . http_build_query($regdata));
		}

		$max_dailies = intval(DI::config()->get('system', 'max_daily_registrations'));
		if ($max_dailies) {
			$count = DBA::count('user', ['`register_date` > UTC_TIMESTAMP - INTERVAL 1 day']);
			if ($count >= $max_dailies) {
				return;
			}
		}

		switch (self::getPolicy()) {
			case self::OPEN:
				$blocked  = 0;
				$verified = 1;
				break;

			case self::APPROVE:
				$blocked  = 1;
				$verified = 0;
				break;

			case self::CLOSED:
			default:
				if (!$this->session->isSiteAdmin()) {
					DI::sysmsg()->addNotice(DI::l10n()->t('Permission denied.'));
					return;
				}
				$blocked  = 1;
				$verified = 0;
				break;
		}

		$netpublish = !empty($_POST['profile_publish_reg']);

		$arr = $_POST;

		// Is there text in the tar pit?
		if (!empty($arr['email'])) {
			$this->logger->info('Tar pit', $arr);
			DI::sysmsg()->addNotice(DI::l10n()->t('You have entered too much information.'));
			DI::baseUrl()->redirect('register/');
		}

		if ($additional_account) {
			$user = DBA::selectFirst('user', ['email'], ['uid' => DI::userSession()->getLocalUserId()]);
			if (!DBA::isResult($user)) {
				DI::sysmsg()->addNotice(DI::l10n()->t('User not found.'));
				DI::baseUrl()->redirect('register');
			}

			$blocked  = 0;
			$verified = 1;

			$arr['password1'] = $arr['confirm'] = $arr['parent_password'];
			$arr['repeat']    = $arr['email'] = $user['email'];
		} else {
			// Overwriting the "tar pit" field with the real one
			$arr['email'] = $arr['field1'];
		}

		if ($arr['email'] != $arr['repeat']) {
			$this->logger->info('Mail mismatch', $arr);
			DI::sysmsg()->addNotice(DI::l10n()->t('Please enter the identical mail address in the second field.'));
			$regdata = ['email' => $arr['email'], 'nickname' => $arr['nickname'], 'username' => $arr['username']];
			DI::baseUrl()->redirect('register?' . http_build_query($regdata));
		}

		//Check if nickname contains only US-ASCII and do not start with a digit
		if (!preg_match('/^[a-zA-Z][a-zA-Z0-9]*$/', $arr['nickname'])) {
			if (is_numeric(substr($arr['nickname'], 0, 1))) {
				DI::sysmsg()->addNotice(DI::l10n()->t("Nickname cannot start with a digit."));
			} else {
				DI::sysmsg()->addNotice(DI::l10n()->t("Nickname can only contain US-ASCII characters."));
			}
			$regdata = ['email' => $arr['email'], 'nickname' => $arr['nickname'], 'username' => $arr['username']];
			DI::baseUrl()->redirect('register?' . http_build_query($regdata));
			return;
		}

		$arr['blocked']  = $blocked;
		$arr['verified'] = $verified;
		$arr['language'] = L10n::detectLanguage($_SERVER, $_GET, DI::config()->get('system', 'language'));

		try {
			$result = Model\User::create($arr);
		} catch (\Exception $e) {
			DI::sysmsg()->addNotice($e->getMessage());
			return;
		}

		$user = $result['user'];

		$base_url = (string)DI::baseUrl();

		if ($netpublish && self::getPolicy() !== self::APPROVE) {
			$url = $base_url . '/profile/' . $user['nickname'];
			Worker::add(Worker::PRIORITY_LOW, 'Directory', $url);
		}

		if ($additional_account) {
			DBA::update('user', ['parent-uid' => DI::userSession()->getLocalUserId()], ['uid' => $user['uid']]);
			DI::sysmsg()->addInfo(DI::l10n()->t('The additional account was created.'));
			DI::baseUrl()->redirect('delegation');
		}

		$using_invites = DI::config()->get('system', 'invitation_only');
		$num_invites   = DI::config()->get('system', 'number_invites');
		$invite_id     = (!empty($_POST['invite_id']) ? trim($_POST['invite_id']) : '');

		if (self::getPolicy() === self::OPEN) {
			if ($using_invites && $invite_id) {
				Model\Register::deleteByHash($invite_id);
				DI::pConfig()->set($user['uid'], 'system', 'invites_remaining', $num_invites);
			}

			// Only send a password mail when the password wasn't manually provided
			if (empty($_POST['password1']) || empty($_POST['confirm'])) {
				$res = Model\User::sendRegisterOpenEmail(
					DI::l10n()->withLang($arr['language']),
					$user,
					DI::config()->get('config', 'sitename'),
					$base_url,
					$result['password']
				);

				if ($res) {
					DI::sysmsg()->addInfo(DI::l10n()->t('Registration successful. Please check your email for further instructions.'));
					if (DI::config()->get('system', 'register_notification')) {
						$this->sendNotification($user, 'SYSTEM_REGISTER_NEW');
					}
					DI::baseUrl()->redirect();
				} else {
					DI::sysmsg()->addNotice(
						DI::l10n()->t(
							'Failed to send email message. Here your accout details:<br> login: %s<br> password: %s<br><br>You can change your password after login.',
							$user['email'],
							$result['password']
						)
					);
				}
			} else {
				DI::sysmsg()->addInfo(DI::l10n()->t('Registration successful.'));
				if (DI::config()->get('system', 'register_notification')) {
					$this->sendNotification($user, 'SYSTEM_REGISTER_NEW');
				}
				DI::baseUrl()->redirect();
			}
		} elseif (self::getPolicy() === self::APPROVE) {
			if (!User::getAdminEmailList()) {
				$this->logger->critical('Registration policy is set to APPROVE but no admin email address has been set in config.admin_email');
				DI::sysmsg()->addNotice(DI::l10n()->t('Your registration can not be processed.'));
				DI::baseUrl()->redirect();
			}

			// Check if the note to the admin is actually filled out
			if (empty($_POST['permonlybox'])) {
				DI::sysmsg()->addNotice(DI::l10n()->t('You have to leave a request note for the admin.')
					. DI::l10n()->t('Your registration can not be processed.'));

				$this->baseUrl->redirect('register');
			}

			try {
				Model\Register::createForApproval($user['uid'], DI::config()->get('system', 'language'), $_POST['permonlybox']);
			} catch (\Throwable $e) {
				$this->logger->error('Unable to create a `register` record.', ['user' => $user]);
				DI::sysmsg()->addNotice(DI::l10n()->t('An internal error occured.')
					. DI::l10n()->t('Your registration can not be processed.'));
				$this->baseUrl->redirect('register');
			}

			// invite system
			if ($using_invites && $invite_id) {
				Model\Register::deleteByHash($invite_id);
				DI::pConfig()->set($user['uid'], 'system', 'invites_remaining', $num_invites);
			}

			// send notification to the admin
			$this->sendNotification($user, 'SYSTEM_REGISTER_REQUEST');

			// send notification to the user, that the registration is pending
			Model\User::sendRegisterPendingEmail(
				$user,
				DI::config()->get('config', 'sitename'),
				$base_url,
				$result['password']
			);

			DI::sysmsg()->addInfo(DI::l10n()->t('Your registration is pending approval by the site owner.'));
			DI::baseUrl()->redirect();
		}
	}

	private function sendNotification(array $user, string $event)
	{
		foreach (User::getAdminListForEmailing(['uid', 'language', 'email']) as $admin) {
			DI::notify()->createFromArray([
				'type'                      => Model\Notification\Type::SYSTEM,
				'event'                     => $event,
				'uid'                       => $admin['uid'],
				'link'                      => DI::baseUrl() . '/moderation/users/',
				'source_name'               => $user['username'],
				'source_mail'               => $user['email'],
				'source_nick'               => $user['nickname'],
				'source_link'               => DI::baseUrl() . '/moderation/users/',
				'source_photo'              => User::getAvatarUrl($user, Proxy::SIZE_THUMB),
				'show_in_notification_page' => false
			]);
		}
	}
	public static function getPolicy(): int
	{
		$admins = User::getAdminList(['login_date']);
		$days   = DI::config()->get('system', 'admin_inactivity_limit');
		if ($days == 0 || empty($admins)) {
			return intval(DI::config()->get('config', 'register_policy'));
		}

		$inactive_since = DateTimeFormat::utc('now - ' . $days . ' day');
		foreach ($admins as $admin) {
			if (strtotime($admin['login_date']) > strtotime($inactive_since)) {
				return intval(DI::config()->get('config', 'register_policy'));
			}
		}
		return self::CLOSED;
	}
}
