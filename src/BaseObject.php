<?php
/**
 * @file src/BaseObject.php
 */
namespace Friendica;

use Friendica\Network\HTTPException\InternalServerErrorException;

require_once 'boot.php';

/**
 * Basic object
 *
 * Contains what is useful to any object
 */
class BaseObject
{
	private static $app = null;

	/**
	 * Get the app
	 *
	 * Same as get_app from boot.php
	 *
	 * @return App
	 *
	 * @throws InternalServerErrorException if Friendica is not loaded correctly
	 */
	public static function getApp()
	{
		if (empty(self::$app)) {
			throw new InternalServerErrorException('Friendica App not loaded');
		}

		return self::$app;
	}

	/**
	 * Set the app
	 *
	 * @param App $app App
	 *
	 * @return void
	 */
	public static function setApp(App $app)
	{
		self::$app = $app;
	}
}
