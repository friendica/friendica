<?php

namespace Friendica\Registry;

use Friendica\App as AppNamespace;
use Friendica\BaseRegistry;

/**
 * Registry for dynamic classes of the "Friendica\App" namespace
 */
abstract class App extends BaseRegistry
{

	/**
	 * @return AppNamespace\Authentication
	 */
	public static function auth()
	{
		return self::$dice->create(AppNamespace\Authentication::class);
	}

	/**
	 * @return AppNamespace\BaseURL
	 */
	public static function baseUrl()
	{
		return self::$dice->create(AppNamespace\BaseURL::class);
	}

	/**
	 * @return AppNamespace\Module
	 */
	public static function module()
	{
		return self::$dice->create(AppNamespace\Module::class);
	}

	/**
	 * @return AppNamespace\Arguments
	 */
	public static function args()
	{
		return self::$dice->create(AppNamespace\Arguments::class);
	}

	/**
	 * @return AppNamespace\Mode
	 */
	public static function mode()
	{
		return self::$dice->create(AppNamespace\Mode::class);
	}

	/**
	 * @return AppNamespace\Page
	 */
	public static function page()
	{
		return self::$dice->create(AppNamespace\Page::class);
	}
}
