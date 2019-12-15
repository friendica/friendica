<?php
/**
 * @file mod/openid.php
 */

use Friendica\App;
use Friendica\Core\Config;
use Friendica\Core\L10n;
use Friendica\Core\Logger;
use Friendica\Core\Session;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Util\Strings;

function openid_content(App $a) {

	if (Config::get('system','no_openid')) {
		DI::baseUrl()->redirect();
	}

	Logger::log('mod_openid ' . print_r($_REQUEST,true), Logger::DATA);

	if (!empty($_GET['openid_mode']) && !empty($_SESSION['openid'])) {

		$openid = new LightOpenID(DI::baseUrl()->getHostname());

		if ($openid->validate()) {
			$authid = $openid->data['openid_identity'];

			if (empty($authid)) {
				Logger::log(L10n::t('OpenID protocol error. No ID returned.') . EOL);
				DI::baseUrl()->redirect();
			}

			// NOTE: we search both for normalised and non-normalised form of $authid
			//       because the normalization step was removed from setting
			//       mod/settings.php in 8367cad so it might have left mixed
			//       records in the user table
			//
			$condition = ['blocked' => false, 'account_expired' => false, 'account_removed' => false, 'verified' => true,
				'openid' => [$authid, Strings::normaliseOpenID($authid)]];
			$user  = DBA::selectFirst('user', [], $condition);
			if (DBA::isResult($user)) {

				// successful OpenID login

				unset($_SESSION['openid']);

				DI::auth()->setForUser($a, $user, true, true);

				// just in case there was no return url set
				// and we fell through

				DI::baseUrl()->redirect();
			}

			// Successful OpenID login - but we can't match it to an existing account.
			unset($_SESSION['register']);
			Session::set('openid_attributes', $openid->getAttributes());
			Session::set('openid_identity', $authid);

			// Detect the server URL
			$open_id_obj = new LightOpenID(DI::baseUrl()->getHostname());
			$open_id_obj->identity = $authid;
			Session::set('openid_server', $open_id_obj->discover($open_id_obj->identity));

			if (intval(Config::get('config', 'register_policy')) === \Friendica\Module\Register::CLOSED) {
				notice(L10n::t('Account not found. Please login to your existing account to add the OpenID to it.'));
			} else {
				notice(L10n::t('Account not found. Please register a new account or login to your existing account to add the OpenID to it.'));
			}

			DI::baseUrl()->redirect('login');
		}
	}
	notice(L10n::t('Login failed.') . EOL);
	DI::baseUrl()->redirect();
	// NOTREACHED
}
