<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

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
				$condition = ['verified' => true, 'blocked' => false, 'account_removed' => false, 'account_expired' => false,
					'openid'                => [$authId, Strings::normaliseOpenID($authId)]];

				$dba = DI::dba();

				$user = $dba->selectFirst('user', [], $condition);
				if ($dba->isResult($user)) {

					// successful OpenID login
					$session->remove('openid');

					DI::auth()->setForUser($user, true, true);

					$this->baseUrl->redirect(DI::session()->pop('return_path', ''));
				}

				// Successful OpenID login - but we can't match it to an existing account.
				$session->remove('register');
				$session->set('openid_attributes', $openid->getAttributes());
				$session->set('openid_identity', $authId);

				// Detect the server URL
				$open_id_obj = new LightOpenID(DI::baseUrl()->getHost());
				/** @phpstan-ignore-next-line $openid->identity is private, but will be set via magic setter */
				$open_id_obj->identity = $authId;
				/** @phpstan-ignore-next-line $openid->identity is private, but will be retrieved via magic getter */
				$session->set('openid_server', $open_id_obj->discover($open_id_obj->identity));

				if (\Friendica\Module\Register::getPolicy() === \Friendica\Module\Register::CLOSED) {
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
