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

use Friendica\App;
use Friendica\Content\Text\BBCode;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Post;
use Friendica\Model\Tag;

function tagrm_post(App $a)
{
	if (!local_user()) {
		DI::baseUrl()->redirect($_SESSION['photo_return']);
	}

	if (!empty($_POST['submit']) && ($_POST['submit'] === DI::l10n()->t('Cancel'))) {
		DI::baseUrl()->redirect($_SESSION['photo_return']);
	}

	$tags = [];
	foreach ($_POST['tag'] ?? [] as $tag) {
		$tags[] = hex2bin(trim($tag));
	}

	$item_id = $_POST['item'] ?? 0;
	update_tags($item_id, $tags);

	DI::baseUrl()->redirect($_SESSION['photo_return']);
	// NOTREACHED
}

/**
 * Updates tags from an item
 *
 * @param $item_id
 * @param $tags array
 * @throws Exception
 */
function update_tags($item_id, $tags)
{
	if (empty($item_id) || empty($tags)) {
		return;
	}

	$item = Post::selectFirst(['uri-id'], ['id' => $item_id, 'uid' => local_user()]);
	if (!DBA::isResult($item)) {
		return;
	}

	foreach ($tags as $new_tag) {
		if (preg_match_all('/([#@!])\[url\=([^\[\]]*)\]([^\[\]]*)\[\/url\]/ism', $new_tag, $results, PREG_SET_ORDER)) {
			foreach ($results as $tag) {
				Tag::removeByHash($item['uri-id'], $tag[1], $tag[3], $tag[2]);
			}
		}
	}
}

function tagrm_content(App $a)
{
	$o = '';

	$photo_return = $_SESSION['photo_return'] ?? '';

	if (!local_user()) {
		DI::baseUrl()->redirect($photo_return);
		// NOTREACHED
	}

	if (DI::args()->getArgc()== 3) {
		update_tags(DI::args()->getArgv()[1], [trim(hex2bin(DI::args()->getArgv()[2]))]);
		DI::baseUrl()->redirect($photo_return);
	}

	$item_id = ((DI::args()->getArgc()> 1) ? intval(DI::args()->getArgv()[1]) : 0);
	if (!$item_id) {
		DI::baseUrl()->redirect($photo_return);
		// NOTREACHED
	}

	$item = Post::selectFirst(['uri-id'], ['id' => $item_id, 'uid' => local_user()]);
	if (!DBA::isResult($item)) {
		DI::baseUrl()->redirect($photo_return);
	}

	$tag_text = Tag::getCSVByURIId($item['uri-id']);

	$arr = explode(',', $tag_text);

	if (empty($arr)) {
		DI::baseUrl()->redirect($photo_return);
	}

	$o .= '<h3>' . DI::l10n()->t('Remove Item Tag') . '</h3>';

	$o .= '<p id="tag-remove-desc">' . DI::l10n()->t('Select a tag to remove: ') . '</p>';

	$o .= '<form id="tagrm" action="tagrm" method="post" >';
	$o .= '<input type="hidden" name="item" value="' . $item_id . '" />';
	$o .= '<ul>';

	foreach ($arr as $x) {
		$o .= '<li><input type="checkbox" name="tag[]" value="' . bin2hex($x) . '" >' . BBCode::convert($x) . '</input></li>';
	}

	$o .= '</ul>';
	$o .= '<input id="tagrm-submit" type="submit" name="submit" value="' . DI::l10n()->t('Remove') .'" />';
	$o .= '<input id="tagrm-cancel" type="submit" name="submit" value="' . DI::l10n()->t('Cancel') .'" />';
	$o .= '</form>';

	return $o;
}
