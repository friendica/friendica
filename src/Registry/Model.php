<?php

namespace Friendica\Registry;

use Friendica\BaseRegistry;
use Friendica\Model as M;

/**
 * Registry for dynamic classes of the "Friendica\Model" namespace
 */
abstract class Model extends BaseRegistry
{
	/**
	 * @return M\Notify
	 */
	public static function notify()
	{
		return self::$dice->create(M\Notify::class);
	}

	/**
	 * @return M\User\Cookie
	 */
	public static function cookie()
	{
		return self::$dice->create(M\User\Cookie::class);
	}

	/**
	 * @return M\Storage\IStorage
	 */
	public static function storage()
	{
		return self::$dice->create(M\Storage\IStorage::class);
	}
}
