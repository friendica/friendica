<?php


namespace Friendica\Registry;

use Friendica\BaseRegistry;
use Friendica\Repository as RepositoryNamespace;

/**
 * Registry for dynamic classes of the "Friendica\Repository" namespace
 */
abstract class Repository extends BaseRegistry
{
	/**
	 * @return RepositoryNamespace\Introduction
	 */
	public static function intro()
	{
		return self::$dice->create(RepositoryNamespace\Introduction::class);
	}

	/**
	 * @return RepositoryNamespace\Storage
	 */
	public static function storage()
	{
		return self::$dice->create(RepositoryNamespace\Storage::class);
	}
}
