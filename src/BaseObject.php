<?php
/**
 * @file src/BaseObject.php
 */
namespace Friendica;

require_once 'boot.php';

use Friendica\Util\LoggerFactory;

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
	 * @throws \Exception
	 */
	public static function getApp()
	{
		if (empty(self::$app)) {
			$logger = $logger = LoggerFactory::create('app');
			self::$app = new App(dirname(__DIR__), $logger);
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
