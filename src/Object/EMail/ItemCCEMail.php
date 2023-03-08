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

namespace Friendica\Object\EMail;

use Friendica\App;
use Friendica\App\BaseURL;
use Friendica\Content\Text\HTML;
use Friendica\Core\L10n;
use Friendica\Model\Item;
use Friendica\Model\User;
use Friendica\Object\Email;
use Friendica\Protocol\Email as EmailProtocol;

/**
 * Class for creating CC emails based on a received item
 */
class ItemCCEMail extends Email
{
	public function __construct(App $a, L10n $l10n, BaseURL $baseUrl, array $item, string $toAddress, string $authorThumb)
	{
		$user = User::getById($a->getLoggedInUserId());

		$disclaimer = '<hr />' . $l10n->t('This message was sent to you by %s, a member of the Friendica social network.', $user['username'])
		              . '<br />';
		$disclaimer .= $l10n->t('You may visit them online at %s', $baseUrl . '/profile/' . $a->getLoggedInUserNickname()) . '<br />';
		$disclaimer .= $l10n->t('Please contact the sender by replying to this post if you do not wish to receive these messages.') . '<br />';
		if (!$item['title'] == '') {
			$subject = $item['title'];
		} else {
			$subject = '[Friendica]' . ' ' . $l10n->t('%s posted an update.', $user['username']);
		}
		$link    = '<a href="' . $baseUrl . '/profile/' . $a->getLoggedInUserNickname() . '"><img src="' . $authorThumb . '" alt="' . $user['username'] . '" /></a><br /><br />';
		$html    = Item::prepareBody($item);
		$message = '<html><body>' . $link . $html . $disclaimer . '</body></html>';;

		parent::__construct($user['username'], $user['email'], $user['email'], $toAddress,
			$subject, $message, HTML::toPlaintext($html . $disclaimer));
	}
}
