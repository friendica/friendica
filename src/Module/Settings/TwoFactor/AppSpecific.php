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
use Friendica\Security\TwoFactor\Model\AppSpecificPassword;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

/**
 * // Page 5: 2FA enabled, app-specific password generation
 *
 * @package Friendica\Module\TwoFactor
 */
class AppSpecific extends BaseSettings
{
	private $appSpecificPassword = null;

	/** @var IManagePersonalConfigValues */
	protected $pConfig;
	/** @var SystemMessages */
	protected $systemMessages;

	public function __construct(
		SystemMessages $systemMessages,
		IManagePersonalConfigValues $pConfig,
		IHandleUserSessions $session,
		App\Page $page,
		L10n $l10n,
		App\BaseURL $baseUrl,
		App\Arguments $args,
		LoggerInterface $logger,
		Profiler $profiler,
		Response $response,
		array $server,
		array $parameters = [],
	) {
		parent::__construct($session, $page, $l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->pConfig        = $pConfig;
		$this->systemMessages = $systemMessages;

		if (!$this->session->getLocalUserId()) {
			return;
		}

		$verified = $this->pConfig->get($this->session->getLocalUserId(), '2fa', 'verified');

		if (!$verified) {
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

		if (!empty($request['action'])) {
			self::checkFormSecurityTokenRedirectOnError('settings/2fa/app_specific', 'settings_2fa_app_specific');

			switch ($request['action']) {
				case 'generate':
					$description = $request['description'] ?? '';
					if (empty($description)) {
						$this->systemMessages->addNotice($this->t('App-specific password generation failed: The description is empty.'));
						$this->baseUrl->redirect('settings/2fa/app_specific?t=' . self::getFormSecurityToken('settings_2fa_password'));
					} elseif (AppSpecificPassword::checkDuplicateForUser($this->session->getLocalUserId(), $description)) {
						$this->systemMessages->addNotice($this->t('App-specific password generation failed: This description already exists.'));
						$this->baseUrl->redirect('settings/2fa/app_specific?t=' . self::getFormSecurityToken('settings_2fa_password'));
					} else {
						$this->appSpecificPassword = AppSpecificPassword::generateForUser($this->session->getLocalUserId(), $request['description'] ?? '');
						$this->systemMessages->addInfo($this->t('New app-specific password generated.'));
					}

					break;
				case 'revoke_all':
					AppSpecificPassword::deleteAllForUser($this->session->getLocalUserId());
					$this->systemMessages->addInfo($this->t('App-specific passwords successfully revoked.'));

					$this->baseUrl->redirect('settings/2fa/app_specific?t=' . self::getFormSecurityToken('settings_2fa_password'));
			}
		}

		if (!empty($request['revoke_id'])) {
			self::checkFormSecurityTokenRedirectOnError('settings/2fa/app_specific', 'settings_2fa_app_specific');

			if (AppSpecificPassword::deleteForUser($this->session->getLocalUserId(), $request['revoke_id'])) {
				$this->systemMessages->addInfo($this->t('App-specific password successfully revoked.'));
			}

			$this->baseUrl->redirect('settings/2fa/app_specific?t=' . self::getFormSecurityToken('settings_2fa_password'));
		}
	}

	protected function content(array $request = []): string
	{
		if (!$this->session->getLocalUserId()) {
			return Login::form('settings/2fa/app_specific');
		}

		parent::content();

		$appSpecificPasswords = AppSpecificPassword::getListForUser($this->session->getLocalUserId());

		return Renderer::replaceMacros(Renderer::getMarkupTemplate('settings/twofactor/app_specific.tpl'), [
			'$form_security_token'     => self::getFormSecurityToken('settings_2fa_app_specific'),
			'$password_security_token' => self::getFormSecurityToken('settings_2fa_password'),

			'$title'                           => $this->t('Two-factor app-specific passwords'),
			'$help_label'                      => $this->t('Help'),
			'$message'                         => $this->t('<p>App-specific passwords are randomly generated passwords used instead your regular password to authenticate your account on third-party applications that don\'t support two-factor authentication.</p>'),
			'$generated_message'               => $this->t('Make sure to copy your new app-specific password now. You won’t be able to see it again!'),
			'$generated_app_specific_password' => $this->appSpecificPassword,

			'$description_label' => $this->t('Description'),
			'$last_used_label'   => $this->t('Last Used'),
			'$revoke_label'      => $this->t('Revoke'),
			'$revoke_all_label'  => $this->t('Revoke All'),

			'$app_specific_passwords'        => $appSpecificPasswords,
			'$generate_message'              => $this->t('When you generate a new app-specific password, you must use it right away, it will be shown to you once after you generate it.'),
			'$generate_title'                => $this->t('Generate new app-specific password'),
			'$description_placeholder_label' => $this->t('Friendiqa on my Fairphone 2...'),
			'$generate_label'                => $this->t('Generate'),
		]);
	}
}
