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
use Friendica\Core\Protocol;
use Friendica\Core\System;
use Friendica\DI;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Network\HTTPException;
use Friendica\Protocol\Diaspora;

/**
 * Performs an activity (like, dislike, announce, attendyes, attendno, attendmaybe)
 * and optionally redirects to a return path
 */
class Activity extends BaseModule
{
	protected function rawContent(array $request = [])
	{
		if (!DI::userSession()->isAuthenticated()) {
			throw new HTTPException\ForbiddenException();
		}

		if (empty($this->parameters['id']) || empty($this->parameters['verb'])) {
			throw new HTTPException\BadRequestException();
		}

		$verb    = $this->parameters['verb'];
		$itemId  = $this->parameters['id'];
		$handled = false;

		if (in_array($verb, ['announce', 'unannounce'])) {
			$item = Post::selectFirst(['network', 'uri-id'], ['id' => $itemId, 'uid' => [DI::userSession()->getLocalUserId(), 0]]);
			if ($item['network'] == Protocol::DIASPORA) {
				$quote = Post::selectFirst(['id'], ['quote-uri-id' => $item['uri-id'], 'body' => '', 'origin' => true, 'uid' => DI::userSession()->getLocalUserId()]);
				if (!empty($quote['id'])) {
					if (!Item::markForDeletionById($quote['id'])) {
						throw new HTTPException\BadRequestException();
					}
				} else {
					Diaspora::performReshare($item['uri-id'], DI::userSession()->getLocalUserId());
				}
				$handled = true;
			}
		}

		if (!$handled && !Item::performActivity($itemId, $verb, DI::userSession()->getLocalUserId())) {
			throw new HTTPException\BadRequestException();
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
			'status' => 'ok',
			'item_id' => $itemId,
			'verb' => $verb,
			'state' => 1,
		];

		System::jsonExit($return);
	}
}
