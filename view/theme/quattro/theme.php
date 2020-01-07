<?php
/**
 * Name: Quattro
 * Version: 0.6
 * Author: Fabio <http://kirgroup.com/profile/fabrixxm>
 * Maintainer: Fabio <http://kirgroup.com/profile/fabrixxm>
 * Maintainer: Tobias <https://diekershoff.homeunix.net/friendica/profile/tobias>
 */

use Friendica\App;
use Friendica\Registry\App as A;

function quattro_init(App $a) {
	A::page()['htmlhead'] .= '<script src="' . A::baseUrl() . '/view/theme/quattro/tinycon.min.js"></script>';
	A::page()['htmlhead'] .= '<script src="' . A::baseUrl() . '/view/theme/quattro/js/quattro.js"></script>';;
}
