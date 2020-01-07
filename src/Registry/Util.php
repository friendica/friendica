<?php

namespace Friendica\Registry;

use Friendica\BaseRegistry;
use Friendica\Util as U;

/**
 * Registry for dynamic classes of the "Friendica\Util" namespace
 */
abstract class Util extends BaseRegistry
{
	/**
	 * @return U\FileSystem
	 */
	public static function fs()
	{
		return self::$dice->create(U\FileSystem::class);
	}

	/**
	 * @return U\DateTimeFormat
	 */
	public static function dtFormat()
	{
		return self::$dice->create(U\DateTimeFormat::class);
	}

	/**
	 * @return U\ACLFormatter
	 */
	public static function aclFormatter()
	{
		return self::$dice->create(U\ACLFormatter::class);
	}

	/**
	 * @return U\Profiler
	 */
	public static function profiler()
	{
		return self::$dice->create(U\Profiler::class);
	}
}
