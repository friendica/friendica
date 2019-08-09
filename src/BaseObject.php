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
	}

	/**
	 * Get the app
	 *
	 * Same as get_app from boot.php
	 *
	 * @return App
	 */
	public static function getApp()
	{
		return self::getClass(App::class);
	}

	/**
	 * Returns the initialized class based on it's name
	 *
	 * @param string $name   The name of the class
	 * @param array  $params Parameters, which are used to load the class with
	 *
	 * @return object The initialized name
	 *
	 * @throws InternalServerErrorException
	 */
	protected static function getClass(string $name, array $params = [])
	{
		if (empty(self::$dice)) {
			throw new InternalServerErrorException('DICE isn\'t initialized.');
		}

		if (class_exists($name) || interface_exists($name)) {
			return self::$dice->create($name, $params);
		} else {
			throw new InternalServerErrorException('Class \'' . $name . '\' isn\'t valid.');
		}
	}
}
