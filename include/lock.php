<?php

// Provide some ability to lock a PHP function so that multiple processes
// can't run the function concurrently
if(! function_exists('lock_function')) {
function lock_function($fn_name, $block = true, $wait_sec = 2, $timeout = 30) {
	if( $wait_sec == 0 )
		$wait_sec = 2;	// don't let the user pick a value that's likely to crash the system

	$got_lock = false;
	$start = time();

	do {
		q("LOCK TABLE `locks` WRITE");
		$r = q("SELECT `locked`, `created` FROM `locks` WHERE `name` = '%s' LIMIT 1",
			dbesc($fn_name)
		);

		if((dbm::is_result($r)) AND (!$r[0]['locked'] OR (strtotime($r[0]['created']) < time() - 3600))) {
			q("UPDATE `locks` SET `locked` = 1, `created` = '%s' WHERE `name` = '%s'",
				dbesc(datetime_convert()),
				dbesc($fn_name)
			);
			$got_lock = true;
		}
		elseif (! dbm::is_result($r)) {
			/// @TODO the Boolean value for count($r) should be equivalent to the Boolean value of $r
			q("INSERT INTO `locks` (`name`, `created`, `locked`) VALUES ('%s', '%s', 1)",
				dbesc($fn_name),
				dbesc(datetime_convert())
			);
			$got_lock = true;
		}

		q("UNLOCK TABLES");

		if(($block) && (! $got_lock))
			sleep($wait_sec);

	} while(($block) && (! $got_lock) && ((time() - $start) < $timeout));

	logger('lock_function: function ' . $fn_name . ' with blocking = ' . $block . ' got_lock = ' . $got_lock . ' time = ' . (time() - $start), LOGGER_DEBUG);

	return $got_lock;
}}


if(! function_exists('block_on_function_lock')) {
function block_on_function_lock($fn_name, $wait_sec = 2, $timeout = 30) {
	if( $wait_sec == 0 )
		$wait_sec = 2;	// don't let the user pick a value that's likely to crash the system

	$start = time();

	do {
		$r = q("SELECT locked FROM locks WHERE name = '%s' LIMIT 1",
				dbesc($fn_name)
		     );

		if (dbm::is_result($r) && $r[0]['locked'])
			sleep($wait_sec);

	} while(dbm::is_result($r) && $r[0]['locked'] && ((time() - $start) < $timeout));

	return;
}}


if(! function_exists('unlock_function')) {
function unlock_function($fn_name) {
	$r = q("UPDATE `locks` SET `locked` = 0, `created` = '0000-00-00 00:00:00' WHERE `name` = '%s'",
			dbesc($fn_name)
	     );

	logger('unlock_function: released lock for function ' . $fn_name, LOGGER_DEBUG);

	return;
}}
