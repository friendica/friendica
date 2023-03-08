<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
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
use Friendica\DI;
use Friendica\Util\Strings;
use LightOpenID;

/**
 * Performs an login with OpenID
 */
class OpenID extends BaseModule
{
	protected function content(array $request = []): string
	{
		if (DI::config()->get('system', 'no_openid')) {
			DI::baseUrl()->redirect();
		}

		DI::logger()->debug('mod_openid.', ['request' => $_REQUEST]);

		$session = DI::session();

		if (!empty($_GET['openid_mode']) && !empty($session->get('openid'))) {

			$openid = new LightOpenID(DI::baseUrl()->getHost());

			$l10n = DI::l10n();

			if ($openid->validate()) {
				$authId = $openid->data['openid_identity'];

				if (empty($authId)) {
					DI::logger()->info($l10n->t('OpenID protocol error. No ID returned'));
					DI::baseUrl()->redirect();
				}

				// NOTE: we search both for normalised and non-normalised form of $authid
				//       because the normalization step was removed from settings
				//       in commit 8367cadeeffec4b6792a502847304b17ceba5882, so it might
				//       have left mixed records in the user table
				//
				$condition = ['blocked' => false, 'account_expired' => false, 'account_removed' => false, 'verified' => true,
				              'openid' => [$authId, Strings::normaliseOpenID($authId)]];

				$dba = DI::dba();

				$user  = $dba->selectFirst('user', [], $condition);
				if ($dba->isResult($user)) {

					// successful OpenID login
					$session->remove('openid');

					DI::auth()->setForUser(DI::app(), $user, true, true);

					$this->baseUrl->redirect(DI::session()->pop('return_path', ''));
				}

				// Successful OpenID login - but we can't match it to an existing account.
				$session->remove('register');
				$session->set('openid_attributes', $openid->getAttributes());
				$session->set('openid_identity', $authId);

				// Detect the server URL
				$open_id_obj = new LightOpenID(DI::baseUrl()->getHost());
				$open_id_obj->identity = $authId;
				$session->set('openid_server', $open_id_obj->discover($open_id_obj->identity));

				if (intval(DI::config()->get('config', 'register_policy')) === \Friendica\Module\Register::CLOSED) {
					DI::sysmsg()->addNotice($l10n->t('Account not found. Please login to your existing account to add the OpenID to it.'));
				} else {
					DI::sysmsg()->addNotice($l10n->t('Account not found. Please register a new account or login to your existing account to add the OpenID to it.'));
				}

				DI::baseUrl()->redirect('login');
			}
		}

		return '';
	}
}
