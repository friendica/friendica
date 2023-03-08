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

namespace Friendica\Module\Settings;

use Friendica\App;
use Friendica\Content\Feature;
use Friendica\Core\L10n;
use Friendica\Core\PConfig\Capability\IManagePersonalConfigValues;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Module\BaseSettings;
use Friendica\Module\Response;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

class Features extends BaseSettings
{
	/** @var IManagePersonalConfigValues */
	private $pConfig;

	public function __construct(IManagePersonalConfigValues $pConfig, IHandleUserSessions $session, App\Page $page, L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, array $server, array $parameters = [])
	{
		parent::__construct($session, $page, $l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->pConfig = $pConfig;
	}

	protected function post(array $request = [])
	{
		BaseSettings::checkFormSecurityTokenRedirectOnError('/settings/features', 'settings_features');
		foreach ($request as $k => $v) {
			if (strpos($k, 'feature_') === 0) {
				$this->pConfig->set($this->session->getLocalUserId(), 'feature', substr($k, 8), ((intval($v)) ? 1 : 0));
			}
		}
	}

	protected function content(array $request = []): string
	{
		parent::content($request);

		$arr      = [];
		$features = Feature::get();
		foreach ($features as $name => $feature) {
			$arr[$name]    = [];
			$arr[$name][0] = $feature[0];
			foreach (array_slice($feature, 1) as $f) {
				$arr[$name][1][] = ['feature_' . $f[0], $f[1], Feature::isEnabled($this->session->getLocalUserId(), $f[0]), $f[2]];
			}
		}

		$tpl = Renderer::getMarkupTemplate('settings/features.tpl');
		return Renderer::replaceMacros($tpl, [
			'$form_security_token' => BaseSettings::getFormSecurityToken('settings_features'),
			'$title'               => $this->t('Additional Features'),
			'$features'            => $arr,
			'$submit'              => $this->t('Save Settings'),
		]);
	}
}
