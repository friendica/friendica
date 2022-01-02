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

namespace Friendica\Module;

use Friendica\BaseModule;
use Friendica\DI;
use Friendica\Protocol\Feed as ProtocolFeed;

/**
 * Provides public Atom feeds
 *
 * Currently supported:
 * - /feed/[nickname]/ => posts
 * - /feed/[nickname]/posts => posts
 * - /feed/[nickname]/comments => comments
 * - /feed/[nickname]/replies => comments
 * - /feed/[nickname]/activity => activity
 *
 * The nocache GET parameter is provided mainly for debug purposes, requires auth
 *
 * @author Hypolite Petovan <hypolite@mrpetovan.com>
 */
class Feed extends BaseModule
{
	protected function content(array $request = []): string
	{
		$a = DI::app();

		$last_update = $_GET['last_update'] ?? '';
		$nocache     = !empty($_GET['nocache']) && local_user();

		$type = null;
		// @TODO: Replace with parameter from router
		if (DI::args()->getArgc() > 2) {
			$type = DI::args()->getArgv()[2];
		}

		switch ($type) {
			case 'posts':
			case 'comments':
			case 'activity':
				// Correct type names, no change needed
				break;
			case 'replies':
				$type = 'comments';
				break;
			default:
				$type = 'posts';
		}

		header("Content-type: application/atom+xml; charset=utf-8");
		echo ProtocolFeed::atom($this->parameters['nickname'], $last_update, 10, $type, $nocache, true);
		exit();
	}
}
