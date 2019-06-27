<?php

namespace Friendica\Core\Config\Adapter;

use Friendica\Database\Database;

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
	 * @var array true if config for user is loaded
	 */
	private $config_loaded;

	/**
	 * {@inheritDoc}
	 *
	 * @param int $uid The UID of the current user
	 */
	public function __construct(Database $dba, $uid = null)
	{
		parent::__construct($dba);

		$this->config_loaded = [];

		if (isset($uid)) {
			$this->load($uid, 'config');
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function load(string $uid, string $cat)
	{
		$return = [];

		if (empty($uid)) {
			return $return;
		}

		if (!$this->isLoaded($uid, $cat, null)) {
			return $return;
		}

		$pconfigs = $this->dba->select('pconfig', ['cat', 'v', 'k'], ['uid' => $uid]);
		while ($pconfig = $this->dba->fetch($pconfigs)) {
			$value = $this->toConfigValue($pconfig['v']);
			if (isset($value)) {
				$return[$pconfig['cat']][$pconfig['k']] = $value;
			}
		}
		$this->dba->close($pconfigs);

		$this->config_loaded[$uid] = true;

		return $return;
	}

	/**
	 * {@inheritdoc}
	 */
	public function get(string $uid, string $cat, string $key)
	{
		if (!$this->isConnected()) {
			return null;
		}

		if (!$this->isLoaded($uid, $cat, $key)) {
			$this->load($uid, $cat);
		}

		$config = $this->dba->selectFirst('pconfig', ['v'], ['uid' => $uid, 'cat' => $cat, 'k' => $key]);
		if ($this->dba->isResult($config)) {
			$value = $this->toConfigValue($config['v']);

			if (isset($value)) {
				return $value;
			}
		}
		return null;
	}

	/**
	 * {@inheritdoc}
	 */
	public function set(string $uid, string $cat, string $key, $value)
	{
		if (!$this->isConnected()) {
			return false;
		}

		if (!$this->isLoaded($uid, $cat, $key)) {
			$this->load($uid, $cat);
		}
		// We store our setting values as strings.
		// So we have to do the conversion here so that the compare below works.
		// The exception are array values.
		$compare_value = !is_array($value) ? (string)$value : $value;
		$stored_value = $this->get($uid, $cat, $key);

		if (isset($stored_value) && $stored_value === $compare_value) {
			return true;
		}

		$dbvalue = $this->toDbValue($value);

		return $this->dba->update('pconfig', ['v' => $dbvalue], ['uid' => $uid, 'cat' => $cat, 'k' => $key], true);
	}

	/**
	 * {@inheritdoc}
	 */
	public function delete(string $uid, string $cat, string $key)
	{
		if (!$this->isConnected()) {
			return false;
		}

		if (!$this->isLoaded($uid, $cat, $key)) {
			$this->load($uid, $cat);
		}

		return $this->dba->delete('pconfig', ['uid' => $uid, 'cat' => $cat, 'k' => $key]);
	}

	/**
	 * {@inheritdoc}
	 */
	public function isLoaded(string $uid, string $cat, string $key)
	{
		if (!$this->isConnected()) {
			return false;
		}

		return isset($this->config_loaded[$uid]) && $this->config_loaded[$uid];
	}
}
