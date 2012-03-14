<?php

require_once('include/security.php');
require_once('include/bbcode.php');
require_once('include/items.php');


function filer_content(&$a) {

	if(! local_user()) {
		killme();
	}

	$term = notags(trim($_GET['term']));
	$item_id = (($a->argc > 1) ? notags(trim($a->argv[1])) : 0);

	logger('filer: tag ' . $term . ' item ' . $item_id);

	if($item_id && strlen($term))
		file_tag_save_file(local_user(),$item_id,$term);

	killme();
}
