<?php

namespace Friendica\Module;

use Friendica\BaseModule;
use Friendica\Core\System;

/**
 * Prints the PHP info for site admins
 */
class Phpinfo extends BaseModule
{
	public static function init()
	{
		if (!is_site_admin()) {
			System::httpExit(404);
		}
	}

	public static function rawContent()
	{
		phpinfo();
		exit();
	}
}
