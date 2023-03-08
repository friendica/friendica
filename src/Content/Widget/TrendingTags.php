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

namespace Friendica\Content\Widget;

use Friendica\Core\Renderer;
use Friendica\DI;
use Friendica\Model\Tag;

/**
 * Trending tags aside widget for the community pages, handles both local and global scopes
 *
 * @package Friendica\Content\Widget
 */
class TrendingTags
{
	/**
	 * @param string $content 'global' (all posts) or 'local' (this node's posts only)
	 * @param int    $period  Period in hours to consider posts
	 *
	 * @return string Formatted HTML code
	 * @throws \Exception
	 */
	public static function getHTML(string $content = 'global', int $period = 24): string
	{
		if ($content == 'local') {
			$tags = Tag::getLocalTrendingHashtags($period, 20);
		} else {
			$tags = Tag::getGlobalTrendingHashtags($period, 20);
		}

		$tpl = Renderer::getMarkupTemplate('widget/trending_tags.tpl');
		$o = Renderer::replaceMacros($tpl, [
			'$title' => DI::l10n()->tt('Trending Tags (last %d hour)', 'Trending Tags (last %d hours)', $period),
			'$more'  => DI::l10n()->t('More Trending Tags'),
			'$tags'  => $tags,
		]);

		return $o;
	}
}
