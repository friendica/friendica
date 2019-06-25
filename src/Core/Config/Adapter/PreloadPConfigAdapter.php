<?php

namespace Friendica\Core\Config\Adapter;

use Friendica\Database\DBA;

/**
 * Preload User Configuration Adapter
 *
 * Minimizes the number of database queries to retrieve configuration values at the cost of memory.
 *
 * @author Hypolite Petovan <hypolite@mrpetovan.com>
 */
class PreloadPConfigAdapter extends AbstractDbaConfigAdapter implements IPConfigAdapter
{
	/**
	 * @var array Contains the users' config from the DB
	 */
	private $config;

	/**
	 * {@inheritdoc}
	 */
	public function load($uid, $cat = null)
	{
		$return = [];

		if (empty($uid)) {
			return $return;
		}

		if ($this->isLoaded($uid)) {
			return $this->config[$uid];
		}

		$pconfigs = DBA::select('pconfig', ['cat', 'v', 'k'], ['uid' => $uid]);
		while ($pconfig = DBA::fetch($pconfigs)) {
			$value = $this->toConfigValue($pconfig['v']);
			if (isset($value)) {
				$return[$pconfig['cat']][$pconfig['k']] = $value;
			}
		}
		DBA::close($pconfigs);

		$this->config[$uid] = $return;

		return $return;
	}

	/**
	 * {@inheritdoc}
	 */
	public function get($uid, $cat, $key)
	{
		if (!$this->isConnected()) {
			return null;
		}

		if (!$this->isLoaded($uid, $cat, $key)) {
			$this->load($uid);
		}

		return $this->config[$uid][$cat][$key] ?? null;
	}

	/**
	 * {@inheritdoc}
	 */
	public function set($uid, $cat, $key, $value)
	{
		if (!$this->isConnected()) {
			return false;
		}

		if (!$this->isLoaded($uid, $cat, $key)) {
			$this->load($uid);
		}

		// We store our setting values as strings.
		// So we have to do the conversion here so that the compare below works.
		// The exception are array values.
		$compare_value = !is_array($value) ? (string)$value : $value;
		$stored_value = $this->get($uid, $cat, $key);

		if (isset($stored_value) && $stored_value === $compare_value) {
			return true;
		}

		$this->config[$uid][$cat][$key] = $value;

		$dbvalue = $this->toDbValue($value);

		return DBA::update('pconfig', ['v' => $dbvalue], ['uid' => $uid, 'cat' => $cat, 'k' => $key], true);
	}

	/**
	 * {@inheritdoc}
	 */
	public function delete($uid, $cat, $key)
	{
		if (!$this->isConnected()) {
			return false;
		}

		unset($this->config[$uid][$cat][$key]);

		return DBA::delete('pconfig', ['uid' => $uid, 'cat' => $cat, 'k' => $key]);
	}

	/**
	 * {@inheritdoc}
	 */
	public function isLoaded($uid, $cat = null, $key = null)
	{
		if (!$this->isConnected()) {
			return false;
		}

		return !empty($this->config[$uid]);
	}
}
