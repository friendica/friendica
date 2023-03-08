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
 * This file contains functions for page construction
 *
 */

use Friendica\App;
use Friendica\DI;

/**
 * Load page template in dependence of the template mode
 *
 * @todo Check if this is really needed.
 */
function load_page(App $a)
{
	if (isset($_GET['mode']) && ($_GET['mode'] == 'minimal')) {
		require 'view/theme/frio/minimal.php';
	} elseif ((isset($_GET['mode']) && ($_GET['mode'] == 'none'))) {
		require 'view/theme/frio/none.php';
	} else {
		$template = 'view/theme/' . $a->getCurrentTheme() . '/'
			. ((DI::page()['template'] ?? '') ?: 'default' ) . '.php';
		if (file_exists($template)) {
			require_once $template;
		} else {
			require_once str_replace('theme/' . $a->getCurrentTheme() . '/', '', $template);
		}
	}
}

/**
 * Check if page is a modal page
 *
 * This function checks if $_REQUEST['pagename'] is
 * a defined in a $modalpages
 *
 * @return bool
 */
function is_modal() {
	$is_modal = false;
	$modalpages = get_modalpage_list();

	foreach ($modalpages as $r => $value) {
		if(strpos($_REQUEST['pagename'],$value) !== false) {
			$is_modal = true;
		}
	}

	return $is_modal;
}

/**
 * Array with modal pages
 *
 * The array contains the page names of the pages
 * which should displayed as modals
 *
 * @return array Page names as path
 */
function get_modalpage_list() {
	//Array of pages which getting bootstrap modal dialogs
	$modalpages = [
		'message/new',
		'settings/oauth/add',
		'calendar/event/new',
//		'fbrowser/image/'
	];

	return $modalpages;
}

/**
 * Array with standard pages
 *
 * The array contains the page names of the pages
 * which should displayed as standard-page
 *
 * @return array Pagenames as path
 */
function get_standard_page_list() {
	//Arry of pages wich getting the standard page template
	$standardpages = [//'profile',
//			'fbrowser/image/'
	];

	return $standardpages;
}

/**
 * Check if page is standard page
 *
 * This function checks if $_REQUEST['pagename'] is
 * a defined $standardpages
 *
 * @param string $pagetitle Title of the actual page
 * @return bool
 */
function is_standard_page($pagetitle) {
	$is_standard_page = false;
	$standardpages = get_standard_page_list();

	foreach ($standardpages as $r => $value) {
		if(strpos($pagetitle,$value) !== false) {
			$is_standard_page = true;
		}
	}

	return $is_standard_page;
}
/**
 * Get the typ of the page
 *
 * @param type $pagetitle
 * @return string
 */
function get_page_type($pagetitle) {
	$page_type = "";

	if(is_modal())
		$page_type = "modal";

	if(is_standard_page($pagetitle))
		$page_type = "standard_page";

	if($page_type)
		return $page_type;

}
