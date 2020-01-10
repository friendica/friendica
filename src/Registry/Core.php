<?php

namespace Friendica\Registry;

use Friendica\BaseRegistry;
use Friendica\Util\Logger\WorkerLogger;
use Psr\Log\LoggerInterface;
use Friendica\Core as CoreNamespace;

/**
 * Registry for dynamic classes of the "Friendica\Core" namespace
 */
abstract class Core extends BaseRegistry
{
	/**
	 * @return CoreNamespace\Config\IPConfiguration
	 */
	public static function pConfig()
	{
		return self::$dice->create(CoreNamespace\Config\IPConfiguration::class);
	}

	/**
	 * @return CoreNamespace\Session\ISession
	 */
	public static function session()
	{
		return self::$dice->create(CoreNamespace\Session\ISession::class);
	}

	/**
	 * @return LoggerInterface
	 */
	public static function devLogger()
	{
		return self::$dice->create('$devLogger');
	}

	/**
	 * @return LoggerInterface
	 */
	public static function workerLogger()
	{
		return self::$dice->create(WorkerLogger::class);
	}

	/**
	 * @return LoggerInterface
	 */
	public static function logger()
	{
		return self::$dice->create(LoggerInterface::class);
	}

	/**
	 * @return CoreNamespace\Cache\ICache
	 */
	public static function cache()
	{
		return self::$dice->create(CoreNamespace\Cache\ICache::class);
	}

	/**
	 * @return CoreNamespace\L10n\L10n
	 */
	public static function l10n()
	{
		return self::$dice->create(CoreNamespace\L10n\L10n::class);
	}

	/**
	 * @return CoreNamespace\Lock\ILock
	 */
	public static function lock()
	{
		return self::$dice->create(CoreNamespace\Lock\ILock::class);
	}

	/**
	 * @return CoreNamespace\Config\IConfiguration
	 */
	public static function config()
	{
		return self::$dice->create(CoreNamespace\Config\IConfiguration::class);
	}

	/**
	 * @return CoreNamespace\Process
	 */
	public static function process()
	{
		return self::$dice->create(CoreNamespace\Process::class);
	}
}
