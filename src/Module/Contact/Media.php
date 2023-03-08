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

namespace Friendica\Module\Contact;

use Friendica\BaseModule;
use Friendica\Content\Widget;
use Friendica\DI;
use Friendica\Model;
use Friendica\Model\Contact as ModelContact;
use Friendica\Module\Contact;
use Friendica\Network\HTTPException\BadRequestException;

/**
 * GUI for media posts of a contact
 */
class Media extends BaseModule
{
	protected function content(array $request = []): string
	{
		$cid = $this->parameters['id'];

		$contact = Model\Contact::selectFirst([], ['id' => $cid]);
		if (empty($contact)) {
			throw new BadRequestException(DI::l10n()->t('Contact not found.'));
		}

		DI::page()['aside'] = Widget\VCard::getHTML($contact);

		$o = Contact::getTabsHTML($contact, Contact::TAB_MEDIA);

		$o .= ModelContact::getPostsFromUrl($contact['url'], false, 0, 0, true);

		return $o;
	}
}
