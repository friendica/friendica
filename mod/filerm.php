<?php

use Friendica\App;
use Friendica\Core\Logger;
use Friendica\Model\FileTag;
use Friendica\Util\XML;

function filerm_content(App $a)
{
	if (! local_user())
	{
		exit();
	}

	$term = XML::unescape(trim($_GET['term']));
	$cat = XML::unescape(trim(defaults($_GET, 'cat', '')));

	$category = (($cat) ? true : false);

	if ($category)
	{
		$term = $cat;
	}

	$item_id = (($a->argc > 1) ? intval($a->argv[1]) : 0);

	Logger::log('filerm: tag ' . $term . ' item ' . $item_id  . ' category ' . ($category ? 'true' :  'false'));

	if ($item_id && strlen($term)) {
		if (FileTag::unsaveFile(local_user(), $item_id, $term, $category)) {
			info('Item removed');
		}
	}
	else {
		info('Item was not deleted');
	}

	$a->internalRedirect('/network?f=&file=' . rawurlencode($term));
	exit();
}
