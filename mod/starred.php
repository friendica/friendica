<?php


function starred_init(&$a) {

	$starred = 0;

	if(! local_user())
		killme();
	if($a->argc > 1)
		$message_id = intval($a->argv[1]);
	if(! $message_id)
		killme();

	$r = q("SELECT starred FROM item WHERE uid = %d AND id = %d LIMIT 1",
		intval(local_user()),
		intval($message_id)
	);
	if(! count($r))
		killme();

	if(! intval($r[0]['starred']))
		$starred = 1;

	$r = q("UPDATE item SET starred = %d WHERE uid = %d and id = %d LIMIT 1",
		intval($starred),
		intval(local_user()),
		intval($message_id)
	);
 
	// the json doesn't really matter, it will either be 0 or 1

	echo json_encode($starred);
	killme();
}
