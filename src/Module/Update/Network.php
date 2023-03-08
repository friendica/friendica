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

namespace Friendica\Module\Update;

use Friendica\Content\Conversation;
use Friendica\Core\System;
use Friendica\DI;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Module\Conversation\Network as NetworkModule;

class Network extends NetworkModule
{
	protected function rawContent(array $request = [])
	{
		if (!isset($request['p']) || !isset($request['item'])) {
			System::exit();
		}

		$this->parseRequest($request);

		$profile_uid = intval($request['p']);

		$o = '';

		if (empty($request['force'])) {
			System::htmlUpdateExit($o);
		}

		if (!empty($request['item'])) {
			$item = Post::selectFirst(['parent'], ['id' => $request['item']]);
			$parent = $item['parent'] ?? 0;
		} else {
			$parent = 0;
		}

		$conditionFields = [];
		if (!empty($parent)) {
			// Load only a single thread
			$conditionFields['parent'] = $parent;
		} elseif (self::$order === 'received') {
			// Only load new toplevel posts
			$conditionFields['unseen'] = true;
			$conditionFields['gravity'] = Item::GRAVITY_PARENT;
		} else {
			// Load all unseen items
			$conditionFields['unseen'] = true;
		}

		$params = ['limit' => 100];
		$table = 'network-item-view';

		$items = self::getItems($table, $params, $conditionFields);

		if (self::$order === 'received') {
			$ordering = '`received`';
		} elseif (self::$order === 'created') {
			$ordering = '`created`';
		} else {
			$ordering = '`commented`';
		}

		$o = DI::conversation()->create($items, Conversation::MODE_NETWORK, $profile_uid, false, $ordering, DI::userSession()->getLocalUserId());

		System::htmlUpdateExit($o);
	}
}
