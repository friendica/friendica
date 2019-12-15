<?php
/**
 * @file src/BaseObject.php
 */
namespace Friendica;

require_once __DIR__ . '/../boot.php';

use Dice\Dice;
use Friendica\Network\HTTPException\InternalServerErrorException;

/**
 * Basic object
 *
 * Contains what is useful to any object
 *
 * Act's like a global registry for classes
 */
class BaseObject
{
	/**
	 * @var Dice The Dependency Injection library
	 */
	private static $dice;

	/**
	 * Set's the dependency injection library for a global usage
	 *
	 * @param Dice $dice The dependency injection library
	 */
	public static function setDependencyInjection(Dice $dice)
	{
		self::$dice = $dice;
		DI::init($dice);
	}

	/**
	 * Returns the initialized class based on it's name
	 *
	 * @param string $name The name of the class
	 *
	 * @return object The initialized name
	 *
	 * @throws InternalServerErrorException
	 */
	public static function getClass(string $name)
	{
		if (empty(self::$dice)) {
			throw new InternalServerErrorException('DICE isn\'t initialized.');
		}

		if (class_exists($name) || interface_exists($name)) {
			return self::$dice->create($name);
		} else {
			throw new InternalServerErrorException('Class \'' . $name . '\' isn\'t valid.');
		}
	}
}
