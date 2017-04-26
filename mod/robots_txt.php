<?php
/**
 * @file mod/robots_text.php
 * @brief Module which returns the default robots.txt
 * @version 0.1.2
 */


/**
 * Return at init
 * @param App $a
 * @return void
 */
function robots_txt_init(App $a) {

	/** @var string[] globally disallowed url */
	$allDisalloweds = array(
		"/settings/",
		"/admin/",
		"/message/",
	);

	header("Content-Type: text/plain");
	echo "User-agent: *\n";
	foreach($allDisalloweds as $disallowed) {
		echo "Disallow: {$disallowed}\n";
	}
	killme();
}
