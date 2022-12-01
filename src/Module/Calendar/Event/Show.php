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

namespace Friendica\Module\Calendar\Event;

use Friendica\App;
use Friendica\BaseModule;
use Friendica\Content\Feature;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Core\System;
use Friendica\Model\Event;
use Friendica\Model\User;
use Friendica\Module\Response;
use Friendica\Network\HTTPException;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

/**
 * Displays one specific event in a <div> container
 */
class Show extends BaseModule
{
	/** @var IHandleUserSessions */
	protected $session;
	/** @var App */
	private $app;

	public function __construct(App $app, L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, IHandleUserSessions $session, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->session = $session;
		$this->app     = $app;
	}

	protected function rawContent(array $request = [])
	{
		$nickname = $this->parameters['nickname'] ?? $this->app->getLoggedInUserNickname();
		if (!$nickname) {
			throw new HTTPException\UnauthorizedException();
		}

		$owner = Event::getOwnerForNickname($nickname);

		$event = Event::getByIdAndUid($owner['uid'], (int)$this->parameters['id'] ?? 0);
		if (empty($event)) {
			throw new HTTPException\NotFoundException($this->t('Event not found.'));
		}

		$tplEvent = Event::prepareForItem($event);

		$event_item = [];
		foreach ($tplEvent['item'] as $k => $v) {
			$event_item[str_replace('-', '_', $k)] = $v;
		}
		$tplEvent['item'] = $event_item;

		$tpl = Renderer::getMarkupTemplate('calendar/event.tpl');

		$o = Renderer::replaceMacros($tpl, [
			'$event' => $tplEvent,
		]);

		System::httpExit($o);
	}
}
