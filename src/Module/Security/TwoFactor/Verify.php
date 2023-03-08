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
use Friendica\Core\PConfig\Capability\IManagePersonalConfigValues;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Module\Response;
use Friendica\Util\Profiler;
use PragmaRX\Google2FA\Google2FA;
use Friendica\Security\TwoFactor;
use Psr\Log\LoggerInterface;

/**
 * Page 1: Authenticator code verification
 *
 * @package Friendica\Module\TwoFactor
 */
class Verify extends BaseModule
{
	protected $errors = [];

	/** @var IManagePersonalConfigValues  */
	protected $pConfig;
	/** @var IHandleUserSessions */
	protected $session;

	public function __construct(L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, IManagePersonalConfigValues $pConfig, IHandleUserSessions $session, $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->session     = $session;
		$this->pConfig     = $pConfig;
	}

	protected function post(array $request = [])
	{
		if (!$this->session->getLocalUserId()) {
			return;
		}

		if (($request['action'] ?? '') === 'verify') {
			self::checkFormSecurityTokenRedirectOnError('2fa', 'twofactor_verify');

			$code = $request['verify_code'] ?? '';

			$valid = (new Google2FA())->verifyKey($this->pConfig->get($this->session->getLocalUserId(), '2fa', 'secret'), $code);

			// The same code can't be used twice even if it's valid
			if ($valid && $this->session->get('2fa') !== $code) {
				$this->session->set('2fa', $code);

				$this->baseUrl->redirect('2fa/trust');
			} else {
				$this->errors[] = $this->t('Invalid code, please retry.');
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

		return Renderer::replaceMacros(Renderer::getMarkupTemplate('twofactor/verify.tpl'), [
			'$form_security_token' => self::getFormSecurityToken('twofactor_verify'),

			'$title'            => $this->t('Two-factor authentication'),
			'$message'          => $this->t('<p>Open the two-factor authentication app on your device to get an authentication code and verify your identity.</p>'),
			'$errors_label'     => $this->tt('Error', 'Errors', count($this->errors)),
			'$errors'           => $this->errors,
			'$recovery_message' => $this->t('If you do not have access to your authentication code you can use a <a href="%s">two-factor recovery code</a>.', '2fa/recovery'),
			'$verify_code'      => ['verify_code', $this->t('Please enter a code from your authentication app'), '', '', $this->t('Required'), 'autofocus autocomplete="one-time-code" placeholder="000000" inputmode="numeric" pattern="[0-9]*"'],
			'$verify_label'     => $this->t('Verify code and complete login'),
		]);
	}
}
