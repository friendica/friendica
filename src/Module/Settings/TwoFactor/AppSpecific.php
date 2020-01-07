<?php


namespace Friendica\Module\Settings\TwoFactor;


use Friendica\Core\L10n;
use Friendica\Core\PConfig;
use Friendica\Core\Renderer;
use Friendica\Model\TwoFactor\AppSpecificPassword;
use Friendica\Module\BaseSettingsModule;
use Friendica\Module\Security\Login;
use Friendica\Registry\App;

/**
 * // Page 5: 2FA enabled, app-specific password generation
 *
 * @package Friendica\Module\TwoFactor
 */
class AppSpecific extends BaseSettingsModule
{
	private static $appSpecificPassword = null;

	public static function init(array $parameters = [])
	{
		if (!local_user()) {
			return;
		}

		$verified = PConfig::get(local_user(), '2fa', 'verified');

		if (!$verified) {
			App::baseUrl()->redirect('settings/2fa');
		}

		if (!self::checkFormSecurityToken('settings_2fa_password', 't')) {
			notice(L10n::t('Please enter your password to access this page.'));
			App::baseUrl()->redirect('settings/2fa');
		}
	}

	public static function post(array $parameters = [])
	{
		if (!local_user()) {
			return;
		}

		if (!empty($_POST['action'])) {
			self::checkFormSecurityTokenRedirectOnError('settings/2fa/app_specific', 'settings_2fa_app_specific');

			switch ($_POST['action']) {
				case 'generate':
					$description = $_POST['description'] ?? '';
					if (empty($description)) {
						notice(L10n::t('App-specific password generation failed: The description is empty.'));
						App::baseUrl()->redirect('settings/2fa/app_specific?t=' . self::getFormSecurityToken('settings_2fa_password'));
					} elseif (AppSpecificPassword::checkDuplicateForUser(local_user(), $description)) {
						notice(L10n::t('App-specific password generation failed: This description already exists.'));
						App::baseUrl()->redirect('settings/2fa/app_specific?t=' . self::getFormSecurityToken('settings_2fa_password'));
					} else {
						self::$appSpecificPassword = AppSpecificPassword::generateForUser(local_user(), $_POST['description'] ?? '');
						notice(L10n::t('New app-specific password generated.'));
					}

					break;
				case 'revoke_all' :
					AppSpecificPassword::deleteAllForUser(local_user());
					notice(L10n::t('App-specific passwords successfully revoked.'));
					App::baseUrl()->redirect('settings/2fa/app_specific?t=' . self::getFormSecurityToken('settings_2fa_password'));
					break;
			}
		}

		if (!empty($_POST['revoke_id'])) {
			self::checkFormSecurityTokenRedirectOnError('settings/2fa/app_specific', 'settings_2fa_app_specific');

			if (AppSpecificPassword::deleteForUser(local_user(), $_POST['revoke_id'])) {
				notice(L10n::t('App-specific password successfully revoked.'));
			}

			App::baseUrl()->redirect('settings/2fa/app_specific?t=' . self::getFormSecurityToken('settings_2fa_password'));
		}
	}

	public static function content(array $parameters = [])
	{
		if (!local_user()) {
			return Login::form('settings/2fa/app_specific');
		}

		parent::content($parameters);

		$appSpecificPasswords = AppSpecificPassword::getListForUser(local_user());

		return Renderer::replaceMacros(Renderer::getMarkupTemplate('settings/twofactor/app_specific.tpl'), [
			'$form_security_token'     => self::getFormSecurityToken('settings_2fa_app_specific'),
			'$password_security_token' => self::getFormSecurityToken('settings_2fa_password'),

			'$title'                  => L10n::t('Two-factor app-specific passwords'),
			'$help_label'             => L10n::t('Help'),
			'$message'                => L10n::t('<p>App-specific passwords are randomly generated passwords used instead your regular password to authenticate your account on third-party applications that don\'t support two-factor authentication.</p>'),
			'$generated_message'      => L10n::t('Make sure to copy your new app-specific password now. You won’t be able to see it again!'),
			'$generated_app_specific_password' => self::$appSpecificPassword,

			'$description_label'      => L10n::t('Description'),
			'$last_used_label'        => L10n::t('Last Used'),
			'$revoke_label'           => L10n::t('Revoke'),
			'$revoke_all_label'       => L10n::t('Revoke All'),

			'$app_specific_passwords' => $appSpecificPasswords,
			'$generate_message'       => L10n::t('When you generate a new app-specific password, you must use it right away, it will be shown to you once after you generate it.'),
			'$generate_title'         => L10n::t('Generate new app-specific password'),
			'$description_placeholder_label' => L10n::t('Friendiqa on my Fairphone 2...'),
			'$generate_label' => L10n::t('Generate'),
		]);
	}
}
