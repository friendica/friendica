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

namespace Friendica\Module\Filer;

use Friendica\App;
use Friendica\BaseModule;
use Friendica\Core\L10n;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\Model\Post;
use Friendica\Module\Response;
use Friendica\Navigation\SystemMessages;
use Friendica\Network\HTTPException;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

/**
 * Remove a tag from a file
 */
class RemoveTag extends BaseModule
{
	/** @var SystemMessages */
	private $systemMessages;
	/** @var IHandleUserSessions */
	private $userSession;

	public function __construct(SystemMessages $systemMessages, L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, IHandleUserSessions $userSession, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->systemMessages = $systemMessages;
		$this->userSession    = $userSession;
	}

	protected function post(array $request = [])
	{
		System::httpError($this->removeTag($request));
	}

	protected function content(array $request = []): string
	{
		if (!$this->userSession->getLocalUserId()) {
			throw new HTTPException\ForbiddenException();
		}

		$this->removeTag($request, $type, $term);

		if ($type == Post\Category::FILE) {
			$this->baseUrl->redirect('filed?file=' . rawurlencode($term));
		}

		return '';
	}

	/**
	 * @param array       $request The $_REQUEST array
	 * @param string|null $type    Output parameter with the computed type
	 * @param string|null $term    Output parameter with the computed term
	 *
	 * @return int The relevant HTTP code
	 *
	 * @throws \Exception
	 */
	private function removeTag(array $request, string &$type = null, string &$term = null): int
	{
		$item_id = $this->parameters['id'] ?? 0;

		$term = trim($request['term'] ?? '');
		$cat = trim($request['cat'] ?? '');

		if (!empty($cat)) {
			$type = Post\Category::CATEGORY;
			$term = $cat;
		} else {
			$type = Post\Category::FILE;
		}

		$this->logger->info('Filer - Remove Tag', [
			'term' => $term,
			'item' => $item_id,
			'type' => $type
		]);

		if (!$item_id || !strlen($term)) {
			$this->systemMessages->addNotice($this->l10n->t('Item was not deleted'));
			return 401;
		}

		$item = Post::selectFirst(['uri-id'], ['id' => $item_id]);
		if (!DBA::isResult($item)) {
			return 404;
		}

		if (!Post\Category::deleteFileByURIId($item['uri-id'], $this->userSession->getLocalUserId(), $type, $term)) {
			$this->systemMessages->addNotice($this->l10n->t('Item was not removed'));
			return 500;
		}

		return 200;
	}
}
