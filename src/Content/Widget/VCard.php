<?php
/**
 * @copyright Copyright (C) 2010-2024, the Friendica project
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
	 * @param array $contact
	 * @param bool  $hide_mention
	 * @return string
	 */
	public static function getHTML(array $contact, bool $hide_mention = false): string
	{
		if (!isset($contact['network']) || !isset($contact['id'])) {
			Logger::warning('Incomplete contact', ['contact' => $contact ?? []]);
		}

		$contact_url = Contact::getProfileLink($contact);

		if ($contact['network'] != '') {
			$network_link   = Strings::formatNetworkName($contact['network'], $contact_url);
			$network_avatar = ContactSelector::networkToIcon($contact['network'], $contact_url);
		} else {
			$network_link   = '';
			$network_avatar = '';
		}

		$follow_link      = '';
		$unfollow_link    = '';
		$wallmessage_link = '';
		$mention_label    = '';
		$mention_link     = '';
		$showgroup_link   = '';

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
					$unfollow_link = 'contact/unfollow?url=' . urlencode($contact_url) . '&auto=1';
				} elseif (!$pending) {
					$follow_link = 'contact/follow?url=' . urlencode($contact_url) . '&auto=1';
				}
			}

			if (in_array($rel, [Contact::FOLLOWER, Contact::FRIEND]) && Contact::canReceivePrivateMessages($contact)) {
				$wallmessage_link = 'message/new/' . $id;
			}

			if ($contact['contact-type'] == Contact::TYPE_COMMUNITY) {
				if (!$hide_mention) {
					$mention_label  = DI::l10n()->t('Post to group');
					$mention_link   = 'compose/0?body=!' . $contact['addr'];
				}
				$showgroup_link = 'network/group/' . $id;
			} elseif (!$hide_mention) {
				$mention_label = DI::l10n()->t('Mention');
				$mention_link  = 'compose/0?body=@' . $contact['addr'];
			}
		}

		return Renderer::replaceMacros(Renderer::getMarkupTemplate('widget/vcard.tpl'), [
			'$contact'          => $contact,
			'$photo'            => $photo,
			'$url'              => Contact::magicLinkByContact($contact, $contact_url),
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
			'$mention'          => $mention_label,
			'$mention_link'     => $mention_link,
			'$showgroup'        => DI::l10n()->t('View group'),
			'$showgroup_link'   => $showgroup_link,
		]);
	}
}
