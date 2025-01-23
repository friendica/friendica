<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Content\Widget;

use Friendica\Content\ContactSelector;
use Friendica\Content\Text\BBCode;
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
	 * @param array $contact
	 * @param bool  $hide_mention
	 * @param bool  $hide_follow
	 * @return string
	 */
	public static function getHTML(array $contact, bool $hide_mention = false, bool $hide_follow = false): string
	{
		if (!isset($contact['network']) || !isset($contact['id'])) {
			DI::logger()->warning('Incomplete contact', ['contact' => $contact]);
		}

		$contact_url = Contact::getProfileLink($contact);

		if ($contact['network'] != '') {
			$network_link = Strings::formatNetworkName($contact['network'], $contact_url);
			$network_svg  = ContactSelector::networkToSVG($contact['network'], $contact['gsid'], '', DI::userSession()->getLocalUserId());
		} else {
			$network_link = '';
			$network_svg  = '';
		}

		$follow_link      = '';
		$unfollow_link    = '';
		$wallmessage_link = '';
		$mention_label    = '';
		$mention_link     = '';
		$showgroup_link   = '';

		$photo = Contact::getPhoto($contact);

		if (DI::userSession()->getLocalUserId()) {
			if (Contact\User::isIsBlocked($contact['id'], DI::userSession()->getLocalUserId())) {
				$hide_follow  = true;
				$hide_mention = true;
			}

			if ($contact['uid']) {
				$id      = $contact['id'];
				$rel     = $contact['rel'];
				$pending = $contact['pending'];
			} else {
				$pcontact = Contact::selectFirst([], ['uid' => DI::userSession()->getLocalUserId(), 'uri-id' => $contact['uri-id'], 'deleted' => false]);

				$id      = $pcontact['id']      ?? $contact['id'];
				$rel     = $pcontact['rel']     ?? Contact::NOTHING;
				$pending = $pcontact['pending'] ?? false;

				if (!empty($pcontact) && in_array($pcontact['network'], [Protocol::MAIL, Protocol::FEED])) {
					$photo = Contact::getPhoto($pcontact);
				}
			}

			if (!$hide_follow && empty($contact['self']) && Protocol::supportsFollow($contact['network'])) {
				if (in_array($rel, [Contact::SHARING, Contact::FRIEND])) {
					$unfollow_link = 'contact/unfollow?url=' . urlencode($contact_url) . '&auto=1';
				} elseif (!$pending) {
					$follow_link = 'contact/follow?binurl=' . bin2hex($contact_url) . '&auto=1';
				}
			}

			if (in_array($rel, [Contact::FOLLOWER, Contact::FRIEND]) && Contact::canReceivePrivateMessages($contact)) {
				$wallmessage_link = 'message/new/' . $id;
			}

			if ($contact['contact-type'] == Contact::TYPE_COMMUNITY) {
				if (!$hide_mention) {
					$mention_label = DI::l10n()->t('Post to group');
					$mention_link  = 'compose/0?body=!' . $contact['addr'];
				}
				$showgroup_link = 'contact/' . $id . '/conversations';
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
			'$network_svg'      => $network_svg,
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
