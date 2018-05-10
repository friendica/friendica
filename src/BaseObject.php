<?php
/**
 * @file src/BaseObject.php
 */
namespace Friendica;

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
	 */
	public static function getApp()
	{
		if (self::$app) {
			return self::$app;
		}

		self::$app = get_app();

		return self::$app;
	}

	/**
	 * Set the app
	 *
	 * @param object $app App
	 *
	 * @return void
	 */
	public static function setApp($app)
	{
		self::$app = $app;
	}
}
