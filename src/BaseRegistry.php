<?php

namespace Friendica;

use Dice\Dice;

/**
 * This class is the base class for every registry service in Friendica
 *
 * It implements a central storage for objects often used throughout the application
 * in form of an abstract class with only static methods
 *
 * @see https://designpatternsphp.readthedocs.io/en/latest/Structural/Registry/README.html
 */
abstract class BaseRegistry
{
	/** @var Dice */
	protected static $dice;

	private function __construct()
	{
	}

	public static function init(Dice $dice)
	{
		static::$dice = $dice;
	}
}
