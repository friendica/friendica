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

namespace Friendica\Module\Settings\TwoFactor;

use Friendica\Core\Renderer;
use Friendica\DI;
use Friendica\Network\HTTPException\FoundException;
use Friendica\Security\TwoFactor\Model\AppSpecificPassword;
use Friendica\Security\TwoFactor\Model\RecoveryCode;
use Friendica\Model\User;
use Friendica\Module\BaseSettings;
use Friendica\Module\Security\Login;
use PragmaRX\Google2FA\Google2FA;

class Index extends BaseSettings
{
	protected function post(array $request = [])
	{
		if (!DI::userSession()->getLocalUserId()) {
			return;
		}

		self::checkFormSecurityTokenRedirectOnError('settings/2fa', 'settings_2fa');

		try {
			User::getIdFromPasswordAuthentication(DI::userSession()->getLocalUserId(), $_POST['password'] ?? '');

			$has_secret = (bool)DI::pConfig()->get(DI::userSession()->getLocalUserId(), '2fa', 'secret');
			$verified   = DI::pConfig()->get(DI::userSession()->getLocalUserId(), '2fa', 'verified');

			switch ($_POST['action'] ?? '') {
				case 'enable':
					if (!$has_secret && !$verified) {
						$Google2FA = new Google2FA();

						DI::pConfig()->set(DI::userSession()->getLocalUserId(), '2fa', 'secret', $Google2FA->generateSecretKey(32));

						DI::baseUrl()
						  ->redirect('settings/2fa/recovery?t=' . self::getFormSecurityToken('settings_2fa_password'));
					}
					break;
				case 'disable':
					if ($has_secret) {
						RecoveryCode::deleteForUser(DI::userSession()->getLocalUserId());
						DI::pConfig()->delete(DI::userSession()->getLocalUserId(), '2fa', 'secret');
						DI::pConfig()->delete(DI::userSession()->getLocalUserId(), '2fa', 'verified');
						DI::session()->remove('2fa');

						DI::sysmsg()->addInfo(DI::l10n()->t('Two-factor authentication successfully disabled.'));
						DI::baseUrl()->redirect('settings/2fa');
					}
					break;
				case 'recovery':
					if ($has_secret) {
						DI::baseUrl()
						  ->redirect('settings/2fa/recovery?t=' . self::getFormSecurityToken('settings_2fa_password'));
					}
					break;
				case 'app_specific':
					if ($has_secret) {
						DI::baseUrl()
						  ->redirect('settings/2fa/app_specific?t=' . self::getFormSecurityToken('settings_2fa_password'));
					}
					break;
				case 'trusted':
					if ($has_secret) {
						DI::baseUrl()
						  ->redirect('settings/2fa/trusted?t=' . self::getFormSecurityToken('settings_2fa_password'));
					}
					break;
				case 'configure':
					if (!$verified) {
						DI::baseUrl()
						  ->redirect('settings/2fa/verify?t=' . self::getFormSecurityToken('settings_2fa_password'));
					}
					break;
			}
		} catch (FoundException $exception) {
			// Nothing to do here
		} catch (\Exception $e) {
			DI::sysmsg()->addNotice(DI::l10n()->t($e->getMessage()));
		}
	}

	protected function content(array $request = []): string
	{
		if (!DI::userSession()->getLocalUserId()) {
			return Login::form('settings/2fa');
		}

		parent::content();

		$has_secret = (bool) DI::pConfig()->get(DI::userSession()->getLocalUserId(), '2fa', 'secret');
		$verified = DI::pConfig()->get(DI::userSession()->getLocalUserId(), '2fa', 'verified');

		return Renderer::replaceMacros(Renderer::getMarkupTemplate('settings/twofactor/index.tpl'), [
			'$form_security_token' => self::getFormSecurityToken('settings_2fa'),
			'$title'               => DI::l10n()->t('Two-factor authentication'),
			'$help_label'          => DI::l10n()->t('Help'),
			'$status_title'        => DI::l10n()->t('Status'),
			'$message'             => DI::l10n()->t('<p>Use an application on a mobile device to get two-factor authentication codes when prompted on login.</p>'),
			'$has_secret'          => $has_secret,
			'$verified'            => $verified,

			'$auth_app_label'         => DI::l10n()->t('Authenticator app'),
			'$app_status'             => $has_secret ? $verified ? DI::l10n()->t('Configured') : DI::l10n()->t('Not Configured') : DI::l10n()->t('Disabled'),
			'$not_configured_message' => DI::l10n()->t('<p>You haven\'t finished configuring your authenticator app.</p>'),
			'$configured_message'     => DI::l10n()->t('<p>Your authenticator app is correctly configured.</p>'),

			'$recovery_codes_title'     => DI::l10n()->t('Recovery codes'),
			'$recovery_codes_remaining' => DI::l10n()->t('Remaining valid codes'),
			'$recovery_codes_count'     => RecoveryCode::countValidForUser(DI::userSession()->getLocalUserId()),
			'$recovery_codes_message'   => DI::l10n()->t('<p>These one-use codes can replace an authenticator app code in case you have lost access to it.</p>'),

			'$app_specific_passwords_title'     => DI::l10n()->t('App-specific passwords'),
			'$app_specific_passwords_remaining' => DI::l10n()->t('Generated app-specific passwords'),
			'$app_specific_passwords_count'     => AppSpecificPassword::countForUser(DI::userSession()->getLocalUserId()),
			'$app_specific_passwords_message'   => DI::l10n()->t('<p>These randomly generated passwords allow you to authenticate on apps not supporting two-factor authentication.</p>'),

			'$action_title'         => DI::l10n()->t('Actions'),
			'$password'             => ['password', DI::l10n()->t('Current password:'), '', DI::l10n()->t('You need to provide your current password to change two-factor authentication settings.'), DI::l10n()->t('Required'), 'autofocus'],
			'$enable_label'         => DI::l10n()->t('Enable two-factor authentication'),
			'$disable_label'        => DI::l10n()->t('Disable two-factor authentication'),
			'$recovery_codes_label' => DI::l10n()->t('Show recovery codes'),
			'$app_specific_passwords_label' => DI::l10n()->t('Manage app-specific passwords'),
			'$trusted_browsers_label' => DI::l10n()->t('Manage trusted browsers'),
			'$configure_label'      => DI::l10n()->t('Finish app configuration'),
		]);
	}
}
