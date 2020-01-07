<?php
/**
 * @file src/Render/FriendicaSmarty.php
 */
namespace Friendica\Render;

use Friendica\Registry\DI;
use Smarty;
use Friendica\Core\Renderer;

/**
 * Friendica extension of the Smarty3 template engine
 *
 * @author Hypolite Petovan <hypolite@mrpetovan.com>
 */
class FriendicaSmarty extends Smarty
{
	const SMARTY3_TEMPLATE_FOLDER = 'templates';

	public $filename;

	function __construct()
	{
		parent::__construct();

		$a = DI::app();
		$theme = $a->getCurrentTheme();

		// setTemplateDir can be set to an array, which Smarty will parse in order.
		// The order is thus very important here
		$template_dirs = ['theme' => "view/theme/$theme/" . self::SMARTY3_TEMPLATE_FOLDER . "/"];
		if (!empty($a->theme_info['extends'])) {
			$template_dirs = $template_dirs + ['extends' => "view/theme/" . $a->theme_info["extends"] . "/" . self::SMARTY3_TEMPLATE_FOLDER . "/"];
		}

		$template_dirs = $template_dirs + ['base' => "view/" . self::SMARTY3_TEMPLATE_FOLDER . "/"];
		$this->setTemplateDir($template_dirs);

		$this->setCompileDir('view/smarty3/compiled/');
		$this->setConfigDir('view/smarty3/config/');
		$this->setCacheDir('view/smarty3/cache/');

		$this->left_delimiter = Renderer::getTemplateLeftDelimiter('smarty3');
		$this->right_delimiter = Renderer::getTemplateRightDelimiter('smarty3');

		$this->escape_html = true;

		// Don't report errors so verbosely
		$this->error_reporting = E_ALL & ~E_NOTICE;
	}

	function parsed($template = '')
	{
		if ($template) {
			return $this->fetch('string:' . $template);
		}
		return $this->fetch('file:' . $this->filename);
	}

}