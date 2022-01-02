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
use Friendica\Core\Renderer;
use Friendica\DI;
use Friendica\Module\Security\Login;

function oexchange_init(App $a) {

	if ((DI::args()->getArgc() > 1) && (DI::args()->getArgv()[1] === 'xrd')) {
		$tpl = Renderer::getMarkupTemplate('oexchange_xrd.tpl');

		$o = Renderer::replaceMacros($tpl, ['$base' => DI::baseUrl()]);
		echo $o;
		exit();
	}
}

function oexchange_content(App $a) {

	if (!local_user()) {
		$o = Login::form();
		return $o;
	}

	if ((DI::args()->getArgc() > 1) && DI::args()->getArgv()[1] === 'done') {
		return;
	}

	$url         = !empty($_REQUEST['url'])         ? trim($_REQUEST['url'])         : '';
	$title       = !empty($_REQUEST['title'])       ? trim($_REQUEST['title'])       : '';
	$description = !empty($_REQUEST['description']) ? trim($_REQUEST['description']) : '';
	$tags        = !empty($_REQUEST['tags'])        ? trim($_REQUEST['tags'])        : '';

	$s = \Friendica\Content\Text\BBCode::embedURL($url, true, $title, $description, $tags);

	if (!strlen($s)) {
		return;
	}

	$post = [];

	$post['profile_uid'] = local_user();
	$post['return'] = '/oexchange/done';
	$post['body'] = Friendica\Content\Text\HTML::toBBCode($s);

	$_REQUEST = $post;
	require_once('mod/item.php');
	item_post($a);
}
