<?php

namespace Friendica\Core\Config\Adapter;

use Friendica\Network\HTTPException\InternalServerErrorException;

/**
 * Preload Configuration Adapter
 *
 * Minimizes the number of database queries to retrieve configuration values at the cost of memory.
 *
 * @author Hypolite Petovan <hypolite@mrpetovan.com>
 */
class PreloadConfigAdapter extends AbstractDbaConfigAdapter implements IConfigAdapter
{
	private $config_loaded = false;

	/**
	 * {@inheritdoc}
	 */
	public function load(string $cat = 'config')
	{
		$return = [];

		if (!$this->isConnected()) {
			return $return;
		}

		if ($this->config_loaded) {
			return $return;
		}

		$configs = $this->dba->select('config', ['cat', 'v', 'k']);
		while ($config = $this->dba->fetch($configs)) {
			$value = $this->toConfigValue($config['v']);
			if (isset($value)) {
				$return[$config['cat']][$config['k']] = $value;
			}
		}
		$this->dba->close($configs);

		$this->config_loaded = true;

		return $return;
	}

	/**
	 * {@inheritdoc}
	 */
	public function get(string $cat, string $key)
	{
		throw new InternalServerErrorException('Preload Config shouldn\'t use get');
	}

	/**
	 * {@inheritdoc}
	 */
	public function set(string $cat, string $key, $value)
	{
		if (!$this->isConnected()) {
			return false;
		}

		// We store our setting values as strings.
		// So we have to do the conversion here so that the compare below works.
		// The exception are array values.
		$compare_value = !is_array($value) ? (string)$value : $value;
		$stored_value = $this->get($cat, $key);

		if (isset($stored_value) && $stored_value === $compare_value) {
			return true;
		}

		$dbvalue = $this->toDbValue($value);

		return $this->dba->update('config', ['v' => $dbvalue], ['cat' => $cat, 'k' => $key], true);
	}

	/**
	 * {@inheritdoc}
	 */
	public function delete(string $cat, string $key)
	{
		if (!$this->isConnected()) {
			return false;
		}

		return $this->dba->delete('config', ['cat' => $cat, 'k' => $key]);
	}

	/**
	 * {@inheritdoc}
	 */
	public function isLoaded(string $cat, string $key)
	{
		if (!$this->isConnected()) {
			return false;
		}

		return $this->config_loaded;
	}
}
