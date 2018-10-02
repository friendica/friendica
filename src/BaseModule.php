<?php

namespace Friendica;

/**
 * All modules in Friendica should extend BaseModule, although not all modules
 * need to extend all the methods described here
 *
 * The filename of the module in src/Module needs to match the class name
 * exactly to make the module available.
 *
 * @author Hypolite Petovan <hypolite@mrpetovan.com>
 */
abstract class BaseModule extends BaseObject
{
	/**
	 * @brief Initialization method common to both content() and post()
	 *
	 * Extend this method if you need to do any shared processing before both
	 * content() or post()
	 */
	public static function init()
	{

	}

	/**
	 * @brief Module GET method to display any content
	 *
	 * Extend this method if the module is supposed to return any display
	 * through a GET request. It can be an HTML page through templating or a
	 * XML feed or a JSON output.
	 *
	 * @return string
	 */
	public static function content()
	{
		$o = '';

		return $o;
	}

	/**
	 * @brief Module POST method to process submitted data
	 *
	 * Extend this method if the module is supposed to process POST requests.
	 * Doesn't display any content
	 */
	public static function post()
	{
		// goaway('module');
	}

	/**
	 * @brief Called after post()
	 *
	 * Unknown purpose
	 */
	public static function afterpost()
	{

	}
}
