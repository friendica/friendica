<?php

require_once("library/Smarty/libs/Smarty.class.php");

class FriendicaSmarty extends Smarty {

	public $filename;

	function __construct() {
		parent::__construct();

		$a = get_app();
		$theme = current_theme();

		// setTemplateDir can be set to an array, which Smarty will parse in order.
		// The order is thus very important here
		$template_dirs = array('theme' => "view/theme/$theme/smarty3/");
		if( x($a->theme_info,"extends") )
			$template_dirs = $template_dirs + array('extends' => "view/theme/".$a->theme_info["extends"]."/smarty3/");
		$template_dirs = $template_dirs + array('base' => 'view/smarty3/');
		$this->setTemplateDir($template_dirs);

		$this->setCompileDir('view/smarty3/compiled/');
		$this->setConfigDir('view/smarty3/config/');
		$this->setCacheDir('view/smarty3/cache/');

		$this->left_delimiter = $a->get_template_ldelim('smarty3');
		$this->right_delimiter = $a->get_template_rdelim('smarty3');

		// Don't report errors so verbosely
		$this->error_reporting = E_ALL & ~E_NOTICE;
	}

	function parsed($template = '') {
		if($template) {
			return $this->fetch('string:' . $template);
		}
		return $this->fetch('file:' . $this->filename);
	}
}



