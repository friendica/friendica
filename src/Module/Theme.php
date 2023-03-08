<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Friendica\Module;

use Friendica\BaseModule;
use Friendica\Core\System;
use Friendica\Util\Strings;

/**
 * load view/theme/$current_theme/style.php with friendica context
 */
class Theme extends BaseModule
{
	protected function rawContent(array $request = [])
	{
		header('Content-Type: text/css');

		$theme = Strings::sanitizeFilePathItem($this->parameters['theme']);

		if (file_exists("view/theme/$theme/theme.php")) {
			require_once "view/theme/$theme/theme.php";
		}

		// set the path for later use in the theme styles
		$THEMEPATH = "view/theme/$theme";
		if (file_exists("view/theme/$theme/style.php")) {
			require_once "view/theme/$theme/style.php";
		}
		System::exit();
	}
}
