<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Content\Widget;

use Friendica\Core\Renderer;
use Friendica\Database\DBA;
use Friendica\Model\Contact;
use Friendica\Network\HTTPException;
use Friendica\Util\Strings;

class Hovercard
{
	/**
	 * @param array $contact
	 * @param int   $localUid Used to show user actions
	 * @return string
	 * @throws HTTPException\InternalServerErrorException
	 * @throws HTTPException\ServiceUnavailableException
	 * @throws \ImagickException
	 */
	public static function getHTML(array $contact, int $localUid = 0): string
	{
		if ($localUid) {
			$actions = Contact::photoMenu($contact, $localUid);
		} else {
			$actions = [];
		}

		$contact_url = Contact::getProfileLink($contact);

		// Move the contact data to the profile array so we can deliver it to
		$tpl = Renderer::getMarkupTemplate('hovercard.tpl');
		return Renderer::replaceMacros($tpl, [
			'$profile' => [
				'name'         => $contact['name'],
				'nick'         => $contact['nick'],
				'addr'         => $contact['addr'] ?: $contact_url,
				'thumb'        => Contact::getThumb($contact),
				'url'          => Contact::magicLinkByContact($contact),
				'nurl'         => $contact['nurl'],
				'location'     => $contact['location'],
				'about'        => $contact['about'],
				'network_link' => Strings::formatNetworkName($contact['network'], $contact_url, $contact['gsid']),
				'tags'         => $contact['keywords'],
				'bd'           => $contact['bd'] <= DBA::NULL_DATE ? '' : $contact['bd'],
				'account_type' => Contact::getAccountType($contact['contact-type']),
				'contact_type' => $contact['contact-type'],
				'actions'      => $actions,
				'self'         => $contact['self'],
			],
		]);
	}
}
