<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Security;

use Friendica\App;
use Friendica\BaseModule;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\Hook;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\DI;
use Friendica\Module\Register;
use Friendica\Module\Response;
use Friendica\Security\Authentication;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

/**
 * Login module
 */
class Login extends BaseModule
{
	/** @var Authentication */
	private $auth;

	/** @var IManageConfigValues */
	private $config;

	/** @var IHandleUserSessions */
	private $session;

	public function __construct(Authentication $auth, IManageConfigValues $config, IHandleUserSessions $session, L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->auth    = $auth;
		$this->config  = $config;
		$this->session = $session;
	}

	protected function content(array $request = []): string
	{
		if (!empty($request['return_authorize'])) {
			$return_path = 'oauth/authorize?' . $request['return_authorize'];
		} else {
			$return_path = $request['return_path'] ?? $this->session->pop('return_path', '') ;
		}

		if ($this->session->getLocalUserId()) {
			$this->baseUrl->redirect($return_path);
		}

		return self::form($return_path, \Friendica\Module\Register::getPolicy() !== \Friendica\Module\Register::CLOSED);
	}

	protected function post(array $request = [])
	{
		// Save sysmessages before clearing session
		$notices = $this->session->get('sysmsg', []);
		$infos   = $this->session->get('sysmsg_info', []);

		$this->session->clear();

		// Restore sysmessages after clearing
		if (!empty($notices)) {
			$this->session->set('sysmsg', $notices);
		}
		if (!empty($infos)) {
			$this->session->set('sysmsg_info', $infos);
		}

		// OpenId Login
		if (
			empty($request['password'])
			&& (!empty($request['openid_url'])
				|| !empty($request['username']))
		) {
			$openid_url = trim(($request['openid_url'] ?? '') ?: $request['username']);

			$this->auth->withOpenId($openid_url, !empty($request['remember']));
		}

		if (!empty($request['auth-params']) && $request['auth-params'] === 'login') {
			$this->auth->withPassword(
				trim($request['username']),
				trim($request['password']),
				!empty($request['remember']),
				$request['return_path'] ?? ''
			);
		}
	}

	/**
	 * Wrapper for adding a login box.
	 *
	 * @param string|null $return_path The path relative to the base the user should be sent back to after login completes.
	 * @param bool        $register    If $register == true provide a registration link.
	 *                                 This will almost always depend on the value of config.register_policy.
	 *
	 * @return string Returns the complete html for inserting into the page
	 *
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \Friendica\Network\HTTPException\ServiceUnavailableException
	 * @hooks 'login_hook' string $o
	 */
	public static function form(string $return_path = null, bool $register = false): string
	{
		$noid = DI::config()->get('system', 'no_openid');

		if ($noid) {
			DI::session()->remove('openid_identity');
			DI::session()->remove('openid_attributes');
		}

		// Retrieve system messages to display on the login page
		$notices = DI::sysmsg()->flushNotices();

		$reg = false;
		if ($register && Register::getPolicy() !== Register::CLOSED) {
			$reg = [
				'title' => DI::l10n()->t('Create an account'),
				'desc'  => DI::l10n()->t('Register'),
				'url'   => self::getRegisterURL()
			];
		}

		if (DI::userSession()->getLocalUserId()) {
			$tpl = Renderer::getMarkupTemplate('logout.tpl');
		} else {
			DI::page()['htmlhead'] .= Renderer::replaceMacros(
				Renderer::getMarkupTemplate('login_head.tpl'),
				[
				]
			);

			$tpl = Renderer::getMarkupTemplate('login.tpl');
		}

		if (!empty(DI::session()->get('openid_identity'))) {
			$openid_title    = DI::l10n()->t('Your OpenID: ');
			$openid_readonly = true;
			$identity        = DI::session()->get('openid_identity');
			$username_desc   = DI::l10n()->t('Please enter your username and password to add the OpenID to your existing account.');
		} else {
			$openid_title    = DI::l10n()->t('Or sign in using OpenID');
			$openid_readonly = false;
			$identity        = '';
			$username_desc   = '';
		}
		$openid_placeholder = DI::l10n()->t('OpenID');

		$o = Renderer::replaceMacros(
			$tpl,
			[
				'$notices'  => $notices,
				'$dest_url' => DI::baseUrl() . '/login',
				'$logout'   => DI::l10n()->t('Sign out'),
				'$login'    => DI::l10n()->t('Sign in'),
				'$new'      => DI::l10n()->t('New here?'),

				'$lname'     => ['username', DI::l10n()->t('Nickname or email'), '', $username_desc, '', 'autofocus', '', DI::l10n()->t('Nickname or email')],
				'$lpassword' => ['password', DI::l10n()->t('Password'), '', '', '', '', '', DI::l10n()->t('Password')],
				'$lremember' => ['remember', DI::l10n()->t('Remember me'), 0,  ''],

				'$openid'  => !$noid,
				'$lopenid' => ['openid_url', $openid_title, $identity, '', $openid_readonly, $openid_placeholder],

				'$hiddens' => ['return_path' => $return_path ?? DI::args()->getQueryString()],

				'$register' => $reg,

				'$lostpass' => DI::l10n()->t('Forgot your password?'),
				'$lostlink' => DI::l10n()->t('Password Reset'),

				'$tostitle' => DI::l10n()->t('Website Terms of Service'),
				'$toslink'  => DI::l10n()->t('terms of service'),

				'$privacytitle' => DI::l10n()->t('Website Privacy Policy'),
				'$privacylink'  => DI::l10n()->t('privacy policy'),
			]
		);

		Hook::callAll('login_hook', $o);

		return $o;
	}

	/**
	 * Get the URL to the register page and add OpenID parameters to it
	 */
	private static function getRegisterURL(): string
	{
		if (empty(DI::session()->get('openid_identity'))) {
			return 'register';
		}

		$args = [];
		$attr = DI::session()->get('openid_attributes', []);

		if (is_array($attr) && count($attr)) {
			foreach ($attr as $k => $v) {
				if ($k === 'namePerson/friendly') {
					$nick = trim($v);
				}
				if ($k === 'namePerson/first') {
					$first = trim($v);
				}
				if ($k === 'namePerson') {
					$args['username'] = trim($v);
				}
				if ($k === 'contact/email') {
					$args['email'] = trim($v);
				}
				if ($k === 'media/image/aspect11') {
					$photosq = bin2hex(trim($v));
				}
				if ($k === 'media/image/default') {
					$photo = bin2hex(trim($v));
				}
			}
		}

		if (!empty($nick)) {
			$args['nickname'] = $nick;
		} elseif (!empty($first)) {
			$args['nickname'] = $first;
		}

		if (!empty($photosq)) {
			$args['photo'] = $photosq;
		} elseif (!empty($photo)) {
			$args['photo'] = $photo;
		}

		$args['openid_url'] = trim(DI::session()->get('openid_identity'));

		return 'register?' . http_build_query($args);
	}
}
