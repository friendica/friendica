<?php

/**
 * @file src/Module/Login.php
 */

namespace Friendica\Module\Security;

use Friendica\BaseModule;
use Friendica\Core\Config;
use Friendica\Core\Hook;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Core\Session;
use Friendica\Registry\DI;
use Friendica\Module\Register;
use Friendica\Registry\App;
use Friendica\Registry\Core;
use Friendica\Util\Strings;

/**
 * Login module
 *
 * @author Hypolite Petovan <hypolite@mrpetovan.com>
 */
class Login extends BaseModule
{
	public static function content(array $parameters = [])
	{
		if (local_user()) {
			App::baseUrl()->redirect();
		}

		return self::form(Session::get('return_path'), intval(Config::get('config', 'register_policy')) !== \Friendica\Module\Register::CLOSED);
	}

	public static function post(array $parameters = [])
	{
		$return_path = Session::get('return_path');
		Session::clear();
		Session::set('return_path', $return_path);

		// OpenId Login
		if (
			empty($_POST['password'])
			&& (!empty($_POST['openid_url'])
				|| !empty($_POST['username']))
		) {
			$openid_url = trim(($_POST['openid_url'] ?? '') ?: $_POST['username']);

			App::auth()->withOpenId($openid_url, !empty($_POST['remember']));
		}

		if (!empty($_POST['auth-params']) && $_POST['auth-params'] === 'login') {
			App::auth()->withPassword(
				DI::app(),
				trim($_POST['username']),
				trim($_POST['password']),
				!empty($_POST['remember'])
			);
		}
	}

	/**
	 * @brief Wrapper for adding a login box.
	 *
	 * @param string $return_path  The path relative to the base the user should be sent
	 *                             back to after login completes
	 * @param bool   $register     If $register == true provide a registration link.
	 *                             This will most always depend on the value of config.register_policy.
	 * @param array  $hiddens      optional
	 *
	 * @return string Returns the complete html for inserting into the page
	 *
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @hooks 'login_hook' string $o
	 */
	public static function form($return_path = null, $register = false, $hiddens = [])
	{
		$o = '';

		$noid = Config::get('system', 'no_openid');

		if ($noid) {
			Session::remove('openid_identity');
			Session::remove('openid_attributes');
		}

		$reg = false;
		if ($register && intval(Core::config()->get('config', 'register_policy')) !== Register::CLOSED) {
			$reg = [
				'title' => L10n::t('Create a New Account'),
				'desc' => L10n::t('Register'),
				'url' => self::getRegisterURL()
			];
		}

		if (is_null($return_path)) {
			$return_path = App::args()->getQueryString();
		}

		if (local_user()) {
			$tpl = Renderer::getMarkupTemplate('logout.tpl');
		} else {
			App::page()['htmlhead'] .= Renderer::replaceMacros(
				Renderer::getMarkupTemplate('login_head.tpl'),
				[
					'$baseurl' => App::baseUrl()->get(true)
				]
			);

			$tpl = Renderer::getMarkupTemplate('login.tpl');
			$_SESSION['return_path'] = $return_path;
		}

		if (!empty(Session::get('openid_identity'))) {
			$openid_title = L10n::t('Your OpenID: ');
			$openid_readonly = true;
			$identity = Session::get('openid_identity');
			$username_desc = L10n::t('Please enter your username and password to add the OpenID to your existing account.');
		} else {
			$openid_title = L10n::t('Or login using OpenID: ');
			$openid_readonly = false;
			$identity = '';
			$username_desc = '';
		}

		$o .= Renderer::replaceMacros(
			$tpl,
			[
				'$dest_url'     => App::baseUrl()->get(true) . '/login',
				'$logout'       => L10n::t('Logout'),
				'$login'        => L10n::t('Login'),

				'$lname'        => ['username', L10n::t('Nickname or Email: '), '', $username_desc],
				'$lpassword'    => ['password', L10n::t('Password: '), '', ''],
				'$lremember'    => ['remember', L10n::t('Remember me'), 0,  ''],

				'$openid'       => !$noid,
				'$lopenid'      => ['openid_url', $openid_title, $identity, '', $openid_readonly],

				'$hiddens'      => $hiddens,

				'$register'     => $reg,

				'$lostpass'     => L10n::t('Forgot your password?'),
				'$lostlink'     => L10n::t('Password Reset'),

				'$tostitle'     => L10n::t('Website Terms of Service'),
				'$toslink'      => L10n::t('terms of service'),

				'$privacytitle' => L10n::t('Website Privacy Policy'),
				'$privacylink'  => L10n::t('privacy policy'),
			]
		);

		Hook::callAll('login_hook', $o);

		return $o;
	}

	/**
	 * Get the URL to the register page and add OpenID parameters to it
	 */
	private static function getRegisterURL()
	{
		if (empty(Session::get('openid_identity'))) {
			return 'register';
		}

		$args = [];
		$attr = Session::get('openid_attributes', []);

		if (is_array($attr) && count($attr)) {
			foreach ($attr as $k => $v) {
				if ($k === 'namePerson/friendly') {
					$nick = Strings::escapeTags(trim($v));
				}
				if ($k === 'namePerson/first') {
					$first = Strings::escapeTags(trim($v));
				}
				if ($k === 'namePerson') {
					$args['username'] = Strings::escapeTags(trim($v));
				}
				if ($k === 'contact/email') {
					$args['email'] = Strings::escapeTags(trim($v));
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

		$args['openid_url'] = Strings::escapeTags(trim(Session::get('openid_identity')));

		return 'register?' . http_build_query($args);
	}
}
