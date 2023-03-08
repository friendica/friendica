<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Friendica\Module\Security\TwoFactor;

use Friendica\App;
use Friendica\BaseModule;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\DI;
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

					DI::sysmsg()->addInfo($this->t('Logged out.'));
					$this->baseUrl->redirect();
					break;
				case 'sign_out':
					$this->trustedBrowserRepository->removeForUser($this->session->getLocalUserId(), $this->cookie->get('2fa_cookie_hash'));
					$this->cookie->clear();
					$this->session->clear();

					DI::sysmsg()->addInfo($this->t('Logged out.'));
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

				DI::sysmsg()->addInfo($this->t('Logged out.'));
				$this->baseUrl->redirect();
			}
		} catch (TwoFactor\Exception\TrustedBrowserNotFoundException $exception) {
			$this->cookie->clear();
			$this->session->clear();

			DI::sysmsg()->addInfo($this->t('Logged out.'));
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
