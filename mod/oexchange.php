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
use Friendica\Content\Text\HTML;
use Friendica\Core\System;
use Friendica\DI;
use Friendica\Module\Response;
use Friendica\Module\Security\Login;
use Friendica\Util\XML;

function oexchange_init(App $a)
{
	if ((DI::args()->getArgc() <= 1) || (DI::args()->getArgv()[1] != 'xrd')) {
		return;
	}

	$baseURL = DI::baseUrl()->get();

	$xml = null;

	XML::fromArray([
		'XRD' => [
			'@attributes' => [
				'xmlns'    => 'http://docs.oasis-open.org/ns/xri/xrd-1.0',
			],
			'Subject' => $baseURL,
			'1:Property' => [
				'@attributes' => [
					'type'  => 'http://www.oexchange.org/spec/0.8/prop/vendor',
				],
				'Friendica'
			],
			'2:Property' => [
				'@attributes' => [
					'type'  => 'http://www.oexchange.org/spec/0.8/prop/title',
				],
				'Friendica Social Network'
			],
			'3:Property' => [
				'@attributes' => [
					'type'  => 'http://www.oexchange.org/spec/0.8/prop/name',
				],
				'Friendica'
			],
			'4:Property' => [
				'@attributes' => [
					'type'  => 'http://www.oexchange.org/spec/0.8/prop/prompt',
				],
				'Send to Friendica'
			],
			'1:link' => [
				'@attributes' => [
					'rel'  => 'icon',
					'type' => 'image/png',
					'href' => $baseURL . '/images/friendica-16.png'
				]
			],
			'2:link' => [
				'@attributes' => [
					'rel'  => 'icon32',
					'type' => 'image/png',
					'href' => $baseURL . '/images/friendica-32.png'
				]
			],
			'3:link' => [
				'@attributes' => [
					'rel'  => 'http://www.oexchange.org/spec/0.8/rel/offer',
					'type' => 'text/html',
					'href' => $baseURL . '/oexchange'
				]
			],
		],
	], $xml);

	System::httpExit($xml->saveXML(), Response::TYPE_XML, 'application/xrd+xml');
}

function oexchange_content(App $a)
{
	if (!DI::userSession()->getLocalUserId()) {
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

	$s = BBCode::embedURL($url, true, $title, $description, $tags);

	if (!strlen($s)) {
		return;
	}

	$post = [];

	$post['return'] = '/oexchange/done';
	$post['body'] = HTML::toBBCode($s);

	$_REQUEST = $post;
	require_once 'mod/item.php';
	item_post($a);
}
