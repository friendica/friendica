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

namespace Friendica\Module\Filer;

use Friendica\BaseModule;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Post;
use Friendica\Network\HTTPException;
use Friendica\Util\XML;

/**
 * Remove a tag from a file
 */
class RemoveTag extends BaseModule
{
	protected function content(array $request = []): string
	{
		if (!local_user()) {
			throw new HTTPException\ForbiddenException();
		}

		$logger = DI::logger();

		$item_id = $this->parameters['id'] ?? 0;

		$term = XML::unescape(trim($_GET['term'] ?? ''));
		$cat = XML::unescape(trim($_GET['cat'] ?? ''));

		if (!empty($cat)) {
			$type = Post\Category::CATEGORY;
			$term = $cat;
		} else {
			$type = Post\Category::FILE;
		}

		$logger->info('Filer - Remove Tag', [
			'term' => $term,
			'item' => $item_id,
			'type' => $type
		]);

		if ($item_id && strlen($term)) {
			$item = Post::selectFirst(['uri-id'], ['id' => $item_id]);
			if (!DBA::isResult($item)) {
				return '';
			}
			if (!Post\Category::deleteFileByURIId($item['uri-id'], local_user(), $type, $term)) {
				notice(DI::l10n()->t('Item was not removed'));
			}
		} else {
			notice(DI::l10n()->t('Item was not deleted'));
		}

		if ($type == Post\Category::FILE) {
			DI::baseUrl()->redirect('filed?file=' . rawurlencode($term));
		}

		return '';
	}
}
