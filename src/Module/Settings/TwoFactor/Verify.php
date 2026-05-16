<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Settings\TwoFactor;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Friendica\App;
use Friendica\Core\L10n;
use Friendica\Core\PConfig\Capability\IManagePersonalConfigValues;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Module\BaseSettings;
use Friendica\Module\Response;
use Friendica\Module\Security\Login;
use Friendica\Navigation\SystemMessages;
use Friendica\Util\Profiler;
use PragmaRX\Google2FA\Google2FA;
use Psr\Log\LoggerInterface;

/**
 * // Page 4: 2FA enabled but not verified, QR code and verification
 *
 * @package Friendica\Module\TwoFactor\Settings
 */
class Verify extends BaseSettings
{
	/** @var IManagePersonalConfigValues */
	protected $pConfig;
	/** @var SystemMessages */
	protected $systemMessages;

	public function __construct(SystemMessages $systemMessages, IManagePersonalConfigValues $pConfig, IHandleUserSessions $session, App\Page $page, L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, array $server, array $parameters = [])
	{
		parent::__construct($session, $page, $l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->pConfig        = $pConfig;
		$this->systemMessages = $systemMessages;

		if (!$this->session->getLocalUserId()) {
			return;
		}

		$secret   = $this->pConfig->get($this->session->getLocalUserId(), '2fa', 'secret');
		$verified = $this->pConfig->get($this->session->getLocalUserId(), '2fa', 'verified');

		if ($secret && $verified) {
			$this->baseUrl->redirect('settings/2fa');
		}

		if (!self::checkFormSecurityToken('settings_2fa_password', 't')) {
			$this->systemMessages->addNotice($this->t('Please enter your password to access this page.'));
			$this->baseUrl->redirect('settings/2fa');
		}
	}

	protected function post(array $request = [])
	{
		if (!$this->session->getLocalUserId()) {
			return;
		}

		if (($request['action'] ?? '') == 'verify') {
			self::checkFormSecurityTokenRedirectOnError('settings/2fa/verify', 'settings_2fa_verify');

			$google2fa = new Google2FA();

			$valid = $google2fa->verifyKey($this->pConfig->get($this->session->getLocalUserId(), '2fa', 'secret'), $request['verify_code'] ?? '');

			if ($valid) {
				$this->pConfig->set($this->session->getLocalUserId(), '2fa', 'verified', true);
				$this->session->set('2fa', true);

				$this->systemMessages->addInfo($this->t('Two-factor authentication successfully activated.'));

				$this->baseUrl->redirect('settings/2fa');
			} else {
				$this->systemMessages->addNotice($this->t('Invalid code, please retry.'));
			}
		}
	}

	protected function content(array $request = []): string
	{
		if (!$this->session->getLocalUserId()) {
			return Login::form('settings/2fa/verify');
		}

		parent::content();

		$company = 'Friendica';
		$holder  = $this->session->get('my_address');
		$secret  = $this->pConfig->get($this->session->getLocalUserId(), '2fa', 'secret');

		$otpauthUrl = (new Google2FA())->getQRCodeUrl($company, $holder, $secret);

		$qrcode_image = '';
		try {
			$renderer = new ImageRenderer(
				new RendererStyle(256),
				new SvgImageBackEnd(),
			);

			$writer = new Writer($renderer);

			$qrcode_image = str_replace('<?xml version="1.0" encoding="UTF-8"?>', '', $writer->writeString($otpauthUrl));
		} catch (\Throwable $e) {
			$this->logger->warning('QR code generation failed, libxml/XMLWriter extension may be missing.', ['exception' => $e]);
		}

		$shortOtpauthUrl = explode('?', $otpauthUrl)[0];

		$manual_message = $this->t('<p>Or you can submit the authentication settings manually:</p>
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

			'$title'              => $this->t('Two-factor code verification'),
			'$help_label'         => $this->t('Help'),
			'$message'            => $this->t('<p>Please scan this QR Code with your authenticator app and submit the provided code.</p>'),
			'$qrcode_image'       => $qrcode_image,
			'$qrcode_url_message' => $this->t('<p>Or you can open the following URL in your mobile device:</p><p><a href="%s">%s</a></p>', $otpauthUrl, $shortOtpauthUrl),
			'$manual_message'     => $manual_message,
			'$company'            => $company,
			'$holder'             => $holder,
			'$secret'             => $secret,

			'$verify_code'  => ['verify_code', $this->t('Please enter a code from your authentication app'), '', '', $this->t('Required'), 'autofocus autocomplete="off" placeholder="000000"'],
			'$verify_label' => $this->t('Verify code and enable two-factor authentication'),
		]);
	}
}
