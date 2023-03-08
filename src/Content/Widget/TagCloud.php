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
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Item;
use Friendica\Model\Tag;

/**
 * TagCloud widget
 *
 * @author Rabuzarus
 */
class TagCloud
{
	/**
	 * Construct a tag/term cloud block for an user.
	 *
	 * @param int    $uid      The user ID.
	 * @param int    $count    Max number of displayed tags/terms.
	 * @param int    $owner_id The contact ID of the owner of the tagged items.
	 * @param string $flags    Special item flags.
	 * @param int    $type     The tag/term type.
	 *
	 * @return string       HTML formatted output.
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function getHTML(int $uid, int $count = 0, int $owner_id = 0, string $flags = '', int $type = Tag::HASHTAG): string
	{
		$o = '';
		$r = self::tagadelic($uid, $count, $owner_id, $flags, $type);
		if (count($r)) {
			$contact = DBA::selectFirst('contact', ['url'], ['uid' => $uid, 'self' => true]);
			$url = DI::baseUrl()->remove($contact['url']);

			$tags = [];
			foreach ($r as $rr) {
				$tags[] = [
					'level' => $rr[2],
					'url'   => $url . '?tag=' . urlencode($rr[0]),
					'name'  => $rr[0],
				];
			}

			$tpl = Renderer::getMarkupTemplate('widget/tagcloud.tpl');
			$o = Renderer::replaceMacros($tpl, [
				'$title' => DI::l10n()->t('Tags'),
				'$tags'  => $tags
			]);
		}
		return $o;
	}

	/**
	 * Get alphabetical sorted array of used tags/terms of an user including
	 * a weighting by frequency of use.
	 *
	 * @param int    $uid      The user ID.
	 * @param int    $count    Max number of displayed tags/terms.
	 * @param int    $owner_id The contact id of the owner of the tagged items.
	 * @param string $flags    Special item flags.
	 * @param int    $type     The tag/term type.
	 *
	 * @return array        Alphabetical sorted array of used tags of an user.
	 * @throws \Exception
	 */
	private static function tagadelic($uid, $count = 0, $owner_id = 0, $flags = '', $type = Tag::HASHTAG)
	{
		$sql_options = Item::getPermissionsSQLByUserId($uid, 'post-user-view');
		$limit = $count ? sprintf('LIMIT %d', intval($count)) : '';

		if ($flags) {
			if ($flags === 'wall') {
				$sql_options .= ' AND `post-user-view`.`wall` ';
			}
		}

		if ($owner_id) {
			$sql_options .= ' AND `post-user-view`.`owner-id` = ' . intval($owner_id) . ' ';
		}

		// Fetch tags
		$tag_stmt = DBA::p("SELECT `name`, COUNT(`name`) AS `total` FROM `tag-search-view`
			LEFT JOIN `post-user-view` ON `tag-search-view`.`uri-id` = `post-user-view`.`uri-id` AND `tag-search-view`.`uid` = `post-user-view`.`uid`
			WHERE `tag-search-view`.`uid` = ?
			AND `post-user-view`.`visible` AND NOT `post-user-view`.`deleted`
			$sql_options
			GROUP BY `name` ORDER BY `total` DESC $limit",
			$uid
		);
		if (!DBA::isResult($tag_stmt)) {
			return [];
		}

		$r = DBA::toArray($tag_stmt);

		return self::tagCalc($r);
	}

	/**
	 * Calculate weighting of tags according to the frequency of use.
	 *
	 * @param array $arr Array of tags/terms with tag/term name and total count of use.
	 * @return array     Alphabetical sorted array of used tags/terms of an user.
	 */
	private static function tagCalc(array $arr)
	{
		$tags = [];
		$min = 1000000000.0;
		$max = -1000000000.0;
		$x = 0;

		if (!$arr) {
			return [];
		}

		foreach ($arr as $rr) {
			$tags[$x][0] = $rr['name'];
			$tags[$x][1] = log($rr['total']);
			$tags[$x][2] = 0;
			$min = min($min, $tags[$x][1]);
			$max = max($max, $tags[$x][1]);
			$x ++;
		}

		usort($tags, [self::class, 'tagsSort']);
		$range = max(0.01, $max - $min) * 1.0001;

		for ($x = 0; $x < count($tags); $x ++) {
			$tags[$x][2] = 1 + floor(9 * ($tags[$x][1] - $min) / $range);
		}

		return $tags;
	}

	/**
	 * Compare function to sort tags/terms alphabetically.
	 *
	 * @param string $a
	 * @param string $b
	 *
	 * @return int
	 */
	private static function tagsSort($a, $b)
	{
		if (strtolower($a[0]) == strtolower($b[0])) {
			return 0;
		}
		return ((strtolower($a[0]) < strtolower($b[0])) ? -1 : 1);
	}
}
