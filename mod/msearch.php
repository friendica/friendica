<?php

function msearch_post(App $a) {

	$perpage = (($_POST['n']) ? $_POST['n'] : 80);
	$page = (($_POST['p']) ? intval($_POST['p'] - 1) : 0);
	$startrec = (($page+1) * $perpage) - $perpage;

	$search = $_POST['s'];
	if(! strlen($search))
		killme();

	$r = q("SELECT COUNT(*) AS `total` FROM `profile` LEFT JOIN `user` ON `user`.`uid` = `profile`.`uid` WHERE `is-default` = 1 AND `user`.`hidewall` = 0 AND MATCH `pub_keywords` AGAINST ('%s') ",
		dbesc($search)
	);

	if (dbm::is_result($r))
		$total = $r[0]['total'];

	$results = array();

	$r = q("SELECT `pub_keywords`, `username`, `nickname`, `user`.`uid` FROM `user` LEFT JOIN `profile` ON `user`.`uid` = `profile`.`uid` WHERE `is-default` = 1 AND `user`.`hidewall` = 0 AND MATCH `pub_keywords` AGAINST ('%s') LIMIT %d , %d ",
		dbesc($search),
		intval($startrec),
		intval($perpage)
	);

	if (dbm::is_result($r)) {
		foreach($r as $rr)
			$results[] = array(
				'name' => $rr['name'],
				'url' => App::get_baseurl() . '/profile/' . $rr['nickname'],
				'photo' => App::get_baseurl() . '/photo/avatar/' . $rr['uid'] . '.jpg',
				'tags' => str_replace(array(',','  '),array(' ',' '),$rr['pub_keywords'])
			);
	}

	$output = array('total' => $total, 'items_page' => $perpage, 'page' => $page + 1, 'results' => $results);

	echo json_encode($output);

	killme();

}
