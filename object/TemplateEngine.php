<?php
require_once 'boot.php';


/**
 * Interface for template engines
 */
interface ITemplateEngine {
	public function replace_macros($s,$v);
	public function get_template_file($file, $root='');
}
