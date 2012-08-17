<?php
if(class_exists('BaseObject'))
	return;

require_once('boot.php');

/**
 * Basic object
 *
 * Contains what is usefull to any object
 */
class BaseObject {
	private static $app = null;

	/**
	 * Get the app
	 * 
	 * Same as get_app from boot.php
	 */
	public function get_app() {
		if(self::$app)
			return self::$app;

		global $a;
		self::$app = $a;

		return self::$app;
	}

	/**
	 * Set the app
	 */
	public static function set_app($app) {
		self::$app = $app;
	}
}
?>
