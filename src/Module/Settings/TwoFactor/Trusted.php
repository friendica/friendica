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
use Friendica\Navigation\SystemMessages;
use Friendica\Security\TwoFactor;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Profiler;
use Friendica\Util\Temporal;
use Psr\Log\LoggerInterface;
use UAParser\Parser;

/**
 * Manages users' two-factor trusted browsers in the 2fa_trusted_browsers table
 */
class Trusted extends BaseSettings
{
	/** @var IManagePersonalConfigValues */
	protected $pConfig;
	/** @var TwoFactor\Repository\TrustedBrowser */
	protected $trustedBrowserRepo;
	/** @var SystemMessages */
	protected $systemMessages;

	public function __construct(
		SystemMessages $systemMessages,
		IManagePersonalConfigValues $pConfig,
		TwoFactor\Repository\TrustedBrowser $trustedBrowserRepo,
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

		$this->pConfig            = $pConfig;
		$this->trustedBrowserRepo = $trustedBrowserRepo;
		$this->systemMessages     = $systemMessages;

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
			self::checkFormSecurityTokenRedirectOnError('settings/2fa/trusted', 'settings_2fa_trusted');

			switch ($request['action']) {
				case 'remove_all':
					$this->trustedBrowserRepo->removeAllForUser($this->session->getLocalUserId());
					$this->systemMessages->addInfo($this->t('Trusted browsers successfully removed.'));

					$this->baseUrl->redirect('settings/2fa/trusted?t=' . self::getFormSecurityToken('settings_2fa_password'));
			}
		}

		if (!empty($request['remove_id'])) {
			self::checkFormSecurityTokenRedirectOnError('settings/2fa/trusted', 'settings_2fa_trusted');

			if ($this->trustedBrowserRepo->removeForUser($this->session->getLocalUserId(), $request['remove_id'])) {
				$this->systemMessages->addInfo($this->t('Trusted browser successfully removed.'));
			}

			$this->baseUrl->redirect('settings/2fa/trusted?t=' . self::getFormSecurityToken('settings_2fa_password'));
		}
	}


	protected function content(array $request = []): string
	{
		parent::content();

		$trustedBrowsers = $this->trustedBrowserRepo->selectAllByUid($this->session->getLocalUserId());

		$parser = Parser::create();

		$trustedBrowserDisplay = array_map(function (TwoFactor\Model\TrustedBrowser $trustedBrowser) use ($parser) {
			$dates = [
				'created_ago'     => Temporal::getRelativeDate($trustedBrowser->created),
				'created_utc'     => DateTimeFormat::utc($trustedBrowser->created, 'c'),
				'created_local'   => DateTimeFormat::local($trustedBrowser->created, 'r'),
				'last_used_ago'   => Temporal::getRelativeDate($trustedBrowser->last_used),
				'last_used_utc'   => $trustedBrowser->last_used ? DateTimeFormat::utc($trustedBrowser->last_used, 'c') : '',
				'last_used_local' => $trustedBrowser->last_used ? DateTimeFormat::local($trustedBrowser->last_used, 'r') : '',
			];

			$result = $parser->parse($trustedBrowser->user_agent);

			$uaData = [
				'os'              => $result->os->family,
				'device'          => $result->device->family,
				'browser'         => $result->ua->family,
				'trusted_labeled' => $trustedBrowser->trusted ? $this->t('Yes') : $this->t('No'),
			];

			return $trustedBrowser->toArray() + $dates + $uaData;
		}, $trustedBrowsers->getArrayCopy());

		return Renderer::replaceMacros(Renderer::getMarkupTemplate('settings/twofactor/trusted_browsers.tpl'), [
			'$form_security_token'     => self::getFormSecurityToken('settings_2fa_trusted'),
			'$password_security_token' => self::getFormSecurityToken('settings_2fa_password'),

			'$title'            => $this->t('Two-factor Trusted Browsers'),
			'$message'          => $this->t('Trusted browsers are individual browsers you chose to skip two-factor authentication to access Friendica. Please use this feature sparingly, as it can negate the benefit of two-factor authentication.'),
			'$device_label'     => $this->t('Device'),
			'$os_label'         => $this->t('OS'),
			'$browser_label'    => $this->t('Browser'),
			'$trusted_label'    => $this->t('Trusted'),
			'$created_label'    => $this->t('Created At'),
			'$last_used_label'  => $this->t('Last Use'),
			'$remove_label'     => $this->t('Remove'),
			'$remove_all_label' => $this->t('Remove All'),

			'$trusted_browsers' => $trustedBrowserDisplay,
		]);
	}
}
