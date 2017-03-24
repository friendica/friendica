<?php
//# Install PSR-0-compatible class autoloader
//spl_autoload_register(function($class){
//	require preg_replace('{\\\\|_(?!.*\\\\)}', DIRECTORY_SEPARATOR, ltrim($class, '\\')).'.php';
//});

require_once("library/php-markdown/Michelf/MarkdownExtra.inc.php");
# Get Markdown class
use \Michelf\MarkdownExtra;

function Markdown($text) {

	$a = get_app();

	$stamp1 = microtime(true);

	# Read file and pass content through the Markdown parser
	$MarkdownParser = new MarkdownExtra();
	$MarkdownParser->hard_wrap = true;
	$html = $MarkdownParser->transform($text);

	$a->save_timestamp($stamp1, "parser");

	return $html;
}
?>
