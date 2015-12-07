<?php
/**
 * Name: Quattro
 * Version: 0.6
 * Author: Fabio <http://kirgroup.com/profile/fabrixxm>
 * Maintainer: Fabio <http://kirgroup.com/profile/fabrixxm>
 * Maintainer: Tobias <https://diekershoff.homeunix.net/friendica/profile/tobias>
 */

function quattro_init(&$a) {
	$a->theme_info = array();

	$a->page['htmlhead'] .= '<script src="'.$a->get_baseurl().'/view/theme/quattro/tinycon.min.js"></script>';
	$a->page['htmlhead'] .= '<script src="'.$a->get_baseurl().'/view/theme/quattro/js/quattro.js"></script>';;
}
