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

use Friendica\BaseModule;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Network\HTTPException;

/**
 * Toggle starred items
 */
class Star extends BaseModule
{
	protected function rawContent(array $request = [])
	{
		$l10n = DI::l10n();

		if (!DI::userSession()->isAuthenticated()) {
			throw new HttpException\ForbiddenException($l10n->t('Access denied.'));
		}

		if (empty($this->parameters['id'])) {
			throw new HTTPException\BadRequestException();
		}

		$itemId = intval($this->parameters['id']);


		$item = Post::selectFirstForUser(DI::userSession()->getLocalUserId(), ['uid', 'uri-id', 'starred'], ['uid' => [0, DI::userSession()->getLocalUserId()], 'id' => $itemId]);
		if (empty($item)) {
			throw new HTTPException\NotFoundException();
		}

		if ($item['uid'] == 0) {
			$stored = Item::storeForUserByUriId($item['uri-id'], DI::userSession()->getLocalUserId(), ['post-reason' => Item::PR_ACTIVITY]);
			if (!empty($stored)) {
				$item = Post::selectFirst(['starred'], ['id' => $stored]);
				if (!DBA::isResult($item)) {
					throw new HTTPException\NotFoundException();
				}
				$itemId = $stored;
			} else {
				throw new HTTPException\NotFoundException();
			}
		}

		$starred = !(bool)$item['starred'];

		Item::update(['starred' => $starred], ['id' => $itemId]);

		// See if we've been passed a return path to redirect to
		$return_path = $_REQUEST['return'] ?? '';
		if (!empty($return_path)) {
			$rand = '_=' . time();
			if (strpos($return_path, '?')) {
				$rand = "&$rand";
			} else {
				$rand = "?$rand";
			}

			DI::baseUrl()->redirect($return_path . $rand);
		}

		$return = [
			'status'  => 'ok',
			'item_id' => $itemId,
			'verb'    => 'star',
			'state'   => (int)$starred,
		];

		System::jsonExit($return);
	}
}
