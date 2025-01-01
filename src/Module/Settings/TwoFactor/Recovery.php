<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Settings\TwoFactor;

use Friendica\App;
use Friendica\Core\L10n;
use Friendica\Core\PConfig\Capability\IManagePersonalConfigValues;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Module\BaseSettings;
use Friendica\Module\Response;
use Friendica\Module\Security\Login;
use Friendica\Navigation\SystemMessages;
use Friendica\Security\TwoFactor\Model\RecoveryCode;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

/**
 * // Page 3: 2FA enabled but not verified, show recovery codes
 *
 * @package Friendica\Module\TwoFactor
 */
class Recovery extends BaseSettings
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

		$secret = $this->pConfig->get($this->session->getLocalUserId(), '2fa', 'secret');

		if (!$secret) {
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

		if (!empty($_POST['action'])) {
			self::checkFormSecurityTokenRedirectOnError('settings/2fa/recovery', 'settings_2fa_recovery');

			if ($_POST['action'] == 'regenerate') {
				RecoveryCode::regenerateForUser($this->session->getLocalUserId());
				$this->systemMessages->addInfo($this->t('New recovery codes successfully generated.'));
				$this->baseUrl->redirect('settings/2fa/recovery?t=' . self::getFormSecurityToken('settings_2fa_password'));
			}
		}
	}

	protected function content(array $request = []): string
	{
		if (!$this->session->getLocalUserId()) {
			return Login::form('settings/2fa/recovery');
		}

		parent::content();

		if (!RecoveryCode::countValidForUser($this->session->getLocalUserId())) {
			RecoveryCode::generateForUser($this->session->getLocalUserId());
		}

		$recoveryCodes = RecoveryCode::getListForUser($this->session->getLocalUserId());

		$verified = $this->pConfig->get($this->session->getLocalUserId(), '2fa', 'verified');

		return Renderer::replaceMacros(Renderer::getMarkupTemplate('settings/twofactor/recovery.tpl'), [
			'$form_security_token'     => self::getFormSecurityToken('settings_2fa_recovery'),
			'$password_security_token' => self::getFormSecurityToken('settings_2fa_password'),

			'$title'              => $this->t('Two-factor recovery codes'),
			'$help_label'         => $this->t('Help'),
			'$message'            => $this->t('<p>Recovery codes can be used to access your account in the event you lose access to your device and cannot receive two-factor authentication codes.</p><p><strong>Put these in a safe spot!</strong> If you lose your device and don’t have the recovery codes you will lose access to your account.</p>'),
			'$recovery_codes'     => $recoveryCodes,
			'$regenerate_message' => $this->t('When you generate new recovery codes, you must copy the new codes. Your old codes won’t work anymore.'),
			'$regenerate_label'   => $this->t('Generate new recovery codes'),
			'$verified'           => $verified,
			'$verify_label'       => $this->t('Next: Verification'),
		]);
	}
}
