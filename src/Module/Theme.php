<?php

namespace Friendica\Module;

use Friendica\BaseModule;
use Friendica\DI;
use Friendica\Util\Strings;

/**
 * load view/theme/$current_theme/style.php with friendica context
 */
class Theme extends BaseModule
{
	public static function rawContent(array $parameters = [])
	{
		header("Content-Type: text/css");

		$a = DI::app();

		if ($a->argc == 4) {
			$theme = $a->argv[2];
			$theme = Strings::sanitizeFilePathItem($theme);

			// set the path for later use in the theme styles
			$THEMEPATH = "view/theme/$theme";
			if (file_exists("view/theme/$theme/style.php")) {
				require_once("view/theme/$theme/style.php");
			}
		}

		exit();
	}
}
