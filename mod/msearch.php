<?php

use Friendica\App;
use Friendica\Database\DBA;
use Friendica\Registry\App as A;

function msearch_post(App $a)
{
	$search = $_POST['s'] ?? '';
	$perpage  = intval(($_POST['n'] ?? 0) ?: 80);
	$page     = intval(($_POST['p'] ?? 0) ?: 1);
	$startrec = ($page - 1) * $perpage;

	$total = 0;
	$results = [];

	if (!strlen($search)) {
		$output = ['total' => 0, 'items_page' => $perpage, 'page' => $page, 'results' => $results];
		echo json_encode($output);
		exit();
	}

	$total = 0;

	$count_stmt = DBA::p(
		"SELECT COUNT(*) AS `total`
			FROM `profile`
		  	JOIN `user` ON `user`.`uid` = `profile`.`uid`
			WHERE `is-default` = 1
			AND `user`.`hidewall` = 0
		  	AND MATCH(`pub_keywords`) AGAINST (?)",
		$search
	);
	if (DBA::isResult($count_stmt)) {
		$row = DBA::fetch($count_stmt);
		$total = $row['total'];
	}

	DBA::close($count_stmt);

	$search_stmt = DBA::p(
		"SELECT `pub_keywords`, `username`, `nickname`, `user`.`uid`
			FROM `user`
			JOIN `profile` ON `user`.`uid` = `profile`.`uid`
			WHERE `is-default` = 1
			AND `user`.`hidewall` = 0
			AND MATCH(`pub_keywords`) AGAINST (?)
			LIMIT ?, ?",
		$search,
		$startrec,
		$perpage
	);

	while($search_result = DBA::fetch($search_stmt)) {
		$results[] = [
			'name'  => $search_result['name'],
			'url'   => A::baseUrl() . '/profile/' . $search_result['nickname'],
			'photo' => A::baseUrl() . '/photo/avatar/' . $search_result['uid'] . '.jpg',
			'tags'  => str_replace([',', '  '], [' ', ' '], $search_result['pub_keywords'])
		];
	}

	$output = ['total' => $total, 'items_page' => $perpage, 'page' => $page, 'results' => $results];

	echo json_encode($output);

	exit();
}
