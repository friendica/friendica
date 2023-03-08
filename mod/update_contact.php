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
 * See update_profile.php for documentation
 *
 */

use Friendica\App;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Post;
use Friendica\Model\Contact;

function update_contact_content(App $a)
{
	if (!empty(DI::args()->get(1)) && !empty($_GET['force'])) {
		$contact = Contact::getById(DI::args()->get(1), ['id', 'deleted']);
		if (DBA::isResult($contact) && empty($contact['deleted'])) {
			DI::page()['aside'] = '';

			if (!empty($_GET['item'])) {
				$item = Post::selectFirst(['parent'], ['id' => $_GET['item']]);
			}

			$text = Contact::getPostsFromId($contact['id'], true, true, $item['parent'] ?? 0);
		}
	}

	System::htmlUpdateExit($text ?? '');
}
