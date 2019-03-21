<?php

namespace Friendica\Core\Config\Cache;

/**
 * The Friendica config cache for the application
 * Initial, all *.config.php files are loaded into this cache with the
 * ConfigCacheLoader ( @see ConfigCacheLoader )
 *
 * Is used for further caching operations too (depending on the ConfigAdapter )
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
			if (isset($config[$category]) && is_array($config[$category])) {
				$keys = array_keys($config[$category]);

				foreach ($keys as $key) {
					$value = $config[$category][$key];
					if (isset($value) && $value !== '!<unset>!') {
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
		} elseif ($key == null && isset($this->config[$cat])) {
			return $this->config[$cat];
		} else {
			return '!<unset>!';
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function has($cat, $key = null)
	{
		return (isset($this->config[$cat][$key]) && $this->config[$cat][$key] !== '!<unset>!') ||
		($key == null && isset($this->config[$cat]) && $this->config[$cat] !== '!<unset>!' && is_array($this->config[$cat]));
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
		// Only arrays are serialized in database, so we have to unserialize sparingly
		$value = is_string($value) && preg_match("|^a:[0-9]+:{.*}$|s", $value) ? unserialize($value) : $value;

		if (!isset($this->config[$cat])) {
			$this->config[$cat] = [];
		}

		$this->config[$cat][$key] = $value;

		return true;
	}

	/**
	 * {@inheritdoc}
	 */
	public function hasP($uid, $cat, $key = null)
	{
		return (isset($this->config[$uid][$cat][$key]) && $this->config[$uid][$cat][$key] !== '!<unset>!') ||
			($key == null && isset($this->config[$uid][$cat]) && $this->config[$uid][$cat] !== '!<unset>!' && is_array($this->config[$uid][$cat]));
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
					if (isset($value) && $value !== '!<unset>!') {
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
		} elseif ($key == null && isset($this->config[$uid][$cat])) {
			return $this->config[$uid][$cat];
		} else {
			return '!<unset>!';
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function setP($uid, $cat, $key, $value)
	{
		// Only arrays are serialized in database, so we have to unserialize sparingly
		$value = is_string($value) && preg_match("|^a:[0-9]+:{.*}$|s", $value) ? unserialize($value) : $value;

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
}
