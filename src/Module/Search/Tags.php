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

namespace Friendica\Module\Search;

use Friendica\App;
use Friendica\BaseModule;
use Friendica\Core\L10n;
use Friendica\Core\System;
use Friendica\Database\Database;
use Friendica\Model\User;
use Friendica\Module\Response;
use Friendica\Util\Profiler;
use Friendica\Util\Proxy;
use Psr\Log\LoggerInterface;

/**
 * Search users because of their public/private tags
 */
class Tags extends BaseModule
{
	const DEFAULT_ITEMS_PER_PAGE = 80;

	/** @var Database */
	protected $database;

	public function __construct(L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, Database $database, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->database = $database;
	}

	protected function rawContent(array $request = [])
	{
		$tags     = $request['s'] ?? '';
		$perPage  = intval($request['n'] ?? self::DEFAULT_ITEMS_PER_PAGE);
		$page     = intval($request['p'] ?? 1);
		$startRec = ($page - 1) * $perPage;

		$results = [];

		if (empty($tags)) {
			System::jsonExit([
				'total'      => 0,
				'items_page' => $perPage,
				'page'       => $page,
				'results'    => $results,
			]);
		}

		$condition = [
			"`net-publish` AND MATCH(`pub_keywords`) AGAINST (?)",
			$tags
		];

		$totalCount = $this->database->count('owner-view', $condition);
		if ($totalCount === 0) {
			System::jsonExit([
				'total'      => 0,
				'items_page' => $perPage,
				'page'       => $page,
				'results'    => $results,
			]);
		}

		$searchStmt = $this->database->select('owner-view',
			['pub_keywords', 'name', 'nickname', 'uid'],
			$condition,
			['limit' => [$startRec, $perPage]]);

		while ($searchResult = $this->database->fetch($searchStmt)) {
			$results[] = [
				'name'  => $searchResult['name'],
				'url'   => $this->baseUrl . '/profile/' . $searchResult['nickname'],
				'photo' => User::getAvatarUrl($searchResult, Proxy::SIZE_THUMB),
			];
		}

		$this->database->close($searchStmt);

		System::jsonExit([
			'total'      => $totalCount,
			'items_page' => $perPage,
			'page'       => $page,
			'results'    => $results,
		]);
	}
}
