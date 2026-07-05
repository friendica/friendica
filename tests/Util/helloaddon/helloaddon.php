<?php

// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

/**
 * Name: Hello Addon
 * Description: For testing purpose only
 * Version: 1.0
 * Author: Artur Weigandt <dont-mail-me@example.com>
 */

use Friendica\Core\Hook;

function helloaddon_install()
{
	Hook::register('page_end', 'addon/helloaddon/helloaddon.php', 'helloaddon_page_end');
}

function helloaddon_uninstall()
{
	Hook::unregister('page_end', 'addon/helloaddon/helloaddon.php', 'helloaddon_page_end');
}

function helloaddon_page_end(&$html)
{
	$html .= '<p>Hello, World!</p>';
}
