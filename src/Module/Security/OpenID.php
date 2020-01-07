<?php

namespace Friendica\Module\Security;

use Friendica\BaseModule;
use Friendica\Registry\DI;
use Friendica\Registry\App;
use Friendica\Registry\Core;
use Friendica\Util\Strings;
use LightOpenID;

/**
 * Performs an login with OpenID
 */
class OpenID extends BaseModule
{
	public static function content(array $parameters = [])
	{
		if (Core::config()->get('system', 'no_openid')) {
			App::baseUrl()->redirect();
		}

		Core::logger()->debug('mod_openid.', ['request' => $_REQUEST]);

		$session = Core::session();

		if (!empty($_GET['openid_mode']) && !empty($session->get('openid'))) {

			$openid = new LightOpenID(App::baseUrl()->getHostname());

			$l10n = Core::l10n();

			if ($openid->validate()) {
				$authId = $openid->data['openid_identity'];

				if (empty($authId)) {
					Core::logger()->info($l10n->t('OpenID protocol error. No ID returned'));
					App::baseUrl()->redirect();
				}

				// NOTE: we search both for normalised and non-normalised form of $authid
				//       because the normalization step was removed from setting
				//       mod/settings.php in 8367cad so it might have left mixed
				//       records in the user table
				//
				$condition = ['blocked' => false, 'account_expired' => false, 'account_removed' => false, 'verified' => true,
				              'openid' => [$authId, Strings::normaliseOpenID($authId)]];

				$dba = DI::dba();

				$user  = $dba->selectFirst('user', [], $condition);
				if ($dba->isResult($user)) {

					// successful OpenID login
					$session->remove('openid');

					App::auth()->setForUser(DI::app(), $user, true, true);

					// just in case there was no return url set
					// and we fell through
					App::baseUrl()->redirect();
				}

				// Successful OpenID login - but we can't match it to an existing account.
				$session->remove('register');
				$session->set('openid_attributes', $openid->getAttributes());
				$session->set('openid_identity', $authId);

				// Detect the server URL
				$open_id_obj = new LightOpenID(App::baseUrl()->getHostName());
				$open_id_obj->identity = $authId;
				$session->set('openid_server', $open_id_obj->discover($open_id_obj->identity));

				if (intval(Core::config()->get('config', 'register_policy')) === \Friendica\Module\Register::CLOSED) {
					notice($l10n->t('Account not found. Please login to your existing account to add the OpenID to it.'));
				} else {
					notice($l10n->t('Account not found. Please register a new account or login to your existing account to add the OpenID to it.'));
				}

				App::baseUrl()->redirect('login');
			}
		}
	}
}
