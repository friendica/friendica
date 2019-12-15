<?php


namespace Friendica\Module\Settings\TwoFactor;


use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Friendica\BaseModule;
use Friendica\Core\L10n;
use Friendica\Core\PConfig;
use Friendica\Core\Renderer;
use Friendica\Core\Session;
use Friendica\DI;
use Friendica\Module\BaseSettingsModule;
use Friendica\Module\Login;
use PragmaRX\Google2FA\Google2FA;

/**
 * // Page 4: 2FA enabled but not verified, QR code and verification
 *
 * @package Friendica\Module\TwoFactor\Settings
 */
class Verify extends BaseSettingsModule
{
	public static function init(array $parameters = [])
	{
		if (!local_user()) {
			return;
		}

		$secret = PConfig::get(local_user(), '2fa', 'secret');
		$verified = PConfig::get(local_user(), '2fa', 'verified');

		if ($secret && $verified) {
			DI::app()->internalRedirect('settings/2fa');
		}

		if (!self::checkFormSecurityToken('settings_2fa_password', 't')) {
			notice(L10n::t('Please enter your password to access this page.'));
			DI::app()->internalRedirect('settings/2fa');
		}
	}

	public static function post(array $parameters = [])
	{
		if (!local_user()) {
			return;
		}

		if (($_POST['action'] ?? '') == 'verify') {
			self::checkFormSecurityTokenRedirectOnError('settings/2fa/verify', 'settings_2fa_verify');

			$google2fa = new Google2FA();

			$valid = $google2fa->verifyKey(PConfig::get(local_user(), '2fa', 'secret'), $_POST['verify_code'] ?? '');

			if ($valid) {
				PConfig::set(local_user(), '2fa', 'verified', true);
				Session::set('2fa', true);

				notice(L10n::t('Two-factor authentication successfully activated.'));

				DI::app()->internalRedirect('settings/2fa');
			} else {
				notice(L10n::t('Invalid code, please retry.'));
			}
		}
	}

	public static function content(array $parameters = [])
	{
		if (!local_user()) {
			return Login::form('settings/2fa/verify');
		}

		parent::content($parameters);

		$company = 'Friendica';
		$holder = Session::get('my_address');
		$secret = PConfig::get(local_user(), '2fa', 'secret');

		$otpauthUrl = (new Google2FA())->getQRCodeUrl($company, $holder, $secret);

		$renderer = (new \BaconQrCode\Renderer\Image\Svg())
			->setHeight(256)
			->setWidth(256);

		$writer = new Writer($renderer);

		$qrcode_image = str_replace('<?xml version="1.0" encoding="UTF-8"?>', '', $writer->writeString($otpauthUrl));

		$shortOtpauthUrl = explode('?', $otpauthUrl)[0];

		$manual_message = L10n::t('<p>Or you can submit the authentication settings manually:</p>
<dl>
	<dt>Issuer</dt>
	<dd>%s</dd>
	<dt>Account Name</dt>
	<dd>%s</dd>
	<dt>Secret Key</dt>
	<dd>%s</dd>
	<dt>Type</dt>
	<dd>Time-based</dd>
	<dt>Number of digits</dt>
	<dd>6</dd>
	<dt>Hashing algorithm</dt>
	<dd>SHA-1</dd>
</dl>', $company, $holder, $secret);

		return Renderer::replaceMacros(Renderer::getMarkupTemplate('settings/twofactor/verify.tpl'), [
			'$form_security_token'     => self::getFormSecurityToken('settings_2fa_verify'),
			'$password_security_token' => self::getFormSecurityToken('settings_2fa_password'),

			'$title'              => L10n::t('Two-factor code verification'),
			'$help_label'         => L10n::t('Help'),
			'$message'            => L10n::t('<p>Please scan this QR Code with your authenticator app and submit the provided code.</p>'),
			'$qrcode_image'       => $qrcode_image,
			'$qrcode_url_message' => L10n::t('<p>Or you can open the following URL in your mobile devicde:</p><p><a href="%s">%s</a></p>', $otpauthUrl, $shortOtpauthUrl),
			'$manual_message'     => $manual_message,
			'$company'            => $company,
			'$holder'             => $holder,
			'$secret'             => $secret,

			'$verify_code'  => ['verify_code', L10n::t('Please enter a code from your authentication app'), '', '', 'required', 'autofocus placeholder="000000"'],
			'$verify_label' => L10n::t('Verify code and enable two-factor authentication'),
		]);
	}
}
