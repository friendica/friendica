<?php

namespace Friendica\Registry;

use Friendica\BaseRegistry;
use Friendica\Util\Logger\WorkerLogger;
use Psr\Log\LoggerInterface;
use Friendica\Core as C;

/**
 * Registry for dynamic classes of the "Friendica\Core" namespace
 */
abstract class Core extends BaseRegistry
{
	/**
	 * @return C\Config\IPConfiguration
	 */
	public static function pConfig()
	{
		return self::$dice->create(C\Config\IPConfiguration::class);
	}

	/**
	 * @return C\Session\ISession
	 */
	public static function session()
	{
		return self::$dice->create(C\Session\ISession::class);
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
	 * @return C\Cache\ICache
	 */
	public static function cache()
	{
		return self::$dice->create(C\Cache\ICache::class);
	}

	/**
	 * @return C\L10n\L10n
	 */
	public static function l10n()
	{
		return self::$dice->create(C\L10n\L10n::class);
	}

	/**
	 * @return C\Lock\ILock
	 */
	public static function lock()
	{
		return self::$dice->create(C\Lock\ILock::class);
	}

	/**
	 * @return C\Config\IConfiguration
	 */
	public static function config()
	{
		return self::$dice->create(C\Config\IConfiguration::class);
	}

	/**
	 * @return C\Process
	 */
	public static function process()
	{
		return self::$dice->create(C\Process::class);
	}
}
