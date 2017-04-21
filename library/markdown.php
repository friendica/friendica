<?php
require_once "library/php-markdown/Michelf/MarkdownExtra.inc.php";
use \Michelf\MarkdownExtra;

function Markdown($text) {
	$a = get_app();

	$stamp1 = microtime(true);

	$MarkdownParser = new MarkdownExtra();
	$MarkdownParser->hard_wrap = true;
	$html = $MarkdownParser->transform($text);

	$a->save_timestamp($stamp1, "parser");

	return $html;
}
