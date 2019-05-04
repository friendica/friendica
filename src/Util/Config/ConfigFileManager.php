<?php

namespace Friendica\Util\Config;

use Friendica\Util\BasePath;

/**
 * An abstract class in case of handling with config files
 */
abstract class ConfigFileManager
{
	/**
	 * The Sub directory of the config-files
	 * @var string
	 */
	const SUBDIRECTORY = 'config';

	/**
	 * The default name of the user defined config file
	 * @var string
	 */
	const CONFIG_LOCAL    = 'local';

	/**
	 * The default name of the user defined ini file
	 * @var string
	 */
	const CONFIG_INI      = 'local';

	/**
	 * The default name of the user defined legacy config file
	 * @var string
	 */
	const CONFIG_HTCONFIG = 'htconfig';

	protected $baseDir;
	protected $configDir;

	/**
	 * @param BasePath $basePath The base directory of Friendica
	 */
	public function __construct(BasePath $basePath)
	{
		$this->baseDir = $basePath->getPath();
		$this->configDir = $this->baseDir . DIRECTORY_SEPARATOR . self::SUBDIRECTORY;
	}

	/**
	 * Gets the full name (including the path) for a *.config.php (default is local.config.php)
	 *
	 * @param string $name The config name (default is empty, which means local.config.php)
	 *
	 * @return string The full name or empty if not found
	 */
	protected function getConfigFullName($name = '')
	{
		$name = !empty($name) ? $name : self::CONFIG_LOCAL;

		$fullName = $this->configDir . DIRECTORY_SEPARATOR . $name . '.config.php';
		return file_exists($fullName) ? $fullName : '';
	}

	/**
	 * Gets the full name (including the path) for a *.ini.php (default is local.ini.php)
	 *
	 * @param string $name The config name (default is empty, which means local.ini.php)
	 *
	 * @return string The full name or empty if not found
	 */
	protected function getIniFullName($name = '')
	{
		$name = !empty($name) ? $name : self::CONFIG_INI;

		$fullName = $this->configDir . DIRECTORY_SEPARATOR . $name . '.ini.php';
		return file_exists($fullName) ? $fullName : '';
	}

	/**
	 * Gets the full name (including the path) for a .*.php (default is .htconfig.php)
	 *
	 * @param string $name The config name (default is empty, which means .htconfig.php)
	 *
	 * @return string The full name or empty if not found
	 */
	protected function getHtConfigFullName($name = '')
	{
		$name = !empty($name) ? $name : self::CONFIG_HTCONFIG;

		$fullName = $this->baseDir  . DIRECTORY_SEPARATOR . '.' . $name . '.php';
		return file_exists($fullName) ? $fullName : '';
	}
}
