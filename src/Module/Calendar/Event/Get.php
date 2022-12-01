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
use Friendica\Content\Feature;
use Friendica\Core\L10n;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Core\System;
use Friendica\Model\Event;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Model\User;
use Friendica\Module\Response;
use Friendica\Network\HTTPException;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

/**
 * GET-Controller for event
 * returns the result as JSON
 */
class Get extends \Friendica\BaseModule
{
	/** @var IHandleUserSessions */
	protected $session;

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

		if (!empty($request['id'])) {
			$events = [Event::getByIdAndUid($owner['uid'], $request['id'])];
		} else {
			$events = Event::getListByDate($owner['uid'], $request['start'] ?? '', $request['end'] ?? '');
		}

		System::jsonExit($events ? self::map($events) : []);
	}

	private static function map(array $events): array
	{
		return array_map(function ($event) {
			$item = Post::selectFirst(['plink', 'author-name', 'author-avatar', 'author-link', 'private', 'uri-id'], ['id' => $event['itemid']]);
			if (empty($item)) {
				// Using default values when no item had been found
				$item = ['plink' => '', 'author-name' => '', 'author-avatar' => '', 'author-link' => '', 'private' => Item::PUBLIC, 'uri-id' => ($event['uri-id'] ?? 0)];
			}

			return [
				'id'       => $event['id'],
				'title'    => $event['summary'],
				'start'    => DateTimeFormat::local($event['start']),
				'end'      => DateTimeFormat::local($event['finish']),
				'nofinish' => $event['nofinish'],
				'desc'     => $event['desc'],
				'location' => $event['location'],
				'item'     => $item,
			];
		}, $events);
	}
}
