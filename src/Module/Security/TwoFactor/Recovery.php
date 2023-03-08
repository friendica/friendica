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
use Friendica\Model\User;
use Friendica\Module\Response;
use Friendica\Security\Authentication;
use Friendica\Security\TwoFactor\Model\RecoveryCode;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

/**
 * // Page 1a: Recovery code verification
 *
 * @package Friendica\Module\TwoFactor
 */
class Recovery extends BaseModule
{
	/** @var IHandleUserSessions */
	protected $session;
	/** @var App */
	protected $app;
	/** @var Authentication */
	protected $auth;

	public function __construct(App $app, L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, Authentication $auth, IHandleUserSessions $session, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->app     = $app;
		$this->auth    = $auth;
		$this->session = $session;
	}

	protected function post(array $request = [])
	{
		if (!$this->session->getLocalUserId()) {
			return;
		}

		if (($_POST['action'] ?? '') == 'recover') {
			self::checkFormSecurityTokenRedirectOnError('2fa', 'twofactor_recovery');

			$recovery_code = $_POST['recovery_code'] ?? '';

			if (RecoveryCode::existsForUser($this->session->getLocalUserId(), $recovery_code)) {
				RecoveryCode::markUsedForUser($this->session->getLocalUserId(), $recovery_code);
				$this->session->set('2fa', true);
				DI::sysmsg()->addInfo($this->t('Remaining recovery codes: %d', RecoveryCode::countValidForUser($this->session->getLocalUserId())));

				$this->auth->setForUser($this->app, User::getById($this->app->getLoggedInUserId()), true, true);

				$this->baseUrl->redirect($this->session->pop('return_path', ''));
			} else {
				DI::sysmsg()->addNotice($this->t('Invalid code, please retry.'));
			}
		}
	}

	protected function content(array $request = []): string
	{
		if (!$this->session->getLocalUserId()) {
			$this->baseUrl->redirect();
		}

		// Already authenticated with 2FA token
		if ($this->session->get('2fa')) {
			$this->baseUrl->redirect();
		}

		return Renderer::replaceMacros(Renderer::getMarkupTemplate('twofactor/recovery.tpl'), [
			'$form_security_token' => self::getFormSecurityToken('twofactor_recovery'),

			'$title'            => $this->t('Two-factor recovery'),
			'$message'          => $this->t('<p>You can enter one of your one-time recovery codes in case you lost access to your mobile device.</p>'),
			'$recovery_message' => $this->t('Don’t have your phone? <a href="%s">Enter a two-factor recovery code</a>', '2fa/recovery'),
			'$recovery_code'    => ['recovery_code', $this->t('Please enter a recovery code'), '', '', '', 'placeholder="000000-000000"'],
			'$recovery_label'   => $this->t('Submit recovery code and complete login'),
		]);
	}
}
