<?php

namespace Friendica\Registry;

use Dice\Dice;
use Friendica\App;
use Friendica\BaseRegistry;
use Friendica\Database\Database;

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
	 * @return App
	 */
	public static function app()
	{
		return BaseRegistry::$dice->create(App::class);
	}
}
