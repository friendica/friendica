<?php
// Session management functions. These provide database storage of PHP
// session info.

require_once('include/cache.php');

$session_exists = 0;
$session_expire = 180000;

function ref_session_open($s, $n) {
	return true;
}

function ref_session_read($id) {
	global $session_exists;

	if (!x($id)) {
		return '';
	}

	$memcache = cache::memcache();
	if (is_object($memcache)) {
		$data = $memcache->get(get_app()->get_hostname().":session:".$id);
		if (!is_bool($data)) {
			return $data;
		}
		logger("no data for session $id", LOGGER_TRACE);
		return '';
	}

	$r = q("SELECT `data` FROM `session` WHERE `sid`= '%s'", dbesc($id));

	if (dbm::is_result($r)) {
		$session_exists = true;
		return $r[0]['data'];
	} else {
		logger("no data for session $id", LOGGER_TRACE);
	}

	return '';
}

/**
 * @brief Standard PHP session write callback
 *
 * This callback updates the DB-stored session data and/or the expiration depending
 * on the case. Uses the $session_expire global for existing session, 5 minutes
 * for newly created session.
 *
 * @global bool $session_exists Whether a session with the given id already exists
 * @global int $session_expire Session expiration delay in seconds
 * @param string $id Session ID with format: [a-z0-9]{26}
 * @param string $data Serialized session data
 * @return boolean Returns false if parameters are missing, true otherwise
 */
function ref_session_write($id, $data) {
	global $session_exists, $session_expire;

	if (!$id || !$data) {
		return false;
	}

	$expire = time() + $session_expire;
	$default_expire = time() + 300;

	$memcache = cache::memcache();
	if (is_object($memcache)) {
		$memcache->set(get_app()->get_hostname().":session:".$id, $data, MEMCACHE_COMPRESSED, $expire);
		return true;
	}

	if ($session_exists) {
		$r = q("UPDATE `session`
				SET `data` = '%s', `expire` = '%s'
				WHERE `sid` = '%s'
				AND (`data` != '%s' OR `expire` != '%s')",
				dbesc($data), dbesc($expire), dbesc($id), dbesc($data), dbesc($expire));
	} else {
		$r = q("INSERT INTO `session`
				SET `sid` = '%s', `expire` = '%s', `data` = '%s'",
				dbesc($id), dbesc($default_expire), dbesc($data));
	}

	return true;
}

function ref_session_close() {
	return true;
}

function ref_session_destroy($id) {
	$memcache = cache::memcache();

	if (is_object($memcache)) {
		$memcache->delete(get_app()->get_hostname().":session:".$id);
		return true;
	}

	q("DELETE FROM `session` WHERE `sid` = '%s'", dbesc($id));

	return true;
}

function ref_session_gc($expire) {
	q("DELETE FROM `session` WHERE `expire` < %d", dbesc(time()));

	return true;
}

$gc_probability = 50;

ini_set('session.gc_probability', $gc_probability);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_httponly', 1);

if (!get_config('system', 'disable_database_session')) {
	session_set_save_handler('ref_session_open', 'ref_session_close',
				'ref_session_read', 'ref_session_write',
				'ref_session_destroy', 'ref_session_gc');
}
