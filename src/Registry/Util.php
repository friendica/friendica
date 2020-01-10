<?php

namespace Friendica\Registry;

use Friendica\BaseRegistry;
use Friendica\Util as UtilNamespace;

/**
 * Registry for dynamic classes of the "Friendica\Util" namespace
 */
abstract class Util extends BaseRegistry
{
	/**
	 * @return UtilNamespace\FileSystem
	 */
	public static function fs()
	{
		return self::$dice->create(UtilNamespace\FileSystem::class);
	}

	/**
	 * @return UtilNamespace\DateTimeFormat
	 */
	public static function dtFormat()
	{
		return self::$dice->create(UtilNamespace\DateTimeFormat::class);
	}

	/**
	 * @return UtilNamespace\ACLFormatter
	 */
	public static function aclFormatter()
	{
		return self::$dice->create(UtilNamespace\ACLFormatter::class);
	}

	/**
	 * @return UtilNamespace\Profiler
	 */
	public static function profiler()
	{
		return self::$dice->create(UtilNamespace\Profiler::class);
	}
}
