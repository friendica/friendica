<?php

require_once('include/security.php');
require_once('include/bbcode.php');
require_once('include/items.php');
require_once('include/like.php');

function like_content(&$a) {
	if(! local_user() && ! remote_user()) {
		return false;
	}


	$verb = notags(trim($_GET['verb']));

	if(! $verb)
		$verb = 'like';

	$item_id = (($a->argc > 1) ? notags(trim($a->argv[1])) : 0);

	$r = do_like($item_id, $verb);
	if (!$r) return;

	// See if we've been passed a return path to redirect to
	$return_path = ((x($_REQUEST,'return')) ? $_REQUEST['return'] : '');

	like_content_return(App::get_baseurl(), $return_path);
	killme(); // NOTREACHED
//	return; // NOTREACHED
}


// Decide how to return. If we were called with a 'return' argument,
// then redirect back to the calling page. If not, just quietly end

function like_content_return($baseurl, $return_path) {

	if($return_path) {
		$rand = '_=' . time();
		if(strpos($return_path, '?')) $rand = "&$rand";
		else $rand = "?$rand";

		goaway($baseurl . "/" . $return_path . $rand);
	}

	killme();
}

