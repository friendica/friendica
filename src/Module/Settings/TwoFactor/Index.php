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
use Friendica\Module\Response;
use Friendica\Navigation\SystemMessages;
use Friendica\Network\HTTPException\FoundException;
use Friendica\Security\TwoFactor\Model\AppSpecificPassword;
use Friendica\Security\TwoFactor\Model\RecoveryCode;
use Friendica\Model\User;
use Friendica\Module\BaseSettings;
use Friendica\Module\Security\Login;
use Friendica\Util\Profiler;
use PragmaRX\Google2FA\Google2FA;
use Psr\Log\LoggerInterface;

class Index extends BaseSettings
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
	}

	protected function post(array $request = [])
	{
		if (!$this->session->getLocalUserId()) {
			return;
		}

		self::checkFormSecurityTokenRedirectOnError('settings/2fa', 'settings_2fa');

		try {
			User::getIdFromPasswordAuthentication($this->session->getLocalUserId(), $request['password'] ?? '');

			$has_secret = (bool)$this->pConfig->get($this->session->getLocalUserId(), '2fa', 'secret');
			$verified   = $this->pConfig->get($this->session->getLocalUserId(), '2fa', 'verified');

			switch ($request['action'] ?? '') {
				case 'enable':
					if (!$has_secret && !$verified) {
						$Google2FA = new Google2FA();

						$this->pConfig->set($this->session->getLocalUserId(), '2fa', 'secret', $Google2FA->generateSecretKey(32));

						$this->baseUrl
						  ->redirect('settings/2fa/recovery?t=' . self::getFormSecurityToken('settings_2fa_password'));
					}
					break;
				case 'disable':
					if ($has_secret) {
						RecoveryCode::deleteForUser($this->session->getLocalUserId());
						$this->pConfig->delete($this->session->getLocalUserId(), '2fa', 'secret');
						$this->pConfig->delete($this->session->getLocalUserId(), '2fa', 'verified');
						$this->session->remove('2fa');

						$this->systemMessages->addInfo($this->t('Two-factor authentication successfully disabled.'));
						$this->baseUrl->redirect('settings/2fa');
					}
					break;
				case 'recovery':
					if ($has_secret) {
						$this->baseUrl
						  ->redirect('settings/2fa/recovery?t=' . self::getFormSecurityToken('settings_2fa_password'));
					}
					break;
				case 'app_specific':
					if ($has_secret) {
						$this->baseUrl
						  ->redirect('settings/2fa/app_specific?t=' . self::getFormSecurityToken('settings_2fa_password'));
					}
					break;
				case 'trusted':
					if ($has_secret) {
						$this->baseUrl
						  ->redirect('settings/2fa/trusted?t=' . self::getFormSecurityToken('settings_2fa_password'));
					}
					break;
				case 'configure':
					if (!$verified) {
						$this->baseUrl
						  ->redirect('settings/2fa/verify?t=' . self::getFormSecurityToken('settings_2fa_password'));
					}
					break;
			}
		} catch (FoundException $exception) {
			// Redirection, passing along
			throw $exception;
		} catch (\Exception $e) {
			$this->systemMessages->addNotice($this->t($e->getMessage()));
		}
	}

	protected function content(array $request = []): string
	{
		if (!$this->session->getLocalUserId()) {
			return Login::form('settings/2fa');
		}

		parent::content();

		$has_secret = (bool) $this->pConfig->get($this->session->getLocalUserId(), '2fa', 'secret');
		$verified   = $this->pConfig->get($this->session->getLocalUserId(), '2fa', 'verified');

		return Renderer::replaceMacros(Renderer::getMarkupTemplate('settings/twofactor/index.tpl'), [
			'$form_security_token' => self::getFormSecurityToken('settings_2fa'),
			'$title'               => $this->t('Two-factor authentication'),
			'$help_label'          => $this->t('Help'),
			'$status_title'        => $this->t('Status'),
			'$message'             => $this->t('<p>Use an application on a mobile device to get two-factor authentication codes when prompted on login.</p>'),
			'$has_secret'          => $has_secret,
			'$verified'            => $verified,

			'$auth_app_label'         => $this->t('Authenticator app'),
			'$app_status'             => $has_secret ? $verified ? $this->t('Configured') : $this->t('Not Configured') : $this->t('Disabled'),
			'$not_configured_message' => $this->t('<p>You haven\'t finished configuring your authenticator app.</p>'),
			'$configured_message'     => $this->t('<p>Your authenticator app is correctly configured.</p>'),

			'$recovery_codes_title'     => $this->t('Recovery codes'),
			'$recovery_codes_remaining' => $this->t('Remaining valid codes'),
			'$recovery_codes_count'     => RecoveryCode::countValidForUser($this->session->getLocalUserId()),
			'$recovery_codes_message'   => $this->t('<p>These one-use codes can replace an authenticator app code in case you have lost access to it.</p>'),

			'$app_specific_passwords_title'     => $this->t('App-specific passwords'),
			'$app_specific_passwords_remaining' => $this->t('Generated app-specific passwords'),
			'$app_specific_passwords_count'     => AppSpecificPassword::countForUser($this->session->getLocalUserId()),
			'$app_specific_passwords_message'   => $this->t('<p>These randomly generated passwords allow you to authenticate on apps not supporting two-factor authentication.</p>'),

			'$action_title'                 => $this->t('Actions'),
			'$password'                     => ['password', $this->t('Current password:'), '', $this->t('You need to provide your current password to change two-factor authentication settings.'), $this->t('Required'), 'autofocus'],
			'$enable_label'                 => $this->t('Enable two-factor authentication'),
			'$disable_label'                => $this->t('Disable two-factor authentication'),
			'$recovery_codes_label'         => $this->t('Show recovery codes'),
			'$app_specific_passwords_label' => $this->t('Manage app-specific passwords'),
			'$trusted_browsers_label'       => $this->t('Manage trusted browsers'),
			'$configure_label'              => $this->t('Finish app configuration'),
		]);
	}
}
