<?php

namespace Friendica\Core\Config;

use Exception;
use Friendica\BaseObject;
use Friendica\Database\DBA;

require_once 'include/dba.php';

/**
 * Preload Configuration Adapter
 *
 * Minimizes the number of database queries to retrieve configuration values at the cost of memory.
 *
 * @author Hypolite Petovan <hypolite@mrpetovan.com>
 */
class PreloadConfigAdapter extends BaseObject implements IConfigAdapter
{
	private $config_loaded = false;

	public function __construct()
	{
		$this->load();
	}

	/**
	 * [@inheritdoc}
	 */
	public function load($family = 'config')
	{
		if ($this->config_loaded) {
			return;
		}

		$configs = DBA::select('config', ['cat', 'v', 'k']);
		while ($config = DBA::fetch($configs)) {
			self::getApp()->setConfigValue($config['cat'], $config['k'], $config['v']);
		}
		DBA::close($configs);

		$this->config_loaded = true;
	}

	/**
	 * [@inheritdoc}
	 */
	public function get($cat, $k, $default_value = null, $refresh = false)
	{
		if ($refresh) {
			$config = DBA::selectFirst('config', ['v'], ['cat' => $cat, 'k' => $k]);
			if (DBA::isResult($config)) {
				self::getApp()->setConfigValue($cat, $k, $config['v']);
			}
		}

		$return = self::getApp()->getConfigValue($cat, $k, $default_value);

		return $return;
	}

	/**
	 * [@inheritdoc}
	 */
	public function getAll($cat)
	{
		$config = self::getApp()->getConfigValue($cat);
		if (is_array($config)) {
			return $config;
		} else {
			return [];
		}
	}

	/**
	 * [@inheritdoc}
	 *
	 * @throws Exception if it isn't possible to store the config value
	 */
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

		$result = DBA::update('config', ['v' => $dbvalue], ['cat' => $cat, 'k' => $k], true);
		if (!$result) {
			throw new Exception('Unable to store config value in [' . $cat . '][' . $k . ']');
		}

		return true;
	}

	/**
	 * [@inheritdoc}
	 */
	public function delete($cat, $k)
	{
		self::getApp()->deleteConfigValue($cat, $k);

		$result = DBA::delete('config', ['cat' => $cat, 'k' => $k]);

		return $result;
	}
}
