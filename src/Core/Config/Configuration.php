<?php

namespace Friendica\Core\Config;

use Friendica\Model;

/**
 * This class is responsible for all system-wide configuration values in Friendica
 * There are two types of storage
 * - The Config-Files    (loaded into the FileCache @see Cache\ConfigCache)
 * - The Config-DB-Table (per Config-DB-model @see Model\Config\Config)
 */
abstract class Configuration implements IConfiguration
{
	/**
	 * @var Cache\ConfigCache
	 */
	protected $configCache;

	/**
	 * @var Model\Config\Config
	 */
	protected $configModel;

	/**
	 * @param Cache\ConfigCache  $configCache The configuration cache (based on the config-files)
	 * @param Model\Config\Config $configModel The configuration model
	 */
	public function __construct(Cache\ConfigCache $configCache, Model\Config\Config $configModel)
	{
		$this->configCache = $configCache;
		$this->configModel = $configModel;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getCache()
	{
		return $this->configCache;
	}
}
