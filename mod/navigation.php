<?php

require_once("include/nav.php");

function navigation_content(App &$a) {

	$nav_info = nav_info($a);

	/**
	 * Build the page
	 */

	$tpl = get_markup_template('navigation.tpl');
	return replace_macros($tpl, array(
		'$baseurl' => App::get_baseurl(),
		'$sitelocation' => $nav_info['sitelocation'],
		'$nav' => $nav_info['nav'],
		'$banner' =>  $nav_info['banner'],
		'$emptynotifications' => t('Nothing new here'),
		'$userinfo' => $nav_info['userinfo'],
		'$sel' => 	$a->nav_sel,
		'$apps' => $a->apps,
		'$clear_notifs' => t('Clear notifications')
	));

}
