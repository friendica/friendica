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
use Friendica\Core\Session;
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
		if (DI::config()->get('system', 'block_public') && !Session::isAuthenticated()) {
			throw new HTTPException\ForbiddenException();
		}

		// If a contact is connected the url is internally changed to 'redir/CID'. We need the pure url to search for
		// the contact. So we strip out the contact id from the internal url and look in the contact table for
		// the real url (nurl)
		if (strpos($contact_url, 'redir/') === 0) {
			$cid = intval(substr($contact_url, 6));
		}

		if (strpos($contact_url, 'contact/') === 0) {
			$cid = intval(substr($contact_url, 8));
		}

		if (!empty($cid)) {			
			$remote_contact = Contact::selectFirst(['nurl'], ['id' => $cid]);
			$contact_url = $remote_contact['nurl'] ?? '';
		}

		$contact = [];

		// if it's the url containing https it should be converted to http
		if (!$contact_url) {
			throw new HTTPException\BadRequestException();
		}

		// Search for contact data
		// Look if the local user has got the contact
		if (Session::isAuthenticated()) {
			$contact = Contact::getByURLForUser($contact_url, local_user());
		} else {
			$contact = Contact::getByURL($contact_url, false);
		}

		if (!count($contact)) {
			throw new HTTPException\NotFoundException();
		}

		// Get the photo_menu - the menu if possible contact actions
		if (Session::isAuthenticated()) {
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
				'account_type' => Contact::getAccountType($contact),
				'actions'      => $actions,
			],
		]);

		echo $o;
		exit();
	}
}
