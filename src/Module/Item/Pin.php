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
use Friendica\Model\Post;
use Friendica\Network\HTTPException;

/**
 * Toggle pinned items
 */
class Pin extends BaseModule
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

		$item = Post::selectFirst(['uri-id', 'uid', 'featured', 'author-id'], ['id' => $itemId]);
		if (!DBA::isResult($item)) {
			throw new HTTPException\NotFoundException();
		}

		if (!in_array($item['uid'], [0, DI::userSession()->getLocalUserId()])) {
			throw new HttpException\ForbiddenException($l10n->t('Access denied.'));
		}

		$pinned = !$item['featured'];

		if ($pinned) {
			Post\Collection::add($item['uri-id'], Post\Collection::FEATURED, $item['author-id'], DI::userSession()->getLocalUserId());
		} else {
			Post\Collection::remove($item['uri-id'], Post\Collection::FEATURED, DI::userSession()->getLocalUserId());
		}

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
			'verb'    => 'pin',
			'state'   => (int)$pinned,
		];

		System::jsonExit($return);
	}
}
