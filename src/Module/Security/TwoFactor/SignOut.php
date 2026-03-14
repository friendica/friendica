<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Security\TwoFactor;

use Friendica\App;
use Friendica\BaseModule;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Model\User\Cookie;
use Friendica\Module\Response;
use Friendica\Util\Profiler;
use Friendica\Security\TwoFactor;
use Psr\Log\LoggerInterface;

/**
 * Page 4: Logout dialog for trusted browsers
 *
 * @package Friendica\Module\TwoFactor
 */
class SignOut extends BaseModule
{
	protected $errors = [];

	/** @var IHandleUserSessions */
	protected $session;
	/** @var Cookie  */
	protected $cookie;
	/** @var TwoFactor\Repository\TrustedBrowser  */
	protected $trustedBrowserRepository;

	public function __construct(L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, IHandleUserSessions $session, Cookie $cookie, TwoFactor\Repository\TrustedBrowser $trustedBrowserRepository, Profiler $profiler, Response $response, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->session                  = $session;
		$this->cookie                   = $cookie;
		$this->trustedBrowserRepository = $trustedBrowserRepository;
	}

	protected function post(array $request = [])
	{
		if (!$this->session->getLocalUserId() || !($this->cookie->get('2fa_cookie_hash'))) {
			return;
		}

		$action = $request['action'] ?? '';

		if (!empty($action)) {
			self::checkFormSecurityTokenRedirectOnError('2fa', 'twofactor_signout');

			switch ($action) {
				case 'trust_and_sign_out':
					$trusted = $this->cookie->get('2fa_cookie_hash');
					$this->cookie->reset(['2fa_cookie_hash' => $trusted]);
					$this->session->clear();

					$this->baseUrl->redirect();
					break;
				case 'sign_out':
					$this->trustedBrowserRepository->removeForUser($this->session->getLocalUserId(), $this->cookie->get('2fa_cookie_hash'));
					$this->cookie->clear();
					$this->session->clear();

					$this->baseUrl->redirect();
					break;
				default:
					$this->baseUrl->redirect();
			}
		}
	}

	protected function content(array $request = []): string
	{
		if (!$this->session->getLocalUserId() || !($this->cookie->get('2fa_cookie_hash'))) {
			$this->baseUrl->redirect();
		}

		try {
			$trustedBrowser = $this->trustedBrowserRepository->selectOneByHash($this->cookie->get('2fa_cookie_hash'));
			if (!$trustedBrowser->trusted) {
				$trusted = $this->cookie->get('2fa_cookie_hash');
				$this->cookie->reset(['2fa_cookie_hash' => $trusted]);
				$this->session->clear();

				$this->baseUrl->redirect();
			}
		} catch (TwoFactor\Exception\TrustedBrowserNotFoundException $exception) {
			$this->cookie->clear();
			$this->session->clear();

			$this->baseUrl->redirect();
		}

		return Renderer::replaceMacros(Renderer::getMarkupTemplate('twofactor/signout.tpl'), [
			'$form_security_token' => self::getFormSecurityToken('twofactor_signout'),

			'$title'                    => $this->t('Sign out of this browser?'),
			'$message'                  => $this->t('<p>If you trust this browser, you will not be asked for verification code the next time you sign in.</p>'),
			'$sign_out_label'           => $this->t('Sign out'),
			'$cancel_label'             => $this->t('Cancel'),
			'$trust_and_sign_out_label' => $this->t('Trust and sign out'),
		]);
	}
}
