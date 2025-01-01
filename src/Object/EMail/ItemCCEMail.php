<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Object\EMail;

use Friendica\App\BaseURL;
use Friendica\Content\Text\HTML;
use Friendica\Core\L10n;
use Friendica\Core\Session\Model\UserSession;
use Friendica\Model\Item;
use Friendica\Model\User;
use Friendica\Object\Email;

/**
 * Class for creating CC emails based on a received item
 */
class ItemCCEMail extends Email
{
	public function __construct(UserSession $session, L10n $l10n, BaseURL $baseUrl, array $item, string $toAddress, string $authorThumb)
	{
		$user = User::getById($session->getLocalUserId());

		$disclaimer = '<hr />' . $l10n->t('This message was sent to you by %s, a member of the Friendica social network.', $user['username'])
					  . '<br />';
		$disclaimer .= $l10n->t('You may visit them online at %s', $baseUrl . '/profile/' . $session->getLocalUserNickname()) . '<br />';
		$disclaimer .= $l10n->t('Please contact the sender by replying to this post if you do not wish to receive these messages.') . '<br />';
		if (!$item['title'] == '') {
			$subject = $item['title'];
		} else {
			$subject = '[Friendica]' . ' ' . $l10n->t('%s posted an update.', $user['username']);
		}
		$link    = '<a href="' . $baseUrl . '/profile/' . $session->getLocalUserNickname() . '"><img src="' . $authorThumb . '" alt="' . $user['username'] . '" /></a><br /><br />';
		$html    = Item::prepareBody($item);
		$message = '<html><body>' . $link . $html . $disclaimer . '</body></html>';
		;

		parent::__construct(
			$user['username'],
			$user['email'],
			$user['email'],
			$toAddress,
			$subject,
			$message,
			HTML::toPlaintext($html . $disclaimer)
		);
	}
}
