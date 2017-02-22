<?php

/**
 * @file view/theme/frio/php/frio_boot.php
 *
 * @brief This file contains functions for page contstruction
 *
 */


/**
 * @brief Load page template in dependence of the template mode
 *
 * @todo Check if this is really needed.
 */
function load_page(App $a) {
	if(isset($_GET["mode"]) AND ($_GET["mode"] == "minimal")) {
		require "view/theme/frio/minimal.php";
	} elseif((isset($_GET["mode"]) AND ($_GET["mode"] == "none"))) {
		require "view/theme/frio/none.php";
	} else {
		$template = 'view/theme/' . current_theme() . '/'
			. ((x($a->page,'template')) ? $a->page['template'] : 'default' ) . '.php';
		if(file_exists($template))
			require_once($template);
		else
			require_once(str_replace('theme/' . current_theme() . '/', '', $template));
	}


}


/**
 * @brief Check if page is a modal page
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
 * @brief Array with modalpages
 *
 * The array contains the page names of the pages
 * which should displayed as modals
 *
 * @return array Pagenames as path
 */
function get_modalpage_list() {
	//Arry of pages wich getting bootstrap modal dialogs
	$modalpages = array('poke/',
			'message/new',
			'settings/oauth/add',
			'events/new',
//			'fbrowser/image/'
	);

	return $modalpages;
}

/**
 * @brief Array with standard pages
 *
 * The array contains the page names of the pages
 * which should displayed as standard-page
 *
 * @return array Pagenames as path
 */
function get_standard_page_list() {
	//Arry of pages wich getting the standard page template
	$standardpages = array(//'profile',
//			'fbrowser/image/'
	);

	return $standardpages;
}

/**
 * @brief Check if page is standard page
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
 * @brief Get the typ of the page
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
