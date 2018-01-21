<?php
/**
 * Session management functions. These provide database storage of PHP session info.
 */
use Friendica\Core\Cache;
use Friendica\Core\Config;
use Friendica\Database\DBM;

$session_exists = 0;
$session_expire = 180000;

function ref_session_open()
{
	return true;
}

function ref_session_read($id)
{
	global $session_exists;

	if (!x($id)) {
		return '';
	}

	$memcache = Cache::memcache();
	if (is_object($memcache)) {
		$data = $memcache->get(get_app()->get_hostname().":session:".$id);
		if (!is_bool($data)) {
			$session_exists = true;
			return $data;
		}
		logger("no data for session $id", LOGGER_TRACE);
		return '';
	}

	$session = dba::selectFirst('session', ['data'], ['sid' => $id]);
	if (DBM::isResult($session)) {
		$session_exists = true;
		return $session['data'];
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
 * @global bool   $session_exists Whether a session with the given id already exists
 * @global int    $session_expire Session expiration delay in seconds
 * @param  string $id   Session ID with format: [a-z0-9]{26}
 * @param  string $data Serialized session data
 * @return boolean Returns false if parameters are missing, true otherwise
 */
function ref_session_write($id, $data)
{
	global $session_exists, $session_expire;

	if (!$id) {
		return false;
	}

	if (!$data) {
		return true;
	}

	$expire = time() + $session_expire;
	$default_expire = time() + 300;

	$memcache = Cache::memcache();
	$a = get_app();
	if (is_object($memcache) && is_object($a)) {
		$memcache->set($a->get_hostname().":session:".$id, $data, MEMCACHE_COMPRESSED, $expire);
		return true;
	}

	if ($session_exists) {
		$fields = ['data' => $data, 'expire' => $expire];
		$condition = ["`sid` = ? AND (`data` != ? OR `expire` != ?)", $id, $data, $expire];
		dba::update('session', $fields, $condition);
	} else {
		$fields = ['sid' => $id, 'expire' => $default_expire, 'data' => $data];
		dba::insert('session', $fields);
	}

	return true;
}

function ref_session_close()
{
	return true;
}

function ref_session_destroy($id)
{
	$memcache = Cache::memcache();

	if (is_object($memcache)) {
		$memcache->delete(get_app()->get_hostname().":session:".$id);
		return true;
	}

	dba::delete('session', ['sid' => $id]);
	return true;
}

function ref_session_gc()
{
	dba::delete('session', ["`expire` < ?", time()]);
	return true;
}

$gc_probability = 50;

ini_set('session.gc_probability', $gc_probability);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_httponly', 1);

if (Config::get('system', 'ssl_policy') == SSL_POLICY_FULL) {
	ini_set('session.cookie_secure', 1);
}

if (!Config::get('system', 'disable_database_session')) {
	session_set_save_handler(
		'ref_session_open', 'ref_session_close',
		'ref_session_read', 'ref_session_write',
		'ref_session_destroy', 'ref_session_gc'
	);
}
