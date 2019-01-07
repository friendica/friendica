<?php

use Friendica\App;
use Friendica\Core\System;
use Friendica\Database\DBA;

function msearch_post(App $a) {

	$perpage = (($_POST['n']) ? $_POST['n'] : 80);
	$page = (($_POST['p']) ? intval($_POST['p'] - 1) : 0);
	$startrec = (($page + 1) * $perpage) - $perpage;

	$search = $_POST['s'];
	if (!strlen($search)) {
		exit();
	}

	$r = q("SELECT COUNT(*) AS `total` FROM `profile` LEFT JOIN `user` ON `user`.`uid` = `profile`.`uid` WHERE `is-default` = 1 AND `user`.`hidewall` = 0 AND MATCH `pub_keywords` AGAINST ('%s') ",
		DBA::escape($search)
	);

	if (DBA::isResult($r)) {
		$total = $r[0]['total'];
	}

	$results = [];

	$r = q("SELECT `pub_keywords`, `username`, `nickname`, `user`.`uid` FROM `user` LEFT JOIN `profile` ON `user`.`uid` = `profile`.`uid` WHERE `is-default` = 1 AND `user`.`hidewall` = 0 AND MATCH `pub_keywords` AGAINST ('%s') LIMIT %d , %d ",
		DBA::escape($search),
		intval($startrec),
		intval($perpage)
	);

	if (DBA::isResult($r)) {
		foreach($r as $rr)
			$results[] = [
				'name' => $rr['name'],
				'url' => System::baseUrl() . '/profile/' . $rr['nickname'],
				'photo' => System::baseUrl() . '/photo/avatar/' . $rr['uid'] . '.jpg',
				'tags' => str_replace([',','  '],[' ',' '],$rr['pub_keywords'])
			];
	}

	$output = ['total' => $total, 'items_page' => $perpage, 'page' => $page + 1, 'results' => $results];

	echo json_encode($output);

	exit();

}
