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
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Navigation\SystemMessages;
use Friendica\Network\HTTPException;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

/**
 * This abstract module is meant to be extended by all modules that are reserved to moderator users.
 *
 * It performs a blanket permission check in all the module methods as long as the relevant `parent::method()` is
 * called in the inheriting module.
 *
 * Additionally, it puts together the moderation page aside with all the moderation links.
 *
 * @package Friendica\Module
 */
abstract class BaseModeration extends BaseModule
{
	/** @var IHandleUserSessions */
	protected $session;
	/** @var SystemMessages */
	protected $systemMessages;
	/** @var App */
	protected $app;
	/** @var App\Page */
	protected $page;

	public function __construct(App\Page $page, App $app, SystemMessages $systemMessages, IHandleUserSessions $session, L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->session        = $session;
		$this->systemMessages = $systemMessages;
		$this->app            = $app;
		$this->page           = $page;
	}

	/**
	 * Checks moderator access and throws exceptions if not logged-in moderator
	 *
	 * @param bool $interactive
	 * @return void
	 * @throws HTTPException\ForbiddenException
	 * @throws HTTPException\InternalServerErrorException
	 */
	public function checkModerationAccess(bool $interactive = false)
	{
		if (!$this->session->getLocalUserId()) {
			if ($interactive) {
				$this->systemMessages->addNotice($this->t('Please login to continue.'));
				$this->session->set('return_path', $this->args->getQueryString());
				$this->baseUrl->redirect('login');
			} else {
				throw new HTTPException\UnauthorizedException($this->t('Please login to continue.'));
			}
		}

		if (!$this->app->isSiteAdmin()) {
			throw new HTTPException\ForbiddenException($this->t('You don\'t have access to administration pages.'));
		}

		if ($this->session->getSubManagedUserId()) {
			throw new HTTPException\ForbiddenException($this->t('Submanaged account can\'t access the administration pages. Please log back in as the main account.'));
		}
	}

	protected function content(array $request = []): string
	{
		$this->checkModerationAccess(true);

		// Header stuff
		$this->page['htmlhead'] .= Renderer::replaceMacros(Renderer::getMarkupTemplate('moderation/settings_head.tpl'), []);

		/*
		 * Side bar links
		 */

		// array(url, name, extra css classes)
		// not part of $aside to make the template more adjustable
		$aside_sub = [
			'information' => [$this->t('Information'), [
				'overview' => ['moderation', $this->t('Overview'), 'overview'],
			]],
			'configuration' => [$this->t('Configuration'), [
				'users' => ['moderation/users', $this->t('Users'), 'users'],
			]],
			'tools' => [$this->t('Tools'), [
				'contactblock' => ['moderation/blocklist/contact', $this->t('Contact Blocklist'), 'contactblock'],
				'blocklist'    => ['moderation/blocklist/server', $this->t('Server Blocklist'), 'blocklist'],
				'deleteitem'   => ['moderation/item/delete', $this->t('Delete Item'), 'deleteitem'],
			]],
			'diagnostics' => [$this->t('Diagnostics'), [
				'itemsource' => ['moderation/item/source', $this->t('Item Source'), 'itemsource'],
			]],
		];

		$t = Renderer::getMarkupTemplate('moderation/aside.tpl');
		$this->page['aside'] .= Renderer::replaceMacros($t, [
			'$subpages'  => $aside_sub,
			'$admtxt'    => $this->t('Moderation'),
			'$h_pending' => $this->t('User registrations waiting for confirmation'),
			'$modurl'    => 'moderation/'
		]);

		return '';
	}
}
