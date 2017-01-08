<?php


function randprof_init(App &$a) {
	require_once('include/Contact.php');

	$x = random_profile();

	if ($x) {
		goaway(zrl($x));
	}

	goaway(App::get_baseurl() . '/profile');
}
