<?php

if(! function_exists('load_doc_file')) {
function load_doc_file($s) {
	global $lang;
	if(! isset($lang))
		$lang = 'en';
	$b = basename($s);
	$d = dirname($s);
	if(file_exists("$d/$lang/$b"))
		return file_get_contents("$d/$lang/$b");
	if(file_exists($s))
		return file_get_contents($s);
	return '';
}}



function help_content(&$a) {
	nav_set_selected('help');

	global $lang;

	require_once('library/markdown.php');

	$text = '';

	if($a->argc > 1) {
		$text = load_doc_file('doc/' . $a->argv[1] . '.md');
		$a->page['title'] = t('Help:') . ' ' . str_replace('-',' ',notags($a->argv[1]));
	}
	if(! $text) {
		$text = load_doc_file('doc/Home.md');
		$a->page['title'] = t('Help');
	}
	
	if(! strlen($text)) {
		header($_SERVER["SERVER_PROTOCOL"] . ' 404 ' . t('Not Found'));
		$tpl = get_markup_template("404.tpl");
		return replace_macros($tpl, array(
			'$message' =>  t('Page not found.' )
		));
	}
	
	return Markdown($text);

}
