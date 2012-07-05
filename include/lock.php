<?php

// Provide some ability to lock a PHP function so that multiple processes
// can't run the function concurrently
if(! function_exists('lock_function')) {
function lock_function($fn_name, $block = true, $wait_sec = 2) {
	if( $wait_sec == 0 )
		$wait_sec = 2;	// don't let the user pick a value that's likely to crash the system

	$got_lock = false;

	do {
		q("LOCK TABLE locks WRITE");
		$r = q("SELECT locked FROM locks WHERE name = '%s' LIMIT 1",
			dbesc($fn_name)
		);

		if((count($r)) && (! $r[0]['locked'])) {
			q("UPDATE locks SET locked = 1 WHERE name = '%s' LIMIT 1",
				dbesc($fn_name)
			);
			$got_lock = true;
		}
		elseif(! $r) { // the Boolean value for count($r) should be equivalent to the Boolean value of $r
			q("INSERT INTO locks ( name, locked ) VALUES ( '%s', 1 )",
				dbesc($fn_name)
			);
			$got_lock = true;
		}

		q("UNLOCK TABLES");

		if(($block) && (! $got_lock))
			sleep($wait_sec);

	} while(($block) && (! $got_lock));

	logger('lock_function: function ' . $fn_name . ' with blocking = ' . $block . ' got_lock = ' . $got_lock, LOGGER_DEBUG);
	
	return $got_lock;
}}


if(! function_exists('block_on_function_lock')) {
function block_on_function_lock($fn_name, $wait_sec = 2) {
	if( $wait_sec == 0 )
		$wait_sec = 2;	// don't let the user pick a value that's likely to crash the system

	do {
		$r = q("SELECT locked FROM locks WHERE name = '%s' LIMIT 1",
				dbesc($fn_name)
		     );

		if(count($r) && $r[0]['locked'])
			sleep($wait_sec);

	} while(count($r) && $r[0]['locked']);

	return;
}}


if(! function_exists('unlock_function')) {
function unlock_function($fn_name) {
	//$r = q("LOCK TABLE lock WRITE");
	$r = q("UPDATE locks SET locked = 0 WHERE name = '%s' LIMIT 1",
			dbesc($fn_name)
	     );
	//$r = q("UNLOCK TABLES");

	logger('unlock_function: released lock for function ' . $fn_name, LOGGER_DEBUG);

	return;
}}

?>
