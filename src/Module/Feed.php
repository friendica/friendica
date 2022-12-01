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
use Friendica\Core\System;
use Friendica\Model\User;
use Friendica\Network\HTTPException;
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
 * @author Hypolite Petovan <hypolite@mrpetovan.com>
 */
class Feed extends BaseModule
{
	protected function rawContent(array $request = [])
	{
		$nick = $this->parameters['nickname'] ?? '';
		$type = $this->parameters['type'] ?? null;
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

		$last_update = $this->getRequestValue($request, 'last_update', '');

		$owner = User::getOwnerDataByNick($nick);
		if (!$owner || $owner['account_expired'] || $owner['account_removed']) {
			throw new HTTPException\NotFoundException($this->t('User not found.'));
		}

		if ($owner['blocked'] || $owner['hidewall']) {
			throw new HTTPException\UnauthorizedException($this->t('Access to this profile has been restricted.'));
		}

		$feed = ProtocolFeed::atom($owner, $last_update, 10, $type);

		System::httpExit($feed, Response::TYPE_ATOM);
	}
}
