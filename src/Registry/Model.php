<?php

namespace Friendica\Registry;

use Friendica\BaseRegistry;
use Friendica\Model as ModelNamespace;

/**
 * Registry for dynamic classes of the "Friendica\Model" namespace
 */
abstract class Model extends BaseRegistry
{
	/**
	 * @return ModelNamespace\Notify
	 */
	public static function notify()
	{
		return self::$dice->create(ModelNamespace\Notify::class);
	}

	/**
	 * @return ModelNamespace\User\Cookie
	 */
	public static function cookie()
	{
		return self::$dice->create(ModelNamespace\User\Cookie::class);
	}

	/**
	 * @return ModelNamespace\Storage\IStorage
	 */
	public static function storage()
	{
		return self::$dice->create(ModelNamespace\Storage\IStorage::class);
	}
}
