<?php

namespace Friendica\Core\Session;

use Friendica\BaseObject;
use Friendica\Core\Session;
use Friendica\Database\DBA;
use Friendica\Database\DBM;
use SessionHandlerInterface;

require_once 'boot.php';
require_once 'include/dba.php';
require_once 'include/text.php';

/**
 * SessionHandler using database
 *
 * @author Hypolite Petovan <mrpetovan@gmail.com>
 */
class DatabaseSessionHandler extends BaseObject implements SessionHandlerInterface
{
	public function open($save_path, $session_name)
	{
		return true;
	}

	public function read($session_id)
	{
		if (!x($session_id)) {
			return '';
		}

		$session = DBA::selectFirst('session', ['data'], ['sid' => $session_id]);
		if (DBM::is_result($session)) {
			Session::$exists = true;
			return $session['data'];
		}
		logger("no data for session $session_id", LOGGER_TRACE);

		return '';
	}

	/**
	 * @brief Standard PHP session write callback
	 *
	 * This callback updates the DB-stored session data and/or the expiration depending
	 * on the case. Uses the Session::expire global for existing session, 5 minutes
	 * for newly created session.
	 *
	 * @param  string $session_id   Session ID with format: [a-z0-9]{26}
	 * @param  string $session_data Serialized session data
	 * @return boolean Returns false if parameters are missing, true otherwise
	 */
	public function write($session_id, $session_data)
	{
		if (!$session_id) {
			return false;
		}

		if (!$session_data) {
			return true;
		}

		$expire = time() + Session::$expire;
		$default_expire = time() + 300;

		if (Session::$exists) {
			$fields = ['data' => $session_data, 'expire' => $expire];
			$condition = ["`sid` = ? AND (`data` != ? OR `expire` != ?)", $session_id, $session_data, $expire];
			DBA::update('session', $fields, $condition);
		} else {
			$fields = ['sid' => $session_id, 'expire' => $default_expire, 'data' => $session_data];
			DBA::insert('session', $fields);
		}

		return true;
	}

	public function close()
	{
		return true;
	}

	public function destroy($id)
	{
		DBA::delete('session', ['sid' => $id]);
		return true;
	}

	public function gc($maxlifetime)
	{
		DBA::delete('session', ["`expire` < ?", time()]);
		return true;
	}
}
