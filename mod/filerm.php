<?php

function filerm_content(App &$a) {

	if (! local_user()) {
		killme();
	}

	$term = unxmlify(trim($_GET['term']));
	$cat = unxmlify(trim($_GET['cat']));

	$category = (($cat) ? true : false);
	if($category)
		$term = $cat;

	$item_id = (($a->argc > 1) ? intval($a->argv[1]) : 0);

	logger('filerm: tag ' . $term . ' item ' . $item_id);

	if($item_id && strlen($term))
		file_tag_unsave_file(local_user(),$item_id,$term, $category);

	if(x($_SESSION,'return_url'))
		goaway(App::get_baseurl() . '/' . $_SESSION['return_url']);

	killme();
}
