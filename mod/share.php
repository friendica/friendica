<?php

use Friendica\App;
use Friendica\Database\DBA;
use Friendica\Model\Item;

function share_init(App $a) {
	$post_id = (($a->argc > 1) ? intval($a->argv[1]) : 0);

	if (!$post_id || !local_user()) {
		killme();
	}

	$fields = ['private', 'body', 'author-name', 'author-link', 'author-avatar',
		'guid', 'created', 'plink', 'title'];
	$item = Item::selectFirst($fields, ['id' => $post_id]);

	if (!DBA::isResult($item) || $item['private'] == 1) {
		killme();
	}

	if (strpos($item['body'], "[/share]") !== false) {
		$pos = strpos($item['body'], "[share");
		$o = substr($item['body'], $pos);
	} else {
		$o = share_header($item['author-name'], $item['author-link'], $item['author-avatar'], $item['guid'], $item['created'], $item['plink']);

		if ($item['title']) {
			$o .= '[b]'.$item['title'].'[/b]'."\n";
		}

		$o .= $item['body'];
		$o .= "[/share]";
	}

	echo $o;
	killme();
}

/// @TODO Rewrite to handle over whole record array
function share_header($author, $profile, $avatar, $guid, $posted, $link) {
	$header = "[share author='" . str_replace(["'", "[", "]"], ["&#x27;", "&#x5B;", "&#x5D;"], $author).
		"' profile='" . str_replace(["'", "[", "]"], ["&#x27;", "&#x5B;", "&#x5D;"], $profile).
		"' avatar='" . str_replace(["'", "[", "]"], ["&#x27;", "&#x5B;", "&#x5D;"], $avatar);

	if ($guid) {
		$header .= "' guid='" . str_replace(["'", "[", "]"], ["&#x27;", "&#x5B;", "&#x5D;"], $guid);
	}

	if ($posted) {
		$header .= "' posted='" . str_replace(["'", "[", "]"], ["&#x27;", "&#x5B;", "&#x5D;"], $posted);
	}

	$header .= "' link='" . str_replace(["'", "[", "]"], ["&#x27;", "&#x5B;", "&#x5D;"], $link)."']";

	return $header;
}
