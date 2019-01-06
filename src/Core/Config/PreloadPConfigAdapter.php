<?php

namespace Friendica\Core\Config;

use Exception;
use Friendica\BaseObject;
use Friendica\Database\DBA;

require_once 'include/dba.php';

/**
 * Preload User Configuration Adapter
 *
 * Minimizes the number of database queries to retrieve configuration values at the cost of memory.
 *
 * @author Hypolite Petovan <hypolite@mrpetovan.com>
 */
class PreloadPConfigAdapter extends BaseObject implements IPConfigAdapter
{
	private $config_loaded = false;

	public function __construct($uid)
	{
		$this->load($uid, 'config');
	}

	/**
	 * [@inheritdoc}
	 */
	public function load($uid, $family)
	{
		if ($this->config_loaded) {
			return;
		}

		if (empty($uid)) {
			return;
		}

		$pconfigs = DBA::select('pconfig', ['cat', 'v', 'k'], ['uid' => $uid]);
		while ($pconfig = DBA::fetch($pconfigs)) {
			self::getApp()->setPConfigValue($uid, $pconfig['cat'], $pconfig['k'], $pconfig['v']);
		}
		DBA::close($pconfigs);

		$this->config_loaded = true;
	}

	/**
	 * [@inheritdoc}
	 */
	public function get($uid, $cat, $k, $default_value = null, $refresh = false)
	{
		if (!$this->config_loaded) {
			$this->load($uid, $cat);
		}

		if ($refresh) {
			$config = DBA::selectFirst('pconfig', ['v'], ['uid' => $uid, 'cat' => $cat, 'k' => $k]);
			if (DBA::isResult($config)) {
				self::getApp()->setPConfigValue($uid, $cat, $k, $config['v']);
			} else {
				self::getApp()->deletePConfigValue($uid, $cat, $k);
			}
		}

		$return = self::getApp()->getPConfigValue($uid, $cat, $k, $default_value);

		return $return;
	}

	/**
	 * [@inheritdoc}
	 *
	 * @throws Exception if it isn't possible to store the config value
	 */
	public function set($uid, $cat, $k, $value)
	{
		if (!$this->config_loaded) {
			$this->load($uid, $cat);
		}
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

		$result = DBA::update('pconfig', ['v' => $dbvalue], ['uid' => $uid, 'cat' => $cat, 'k' => $k], true);
		if (!$result) {
			throw new Exception('Unable to store config value in [' . $uid . '][' . $cat . '][' . $k . ']');
		}

		return true;
	}

	/**
	 * [@inheritdoc}
	 */
	public function delete($uid, $cat, $k)
	{
		if (!$this->config_loaded) {
			$this->load($uid, $cat);
		}

		self::getApp()->deletePConfigValue($uid, $cat, $k);

		$result = DBA::delete('pconfig', ['uid' => $uid, 'cat' => $cat, 'k' => $k]);

		return $result;
	}
}
