<?php
/**
 * @file mod/randprof.php
 */
use Friendica\App;
use Friendica\Core\System;
use Friendica\Model\GContact;
use Friendica\Model\Profile;

function randprof_init(App $a)
{
	$x = GContact::getRandomUrl();

	if ($x) {
		goaway(Profile::zrl($x));
	}

	goaway(System::baseUrl() . '/profile');
}
