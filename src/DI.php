<?php

namespace Friendica;

use Dice\Dice;

/**
 * This class is capable of getting all dynamic created classes
 *
 * There has to be a "method" phpDoc for each new class, containing result class for a proper matching
 *
 * @method static App app()
 */
class DI
{
	/** @var Dice */
	private static $dice;

	public static function init(Dice $dice)
	{
		self::$dice = $dice;
	}

	public static function __callStatic($name, $arguments)
	{
		switch ($name) {
			case 'app':
				return self::$dice->create(App::class, $arguments);
			default:
				return null;
		}
	}
}
