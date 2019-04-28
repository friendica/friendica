<?php
/**
 * @file src/BaseObject.php
 */
namespace Friendica;

require_once __DIR__ . '/../boot.php';

/**
 * Basic object
 *
 * Contains what is useful to any object
 */
class BaseObject
{
	/**
	 * @var App
	 */
	private static $app = null;

	/**
	 * Get the app
	 *
	 * Same as get_app from boot.php
	 *
	 * @return App
	 * @throws \Exception
	 */
	public static function getApp()
	{
		if (empty(self::$app)) {
			throw new \Exception('App isn\'t initialized.');
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
