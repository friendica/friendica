<?php

namespace Friendica\Registry;

use Friendica\App as A;
use Friendica\BaseRegistry;

/**
 * Registry for dynamic classes of the "Friendica\App" namespace
 */
abstract class App extends BaseRegistry
{

	/**
	 * @return A\Authentication
	 */
	public static function auth()
	{
		return self::$dice->create(A\Authentication::class);
	}

	/**
	 * @return A\BaseURL
	 */
	public static function baseUrl()
	{
		return self::$dice->create(A\BaseURL::class);
	}

	/**
	 * @return A\Module
	 */
	public static function module()
	{
		return self::$dice->create(A\Module::class);
	}

	/**
	 * @return A\Arguments
	 */
	public static function args()
	{
		return self::$dice->create(A\Arguments::class);
	}

	/**
	 * @return A\Mode
	 */
	public static function mode()
	{
		return self::$dice->create(A\Mode::class);
	}

	/**
	 * @return A\Page
	 */
	public static function page()
	{
		return self::$dice->create(A\Page::class);
	}
}
