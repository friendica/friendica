<?php

use Friendica\App;
use Friendica\Content\Text;
use Friendica\Core\System;

function filerm_content(App $a) {

	if (! local_user()) {
		killme();
	}

	$term = Text::unxmlify(trim($_GET['term']));
	$cat = Text::unxmlify(trim($_GET['cat']));

	$category = (($cat) ? true : false);
	if ($category) {
		$term = $cat;
	}

	$item_id = (($a->argc > 1) ? intval($a->argv[1]) : 0);

	logger('filerm: tag ' . $term . ' item ' . $item_id);

	if ($item_id && strlen($term)) {
		file_tag_unsave_file(local_user(),$item_id,$term, $category);
	}

	//$a->internalRedirect('network');

	killme();
}
