<?php

namespace Friendica\Core\Config\Cache;

/**
 * The Friendica config cache for the application
 * Initial, all *.config.php files are loaded into this cache with the
 * ConfigFileLoader ( @see ConfigFileLoader )
 */
class ConfigCache implements IConfigCache, IPConfigCache
{
	/**
	 * @var array
	 */
	private $config;

	/**
	 * @param array $config    A initial config array
	 */
	public function __construct(array $config = [])
	{
		$this->load($config);
	}

	/**
	 * {@inheritdoc}
	 */
	public function load(array $config, $overwrite = false)
	{
		$categories = array_keys($config);

		foreach ($categories as $category) {
			if (is_array($config[$category])) {
				$keys = array_keys($config[$category]);

				foreach ($keys as $key) {
					$value = $config[$category][$key];
					if (isset($value)) {
						if ($overwrite) {
							$this->set($category, $key, $value);
						} else {
							$this->setDefault($category, $key, $value);
						}
					}
				}
			}
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function get($cat, $key = null)
	{
		if (isset($this->config[$cat][$key])) {
			return $this->config[$cat][$key];
		} elseif (!isset($key) && isset($this->config[$cat])) {
			return $this->config[$cat];
		} else {
			return null;
		}
	}

	/**
	 * Sets a default value in the config cache. Ignores already existing keys.
	 *
	 * @param string $cat Config category
	 * @param string $k   Config key
	 * @param mixed  $v   Default value to set
	 */
	private function setDefault($cat, $k, $v)
	{
		if (!isset($this->config[$cat][$k])) {
			$this->set($cat, $k, $v);
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function set($cat, $key, $value)
	{
		if (!isset($this->config[$cat])) {
			$this->config[$cat] = [];
		}

		$this->config[$cat][$key] = $value;

		return true;
	}

	/**
	 * {@inheritdoc}
	 */
	public function delete($cat, $key)
	{
		if (isset($this->config[$cat][$key])) {
			unset($this->config[$cat][$key]);
			if (count($this->config[$cat]) == 0) {
				unset($this->config[$cat]);
			}
			return true;
		} else {
			return false;
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function loadP($uid, array $config)
	{
		$categories = array_keys($config);

		foreach ($categories as $category) {
			if (isset($config[$category]) && is_array($config[$category])) {

				$keys = array_keys($config[$category]);

				foreach ($keys as $key) {
					$value = $config[$category][$key];
					if (isset($value)) {
						$this->setP($uid, $category, $key, $value);
					}
				}
			}
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function getP($uid, $cat, $key = null)
	{
		if (isset($this->config[$uid][$cat][$key])) {
			return $this->config[$uid][$cat][$key];
		} elseif (!isset($key) && isset($this->config[$uid][$cat])) {
			return $this->config[$uid][$cat];
		} else {
			return null;
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function setP($uid, $cat, $key, $value)
	{
		if (!isset($this->config[$uid]) || !is_array($this->config[$uid])) {
			$this->config[$uid] = [];
		}

		if (!isset($this->config[$uid][$cat])) {
			$this->config[$uid][$cat] = [];
		}

		$this->config[$uid][$cat][$key] = $value;

		return true;
	}

	/**
	 * {@inheritdoc}
	 */
	public function deleteP($uid, $cat, $key)
	{
		if (isset($this->config[$uid][$cat][$key])) {
			unset($this->config[$uid][$cat][$key]);
			if (count($this->config[$uid][$cat]) == 0) {
				unset($this->config[$uid][$cat]);
				if (count($this->config[$uid]) == 0) {
					unset($this->config[$uid]);
				}
			}

			return true;
		} else {
			return false;
		}
	}

	/**
	 * Returns the whole configuration
	 *
	 * @return array The configuration
	 */
	public function getAll()
	{
		return $this->config;
	}

	/**
	 * Returns an array with missing categories/Keys
	 *
	 * @param array $config The array to check
	 *
	 * @return array
	 */
	public function keyDiff(array $config)
	{
		$return = [];

		$categories = array_keys($config);

		foreach ($categories as $category) {
			if (is_array($config[$category])) {
				$keys = array_keys($config[$category]);

				foreach ($keys as $key) {
					if (!isset($this->config[$category][$key])) {
						$return[$category][$key] = $config[$category][$key];
					}
				}
			}
		}

		return $return;
	}

	/**
	 * {@inheritDoc}
	 */
	public function combine(array $config, $overwrite = false)
	{
		$newConfigCache = new ConfigCache($this->config);
		$newConfigCache->load($config, $overwrite);
		return $newConfigCache;
	}
}
