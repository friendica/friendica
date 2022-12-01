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

namespace Friendica\Module\Post;

use Friendica\App;
use Friendica\Content;
use Friendica\Core\L10n;
use Friendica\Core\Protocol;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Core\System;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Module\Response;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

/**
 * Generates a share BBCode block for the provided item.
 *
 * Only used in Ajax calls
 */
class Share extends \Friendica\BaseModule
{
	/** @var IHandleUserSessions */
	private $session;
	/** @var Content\Item */
	private $contentItem;

	public function __construct(Content\Item $contentItem, IHandleUserSessions $session, L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->session     = $session;
		$this->contentItem = $contentItem;
	}

	protected function rawContent(array $request = [])
	{
		$post_id = $this->parameters['post_id'];
		if (!$post_id || !$this->session->getLocalUserId()) {
			System::httpError(403);
		}

		$item = Post::selectFirst(['private', 'body', 'uri', 'plink', 'network'], ['id' => $post_id]);
		if (!$item || $item['private'] == Item::PRIVATE) {
			System::httpError(404);
		}

		$shared = $this->contentItem->getSharedPost($item, ['uri']);
		if ($shared && empty($shared['comment'])) {
			$content = '[share]' . $shared['post']['uri'] . '[/share]';
		} elseif ($item['network'] == Protocol::FEED) {
			$content = '[attachment]' . $item['plink'] . '[/attachment]';
		} else {
			$content = '[share]' . $item['uri'] . '[/share]';
		}

		System::httpExit($content);
	}
}
