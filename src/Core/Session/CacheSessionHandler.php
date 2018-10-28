<?php

namespace Friendica\Core\Session;

use Friendica\BaseObject;
use Friendica\Content\Text;
use Friendica\Core\Cache;
use Friendica\Core\Session;
use SessionHandlerInterface;

require_once 'boot.php';
require_once 'include/text.php';

/**
 * SessionHandler using Friendica Cache
 *
 * @author Hypolite Petovan <hypolite@mrpetovan.com>
 */
class CacheSessionHandler extends BaseObject implements SessionHandlerInterface
{
	public function open($save_path, $session_name)
	{
		return true;
	}

	public function read($session_id)
	{
		if (empty($session_id)) {
			return '';
		}

		$data = Cache::get('session:' . $session_id);
		if (!empty($data)) {
			Session::$exists = true;
			return $data;
		}
		Text::logger("no data for session $session_id", LOGGER_TRACE);
		return '';
	}

	/**
	 * @brief Standard PHP session write callback
	 *
	 * This callback updates the stored session data and/or the expiration depending
	 * on the case. Uses the Session::expire for existing session, 5 minutes
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

		$return = Cache::set('session:' . $session_id, $session_data, Session::$expire);

		return $return;
	}

	public function close()
	{
		return true;
	}

	public function destroy($id)
	{
		$return = Cache::delete('session:' . $id);

		return $return;
	}

	public function gc($maxlifetime)
	{
		return true;
	}
}
