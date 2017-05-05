<?php

/**
 * @file mod/robots_text.php
 * @brief Module which returns the default robots.txt
 * @version 0.1.2
 */

use Friendica\App;

/**
 * @brief Return default robots.txt when init
 * @param App $a
 * @return void
 */
function robots_txt_init(App $a)
{
	$allDisalloweds = array(
		'/settings/',
		'/admin/',
		'/message/',
	);

	header('Content-Type: text/plain');
	echo 'User-agent: *' . PHP_EOL;
	foreach ($allDisalloweds as $disallowed) {
		echo 'Disallow: ' . $disallowed . PHP_EOL;
	}
	killme();
}
