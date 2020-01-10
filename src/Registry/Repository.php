<?php


namespace Friendica\Registry;

use Friendica\BaseRegistry;
use Friendica\Repository as R;

/**
 * Registry for dynamic classes of the "Friendica\Repository" namespace
 */
abstract class Repository extends BaseRegistry
{
	/**
	 * @return R\Introduction
	 */
	public static function intro()
	{
		return self::$dice->create(R\Introduction::class);
	}
}
