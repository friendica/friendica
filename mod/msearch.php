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

use Friendica\App;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\User;
use Friendica\Util\Proxy;

function msearch_post(App $a)
{
	$search = $_POST['s'] ?? '';
	$perpage  = intval(($_POST['n'] ?? 0) ?: 80);
	$page     = intval(($_POST['p'] ?? 0) ?: 1);
	$startrec = ($page - 1) * $perpage;

	$total = 0;
	$results = [];

	if (!strlen($search)) {
		$output = ['total' => 0, 'items_page' => $perpage, 'page' => $page, 'results' => $results];
		echo json_encode($output);
		exit();
	}

	$total = 0;

	$condition = ["`net-publish` AND MATCH(`pub_keywords`) AGAINST (?)", $search];
	$total = DBA::count('owner-view', $condition);

	$search_stmt = DBA::select('owner-view', ['pub_keywords', 'name', 'nickname', 'uid'], $condition, ['limit' => [$startrec, $perpage]]);
	while ($search_result = DBA::fetch($search_stmt)) {
		$results[] = [
			'name'  => $search_result['name'],
			'url'   => DI::baseUrl() . '/profile/' . $search_result['nickname'],
			'photo' => User::getAvatarUrl($search_result, Proxy::SIZE_THUMB),
			'tags'  => str_replace([',', '  '], [' ', ' '], $search_result['pub_keywords'])
		];
	}

	DBA::close($search_stmt);

	$output = ['total' => $total, 'items_page' => $perpage, 'page' => $page, 'results' => $results];

	echo json_encode($output);

	exit();
}
