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

namespace Friendica\Module\Security;

use Friendica\BaseModule;
use Friendica\Core\Hook;
use Friendica\Core\Renderer;
use Friendica\Core\Session;
use Friendica\DI;
use Friendica\Module\Register;

/**
 * Login module
 */
class Login extends BaseModule
{
	protected function content(array $request = []): string
	{
		$return_path = $_REQUEST['return_path'] ?? '' ;

		if (local_user()) {
			DI::baseUrl()->redirect($return_path);
		} elseif (!empty($return_path)) {
			Session::set('return_path', $return_path);
		}

		return self::form(Session::get('return_path'), intval(DI::config()->get('config', 'register_policy')) !== \Friendica\Module\Register::CLOSED);
	}

	protected function post(array $request = [])
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

			DI::auth()->withOpenId($openid_url, !empty($_POST['remember']));
		}

		if (!empty($_POST['auth-params']) && $_POST['auth-params'] === 'login') {
			DI::auth()->withPassword(
				DI::app(),
				trim($_POST['username']),
				trim($_POST['password']),
				!empty($_POST['remember'])
			);
		}
	}

	/**
	 * Wrapper for adding a login box.
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

		$noid = DI::config()->get('system', 'no_openid');

		if ($noid) {
			Session::remove('openid_identity');
			Session::remove('openid_attributes');
		}

		$reg = false;
		if ($register && intval(DI::config()->get('config', 'register_policy')) !== Register::CLOSED) {
			$reg = [
				'title' => DI::l10n()->t('Create a New Account'),
				'desc' => DI::l10n()->t('Register'),
				'url' => self::getRegisterURL()
			];
		}

		if (is_null($return_path)) {
			$return_path = DI::args()->getQueryString();
		}

		if (local_user()) {
			$tpl = Renderer::getMarkupTemplate('logout.tpl');
		} else {
			DI::page()['htmlhead'] .= Renderer::replaceMacros(
				Renderer::getMarkupTemplate('login_head.tpl'),
				[
					'$baseurl' => DI::baseUrl()->get(true)
				]
			);

			$tpl = Renderer::getMarkupTemplate('login.tpl');
			$_SESSION['return_path'] = $return_path;
		}

		if (!empty(Session::get('openid_identity'))) {
			$openid_title = DI::l10n()->t('Your OpenID: ');
			$openid_readonly = true;
			$identity = Session::get('openid_identity');
			$username_desc = DI::l10n()->t('Please enter your username and password to add the OpenID to your existing account.');
		} else {
			$openid_title = DI::l10n()->t('Or login using OpenID: ');
			$openid_readonly = false;
			$identity = '';
			$username_desc = '';
		}

		$o .= Renderer::replaceMacros(
			$tpl,
			[
				'$dest_url'     => DI::baseUrl()->get(true) . '/login',
				'$logout'       => DI::l10n()->t('Logout'),
				'$login'        => DI::l10n()->t('Login'),

				'$lname'        => ['username', DI::l10n()->t('Nickname or Email: '), '', $username_desc],
				'$lpassword'    => ['password', DI::l10n()->t('Password: '), '', ''],
				'$lremember'    => ['remember', DI::l10n()->t('Remember me'), 0,  ''],

				'$openid'       => !$noid,
				'$lopenid'      => ['openid_url', $openid_title, $identity, '', $openid_readonly],

				'$hiddens'      => $hiddens,

				'$register'     => $reg,

				'$lostpass'     => DI::l10n()->t('Forgot your password?'),
				'$lostlink'     => DI::l10n()->t('Password Reset'),

				'$tostitle'     => DI::l10n()->t('Website Terms of Service'),
				'$toslink'      => DI::l10n()->t('terms of service'),

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

		$args['openid_url'] = trim(Session::get('openid_identity'));

		return 'register?' . http_build_query($args);
	}
}
