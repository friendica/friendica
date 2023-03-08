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
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Database\Database;
use Friendica\Module\BaseSettings;
use Friendica\Module\Response;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

class OAuth extends BaseSettings
{
	/** @var Database */
	private $database;

	public function __construct(Database $database, IHandleUserSessions $session, App\Page $page, L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, array $server, array $parameters = [])
	{
		parent::__construct($session, $page, $l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->database = $database;
	}

	protected function post(array $request = [])
	{
		if (!$this->session->getLocalUserId()) {
			return;
		}

		if (!isset($request['delete'])) {
			return;
		}

		BaseSettings::checkFormSecurityTokenRedirectOnError('/settings/oauth', 'settings_oauth');

		$this->database->delete('application-token', ['application-id' => $request['delete'], 'uid' => $this->session->getLocalUserId()]);
		$this->baseUrl->redirect('settings/oauth', true);
	}

	protected function content(array $request = []): string
	{
		parent::content($request);

		$applications = $this->database->selectToArray('application-view', ['id', 'uid', 'name', 'website', 'scopes', 'created_at'], ['uid' => $this->session->getLocalUserId()]);

		$tpl = Renderer::getMarkupTemplate('settings/oauth.tpl');
		return Renderer::replaceMacros($tpl, [
			'$form_security_token' => BaseSettings::getFormSecurityToken('settings_oauth'),
			'$title'               => $this->t('Connected Apps'),
			'$name'                => $this->t('Name'),
			'$website'             => $this->t('Home Page'),
			'$created_at'          => $this->t('Created'),
			'$delete'              => $this->t('Remove authorization'),
			'$apps'                => $applications,
		]);
	}
}
