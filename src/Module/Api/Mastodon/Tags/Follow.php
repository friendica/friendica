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

namespace Friendica\Module\Api\Mastodon\Tags;

use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Module\BaseApi;

/**
 * @see https://docs.joinmastodon.org/methods/tags/#follow
 */
class Follow extends BaseApi
{
	protected function post(array $request = [])
	{
		self::checkAllowedScope(self::SCOPE_WRITE);
		$uid = self::getCurrentUserID();

		if (empty($this->parameters['hashtag'])) {
			DI::mstdnError()->UnprocessableEntity();
		}

		$fields = ['uid' => $uid, 'term' => '#' . ltrim($this->parameters['hashtag'], '#')];
		if (!DBA::exists('search', $fields)) {
			DBA::insert('search', $fields);
		}

		$hashtag = new \Friendica\Object\Api\Mastodon\Tag($this->baseUrl, ['name' => ltrim($this->parameters['hashtag'])], [], true);
		System::jsonExit($hashtag->toArray());
	}
}
