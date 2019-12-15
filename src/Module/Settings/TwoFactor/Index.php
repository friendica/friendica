<?php


namespace Friendica\Module\Settings\TwoFactor;


use Friendica\Core\L10n;
use Friendica\Core\PConfig;
use Friendica\Core\Renderer;
use Friendica\Core\Session;
use Friendica\DI;
use Friendica\Model\TwoFactor\AppSpecificPassword;
use Friendica\Model\TwoFactor\RecoveryCode;
use Friendica\Model\User;
use Friendica\Module\BaseSettingsModule;
use Friendica\Module\Login;
use PragmaRX\Google2FA\Google2FA;

class Index extends BaseSettingsModule
{
	public static function post(array $parameters = [])
	{
		if (!local_user()) {
			return;
		}

		self::checkFormSecurityTokenRedirectOnError('settings/2fa', 'settings_2fa');

		try {
			User::getIdFromPasswordAuthentication(local_user(), $_POST['password'] ?? '');

			$has_secret = (bool) PConfig::get(local_user(), '2fa', 'secret');
			$verified = PConfig::get(local_user(), '2fa', 'verified');

			switch ($_POST['action'] ?? '') {
				case 'enable':
					if (!$has_secret && !$verified) {
						$Google2FA = new Google2FA();

						PConfig::set(local_user(), '2fa', 'secret', $Google2FA->generateSecretKey(32));

						DI::app()->internalRedirect('settings/2fa/recovery?t=' . self::getFormSecurityToken('settings_2fa_password'));
					}
					break;
				case 'disable':
					if ($has_secret) {
						RecoveryCode::deleteForUser(local_user());
						PConfig::delete(local_user(), '2fa', 'secret');
						PConfig::delete(local_user(), '2fa', 'verified');
						Session::remove('2fa');

						notice(L10n::t('Two-factor authentication successfully disabled.'));
						DI::app()->internalRedirect('settings/2fa');
					}
					break;
				case 'recovery':
					if ($has_secret) {
						DI::app()->internalRedirect('settings/2fa/recovery?t=' . self::getFormSecurityToken('settings_2fa_password'));
					}
					break;
				case 'app_specific':
					if ($has_secret) {
						DI::app()->internalRedirect('settings/2fa/app_specific?t=' . self::getFormSecurityToken('settings_2fa_password'));
					}
					break;
				case 'configure':
					if (!$verified) {
						DI::app()->internalRedirect('settings/2fa/verify?t=' . self::getFormSecurityToken('settings_2fa_password'));
					}
					break;
			}
		} catch (\Exception $e) {
			notice(L10n::t('Wrong Password'));
		}
	}

	public static function content(array $parameters = [])
	{
		if (!local_user()) {
			return Login::form('settings/2fa');
		}

		parent::content($parameters);

		$has_secret = (bool) PConfig::get(local_user(), '2fa', 'secret');
		$verified = PConfig::get(local_user(), '2fa', 'verified');

		return Renderer::replaceMacros(Renderer::getMarkupTemplate('settings/twofactor/index.tpl'), [
			'$form_security_token' => self::getFormSecurityToken('settings_2fa'),
			'$title'               => L10n::t('Two-factor authentication'),
			'$help_label'          => L10n::t('Help'),
			'$status_title'        => L10n::t('Status'),
			'$message'             => L10n::t('<p>Use an application on a mobile device to get two-factor authentication codes when prompted on login.</p>'),
			'$has_secret'          => $has_secret,
			'$verified'            => $verified,

			'$auth_app_label'         => L10n::t('Authenticator app'),
			'$app_status'             => $has_secret ? $verified ? L10n::t('Configured') : L10n::t('Not Configured') : L10n::t('Disabled'),
			'$not_configured_message' => L10n::t('<p>You haven\'t finished configuring your authenticator app.</p>'),
			'$configured_message'     => L10n::t('<p>Your authenticator app is correctly configured.</p>'),

			'$recovery_codes_title'     => L10n::t('Recovery codes'),
			'$recovery_codes_remaining' => L10n::t('Remaining valid codes'),
			'$recovery_codes_count'     => RecoveryCode::countValidForUser(local_user()),
			'$recovery_codes_message'   => L10n::t('<p>These one-use codes can replace an authenticator app code in case you have lost access to it.</p>'),

			'$app_specific_passwords_title'     => L10n::t('App-specific passwords'),
			'$app_specific_passwords_remaining' => L10n::t('Generated app-specific passwords'),
			'$app_specific_passwords_count'     => AppSpecificPassword::countForUser(local_user()),
			'$app_specific_passwords_message'   => L10n::t('<p>These randomly generated passwords allow you to authenticate on apps not supporting two-factor authentication.</p>'),

			'$action_title'         => L10n::t('Actions'),
			'$password'             => ['password', L10n::t('Current password:'), '', L10n::t('You need to provide your current password to change two-factor authentication settings.'), 'required', 'autofocus'],
			'$enable_label'         => L10n::t('Enable two-factor authentication'),
			'$disable_label'        => L10n::t('Disable two-factor authentication'),
			'$recovery_codes_label' => L10n::t('Show recovery codes'),
			'$app_specific_passwords_label' => L10n::t('Manage app-specific passwords'),
			'$configure_label'      => L10n::t('Finish app configuration'),
		]);
	}
}
