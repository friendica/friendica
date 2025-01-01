<?php
/**
 * Copyright (C) 2010-2024, the Friendica project
 * SPDX-FileCopyrightText: 2010-2024 the Friendica project
 *
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 */

use Friendica\Content\Conversation;
use Friendica\Content\Nav;
use Friendica\Content\Pager;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Module\BaseProfile;

function notes_init()
{
	if (! DI::userSession()->getLocalUserId()) {
		return;
	}

	Nav::setSelected('home');
}


function notes_content(bool $update = false)
{
	$contactId = DI::appHelper()->getContactId();

	if (!DI::userSession()->getLocalUserId()) {
		DI::sysmsg()->addNotice(DI::l10n()->t('Permission denied.'));
		return;
	}

	$o = BaseProfile::getTabsHTML('notes', true, DI::userSession()->getLocalUserNickname(), false);

	if (!$update) {
		$o .= '<h3>' . DI::l10n()->t('Personal Notes') . '</h3>';

		$x = [
			'lockstate' => 'lock',
			'acl'       => \Friendica\Core\ACL::getSelfOnlyHTML(DI::userSession()->getLocalUserId(), DI::l10n()->t('Personal notes are visible only by yourself.')),
			'button'    => DI::l10n()->t('Save'),
			'acl_data'  => '',
		];

		$o .= DI::conversation()->statusEditor($x, $contactId);
	}

	$condition = ['uid' => DI::userSession()->getLocalUserId(), 'post-type' => Item::PT_PERSONAL_NOTE, 'gravity' => Item::GRAVITY_PARENT,
		'contact-id'       => $contactId];

	if (DI::mode()->isMobile()) {
		$itemsPerPage = DI::pConfig()->get(
			DI::userSession()->getLocalUserId(),
			'system',
			'itemspage_mobile_network',
			DI::config()->get('system', 'itemspage_network_mobile')
		);
	} else {
		$itemsPerPage = DI::pConfig()->get(
			DI::userSession()->getLocalUserId(),
			'system',
			'itemspage_network',
			DI::config()->get('system', 'itemspage_network')
		);
	}

	$pager = new Pager(DI::l10n(), DI::args()->getQueryString(), $itemsPerPage);

	$params = ['order' => ['created' => true],
		'limit'           => [$pager->getStart(), $pager->getItemsPerPage()]];
	$r = Post::selectThreadForUser(DI::userSession()->getLocalUserId(), ['uri-id'], $condition, $params);

	$count = 0;

	if (DBA::isResult($r)) {
		$notes = Post::toArray($r);

		$count = count($notes);

		$o .= DI::conversation()->render($notes, Conversation::MODE_NOTES, $update);
	}

	$o .= $pager->renderMinimal($count);

	return $o;
}
