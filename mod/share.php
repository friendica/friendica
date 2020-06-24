<?php
/**
 * @copyright Copyright (C) 2020, Friendica
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
use Friendica\Model\Item;

function share_init(App $a) {
	$post_id = (($a->argc > 1) ? intval($a->argv[1]) : 0);

	if (!$post_id || !local_user()) {
		exit();
	}

	$fields = ['private', 'body', 'author-name', 'author-link', 'author-avatar',
		'guid', 'created', 'plink', 'title'];
	$item = Item::selectFirst($fields, ['id' => $post_id]);

	if (!DBA::isResult($item) || $item['private'] == Item::PRIVATE) {
		exit();
	}

	if (strpos($item['body'], "[/share]") !== false) {
		$pos = strpos($item['body'], "[share");
		$o = substr($item['body'], $pos);
	} else {
		$o = BBCode::getShareOpeningTag($item['author-name'], $item['author-link'], $item['author-avatar'], $item['plink'], $item['created'], $item['guid']);

		if ($item['title']) {
			$o .= '[h3]'.$item['title'].'[/h3]'."\n";
		}

		$o .= $item['body'];
		$o .= "[/share]";
	}

	echo $o;
	exit();
}
