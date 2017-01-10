<?php

function qsearch_init(App &$a) {

	if (! local_user()) {
		killme();
	}

	$limit = (get_config('system','qsearch_limit') ? intval(get_config('system','qsearch_limit')) : 100);

	$search = ((x($_GET,'s')) ? notags(trim(urldecode($_GET['s']))) : '');

	if(! strlen($search))
		killme();


	if($search)
		$search = dbesc($search);

	$results = array();

	$r = q("SELECT * FROM `group` WHERE `name` REGEXP '$search' AND `deleted` = 0 AND `uid` = %d LIMIT 0, %d ",
		intval(local_user()),
		intval($limit)
	);

	if (dbm::is_result($r)) {

		foreach($r as $rr)
			$results[] = array( 0, (int) $rr['id'], $rr['name'], '', '');
	}

	$sql_extra = ((strlen($search)) ? " AND (`name` REGEXP '$search' OR `nick` REGEXP '$search') " : "");


	$r = q("SELECT * FROM `contact` WHERE `uid` = %d $sql_extra ORDER BY `name` ASC LIMIT 0, %d ",
		intval(local_user()),
		intval($limit)
	);


	if (dbm::is_result($r)) {

		foreach($r as $rr)
			$results[] = array( (int) $rr['id'], 0, $rr['name'],$rr['url'],$rr['photo']);
	}

	echo json_encode((object) $results);
	killme();
}

