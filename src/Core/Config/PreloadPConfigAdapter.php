<?php

namespace Friendica\Core\Config;

use dba;
use Exception;
use Friendica\App;
use Friendica\BaseObject;
use Friendica\Database\DBM;

require_once 'include/dba.php';

/**
 * Preload User Configuration Adapter
 *
 * Minimizes the number of database queries to retrieve configuration values at the cost of memory.
 *
 * @author Hypolite Petovan <mrpetovan@gmail.com>
 */
class PreloadPConfigAdapter extends BaseObject implements IPConfigAdapter
{
	private $config_loaded = false;

	public function __construct($uid)
	{
		$this->load($uid, 'config');
	}

	public function load($uid, $family)
	{
		if ($this->config_loaded) {
			return;
		}

		$pconfigs = dba::select('pconfig', ['cat', 'v', 'k'], ['uid' => $uid]);
		while ($pconfig = dba::fetch($pconfigs)) {
			self::getApp()->setPConfigValue($uid, $pconfig['cat'], $pconfig['k'], $pconfig['v']);
		}
		dba::close($pconfigs);

		$this->config_loaded = true;
	}

	public function get($uid, $cat, $k, $default_value = null, $refresh = false)
	{
		if ($refresh) {
			$config = dba::selectFirst('pconfig', ['v'], ['uid' => $uid, 'cat' => $cat, 'k' => $k]);
			if (DBM::is_result($config)) {
				self::getApp()->setPConfigValue($uid, $cat, $k, $config['v']);
			} else {
				self::getApp()->deletePConfigValue($uid, $cat, $k);
			}
		}

		$return = self::getApp()->getPConfigValue($uid, $cat, $k, $default_value);

		return $return;
	}

	public function set($uid, $cat, $k, $value)
	{
		// We store our setting values as strings.
		// So we have to do the conversion here so that the compare below works.
		// The exception are array values.
		$compare_value = !is_array($value) ? (string)$value : $value;

		if (self::getApp()->getPConfigValue($uid, $cat, $k) === $compare_value) {
			return true;
		}

		self::getApp()->setPConfigValue($uid, $cat, $k, $value);

		// manage array value
		$dbvalue = is_array($value) ? serialize($value) : $value;

		$result = dba::update('pconfig', ['v' => $dbvalue], ['uid' => $uid, 'cat' => $cat, 'k' => $k], true);
		if (!$result) {
			throw new Exception('Unable to store config value in [' . $uid . '][' . $cat . '][' . $k . ']');
		}

		return true;
	}

	public function delete($uid, $cat, $k)
	{
		self::getApp()->deletePConfigValue($uid, $cat, $k);

		$result = dba::delete('pconfig', ['uid' => $uid, 'cat' => $cat, 'k' => $k]);

		return $result;
	}
}
