<?php

require_once "object/TemplateEngine.php";
require_once("library/Smarty/libs/Smarty.class.php");
require_once "include/plugin.php";

define('SMARTY3_TEMPLATE_FOLDER','templates');

class FriendicaSmarty extends Smarty {
	public $filename;

	function __construct() {
		parent::__construct();

		$a = get_app();
		$theme = current_theme();

		// setTemplateDir can be set to an array, which Smarty will parse in order.
		// The order is thus very important here
		$template_dirs = array('theme' => "view/theme/$theme/".SMARTY3_TEMPLATE_FOLDER."/");
		if( x($a->theme_info,"extends") )
			$template_dirs = $template_dirs + array('extends' => "view/theme/".$a->theme_info["extends"]."/".SMARTY3_TEMPLATE_FOLDER."/");
		$template_dirs = $template_dirs + array('base' => "view/".SMARTY3_TEMPLATE_FOLDER."/");
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

class FriendicaSmartyEngine implements ITemplateEngine {
	static $name ="smarty3";

    public function __construct(){
		if(!is_writable('view/smarty3/')){
			echo "<b>ERROR:</b> folder <tt>view/smarty3/</tt> must be writable by webserver."; killme();
		}
	}

	// ITemplateEngine interface
	public function replace_macros($s, $r) {
		$template = '';
		if(gettype($s) === 'string') {
			$template = $s;
			$s = new FriendicaSmarty();
		}

		// "middleware": inject variables into templates
		$arr = array(
			"template"=> basename($s->filename),
			"vars" => $r
		);
		call_hooks("template_vars", $arr);
		$r = $arr['vars'];

		foreach($r as $key=>$value) {
			if($key[0] === '$') {
				$key = substr($key, 1);
			}
			$s->assign($key, $value);
		}
		return $s->parsed($template);
	}

	public function get_template_file($file, $root=''){
		$a = get_app();
		$template_file = get_template_file($a, SMARTY3_TEMPLATE_FOLDER.'/'.$file, $root);
		$template = new FriendicaSmarty();
		$template->filename = $template_file;
		return $template;
	}
}
