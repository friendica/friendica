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

namespace Friendica\Module\Contact;

use Friendica\BaseModule;
use Friendica\Core\Renderer;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Network\HTTPException;
use Friendica\Util\Strings;

/**
 * Asynchronous HTML fragment provider for frio contact hovercards
 */
class Hovercard extends BaseModule
{
	protected function rawContent(array $request = [])
	{
		$contact_url = $_REQUEST['url'] ?? '';

		// Get out if the system doesn't have public access allowed
		if (DI::config()->get('system', 'block_public') && !DI::userSession()->isAuthenticated()) {
			throw new HTTPException\ForbiddenException();
		}

		/* Possible formats for relative URLs that need to be converted to the absolute contact URL:
		 * - contact/redir/123456
		 * - contact/123456/conversations
		 */
		if (strpos($contact_url, 'contact/') === 0 && preg_match('/(\d+)/', $contact_url, $matches)) {
			$remote_contact = Contact::selectFirst(['nurl'], ['id' => $matches[1]]);
			$contact_url = $remote_contact['nurl'] ?? '';
		}

		if (!$contact_url) {
			throw new HTTPException\BadRequestException();
		}

		// Search for contact data
		// Look if the local user has got the contact
		if (DI::userSession()->isAuthenticated()) {
			$contact = Contact::getByURLForUser($contact_url, DI::userSession()->getLocalUserId());
		} else {
			$contact = Contact::getByURL($contact_url, false);
		}

		if (!count($contact)) {
			throw new HTTPException\NotFoundException();
		}

		// Get the photo_menu - the menu if possible contact actions
		if (DI::userSession()->isAuthenticated()) {
			$actions = Contact::photoMenu($contact);
		} else {
			$actions = [];
		}

		// Move the contact data to the profile array so we can deliver it to
		$tpl = Renderer::getMarkupTemplate('hovercard.tpl');
		$o = Renderer::replaceMacros($tpl, [
			'$profile' => [
				'name'         => $contact['name'],
				'nick'         => $contact['nick'],
				'addr'         => $contact['addr'] ?: $contact['url'],
				'thumb'        => Contact::getThumb($contact),
				'url'          => Contact::magicLinkByContact($contact),
				'nurl'         => $contact['nurl'],
				'location'     => $contact['location'],
				'about'        => $contact['about'],
				'network_link' => Strings::formatNetworkName($contact['network'], $contact['url']),
				'tags'         => $contact['keywords'],
				'bd'           => $contact['bd'] <= DBA::NULL_DATE ? '' : $contact['bd'],
				'account_type' => Contact::getAccountType($contact['contact-type']),
				'actions'      => $actions,
			],
		]);

		echo $o;
		System::exit();
	}
}
