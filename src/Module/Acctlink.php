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

namespace Friendica\Module;

use Friendica\BaseModule;
use Friendica\Core\System;
use Friendica\Model\Contact;
use Friendica\Network\HTTPException\NotFoundException;

/**
 * Redirects to another URL based on the parameter 'addr'
 */
class Acctlink extends BaseModule
{
	protected function rawContent(array $request = [])
	{
		$addr = trim($_GET['addr'] ?? '');
		if (!$addr) {
			throw new NotFoundException('Parameter "addr" is missing or empty');
		}

		$contact = Contact::getByURL($addr, null, ['url']) ?? '';
		if (!$contact) {
			throw new NotFoundException('Contact not found');
		}

		System::externalRedirect($contact['url']);
	}
}
