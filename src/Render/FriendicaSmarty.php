<?php
/**
 * @copyright Copyright (C) 2010-2022, the Friendica project
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

namespace Friendica\Render;

use Smarty;
use Friendica\Core\Renderer;

/**
 * Friendica extension of the Smarty3 template engine
 */
class FriendicaSmarty extends Smarty
{
	const SMARTY3_TEMPLATE_FOLDER = 'templates';

	public $filename;

	function __construct(string $theme, array $theme_info)
	{
		parent::__construct();

		// setTemplateDir can be set to an array, which Smarty will parse in order.
		// The order is thus very important here
		$template_dirs = ['theme' => "view/theme/$theme/" . self::SMARTY3_TEMPLATE_FOLDER . "/"];
		if (!empty($theme_info['extends'])) {
			$template_dirs = $template_dirs + ['extends' => "view/theme/" . $theme_info["extends"] . "/" . self::SMARTY3_TEMPLATE_FOLDER . "/"];
		}

		$template_dirs = $template_dirs + ['base' => "view/" . self::SMARTY3_TEMPLATE_FOLDER . "/"];
		$this->setTemplateDir($template_dirs);

		$this->setCompileDir('view/smarty3/compiled/');
		$this->setConfigDir('view/smarty3/');
		$this->setCacheDir('view/smarty3/');

		$this->left_delimiter = Renderer::getTemplateLeftDelimiter('smarty3');
		$this->right_delimiter = Renderer::getTemplateRightDelimiter('smarty3');

		$this->escape_html = true;

		// Don't report errors so verbosely
		$this->error_reporting = E_ALL & ~E_NOTICE;
	}
}
