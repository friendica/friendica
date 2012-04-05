<?php

function uexport_init(&$a) {

	if(! local_user())
		killme();

	$user = array();
	$r = q("SELECT * FROM `user` WHERE `uid` = %d LIMIT 1",
		local_user()
	);
	if(count($r)) {
		foreach($r as $rr)
			foreach($rr as $k => $v)
				$user[$k] = $v;

	}
	$contact = array();
	$r = q("SELECT * FROM `contact` WHERE `uid` = %d ",
		intval(local_user())
	);
	if(count($r)) {
		foreach($r as $rr)
			foreach($rr as $k => $v)
				$contact[][$k] = $v;

	}

	$profile = array();
	$r = q("SELECT * FROM `profile` WHERE `uid` = %d ",
		intval(local_user())
	);
	if(count($r)) {
		foreach($r as $rr)
			foreach($rr as $k => $v)
				$profile[][$k] = $v;
	}

	$output = array('user' => $user, 'contact' => $contact, 'profile' => $profile );

	header("Content-type: application/json");
	echo json_encode($output);

	$r = q("SELECT count(*) as `total` FROM `item` WHERE `uid` = %d ",
		intval(local_user())
	);
	if(count($r))
		$total = $r[0]['total'];

	// chunk the output to avoid exhausting memory

	for($x = 0; $x < $total; $x += 500) {
		$item = array();
		$r = q("SELECT * FROM `item` WHERE `uid` = %d LIMIT %d, %d",
			intval(local_user()),
			intval($x),
			intval(500)
		);
		if(count($r)) {
			foreach($r as $rr)
				foreach($rr as $k => $v)
					$item[][$k] = $v;
		}

		$output = array('item' => $item);
		echo json_encode($output);
	}


	killme();

}