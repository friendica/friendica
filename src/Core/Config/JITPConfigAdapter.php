<?php
namespace Friendica\Core\Config;

use Friendica\BaseObject;
use Friendica\Database\DBA;

require_once 'include/dba.php';

/**
 * JustInTime User Configuration Adapter
 *
 * Default PConfig Adapter. Provides the best performance for pages loading few configuration variables.
 *
 * @author Hypolite Petovan <hypolite@mrpetovan.com>
 */
class JITPConfigAdapter extends BaseObject implements IPConfigAdapter
{
	private $in_db;

	public function load($uid, $cat)
	{
		$a = self::getApp();

		$pconfigs = DBA::select('pconfig', ['v', 'k'], ['cat' => $cat, 'uid' => $uid]);
		if (DBA::isResult($pconfigs)) {
			while ($pconfig = DBA::fetch($pconfigs)) {
				$k = $pconfig['k'];

				self::getApp()->setPConfigValue($uid, $cat, $k, $pconfig['v']);

				$this->in_db[$uid][$cat][$k] = true;
			}
		} else if ($cat != 'config') {
			// Negative caching
			$a->config[$uid][$cat] = "!<unset>!";
		}
		DBA::close($pconfigs);
	}

	public function get($uid, $cat, $k, $default_value = null, $refresh = false)
	{
		$a = self::getApp();

		if (!$refresh) {
			// Looking if the whole family isn't set
			if (isset($a->config[$uid][$cat])) {
				if ($a->config[$uid][$cat] === '!<unset>!') {
					return $default_value;
				}
			}

			if (isset($a->config[$uid][$cat][$k])) {
				if ($a->config[$uid][$cat][$k] === '!<unset>!') {
					return $default_value;
				}
				return $a->config[$uid][$cat][$k];
			}
		}

		$pconfig = DBA::selectFirst('pconfig', ['v'], ['uid' => $uid, 'cat' => $cat, 'k' => $k]);
		if (DBA::isResult($pconfig)) {
			$val = (preg_match("|^a:[0-9]+:{.*}$|s", $pconfig['v']) ? unserialize($pconfig['v']) : $pconfig['v']);

			self::getApp()->setPConfigValue($uid, $cat, $k, $val);

			$this->in_db[$uid][$cat][$k] = true;

			return $val;
		} else {
			self::getApp()->setPConfigValue($uid, $cat, $k, '!<unset>!');

			$this->in_db[$uid][$cat][$k] = false;

			return $default_value;
		}
	}

	public function set($uid, $cat, $k, $value)
	{
		// We store our setting values in a string variable.
		// So we have to do the conversion here so that the compare below works.
		// The exception are array values.
		$dbvalue = (!is_array($value) ? (string)$value : $value);

		$stored = $this->get($uid, $cat, $k, null, true);

		if (($stored === $dbvalue) && $this->in_db[$uid][$cat][$k]) {
			return true;
		}

		self::getApp()->setPConfigValue($uid, $cat, $k, $value);

		// manage array value
		$dbvalue = (is_array($value) ? serialize($value) : $dbvalue);

		$result = DBA::update('pconfig', ['v' => $dbvalue], ['uid' => $uid, 'cat' => $cat, 'k' => $k], true);

		if ($result) {
			$this->in_db[$uid][$cat][$k] = true;
		}

		return $result;
	}

	public function delete($uid, $cat, $k)
	{
		self::getApp()->deletePConfigValue($uid, $cat, $k);

		if (!empty($this->in_db[$uid][$cat][$k])) {
			unset($this->in_db[$uid][$cat][$k]);
		}

		$result = DBA::delete('pconfig', ['uid' => $uid, 'cat' => $cat, 'k' => $k]);

		return $result;
	}
}
