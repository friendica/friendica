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

namespace Friendica\Module\Api\Mastodon\Statuses;

use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Module\BaseApi;

/**
 * @see https://docs.joinmastodon.org/methods/statuses/
 */
class Bookmark extends BaseApi
{
	protected function post(array $request = [])
	{
		self::checkAllowedScope(self::SCOPE_WRITE);
		$uid = self::getCurrentUserID();

		if (empty($this->parameters['id'])) {
			DI::mstdnError()->UnprocessableEntity();
		}

		$item = Post::selectFirst(['uid', 'id', 'gravity'], ['uri-id' => $this->parameters['id'], 'uid' => [$uid, 0]], ['order' => ['uid' => true]]);
		if (!DBA::isResult($item)) {
			DI::mstdnError()->RecordNotFound();
		}

		if ($item['gravity'] != Item::GRAVITY_PARENT) {
			DI::mstdnError()->UnprocessableEntity(DI::l10n()->t('Only starting posts can be bookmarked'));
		}

		if ($item['uid'] == 0) {
			$stored = Item::storeForUserByUriId($this->parameters['id'], $uid, ['post-reason' => Item::PR_ACTIVITY]);
			if (!empty($stored)) {
				$item = Post::selectFirst(['id', 'gravity'], ['id' => $stored]);
				if (!DBA::isResult($item)) {
					DI::mstdnError()->RecordNotFound();
				}
			} else {
				DI::mstdnError()->RecordNotFound();
			}
		}

		Item::update(['starred' => true], ['id' => $item['id']]);

		System::jsonExit(DI::mstdnStatus()->createFromUriId($this->parameters['id'], $uid, self::appSupportsQuotes())->toArray());
	}
}
