<?php
/**
 * @copyright Copyright (C) 2010-2021, the Friendica project
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

namespace Friendica\Module\Debug;

use Friendica\BaseModule;
use Friendica\DI;
use Friendica\Model\Post;
use Friendica\Network\HTTPException;

/**
 * Print the body of an Item
 */
class ItemBody extends BaseModule
{
	public function content(): string
	{
		if (!local_user()) {
			throw new HTTPException\UnauthorizedException(DI::l10n()->t('Access denied.'));
		}

		if (empty(static::$parameters['item'])) {
			throw new HTTPException\NotFoundException(DI::l10n()->t('Item not found.'));
		}

		$itemId = intval(static::$parameters['item']);

		$item = Post::selectFirst(['body'], ['uid' => [0, local_user()], 'uri-id' => $itemId]);

		if (!empty($item)) {
			if (DI::mode()->isAjax()) {
				echo str_replace("\n", '<br />', $item['body']);
				exit();
			} else {
				return str_replace("\n", '<br />', $item['body']);
			}
		} else {
			throw new HTTPException\NotFoundException(DI::l10n()->t('Item not found.'));
		}
	}
}
