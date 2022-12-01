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
use Friendica\Content\Nav;
use Friendica\Content\Pager;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Module\BaseProfile;

function notes_init(App $a)
{
	if (! DI::userSession()->getLocalUserId()) {
		return;
	}

	Nav::setSelected('home');
}


function notes_content(App $a, bool $update = false)
{
	if (!DI::userSession()->getLocalUserId()) {
		DI::sysmsg()->addNotice(DI::l10n()->t('Permission denied.'));
		return;
	}

	$o = BaseProfile::getTabsHTML('notes', true, $a->getLoggedInUserNickname(), false);

	if (!$update) {
		$o .= '<h3>' . DI::l10n()->t('Personal Notes') . '</h3>';

		$x = [
			'lockstate' => 'lock',
			'acl' => \Friendica\Core\ACL::getSelfOnlyHTML(DI::userSession()->getLocalUserId(), DI::l10n()->t('Personal notes are visible only by yourself.')),
			'button' => DI::l10n()->t('Save'),
			'acl_data' => '',
		];

		$o .= DI::conversation()->statusEditor($x, $a->getContactId());
	}

	$condition = ['uid' => DI::userSession()->getLocalUserId(), 'post-type' => Item::PT_PERSONAL_NOTE, 'gravity' => Item::GRAVITY_PARENT,
		'contact-id'=> $a->getContactId()];

	if (DI::mode()->isMobile()) {
		$itemsPerPage = DI::pConfig()->get(DI::userSession()->getLocalUserId(), 'system', 'itemspage_mobile_network',
			DI::config()->get('system', 'itemspage_network_mobile'));
	} else {
		$itemsPerPage = DI::pConfig()->get(DI::userSession()->getLocalUserId(), 'system', 'itemspage_network',
			DI::config()->get('system', 'itemspage_network'));
	}

	$pager = new Pager(DI::l10n(), DI::args()->getQueryString(), $itemsPerPage);

	$params = ['order' => ['created' => true],
		'limit' => [$pager->getStart(), $pager->getItemsPerPage()]];
	$r = Post::selectThreadForUser(DI::userSession()->getLocalUserId(), ['uri-id'], $condition, $params);

	$count = 0;

	if (DBA::isResult($r)) {
		$notes = Post::toArray($r);

		$count = count($notes);

		$o .= DI::conversation()->create($notes, 'notes', $update);
	}

	$o .= $pager->renderMinimal($count);

	return $o;
}
