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

namespace Friendica\Module\Settings\TwoFactor;

use Friendica\App;
use Friendica\Core\L10n;
use Friendica\Core\PConfig\Capability\IManagePersonalConfigValues;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\DI;
use Friendica\Module\Response;
use Friendica\Security\TwoFactor\Model\RecoveryCode;
use Friendica\Module\BaseSettings;
use Friendica\Module\Security\Login;
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

	public function __construct(IManagePersonalConfigValues $pConfig, IHandleUserSessions $session, App\Page $page, L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, array $server, array $parameters = [])
	{
		parent::__construct($session, $page, $l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->pConfig = $pConfig;

		if (!DI::userSession()->getLocalUserId()) {
			return;
		}

		$secret = $this->pConfig->get(DI::userSession()->getLocalUserId(), '2fa', 'secret');

		if (!$secret) {
			$this->baseUrl->redirect('settings/2fa');
		}

		if (!self::checkFormSecurityToken('settings_2fa_password', 't')) {
			DI::sysmsg()->addNotice($this->t('Please enter your password to access this page.'));
			$this->baseUrl->redirect('settings/2fa');
		}
	}

	protected function post(array $request = [])
	{
		if (!DI::userSession()->getLocalUserId()) {
			return;
		}

		if (!empty($_POST['action'])) {
			self::checkFormSecurityTokenRedirectOnError('settings/2fa/recovery', 'settings_2fa_recovery');

			if ($_POST['action'] == 'regenerate') {
				RecoveryCode::regenerateForUser(DI::userSession()->getLocalUserId());
				DI::sysmsg()->addInfo($this->t('New recovery codes successfully generated.'));
				$this->baseUrl->redirect('settings/2fa/recovery?t=' . self::getFormSecurityToken('settings_2fa_password'));
			}
		}
	}

	protected function content(array $request = []): string
	{
		if (!DI::userSession()->getLocalUserId()) {
			return Login::form('settings/2fa/recovery');
		}

		parent::content();

		if (!RecoveryCode::countValidForUser(DI::userSession()->getLocalUserId())) {
			RecoveryCode::generateForUser(DI::userSession()->getLocalUserId());
		}

		$recoveryCodes = RecoveryCode::getListForUser(DI::userSession()->getLocalUserId());

		$verified = $this->pConfig->get(DI::userSession()->getLocalUserId(), '2fa', 'verified');

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
