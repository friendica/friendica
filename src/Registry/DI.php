<?php

namespace Friendica\Registry;

use Dice\Dice;
use Friendica\App as A;
use Friendica\BaseRegistry;
use Friendica\Database\Database;
use Friendica\Registry;

/**
 * The global Dependency injection registry.
 *
 * This is the basic initializing for each other registry and it holds the two most common used dependencies:
 * - App
 * - DBA
 */
abstract class DI extends BaseRegistry
{
	public static function init(Dice $dice)
	{
		parent::init($dice);
	}

	/**
	 * @return Database
	 */
	public static function dba()
	{
		return self::$dice->create(Database::class);
	}

	/**
	 * @return A
	 */
	public static function app()
	{
		return BaseRegistry::$dice->create(A::class);
	}
}
