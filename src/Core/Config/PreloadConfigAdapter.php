<?php

namespace Friendica\Core\Config;

use dba;
use Exception;
use Friendica\App;
use Friendica\BaseObject;
use Friendica\Database\DBM;

require_once 'include/dba.php';

/**
 * Preload Configuration Adapter
 *
 * Minimizes the number of database queries to retrieve configuration values at the cost of memory.
 *
 * @author Hypolite Petovan <mrpetovan@gmail.com>
 */
class PreloadConfigAdapter extends BaseObject implements IConfigAdapter
{
	private $config_loaded = false;

	public function __construct()
	{
		$this->load();
	}

	public function load($family = 'config')
	{
		if ($this->config_loaded) {
			return;
		}

		$configs = dba::select('config', ['cat', 'v', 'k']);
		while ($config = dba::fetch($configs)) {
			self::getApp()->setConfigValue($config['cat'], $config['k'], $config['v']);
		}
		dba::close($configs);

		$this->config_loaded = true;
	}

	public function get($cat, $k, $default_value = null, $refresh = false)
	{
		if ($refresh) {
			$config = dba::selectFirst('config', ['v'], ['cat' => $cat, 'k' => $k]);
			if (DBM::is_result($config)) {
				self::getApp()->setConfigValue($cat, $k, $config['v']);
			}
		}

		$return = self::getApp()->getConfigValue($cat, $k, $default_value);

		return $return;
	}

	public function set($cat, $k, $value)
	{
		// We store our setting values as strings.
		// So we have to do the conversion here so that the compare below works.
		// The exception are array values.
		$compare_value = !is_array($value) ? (string)$value : $value;

		if (self::getApp()->getConfigValue($cat, $k) === $compare_value) {
			return true;
		}

		self::getApp()->setConfigValue($cat, $k, $value);

		// manage array value
		$dbvalue = is_array($value) ? serialize($value) : $value;

		$result = dba::update('config', ['v' => $dbvalue], ['cat' => $cat, 'k' => $k], true);
		if (!$result) {
			throw new Exception('Unable to store config value in [' . $cat . '][' . $k . ']');
		}

		return true;
	}

	public function delete($cat, $k)
	{
		self::getApp()->deleteConfigValue($cat, $k);

		$result = dba::delete('config', ['cat' => $cat, 'k' => $k]);

		return $result;
	}
}
