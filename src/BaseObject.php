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
		if (empty(self::$app)) {
			self::$app = new App(dirname(__DIR__));
		}

		return self::$app;
	}

	/**
	 * Set the app
	 *
	 * @param object $app App
	 *
	 * @return void
	 */
	public static function setApp(App $app)
	{
		self::$app = $app;
	}
}
