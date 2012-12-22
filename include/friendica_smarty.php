<?php

require_once("library/Smarty/libs/Smarty.class.php");

class FriendicaSmarty extends Smarty {

	public $filename;
	public $root;

	function __construct() {
		parent::__construct();

		$a = get_app();

		//$this->root = $_SERVER['DOCUMENT_ROOT'] . '/';
		$this->root = '';

		$this->setTemplateDir($this->root . 'view/smarty3/');
		$this->setCompileDir($this->root . 'view/smarty3/compiled/');
		$this->setConfigDir($this->root . 'view/smarty3/config/');
		$this->setCacheDir($this->root . 'view/smarty3/cache/');

		$this->left_delimiter = $a->smarty3_ldelim;
		$this->right_delimiter = $a->smarty3_rdelim;
	}

	function parsed($template = '') {
		if($template) {
			return $this->fetch('string:' . $template);
		}
		return $this->fetch('file:' . $this->root . $this->filename);
	}
}



