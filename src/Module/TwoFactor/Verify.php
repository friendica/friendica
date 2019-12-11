<?php

namespace Friendica\Module\TwoFactor;

use Friendica\BaseModule;
use Friendica\Core\L10n;
use Friendica\Core\PConfig;
use Friendica\Core\Renderer;
use Friendica\Core\Session;
use PragmaRX\Google2FA\Google2FA;

/**
 * Page 1: Authenticator code verification
 *
 * @package Friendica\Module\TwoFactor
 */
class Verify extends BaseModule
{
	private static $errors = [];

	public static function post(array $parameters = [])
	{
		if (!local_user()) {
			return;
		}

		if (($_POST['action'] ?? '') == 'verify') {
			self::checkFormSecurityTokenRedirectOnError('2fa', 'twofactor_verify');

			$a = self::getApp();

			$code = $_POST['verify_code'] ?? '';

			$valid = (new Google2FA())->verifyKey(PConfig::get(local_user(), '2fa', 'secret'), $code);

			// The same code can't be used twice even if it's valid
			if ($valid && Session::get('2fa') !== $code) {
				Session::set('2fa', $code);

				// Resume normal login workflow
				Session::setAuthenticatedForUser($a, $a->user, true, true);
			} else {
				self::$errors[] = L10n::t('Invalid code, please retry.');
			}
		}
	}

	public static function content(array $parameters = [])
	{
		if (!local_user()) {
			self::getApp()->internalRedirect();
		}

		// Already authenticated with 2FA token
		if (Session::get('2fa')) {
			self::getApp()->internalRedirect();
		}

		return Renderer::replaceMacros(Renderer::getMarkupTemplate('twofactor/verify.tpl'), [
			'$form_security_token' => self::getFormSecurityToken('twofactor_verify'),

			'$title'            => L10n::t('Two-factor authentication'),
			'$message'          => L10n::t('<p>Open the two-factor authentication app on your device to get an authentication code and verify your identity.</p>'),
			'$errors_label'     => L10n::tt('Error', 'Errors', count(self::$errors)),
			'$errors'           => self::$errors,
			'$recovery_message' => L10n::t('Don’t have your phone? <a href="%s">Enter a two-factor recovery code</a>', '2fa/recovery'),
			'$verify_code'      => ['verify_code', L10n::t('Please enter a code from your authentication app'), '', '', 'required', 'autofocus placeholder="000000"', 'tel'],
			'$verify_label'     => L10n::t('Verify code and complete login'),
		]);
	}
}
