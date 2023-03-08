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

use Friendica\Content\ContactSelector;
use Friendica\Content\Text\BBCode;
use Friendica\Core\Logger;
use Friendica\Core\Protocol;
use Friendica\Core\Renderer;
use Friendica\Core\System;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Util\Strings;

/**
 * VCard widget
 *
 * @author Michael Vogel
 */
class VCard
{
	/**
	 * Get HTML for vcard block
	 *
	 * @template widget/vcard.tpl
	 * @return string
	 */
	public static function getHTML(array $contact): string
	{
		if (!isset($contact['network']) || !isset($contact['id'])) {
			Logger::warning('Incomplete contact', ['contact' => $contact ?? [], 'callstack' => System::callstack(20)]);
		}

		if ($contact['network'] != '') {
			$network_link   = Strings::formatNetworkName($contact['network'], $contact['url']);
			$network_avatar = ContactSelector::networkToIcon($contact['network'], $contact['url']);
		} else {
			$network_link   = '';
			$network_avatar = '';
		}

		$follow_link      = '';
		$unfollow_link    = '';
		$wallmessage_link = '';

		$photo   = Contact::getPhoto($contact);

		if (DI::userSession()->getLocalUserId()) {
			if ($contact['uid']) {
				$id      = $contact['id'];
				$rel     = $contact['rel'];
				$pending = $contact['pending'];
			} else {
				$pcontact = Contact::selectFirst([], ['uid' => DI::userSession()->getLocalUserId(), 'uri-id' => $contact['uri-id'], 'deleted' => false]);

				$id      = $pcontact['id'] ?? 0;
				$rel     = $pcontact['rel'] ?? Contact::NOTHING;
				$pending = $pcontact['pending'] ?? false;

				if (!empty($pcontact) && in_array($pcontact['network'], [Protocol::MAIL, Protocol::FEED])) {
					$photo = Contact::getPhoto($pcontact);
				}
			}

			if (empty($contact['self']) && Protocol::supportsFollow($contact['network'])) {
				if (in_array($rel, [Contact::SHARING, Contact::FRIEND])) {
					$unfollow_link = 'contact/unfollow?url=' . urlencode($contact['url']) . '&auto=1';
				} elseif (!$pending) {
					$follow_link = 'contact/follow?url=' . urlencode($contact['url']) . '&auto=1';
				}
			}

			if (in_array($rel, [Contact::FOLLOWER, Contact::FRIEND]) && Contact::canReceivePrivateMessages($contact)) {
				$wallmessage_link = 'message/new/' . $id;
			}
		}

		return Renderer::replaceMacros(Renderer::getMarkupTemplate('widget/vcard.tpl'), [
			'$contact'          => $contact,
			'$photo'            => $photo,
			'$url'              => Contact::magicLinkByContact($contact, $contact['url']),
			'$about'            => BBCode::convertForUriId($contact['uri-id'] ?? 0, $contact['about'] ?? ''),
			'$xmpp'             => DI::l10n()->t('XMPP:'),
			'$matrix'           => DI::l10n()->t('Matrix:'),
			'$location'         => DI::l10n()->t('Location:'),
			'$network_link'     => $network_link,
			'$network_avatar'   => $network_avatar,
			'$network'          => DI::l10n()->t('Network:'),
			'$account_type'     => Contact::getAccountType($contact['contact-type']),
			'$follow'           => DI::l10n()->t('Follow'),
			'$follow_link'      => $follow_link,
			'$unfollow'         => DI::l10n()->t('Unfollow'),
			'$unfollow_link'    => $unfollow_link,
			'$wallmessage'      => DI::l10n()->t('Message'),
			'$wallmessage_link' => $wallmessage_link,
		]);
	}
}
