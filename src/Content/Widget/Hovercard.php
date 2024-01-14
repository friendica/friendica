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
				'network_link' => Strings::formatNetworkName($contact['network'], $contact_url),
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
