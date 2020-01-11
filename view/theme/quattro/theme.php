<?php
/**
 * Name: Quattro
 * Version: 0.6
 * Author: Fabio <http://kirgroup.com/profile/fabrixxm>
 * Maintainer: Fabio <http://kirgroup.com/profile/fabrixxm>
 * Maintainer: Tobias <https://diekershoff.homeunix.net/friendica/profile/tobias>
 */

use Friendica\App;
use Friendica\Registry\App as AppR;

function quattro_init(App $a) {
	AppR::page()['htmlhead'] .= '<script src="' . AppR::baseUrl() . '/view/theme/quattro/tinycon.min.js"></script>';
	AppR::page()['htmlhead'] .= '<script src="' . AppR::baseUrl() . '/view/theme/quattro/js/quattro.js"></script>';;
}
