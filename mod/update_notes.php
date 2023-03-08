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
 * AJAX synchronisation of notes page
 */

use Friendica\App;
use Friendica\Core\System;
use Friendica\DI;

require_once 'mod/notes.php';

function update_notes_content(App $a)
{
	$profile_uid = intval($_GET['p']);

	/**
	 *
	 * Grab the page inner contents by calling the content function from the profile module directly,
	 * but move any image src attributes to another attribute name. This is because
	 * some browsers will prefetch all the images for the page even if we don't need them.
	 * The only ones we need to fetch are those for new page additions, which we'll discover
	 * on the client side and then swap the image back.
	 *
	 */

	$text = notes_content($a, $profile_uid);

	System::htmlUpdateExit($text);
}
