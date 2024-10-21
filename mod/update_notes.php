<?php
/**
 * Copyright (C) 2010-2024, the Friendica project
 * SPDX-FileCopyrightText: 2010-2024 the Friendica project
 *
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * AJAX synchronisation of notes page
 */

use Friendica\App;
use Friendica\Core\System;
use Friendica\DI;

require_once 'mod/notes.php';

function update_notes_content(App $a)
{
	$profile_uid = intval($_GET['p'] ?? 0);

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
