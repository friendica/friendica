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

namespace Friendica\Module\Item;

use Friendica\App;
use Friendica\BaseModule;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\L10n;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Core\System;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Module\Response;
use Friendica\Protocol\DFRN;
use Friendica\Util\Profiler;
use Friendica\Network\HTTPException;
use Psr\Log\LoggerInterface;

/**
 * Controller to display an item (or the whole conversation of an item) as an ATOM Feed
 */
class Feed extends BaseModule
{
	/** @var IManageConfigValues */
	protected $config;
	/** @var IHandleUserSessions */
	protected $session;

	public function __construct(L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, IManageConfigValues $config, IHandleUserSessions $session, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->config  = $config;
		$this->session = $session;
	}

	protected function rawContent(array $request = [])
	{
		if ($this->config->get('system', 'block_public') && !$this->session->isAuthenticated()) {
			throw new HTTPException\UnauthorizedException($this->t('Access denied.'));
		}

		$uriId = $this->parameters['uri-id'];

		$item = Post::selectFirstForUser($this->session->getLocalUserId(), [
			'uri-id',
			'parent-uri-id',
			'author-id',
			'author-link',
			'body',
			'uid',
			'guid',
			'gravity',
		], [
			'uri-id'  => $uriId,
			'private' => [Item::PUBLIC, Item::UNLISTED],
			'uid'     => 0,
		]);

		if (empty($item)) {
			throw new HTTPException\BadRequestException($this->t('Item not found.', ['uri-id' => $uriId]));
		}

		$xml = DFRN::itemFeed($item['uri-id'], $item['uid'], ($this->parameters['mode'] ?? '') === 'conversation');

		if (empty($xml)) {
			throw new HTTPException\InternalServerErrorException($this->t('The feed for this item is unavailable.', ['uri-id' => $uriId]));
		}

		System::httpExit($xml, Response::TYPE_ATOM);
	}
}
