<?php
/**
 * @copyright Copyright (C) 2010-2022, the Friendica project
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
use Friendica\Module\BaseSettings;
use Friendica\Module\Response;
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

	public function __construct(IManagePersonalConfigValues $pConfig, TwoFactor\Repository\TrustedBrowser $trustedBrowserRepo, IHandleUserSessions $session, App\Page $page, L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, array $server, array $parameters = [])
	{
		parent::__construct($session, $page, $l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->pConfig            = $pConfig;
		$this->trustedBrowserRepo = $trustedBrowserRepo;

		if (!DI::userSession()->getLocalUserId()) {
			return;
		}

		$verified = $this->pConfig->get(DI::userSession()->getLocalUserId(), '2fa', 'verified');

		if (!$verified) {
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
			self::checkFormSecurityTokenRedirectOnError('settings/2fa/trusted', 'settings_2fa_trusted');

			switch ($_POST['action']) {
				case 'remove_all':
					$this->trustedBrowserRepo->removeAllForUser(DI::userSession()->getLocalUserId());
					DI::sysmsg()->addInfo($this->t('Trusted browsers successfully removed.'));
					$this->baseUrl->redirect('settings/2fa/trusted?t=' . self::getFormSecurityToken('settings_2fa_password'));
					break;
			}
		}

		if (!empty($_POST['remove_id'])) {
			self::checkFormSecurityTokenRedirectOnError('settings/2fa/trusted', 'settings_2fa_trusted');

			if ($this->trustedBrowserRepo->removeForUser(DI::userSession()->getLocalUserId(), $_POST['remove_id'])) {
				DI::sysmsg()->addInfo($this->t('Trusted browser successfully removed.'));
			}

			$this->baseUrl->redirect('settings/2fa/trusted?t=' . self::getFormSecurityToken('settings_2fa_password'));
		}
	}


	protected function content(array $request = []): string
	{
		parent::content();

		$trustedBrowsers = $this->trustedBrowserRepo->selectAllByUid(DI::userSession()->getLocalUserId());

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
