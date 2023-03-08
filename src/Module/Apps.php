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

namespace Friendica\Module;

use Friendica\App;
use Friendica\BaseModule;
use Friendica\Content\Nav;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Navigation\SystemMessages;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

/**
 * Shows the App menu
 */
class Apps extends BaseModule
{
	/** @var Nav */
	protected $nav;
	/** @var SystemMessages */
	protected $systemMessages;

	public function __construct(SystemMessages $systemMessages, Nav $nav, IHandleUserSessions $session, L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, IManageConfigValues $config, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->nav = $nav;
		$this->systemMessages = $systemMessages;

		$privateaddons = $config->get('config', 'private_addons');
		if ($privateaddons === "1" && !$session->getLocalUserId()) {
			$baseUrl->redirect();
		}
	}

	protected function content(array $request = []): string
	{
		$apps = $this->nav->getAppMenu();
		if (count($apps) == 0) {
			$this->systemMessages->addNotice($this->t('No installed applications.'));
		}

		$tpl = Renderer::getMarkupTemplate('apps.tpl');
		return Renderer::replaceMacros($tpl, [
			'$title' => $this->t('Applications'),
			'$apps'  => $apps,
		]);
	}
}
