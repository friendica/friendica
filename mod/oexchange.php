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
use Friendica\Core\Renderer;
use Friendica\DI;
use Friendica\Module\Security\Login;
use Friendica\Util\Strings;

function oexchange_init(App $a) {

	if (($a->argc > 1) && ($a->argv[1] === 'xrd')) {
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

	if (($a->argc > 1) && $a->argv[1] === 'done') {
		info(DI::l10n()->t('Post successful.') . EOL);
		return;
	}

	$url = ((!empty($_REQUEST['url']))
		? urlencode(Strings::escapeTags(trim($_REQUEST['url']))) : '');
	$title = ((!empty($_REQUEST['title']))
		? '&title=' . urlencode(Strings::escapeTags(trim($_REQUEST['title']))) : '');
	$description = ((!empty($_REQUEST['description']))
		? '&description=' . urlencode(Strings::escapeTags(trim($_REQUEST['description']))) : '');
	$tags = ((!empty($_REQUEST['tags']))
		? '&tags=' . urlencode(Strings::escapeTags(trim($_REQUEST['tags']))) : '');

	$s = DI::httpRequest()->fetch(DI::baseUrl() . '/parse_url?url=' . $url . $title . $description . $tags);

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
