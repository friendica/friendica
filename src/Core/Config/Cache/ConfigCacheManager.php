<?php

namespace Friendica\Core\Config\Cache;

abstract class ConfigCacheManager
{
	/**
	 * The Sub directory of the config-files
	 * @var string
	 */
	const SUBDIRECTORY = 'config';

	protected $baseDir;
	protected $configDir;

	public function __construct($baseDir)
	{
		$this->baseDir = $baseDir;
		$this->configDir = $baseDir . DIRECTORY_SEPARATOR . self::SUBDIRECTORY;
	}

	protected function getConfigFullName($name)
	{
		$fullName = $this->configDir . DIRECTORY_SEPARATOR . $name . '.config.php';

		return file_exists($fullName) ? $fullName : '';
	}

	protected function getIniFullName($name)
	{
		$fullName = $this->configDir . DIRECTORY_SEPARATOR . $name . '.ini.php';

		return file_exists($fullName) ? $fullName : '';
	}

	protected function getHtConfigFullName($name)
	{
		$fullName = $this->baseDir  . DIRECTORY_SEPARATOR . '.' . $name . '.php';

		return file_exists($fullName) ? $fullName : '';
	}
}
