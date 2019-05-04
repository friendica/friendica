<?php
namespace Friendica\Core\Config\Adapter;

/**
 * JustInTime Configuration Adapter
 *
 * Default Config Adapter. Provides the best performance for pages loading few configuration variables.
 *
 * @author Hypolite Petovan <hypolite@mrpetovan.com>
 */
class JITConfigAdapter extends AbstractDbaConfigAdapter implements IConfigAdapter
{
	private $in_db;

	/**
	 * {@inheritdoc}
	 */
	public function load($cat = "config")
	{
		$return = [];

		if (!$this->isConnected()) {
			return $return;
		}

		// We don't preload "system" anymore.
		// This reduces the number of database reads a lot.
		if ($cat === 'system') {
			return $return;
		}

		$database = $this->database;

		$configs = $database->select('config', ['v', 'k'], ['cat' => $cat]);
		while ($config = $database->fetch($configs)) {
			$key   = $config['k'];
			$value = $this->toConfigValue($config['v']);

			// The value was in the db, so don't check it again (unless you have to)
			$this->in_db[$cat][$key] = true;

			// just save it in case it is set
			if (isset($value)) {
				$return[$key] = $value;
			}
		}
		$database->close($configs);

		return [$cat => $return];
	}

	/**
	 * {@inheritdoc}
	 *
	 * @param bool $mark if true, mark the selection of the current cat/key pair
	 */
	public function get($cat, $key, $mark = true)
	{
		if (!$this->isConnected()) {
			return null;
		}

		// The value got checked, so mark it to avoid checking it over and over again
		if ($mark) {
			$this->in_db[$cat][$key] = true;
		}

		$database = $this->database;

		$config = $database->selectFirst('config', ['v'], ['cat' => $cat, 'k' => $key]);
		if ($database->isResult($config)) {
			$value = $this->toConfigValue($config['v']);

			// just return it in case it is set
			if (isset($value)) {
				return $value;
			}
		}

		return null;
	}

	/**
	 * {@inheritdoc}
	 */
	public function set($cat, $key, $value)
	{
		if (!$this->isConnected()) {
			return false;
		}

		// We store our setting values in a string variable.
		// So we have to do the conversion here so that the compare below works.
		// The exception are array values.
		$compare_value = (!is_array($value) ? (string)$value : $value);
		$stored_value = $this->get($cat, $key, false);

		if (!isset($this->in_db[$cat])) {
			$this->in_db[$cat] = [];
		}
		if (!isset($this->in_db[$cat][$key])) {
			$this->in_db[$cat][$key] = false;
		}

		if (isset($stored_value) && ($stored_value === $compare_value) && $this->in_db[$cat][$key]) {
			return true;
		}

		$dbvalue = $this->toDbValue($value);

		$result = $this->database->update('config', ['v' => $dbvalue], ['cat' => $cat, 'k' => $key], true);

		$this->in_db[$cat][$key] = $result;

		return $result;
	}

	/**
	 * {@inheritdoc}
	 */
	public function delete($cat, $key)
	{
		if (!$this->isConnected()) {
			return false;
		}

		if (isset($this->cache[$cat][$key])) {
			unset($this->in_db[$cat][$key]);
		}

		$result = $this->database->delete('config', ['cat' => $cat, 'k' => $key]);

		return $result;
	}

	/**
	 * {@inheritdoc}
	 */
	public function isLoaded($cat, $key)
	{
		if (!$this->isConnected()) {
			return false;
		}

		return (isset($this->in_db[$cat][$key])) && $this->in_db[$cat][$key];
	}
}
